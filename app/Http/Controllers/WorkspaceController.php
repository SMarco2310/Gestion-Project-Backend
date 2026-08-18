<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Workspace;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $query = $user->workspaces();

            if ($request->has('organization_id')) {
                $query->where('organization_id', $request->query('organization_id'));
            }

            $workspaces = $query->withCount(['users', 'projets'])->with('organization:id,name,logo')->get();

            return response()->json([
                'success' => true,
                'data' => $workspaces
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching workspaces: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch workspaces'], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'organization_id' => 'required|exists:organizations,id',
            'kanban_columns' => 'nullable|array',
            'kanban_colors' => 'nullable|array',
            'color' => 'nullable|string',
        ]);

        try {
            $user = $request->user();
            // Verify user belongs to the org
            $org = Organization::findOrFail($validated['organization_id']);
            if (!$org->users()->where('users.id', $user->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $gate = app(\App\Services\FeatureGate::class);
            $limit = $gate->limit($org, 'max_workspaces');

            if ($limit !== null && $org->workspaces()->count() >= $limit) {
                return response()->json([
                    'success' => false,
                    'upgrade_required' => true,
                    'feature' => 'max_workspaces',
                    'message' => 'Workspace limit reached for your current plan.',
                ], 422);
            }

            $validated['created_by'] = $user->id;
            
            // Default kanban columns if not provided
            if (!isset($validated['kanban_columns'])) {
                $validated['kanban_columns'] = ['À faire', 'En cours', 'Terminé'];
            }

            $workspace = Workspace::create($validated);
            
            // Add creator to workspace
            $workspace->users()->attach($user->id, ['role' => 'admin']);

            // Notify organization members about the new workspace
            $orgUsers = $org->users()->where('users.id', '!=', $user->id)->get();
            foreach ($orgUsers as $orgUser) {
                $orgUser->notify(new \App\Notifications\WorkspaceCreatedNotification($workspace, $user));
            }

            return response()->json(['success' => true, 'workspace' => $workspace], 201);
        } catch (\Exception $e) {
            Log::error('Error creating workspace: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create workspace'], 500);
        }
    }

    public function show($id, Request $request)
    {
        try {
            $workspace = Workspace::with(['organization', 'users'])->findOrFail($id);
            
            // Check access
            if (!$workspace->users()->where('users.id', $request->user()->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            return response()->json(['success' => true, 'workspace' => $workspace], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Workspace not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'kanban_columns' => 'nullable|array',
            'kanban_colors' => 'nullable|array',
            'color' => 'nullable|string',
        ]);

        try {
            $workspace = Workspace::findOrFail($id);
            
            // Basic auth check: needs to be admin or at least in workspace. Ideally admin.
            if (!$workspace->users()->where('users.id', $request->user()->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $workspace->update($validated);
            return response()->json(['success' => true, 'workspace' => $workspace], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Update failed'], 500);
        }
    }

    public function destroy($id, Request $request)
    {
        try {
            $workspace = Workspace::findOrFail($id);
            
            if (!$workspace->users()->where('users.id', $request->user()->id)->wherePivot('role', 'admin')->exists()) {
                return response()->json(['success' => false, 'message' => 'Only admins can delete workspaces'], 403);
            }

            $workspace->delete();
            return response()->json(['success' => true, 'message' => 'Deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Delete failed'], 500);
        }
    }

    public function addMember(Request $request, $id)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string'
        ]);

        try {
            $workspace = Workspace::findOrFail($id);
            
            if (!$workspace->users()->where('users.id', $request->user()->id)->wherePivot('role', 'admin')->exists()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            // Check if user is in org
            $org = $workspace->organization;
            if (!$org->users()->where('users.id', $validated['user_id'])->exists()) {
                return response()->json(['success' => false, 'message' => 'User must be an organization member first'], 422);
            }

            $workspace->users()->syncWithoutDetaching([
                $validated['user_id'] => ['role' => $validated['role'] ?? 'member']
            ]);

            $addedUser = \App\Models\User::find($validated['user_id']);
            if ($addedUser && $addedUser->id !== $request->user()->id) {
                $addedUser->notify(new \App\Notifications\WorkspaceAddedNotification($workspace, $request->user()));
            }

            return response()->json(['success' => true, 'message' => 'Member added successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to add member'], 500);
        }
    }

    public function removeMember(Request $request, $id, $userId)
    {
        try {
            $workspace = Workspace::findOrFail($id);
            
            if (!$workspace->users()->where('users.id', $request->user()->id)->wherePivot('role', 'admin')->exists()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $workspace->users()->detach($userId);

            return response()->json(['success' => true, 'message' => 'Member removed successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to remove member'], 500);
        }
    }

    public function updateKanbanColumns(Request $request, $id)
    {
        $validated = $request->validate([
            'kanban_columns' => 'required|array',
            'kanban_colors' => 'nullable|array',
            'renames' => 'nullable|array', // key: old name, value: new name
        ]);

        try {
            $workspace = Workspace::findOrFail($id);
            
            if (!$workspace->users()->where('users.id', $request->user()->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            if ($request->has('renames') && is_array($request->renames)) {
                foreach ($request->renames as $oldName => $newName) {
                    \App\Models\Tache::where('workspace_id', $workspace->id)
                        ->where('board_column', $oldName)
                        ->update(['board_column' => $newName]);
                }
            }

            $updateData = ['kanban_columns' => $request->kanban_columns];
            if ($request->has('kanban_colors')) {
                $updateData['kanban_colors'] = $request->kanban_colors;
            }

            $workspace->update($updateData);
            $freshWs = $workspace->fresh();

            return response()->json([
                'success' => true,
                'message' => 'Kanban columns updated successfully',
                'kanban_columns' => $freshWs->kanban_columns,
                'kanban_colors' => $freshWs->kanban_colors,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating workspace kanban columns: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update kanban columns'], 500);
        }
    }
}
