<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
class InvitationController extends Controller
{
    /**
     * Send an invitation.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'organization_id' => 'required|exists:organizations,id',
                'team_id' => 'nullable|exists:teams,id',
                'projet_id' => 'nullable|exists:projets,id',
                'role' => 'nullable|in:admin,member,membre,proprietaire,owner',
            ]);

            $user = $request->user();
            
            // Check permission: owner/admin/proprietaire of organization or organization creator
            $orgUser = $user->organizations()->where('organizations.id', $validated['organization_id'])->first();
            $allowedRoles = ['proprietaire', 'owner', 'admin', 'creator', 'propriétaire'];
            $hasPerm = ($orgUser && in_array(strtolower($orgUser->pivot->role ?? ''), $allowedRoles)) ||
                       Organization::where('id', $validated['organization_id'])->where('user_id', $user->id)->exists();

            if (!$hasPerm) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to send invitations for this organization.'
                ], 403);
            }

            // Check if user is already a member of this organization
            $alreadyMember = User::where('email', $validated['email'])
                ->whereHas('organizations', function($q) use ($validated) {
                    $q->where('organizations.id', $validated['organization_id']);
                })
                ->exists();

            if ($alreadyMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet utilisateur est déjà membre de cette organisation.'
                ], 422);
            }

            // Enforce max_members: current members + already-pending invitations
            // (accepting all pending invites must not be able to overshoot the limit)
            $organization = Organization::findOrFail($validated['organization_id']);
            $gate = app(\App\Services\FeatureGate::class);
            $limit = $gate->limit($organization, 'max_members');

            if ($limit !== null) {
                $currentMembers = $organization->users()->count();
                $pendingInvitations = Invitation::where('organization_id', $organization->id)
                    ->where('status', 'pending')
                    ->count();

                if ($currentMembers + $pendingInvitations >= $limit) {
                    return response()->json([
                        'success' => false,
                        'upgrade_required' => true,
                        'feature' => 'max_members',
                        'message' => 'Member limit reached for your current plan.',
                    ], 422);
                }
            }

            // Generate token and set expiration to 2 days
            $token = Str::random(40);
            $expiresAt = now()->addDays(2);

            $inputRole = $validated['role'] ?? 'member';
            if ($inputRole === 'membre') $inputRole = 'member';
            if ($inputRole === 'proprietaire') $inputRole = 'owner';

            // Create or update pending invitation
            $invitation = Invitation::updateOrCreate(
                [
                    'email' => $validated['email'],
                    'organization_id' => $validated['organization_id'],
                    'status' => 'pending',
                ],
                [
                    'token' => $token,
                    'team_id' => $validated['team_id'] ?? null,
                    'projet_id' => $validated['projet_id'] ?? null,
                    'role' => $inputRole,
                    'expires_at' => $expiresAt,
                    'invited_by' => $user->id,
                ]
            );

            // Safely attempt to notify the user via mail/database without crashing if mail server fails
            try {
                $invitedUser = User::where('email', $validated['email'])->first();
                if ($invitedUser) {
                    $invitedUser->notify(new OrganizationInvitationNotification($invitation));
                } else {
                    Notification::route('mail', $validated['email'])
                        ->notify(new OrganizationInvitationNotification($invitation));
                }
            } catch (\Throwable $mailErr) {
                Log::warning('Invitation created, but notification email could not be sent: ' . $mailErr->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Invitation sent successfully.',
                'invitation' => $invitation
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error sending invitation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to send invitation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retrieve details of an invitation by token.
     */
    public function show($token)
    {
        try {
            $invitation = Invitation::with(['organization:id,name', 'team:id,name', 'projet:id,name', 'inviter:id,name'])
                ->where('token', $token)
                ->firstOrFail();

            if ($invitation->status !== 'pending' || $invitation->expires_at->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This invitation is expired or has already been accepted.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invitation details retrieved.',
                'invitation' => $invitation
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid invitation token.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching invitation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch invitation details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accept the invitation.
     */
    public function accept(Request $request, $token)
    {
        try {
            $invitation = Invitation::where('token', $token)->firstOrFail();

            if ($invitation->status !== 'pending' || $invitation->expires_at->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This invitation is expired or has already been accepted.'
                ], 400);
            }

            $user = $request->user();

            // Check if the authenticated user's email matches the invite
            if ($user->email !== $invitation->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'This invitation was sent to a different email address.'
                ], 403);
            }

            // Add to organization
            if (!$user->organizations()->where('organization_id', $invitation->organization_id)->exists()) {
                // Re-check max_members: the limit may have been lowered, or other
                // invites accepted, between when this invitation was sent and now.
                $organization = Organization::findOrFail($invitation->organization_id);
                $gate = app(\App\Services\FeatureGate::class);
                $limit = $gate->limit($organization, 'max_members');

                if ($limit !== null && $organization->users()->count() >= $limit) {
                    return response()->json([
                        'success' => false,
                        'upgrade_required' => true,
                        'feature' => 'max_members',
                        'message' => 'Member limit reached for your current plan.',
                    ], 422);
                }

                $orgRole = $invitation->role === 'admin' ? 'admin' : 'membre';
                if ($invitation->role === 'owner') {
                    $orgRole = 'proprietaire';
                }

                $user->organizations()->attach($invitation->organization_id, [
                    'role' => $orgRole,
                    'joined_at' => now(),
                ]);
            }

            // Add to team if specified
            if ($invitation->team_id) {
                if (!$user->teams()->where('team_id', $invitation->team_id)->exists()) {
                    $user->teams()->attach($invitation->team_id, [
                        'joined_at' => now(),
                    ]);
                }
            }

            // Add to project if specified
            if ($invitation->projet_id) {
                if (!$user->projets_collaborated()->where('projet_id', $invitation->projet_id)->exists()) {
                    $user->projets_collaborated()->attach($invitation->projet_id, [
                        'joined_at' => now(),
                    ]);
                }
            }

            // Mark as accepted
            $invitation->update(['status' => 'accepted']);

            return response()->json([
                'success' => true,
                'message' => 'Invitation accepted successfully.',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid invitation token.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error accepting invitation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept invitation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
