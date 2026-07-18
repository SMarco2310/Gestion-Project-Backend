<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Workspace;
use App\Models\Team;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use App\Notifications\TeamAddedNotification;

class WorkspaceTeamController extends Controller
{
    public function index($workspaceId)
    {
        try {
            $workspace = Workspace::findOrFail($workspaceId);
            $teams = $workspace->teams()->with('members')->withCount('members')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Workspace teams retrieved successfully',
                'data' => $teams
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching workspace teams: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch workspace teams',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, $workspaceId)
    {
        try {
            $workspace = Workspace::findOrFail($workspaceId);
            
            // Check if user is an admin of the workspace
            if (!$workspace->users()->where('users.id', $request->user()->id)->wherePivot('role', 'admin')->exists()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: Only workspace admins can manage teams'], 403);
            }
            
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            // Create team at org level
            $team = $workspace->organization->teams()->create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            $team->members()->attach(auth()->id(), [
                'joined_at' => now(),
            ]);

            // Attach to workspace
            $workspace->teams()->attach($team->id);

            // Notify the creator they've been added to the team
            auth()->user()->notify(new TeamAddedNotification($team, $workspace->organization));

            $team->load('members');
            $team->loadCount('members');

            return response()->json([
                'success' => true,
                'message' => 'Team created and attached to workspace successfully',
                'team' => $team
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating workspace team: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create workspace team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function attach(Request $request, $workspaceId)
    {
        try {
            $workspace = Workspace::findOrFail($workspaceId);
            
            if (!$workspace->users()->where('users.id', $request->user()->id)->wherePivot('role', 'admin')->exists()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: Only workspace admins can manage teams'], 403);
            }
            
            $request->validate([
                'team_id' => 'required|uuid|exists:teams,id',
            ]);

            // Ensure the team belongs to the same organization
            $team = Team::findOrFail($request->team_id);
            if ($team->organization_id !== $workspace->organization_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Team does not belong to this organization'
                ], 403);
            }

            // Sync without detaching
            $workspace->teams()->syncWithoutDetaching([$team->id]);
            
            $team->load('members');
            $team->loadCount('members');

            return response()->json([
                'success' => true,
                'message' => 'Team attached to workspace successfully',
                'team' => $team
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error attaching workspace team: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to attach workspace team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function detach($workspaceId, $teamId)
    {
        try {
            $workspace = Workspace::findOrFail($workspaceId);
            
            if (!$workspace->users()->where('users.id', request()->user()->id)->wherePivot('role', 'admin')->exists()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: Only workspace admins can manage teams'], 403);
            }
            
            $workspace->teams()->detach($teamId);
            
            return response()->json([
                'success' => true,
                'message' => 'Team detached from workspace successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error detaching workspace team: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to detach workspace team',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
