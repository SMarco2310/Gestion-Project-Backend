<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use App\Notifications\TeamRemovedNotification;

class TeamMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $organizationId, $teamId)
    {
        try {
            $organization = Organization::findOrFail($organizationId);
            $team = $organization->teams()->findOrFail($teamId);
            
            $members = $team->members()->get();

            return response()->json([
                'success' => true,
                'message' => 'Team members retrieved successfully',
                'data' => $members
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization or Team not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching team members: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch team members',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Add a member to the team.
     */
    public function store(Request $request, $organizationId, $teamId)
    {
        try {
            $organization = Organization::findOrFail($organizationId);
            $team = $organization->teams()->findOrFail($teamId);

            Gate::authorize('update', $team);

            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'role' => 'required|in:team_lead,membre',
            ]);

            // Check if user is part of the organization
            $isOrgMember = $organization->users()->where('user_id', $validated['user_id'])->exists();
            if (!$isOrgMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a member of this organization.'
                ], 403);
            }

            // Check if user is already in the team
            if ($team->members()->where('user_id', $validated['user_id'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already a member of this team.'
                ], 409);
            }

            $team->members()->attach($validated['user_id'], [
                'role' => $validated['role']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Team member added successfully'
            ], 201);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add team members.'
            ], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization or Team not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error adding team member: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add team member',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     * (Update role for a user)
     */
    public function update(Request $request, $organizationId, $teamId, $userId)
    {
        try {
            $organization = Organization::findOrFail($organizationId);
            $team = $organization->teams()->findOrFail($teamId);

            Gate::authorize('update', $team);

            $validated = $request->validate([
                'role' => 'required|in:team_lead,membre',
            ]);

            $targetUser = $team->members()->where('user_id', $userId)->firstOrFail();

            // We could add more granular checks, e.g., a team_lead can't demote themselves unless there's another lead.
            // But basic update is handled here.
            $team->members()->updateExistingPivot($userId, [
                'role' => $validated['role']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Team member role updated successfully',
                'data' => [
                    'user_id' => $userId,
                    'role' => $validated['role']
                ]
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to change team member roles.'
            ], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization, Team, or Member not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating team member role: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update team member role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($organizationId, $teamId, $userId)
    {
        try {
            $organization = Organization::findOrFail($organizationId);
            $team = $organization->teams()->findOrFail($teamId);

            Gate::authorize('update', $team);

            $targetUser = $team->members()->where('user_id', $userId)->firstOrFail();

            try {
                $targetUser->notify(new TeamRemovedNotification($team, $organization));
            } catch (\Exception $e) {
                Log::error('Failed to send TeamRemovedNotification: ' . $e->getMessage());
            }

            $team->members()->detach($userId);

            return response()->json([
                'success' => true,
                'message' => 'Member removed from team successfully'
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to remove team members.'
            ], 403);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization, Team, or Member not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error removing team member: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove team member',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
