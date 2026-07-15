<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTacheRequest;
use App\Http\Requests\UpdateTacheRequest;
use App\Models\Tache;
use App\Models\Projet;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskReopenedNotification;
use App\Notifications\PriorityEscalatedNotification;
use App\Notifications\TaskUnblockedNotification;
use App\Notifications\TaskRemovedNotification;
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

        $doneTasks = $projet->taches()->where('status', 'done')->count();

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
            $user = $request->user();

            $projetQuery = Projet::where('is_archived', false)
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhereHas('teams.members', function ($q2) use ($user) {
                          $q2->where('users.id', $user->id);
                      })
                      ->orWhereHas('users', function ($q3) use ($user) {
                          $q3->where('users.id', $user->id);
                      });
                });

            if ($request->has('organization_id')) {
                $projetQuery->where('organization_id', $request->query('organization_id'));
            }

            $accessibleProjectIds = $projetQuery->pluck('id');

            $query = Tache::whereIn('projet_id', $accessibleProjectIds);

            if ($request->has('projet_id')) {
                if ($accessibleProjectIds->contains($request->query('projet_id'))) {
                    $query->where('projet_id', $request->query('projet_id'));
                } else {
                    $query->where('projet_id', 0); // Force empty result
                }
            }

            $taches = $query->with(['tags', 'projet:id,reference_code,name,end_date', 'assignee', 'subTasks:id,parent_task_id,status'])->withCount('commentaires')->get();

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

            if (isset($validated['tag_ids'])) {
                $tache->tags()->sync($validated['tag_ids']);
            }

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
                'tache' => $tache->fresh(['tags', 'assignee'])->loadCount('commentaires')
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
            $tache = Tache::with(['commentaires', 'subTasks', 'tags', 'assignee', 'checklists.items', 'attachments', 'projet:id,reference_code,name,end_date'])->findOrFail($id);
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

            if ($request->has('tag_ids')) {
                $tach->tags()->sync($request->input('tag_ids', []));
            }

            $this->syncProjectStatus($tach->projet);

            // --- Notification dispatches ---
            try {
                $currentUser = $request->user();
                $projet = $tach->projet;

                // 1. Task Assignment changed or removed
                if ($request->has('assignee_id') && $tach->assignee_id !== $oldAssigneeId) {
                    if ($oldAssigneeId !== null) {
                        $oldAssignee = User::find($oldAssigneeId);
                        if ($oldAssignee && $oldAssignee->id !== $currentUser->id) {
                            $oldAssignee->notify(new TaskRemovedNotification($tach, $projet, $currentUser));
                        }
                    }

                    if ($tach->assignee_id) {
                        $newAssignee = User::find($tach->assignee_id);
                        if ($newAssignee && $newAssignee->id !== $currentUser->id) {
                            $newAssignee->notify(new TaskAssignedNotification($tach, $projet, $currentUser));
                        }
                    }
                }

                // 2. Task Reopened (was done, now something else)
                if ($oldStatus === 'done' && $tach->status !== 'done' && $tach->assignee_id) {
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
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error sending task notification: ' . $e->getMessage());
            }

            // 4. Sub-task completed → check if all siblings are done → notify parent task assignee
            if ($tach->status === 'done' && $oldStatus !== 'done' && $tach->parent_task_id) {
                $parentTask = Tache::find($tach->parent_task_id);
                if ($parentTask) {
                    $allSiblingsDone = Tache::where('parent_task_id', $parentTask->id)
                        ->where('status', '!=', 'done')
                        ->doesntExist();

                    if ($allSiblingsDone && $parentTask->assignee_id) {
                        $parentAssignee = User::find($parentTask->assignee_id);
                        $parentProjet = $parentTask->projet;
                        if ($parentAssignee && $parentProjet) {
                            try {
                                $parentAssignee->notify(new TaskUnblockedNotification($parentTask, $parentProjet));
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Failed to send TaskUnblockedNotification: ' . $e->getMessage());
                            }
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'tache' => $tach->fresh(['tags', 'assignee'])->loadCount('commentaires')
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
                'tache' => $tach->fresh(['tags', 'assignee'])->loadCount('commentaires')
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

    public function storeChecklist(Request $request, $id)
    {
        try {
            $tache = Tache::findOrFail($id);
            Gate::authorize('update', $tache);

            $request->validate(['title' => 'required|string|max:255']);
            
            $checklist = $tache->checklists()->create([
                'title' => $request->title
            ]);

            return response()->json(['success' => true, 'checklist' => $checklist], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroyChecklist(Request $request, $checklist_id)
    {
        try {
            $checklist = Checklist::findOrFail($checklist_id);
            Gate::authorize('update', $checklist->tache);
            $checklist->delete();
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function storeChecklistItem(Request $request, $checklist_id)
    {
        try {
            $checklist = Checklist::findOrFail($checklist_id);
            Gate::authorize('update', $checklist->tache);

            $request->validate(['content' => 'required|string|max:255']);
            
            $item = $checklist->items()->create([
                'content' => $request->input('content'),
                'is_done' => false
            ]);

            return response()->json(['success' => true, 'item' => $item], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateChecklistItem(Request $request, $item_id)
    {
        try {
            $item = ChecklistItem::findOrFail($item_id);
            Gate::authorize('update', $item->checklist->tache);

            if ($request->has('content')) {
                $item->content = $request->input('content');
            }
            if ($request->has('is_done')) {
                $item->is_done = filter_var($request->is_done, FILTER_VALIDATE_BOOLEAN);
            }
            $item->save();

            return response()->json(['success' => true, 'item' => $item], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroyChecklistItem(Request $request, $item_id)
    {
        try {
            $item = ChecklistItem::findOrFail($item_id);
            Gate::authorize('update', $item->checklist->tache);
            $item->delete();
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function storeAttachment(Request $request, $id)
    {
        try {
            $tache = Tache::findOrFail($id);
            Gate::authorize('update', $tache);

            $request->validate([
                'file' => 'required|file|max:10240', // 10MB max
            ]);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('attachments', 'public');
                
                $attachment = $tache->attachments()->create([
                    'user_id' => $request->user()->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize()
                ]);

                return response()->json(['success' => true, 'attachment' => $attachment], 201);
            }
            return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroyAttachment(Request $request, $attachment_id)
    {
        try {
            $attachment = Attachment::findOrFail($attachment_id);
            Gate::authorize('update', $attachment->tache);
            
            Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();
            
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
