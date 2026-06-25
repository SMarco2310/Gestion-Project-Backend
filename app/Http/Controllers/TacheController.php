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
    public function index(Request $request)
    {
        $taches = $request->user()->projets()->with('taches')->get();

        return response()->json($taches,200)
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTacheRequest $request)
    {
        $tache = Tache::create($request->validated());

        return response()->json($tache,201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tache $tache)
    {
        if ($tache->projet()->user_id() !== $request->user()->id) {
            abort(403, 'You do not own this task.');
        }
 
        return response()->json($tache);
  
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTacheRequest $request, Tache $tache)
    {
        $tache->update($request->validated());

        return response()->json($tache->fresh())
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request,Tache $tache)
    {
        if($tache->projet()->user_id() !== $request->user()->id()){
            abort(403, 'You do not own this task.');

        }
        $tache->delete();

        return response()->json(null,204);
    }
}
