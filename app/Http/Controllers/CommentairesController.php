<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentairesRequest;
use App\Http\Requests\UpdateCommentairesRequest;
use App\Models\Commentaires;
use Illuminate\Http\Request;

class CommentairesController extends Controller
{
    // /**
    //  * Display a listing of the resource.
    //  */

    // // the commentaires that belong to a specific tache
    // public function index(Request $request)
    // {
    //     // $commentaires = $request->user()->projets()->taches()->with('commentaires')->get();
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommentairesRequest $request)
    {
        $commentaire= Commentaire::create($request->validated());

        return response()->json($commentaire,201);
    }

    // /**
    //  * Display the specified resource.
    //  */
    // public function show(Commentaires $commentaires)
    // {
    //     //
    // }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommentairesRequest $request, Commentaires $commentaires)
    {
        $commentaires->update($request->validated());

        return response()->json(['commentaires'=>$commentaires,'success'=>true],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Commentaires $commentaires)
    {
        $commentaires->delete();

        return response()->json(null,204);
    }
}
