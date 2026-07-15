<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Notifications\RoleUpdatedNotification;

class OrganizationMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $organizationId)
    {
        try {
            $organization = Organization::findOrFail($organizationId);
            $members = $organization->users()->get()->map(function ($member) {
                return $member->only(['id', 'first_name', 'last_name', 'email', 'profile_picture']);
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Members retrieved successfully',
                'data' => $members
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching members: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch members',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $organizationId)
    {
        return response()->json([
            'success' => false,
            'message' => 'Please use the invitation system to add members.'
        ], 405);
    }

    /**
     * Display the specified resource.
     */
    public function show($organizationId, $userId)
    {
        try {
            $organization = Organization::findOrFail($organizationId);
            $member = $organization->users()->where('user_id', $userId)->firstOrFail();
            
            return response()->json([
                'success' => true,
                'message' => 'Member retrieved successfully',
                'data' => $member
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization or Member not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching member: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * Use this to change the user's role.
     */
    public function update(Request $request, $organizationId, $userId)
    {
        try {
            $organization = Organization::findOrFail($organizationId);

            Gate::authorize('update', $organization);

            $validated = $request->validate([
                'role' => 'required|in:proprietaire,admin,membre',
            ]);

            // Ensure the target user is actually in the organization
            $targetUser = $organization->users()->where('user_id', $userId)->firstOrFail();

            $currentUserOrg = auth()->user()->organizations()->where('organization_id', $organizationId)->first();
            
            // Prevent changing the role of a proprietaire if the current user is only an admin
            if ($targetUser->pivot->role === 'proprietaire' && $currentUserOrg->pivot->role === 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Admins cannot change the role of an owner.'
                ], 403);
            }

            // Prevent downgrading the last admin/owner to a member
            if (in_array($targetUser->pivot->role, ['admin', 'proprietaire']) && $validated['role'] === 'membre') {
                $adminCount = $organization->users()->wherePivotIn('role', ['admin', 'proprietaire'])->count();
                if ($adminCount <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'L\'organisation doit avoir au moins un administrateur.'
                    ], 403);
                }
            }

            // Update the pivot table role
            $organization->users()->updateExistingPivot($userId, [
                'role' => $validated['role']
            ]);

            // Notify the user about the role change
            $targetUser->notify(new RoleUpdatedNotification($organization, $validated['role']));

            return response()->json([
                'success' => true,
                'message' => 'Member role updated successfully',
                'data' => [
                    'user_id' => $userId,
                    'role' => $validated['role']
                ]
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to change member roles.'
            ], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization or Member not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating member role: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update member role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($organizationId, $userId)
    {
        try {
            $organization = Organization::findOrFail($organizationId);

            Gate::authorize('update', $organization);

            $targetUser = $organization->users()->where('user_id', $userId)->firstOrFail();
            $currentUserOrg = auth()->user()->organizations()->where('organization_id', $organizationId)->first();

            if ($targetUser->pivot->role === 'proprietaire' && $currentUserOrg->pivot->role === 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Admins cannot remove an owner.'
                ], 403);
            }

            // Prevent removing the last admin/owner
            if (in_array($targetUser->pivot->role, ['admin', 'proprietaire'])) {
                $adminCount = $organization->users()->wherePivotIn('role', ['admin', 'proprietaire'])->count();
                if ($adminCount <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'L\'organisation doit avoir au moins un administrateur.'
                    ], 403);
                }
            }

            // Remove the user from the organization
            $organization->users()->detach($userId);

            // Also remove them from any teams within this organization
            // Since teams belong to organizations, we find the organization's teams and detach the user
            $teamIds = $organization->teams()->pluck('id');
            $targetUser->teams()->detach($teamIds);

            return response()->json([
                'success' => true,
                'message' => 'Member removed successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization or Member not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error removing member: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove member',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
