<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTacheRequest;
use App\Http\Requests\UpdateTacheRequest;
use App\Models\Tache;
use Illuminate\Http\Request;

class TacheController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    // list all tasks that belong to projects owned by the authenticated user
    public function index(Request $request)
    {
        $taches = Tache::whereHas('projet', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->get();

        return response()->json($taches, 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTacheRequest $request)
    {
        $validated = $request->validated();

        $projet = $request->user()->projets()->findOrFail($validated['projet_id']);

        $tache = $projet->taches()->create($validated);

        return response()->json($tache, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Tache $tache)
    {
        if ($tache->projet->user_id !== $request->user()->id) {
            abort(403, 'You do not own this task.');
        }
        $tache->load('commentaires');
 
        return response()->json($tache, 200);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTacheRequest $request, Tache $tache)
    {
    
        $tache->update($request->validated());

        return response()->json(['Tache'=>$tache->fresh()], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Tache $tache)
    {

        $tache->delete();

        return response()->json(null,204);
    }
}
