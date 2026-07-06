<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjetRequest;
use App\Http\Requests\UpdateProjetRequest;
use Illuminate\Http\Request;
use App\Models\Projet;
use Illuminate\Support\Facades\Log;
use \Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

class ProjetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            $query = Projet::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('teams.members', function ($q2) use ($user) {
                      $q2->where('users.id', $user->id);
                  })
                  ->orWhereHas('users', function ($q3) use ($user) {
                      $q3->where('users.id', $user->id);
                  });
            });

            if ($request->has('organization_id')) {
                $query->where('organization_id', $request->query('organization_id'));
            }

            $projets = $query->with(['taches', 'users', 'teams.members', 'user'])->get();

            return response()->json([
                'success' => true,
                'message' => 'Projects retrieved successfully',
                'data' => $projets
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching projects: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }   

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjetRequest $request)
    {
        try {
            $validated = $request->validated();
            
            // Extract team_ids if present
            $teamIds = [];
            if (isset($validated['team_ids'])) {
                $teamIds = $validated['team_ids'];
                unset($validated['team_ids']);
            }
            
            $userIds = [];
            if (isset($validated['user_ids'])) {
                $userIds = $validated['user_ids'];
                unset($validated['user_ids']);
            }
            
            $projet = $request->user()->projets()->create($validated);

            if (!empty($teamIds)) {
                $projet->teams()->sync($teamIds);
            }
            
            if (!empty($userIds)) {
                $projet->users()->sync($userIds);
            }

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully',
                'projet' => $projet->load(['teams', 'users'])
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating project: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $projet = Projet::findOrFail($id);
            Gate::authorize('view', $projet);

            $projet->load(['taches', 'teams', 'users', 'user']);
            
            return response()->json([
                'success' => true,
                'message' => 'Project retrieved successfully',
                'projet' => $projet
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching project: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjetRequest $request, $id)
    {
        try {
            $projet = Projet::findOrFail($id);
            Gate::authorize('update', $projet);

            $validated = $request->validated();

            if (isset($validated['team_ids'])) {
                $projet->teams()->sync($validated['team_ids']);
                unset($validated['team_ids']);
            }

            if (isset($validated['user_ids'])) {
                $projet->users()->sync($validated['user_ids']);
                unset($validated['user_ids']);
            }

            $projet->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully',
                'projet' => $projet->fresh(['taches', 'users', 'teams.members', 'user'])
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating project: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $projet = Projet::findOrFail($id);
            Gate::authorize('delete', $projet);

            $projet->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Projet supprimé avec succès'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting project: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete project',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
