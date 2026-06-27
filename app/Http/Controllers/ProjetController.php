<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjetRequest;
use App\Http\Requests\UpdateProjetRequest;
use Illuminate\Http\Request;
use App\Models\Projet;

class ProjetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    { 
            
        $projets = $request->user()->projets()->with('taches')->get();

        return response()->json(['projets'=>$projets,'success'=>true],200);
    }

   
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjetRequest $request)
    {
        $projet = $request->user()->projets()->create($request->validate(
            [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'reference_code' => 'required|string|max:255',
                'status' => 'required|in:à faire,en cours,terminé',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
            ]
        ));

        return response()->json(['projet'=>$projet,'success'=>true],201);
    }


    /**
     * Display the specified resource.
     */
    public function show(Request $request,Projet $projet)
    {
        $projet->load('taches');
        return response()->json($projet,200);
    }

    
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjetRequest $request, Projet $projet)
    {
        $projet->update($request->validate(
            [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|nullable|string',
                'reference_code' => 'sometimes|required|string|max:255',
                'status' => 'sometimes|required|in:à faire,en cours,terminé',
                'start_date' => 'sometimes|required|date',
                'end_date' => 'sometimes|required|date',
            ]
        ));

        return response()->json($projet->fresh(),200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request,Projet $projet)
    {
      
        $projet->delete();
        return response()->json(null,204);
            
    }
}
