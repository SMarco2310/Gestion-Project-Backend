<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTacheRequest;
use App\Http\Requests\UpdateTacheRequest;
use App\Models\Tache;
use App\Models\Projet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TacheController extends Controller
{
    /**
     * Check if user has access to a project (as owner or team member).
     */
    private function hasAccessToProject(Projet $projet, $user): bool
    {
        if ($projet->user_id === $user->id) {
            return true;
        }
        if ($projet->team_id && $projet->team()->whereHas('members', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })->exists()) {
            return true;
        }
        return false;
    }

    /**
     * Automatically sync a project's status based on its tasks.
     */
    private function syncProjectStatus(Projet $projet): void
    {
        $totalTasks = $projet->taches()->count();

        if ($totalTasks === 0) {
            $projet->update(['status' => 'à faire']);
            return;
        }

        $doneTasks = $projet->taches()->where('status', 'terminé')->count();

        if ($doneTasks === $totalTasks) {
            $projet->update(['status' => 'terminé']);
        } else {
            if ($projet->status !== 'en cours') {
                $projet->update(['status' => 'en cours']);
            }
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Tache::whereHas('projet', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id)
                      ->orWhereHas('team', function ($teamQuery) use ($request) {
                          $teamQuery->whereHas('members', function ($memberQuery) use ($request) {
                              $memberQuery->where('users.id', $request->user()->id);
                          });
                      });
            });

            if ($request->has('projet_id')) {
                $query->where('projet_id', $request->query('projet_id'));
            }

            $taches = $query->with(['tag'])->withCount('commentaires')->get();

            return response()->json([
                'success' => true,
                'message' => 'Tasks retrieved successfully',
                'data' => $taches
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching tasks: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTacheRequest $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'priority' => 'required|in:faible,moyen,élevé',
                'status' => 'required|in:à faire,en cours,terminé',
                'tag' => 'nullable|in:bug,feature,improvement,documentation,design,testing,deployment',
                'due_date' => 'required|date',
                'projet_id' => 'required|exists:projets,id',
                'parent_task_id' => 'nullable|exists:taches,id',
            ]);

            $projet = Projet::findOrFail($validated['projet_id']);

            if (!$this->hasAccessToProject($projet, $request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to add tasks to this project.'
                ], 403);
            }

            $tache = $projet->taches()->create($validated);

            $this->syncProjectStatus($projet);

            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'tache' => $tache
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
                'message' => 'Project not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error creating task: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        try {
            $tache = Tache::with(['commentaires', 'subTasks', 'tag'])->findOrFail($id);

            if (!$this->hasAccessToProject($tache->projet, $request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this task.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Task retrieved successfully',
                'tache' => $tache
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching task: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTacheRequest $request, $id)
    {
        try {
            $tach = Tache::findOrFail($id);

            if (!$this->hasAccessToProject($tach->projet, $request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to perform this action.'
                ], 403);
            }

            $tach->update($request->validated());

            $this->syncProjectStatus($tach->projet);

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'tache' => $tach->fresh()
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating task: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $tach = Tache::findOrFail($id);

            if (!$this->hasAccessToProject($tach->projet, $request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to perform this action.'
                ], 403);
            }

            $projet = $tach->projet;
            $tach->delete();

            $this->syncProjectStatus($projet);

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting task: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload banner image for the task.
     */
    public function uploadBanner(Request $request, $id)
    {
        try {
            $tach = Tache::findOrFail($id);

            if (!$this->hasAccessToProject($tach->projet, $request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to perform this action.'
                ], 403);
            }

            $request->validate([
                'banner_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            ]);

            if ($request->hasFile('banner_image')) {
                $path = $request->file('banner_image')->store('banners', 'public');
                $tach->update(['banner_image' => '/storage/' . $path]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Banner uploaded successfully',
                'tache' => $tach->fresh()
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
                'message' => 'Task not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error uploading task banner: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload banner',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
