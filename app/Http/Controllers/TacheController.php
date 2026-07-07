<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTacheRequest;
use App\Http\Requests\UpdateTacheRequest;
use App\Models\Tache;
use App\Models\Projet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskReopenedNotification;
use App\Notifications\PriorityEscalatedNotification;
use App\Notifications\TaskUnblockedNotification;
use App\Models\User;
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
        if ($projet->teams()->whereHas('members', function ($q) use ($user) {
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
                      ->orWhereHas('teams', function ($teamQuery) use ($request) {
                          $teamQuery->whereHas('members', function ($memberQuery) use ($request) {
                              $memberQuery->where('users.id', $request->user()->id);
                          });
                      })
                      ->orWhereHas('users', function ($userQuery) use ($request) {
                          $userQuery->where('users.id', $request->user()->id);
                      });
            });

            if ($request->has('projet_id')) {
                $query->where('projet_id', $request->query('projet_id'));
            }

            if ($request->has('organization_id')) {
                $query->whereHas('projet', function ($q) use ($request) {
                    $q->where('organization_id', $request->query('organization_id'));
                });
            }

            $taches = $query->with(['tag', 'projet', 'assignee'])->withCount('commentaires')->get();

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
            $validated = $request->validated();

            $projet = Projet::findOrFail($validated['projet_id']);
            Gate::authorize('view', $projet);

            $tache = $projet->taches()->create($validated);

            $this->syncProjectStatus($projet);

            // Notify assignee if one was set during creation
            if (!empty($validated['assignee_id'])) {
                $assignee = User::find($validated['assignee_id']);
                if ($assignee && $assignee->id !== $request->user()->id) {
                    $assignee->notify(new TaskAssignedNotification($tache, $projet, $request->user()));
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'tache' => $tache->fresh(['tag', 'assignee'])->loadCount('commentaires')
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        } catch (AuthorizationException $e) {
             return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add tasks to this project.'
            ], 403);
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
            $tache = Tache::with(['commentaires', 'subTasks', 'tag', 'assignee'])->findOrFail($id);
            Gate::authorize('view', $tache);

            return response()->json([
                'success' => true,
                'message' => 'Task retrieved successfully',
                'tache' => $tache
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        } catch (AuthorizationException $e) {
             return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this task.'
            ], 403);
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
            Gate::authorize('update', $tach);

            // Capture old values before update for comparison
            $oldAssigneeId = $tach->assignee_id;
            $oldStatus = $tach->status;
            $oldPriority = $tach->priority;

            $tach->update($request->validated());

            $this->syncProjectStatus($tach->projet);

            // --- Notification dispatches ---
            $currentUser = $request->user();
            $projet = $tach->projet;

            // 1. Task Assignment changed
            if ($request->has('assignee_id') && $tach->assignee_id !== $oldAssigneeId && $tach->assignee_id) {
                $newAssignee = User::find($tach->assignee_id);
                if ($newAssignee && $newAssignee->id !== $currentUser->id) {
                    $newAssignee->notify(new TaskAssignedNotification($tach, $projet, $currentUser));
                }
            }

            // 2. Task Reopened (was terminé, now something else)
            if ($oldStatus === 'terminé' && $tach->status !== 'terminé' && $tach->assignee_id) {
                $assignee = User::find($tach->assignee_id);
                if ($assignee && $assignee->id !== $currentUser->id) {
                    $assignee->notify(new TaskReopenedNotification($tach, $projet, $currentUser, $tach->status));
                }
            }

            // 3. Priority Escalated to élevé
            if ($tach->priority === 'élevé' && $oldPriority !== 'élevé' && $tach->assignee_id) {
                $assignee = User::find($tach->assignee_id);
                if ($assignee && $assignee->id !== $currentUser->id) {
                    $assignee->notify(new PriorityEscalatedNotification($tach, $projet, $currentUser));
                }
            }

            // 4. Sub-task completed → check if all siblings are done → notify parent task assignee
            if ($tach->status === 'terminé' && $oldStatus !== 'terminé' && $tach->parent_task_id) {
                $parentTask = Tache::find($tach->parent_task_id);
                if ($parentTask) {
                    $allSiblingsDone = Tache::where('parent_task_id', $parentTask->id)
                        ->where('status', '!=', 'terminé')
                        ->doesntExist();

                    if ($allSiblingsDone && $parentTask->assignee_id) {
                        $parentAssignee = User::find($parentTask->assignee_id);
                        $parentProjet = $parentTask->projet;
                        if ($parentAssignee && $parentProjet) {
                            $parentAssignee->notify(new TaskUnblockedNotification($parentTask, $parentProjet));
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'tache' => $tach->fresh(['tag', 'assignee'])->loadCount('commentaires')
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        } catch (AuthorizationException $e) {
             return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.'
            ], 403);
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
            Gate::authorize('delete', $tach);

            $projet = $tach->projet;
            $tach->delete();

            $this->syncProjectStatus($projet);

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        } catch (AuthorizationException $e) {
             return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.'
            ], 403);
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
            Gate::authorize('update', $tach);

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
                'tache' => $tach->fresh(['tag', 'assignee'])->loadCount('commentaires')
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
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
