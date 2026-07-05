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
                'role' => 'nullable|in:admin,member,membre,proprietaire',
            ]);

            // Ensure the user has permission to invite (must be proprietaire or admin)
            $orgUser = auth()->user()->organizations()->where('organization_id', $validated['organization_id'])->first();
            
            if (!$orgUser || !in_array($orgUser->pivot->role, ['proprietaire', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to send invitations for this organization.'
                ], 403);
            }

            // Generate token and set expiration to 2 days
            $token = Str::random(40);
            $expiresAt = now()->addDays(2);

            $inputRole = $validated['role'] ?? 'member';
            if ($inputRole === 'membre') $inputRole = 'member';
            if ($inputRole === 'proprietaire') $inputRole = 'owner';

            $invitation = Invitation::create([
                'email' => $validated['email'],
                'token' => $token,
                'organization_id' => $validated['organization_id'],
                'team_id' => $validated['team_id'] ?? null,
                'projet_id' => $validated['projet_id'] ?? null,
                'role' => $inputRole,
                'status' => 'pending',
                'expires_at' => $expiresAt,
                'invited_by' => auth()->id(),
            ]);

            // Notify the user via email and database if they exist
            $invitedUser = User::where('email', $validated['email'])->first();
            if ($invitedUser) {
                $invitedUser->notify(new OrganizationInvitationNotification($invitation));
            } else {
                Notification::route('mail', $validated['email'])
                    ->notify(new OrganizationInvitationNotification($invitation));
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
                'message' => 'Failed to send invitation',
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

            $user = auth()->user();

            // Check if the authenticated user's email matches the invite
            if ($user->email !== $invitation->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'This invitation was sent to a different email address.'
                ], 403);
            }

            // Add to organization
            if (!$user->organizations()->where('organization_id', $invitation->organization_id)->exists()) {
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
