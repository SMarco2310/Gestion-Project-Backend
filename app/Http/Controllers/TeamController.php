<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($organizationId)
    {
        try {
            $organization = Organization::findOrFail($organizationId);
            
            return response()->json([
                'success' => true,
                'message' => 'Teams retrieved successfully',
                'data' => $organization->teams
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching teams: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teams',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $organizationId)
    {
        try {
            $organization = Organization::findOrFail($organizationId);
            
            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $team = $organization->teams()->create([
                'name' => $request->name,
            ]);

            $team->members()->attach(auth()->id(), [
                'joined_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Team created successfully',
                'team' => $team
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error creating team: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($organizationId, $teamId)
    {
        try {
            $organization = Organization::findOrFail($organizationId);
            $team = $organization->teams()->findOrFail($teamId);
            
            $team->load('members');
            
            return response()->json([
                'success' => true,
                'message' => 'Team retrieved successfully',
                'team' => $team
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization or Team not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching team: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $organizationId, $teamId)
    {
        try {
            $organization = Organization::findOrFail($organizationId);
            $team = $organization->teams()->findOrFail($teamId);

            $request->validate([
                'name' => 'sometimes|required|string|max:255',
            ]);

            $team->update($request->only('name'));

            return response()->json([
                'success' => true,
                'message' => 'Team updated successfully',
                'team' => $team
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization or Team not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating team: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($organizationId, $teamId)
    {
        try {
            $organization = Organization::findOrFail($organizationId);
            $team = $organization->teams()->findOrFail($teamId);
            
            $team->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Team deleted successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Organization or Team not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting team: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete team',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
