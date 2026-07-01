<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTacheRequest;
use App\Http\Requests\UpdateTacheRequest;
use App\Models\Tache;
use App\Models\Projet;
use Illuminate\Http\Request;

class TacheController extends Controller
{
    /**
     * Automatically sync a project's status based on its tasks.
     * - No tasks left → 'à faire'
     * - All tasks 'terminé' → 'terminé'
     * - Otherwise → 'en cours'
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
            // At least one task exists and not all are done
            if ($projet->status !== 'en cours') {
                $projet->update(['status' => 'en cours']);
            }
        }
    }

    /**
     * Display a listing of the resource.
     */

    // list all tasks that belong to projects owned by the authenticated user
    public function index(Request $request)
    {
        $query = Tache::whereHas('projet', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        });

        if ($request->has('projet_id')) {
            $query->where('projet_id', $request->query('projet_id'));
        }

        $taches = $query->withCount('commentaires')->get();

        return response()->json($taches, 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTacheRequest $request)
    {
        $validated = $request->validate(
            [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'priority' => 'required|in:faible,moyen,élevé',
                'status' => 'required|in:à faire,en cours,terminé',
                'tag' => 'nullable|in:bug,feature,improvement,documentation,design,testing,deployment',
                'due_date' => 'required|date',
                'projet_id' => 'required|exists:projets,id',
                'parent_task_id' => 'nullable|exists:taches,id',
            ]
        );
        

        $projet = $request->user()->projets()->findOrFail($validated['projet_id']);

        $tache = $projet->taches()->create($validated);

        // Auto-sync project status after creating a task
        $this->syncProjectStatus($projet);

        return response()->json(['tache' => $tache, 'success' => true], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Tache $tach)
    {
    
        $tache = Tache::with(['commentaires', 'subTasks'])
        ->whereHas('projet', fn($q) => $q->where('user_id', $request->user()->id))
    ->findOrFail($tach->id);

    
        if ($tache->projet->user_id !== $request->user()->id) {
            abort(403, 'You do not own this task.');
        }
        $tache->load(['commentaires', 'subTasks']);
 
        return response()->json(['tache' => $tache, 'success' => true], 200);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTacheRequest $request, Tache $tach)
    {
        if ($tach->projet->user_id !== $request->user()->id) {
            abort(403, 'You do not own this task.');
        }
        $tach->update($request->validated());

        // Auto-sync project status after updating a task
        $this->syncProjectStatus($tach->projet);

        return response()->json(['tache'=>$tach->fresh(), 'success'=>true], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Tache $tach)
    {
        if ($tach->projet->user_id !== $request->user()->id) {
            abort(403, 'You do not own this task.');
        }

        $projet = $tach->projet;
        $tach->delete();

        // Auto-sync project status after deleting a task
        $this->syncProjectStatus($projet);

        return response()->json(null,204);
    }

    /**
     * Upload banner image for the task.
     */
    public function uploadBanner(Request $request, Tache $tach)
    {
        if ($tach->projet->user_id !== $request->user()->id) {
            abort(403, 'You do not own this task.');
        }

        $request->validate([
            'banner_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        if ($request->hasFile('banner_image')) {
            $path = $request->file('banner_image')->store('banners', 'public');
            $tach->update(['banner_image' => '/storage/' . $path]);
        }

        return response()->json(['tache' => $tach->fresh(), 'success' => true], 200);
    }
}
