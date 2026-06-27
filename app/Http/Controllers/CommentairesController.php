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
    public function index(Request $request)
    {
    $commentaires = $request->user()->commentaires()->get();
    return response()->json($commentaires, 200);
    
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommentairesRequest $request)
    {
        $commentaire= Commentaire::create($request->validate(
            [
                'content' => 'required|string',
                'user_id' => 'required|exists:users,id',
                'tache_id' => 'required|exists:taches,id',
            ]
     
        ));

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
    public function update(UpdateCommentairesRequest $request, Commentaires $commentaire)
    {
        $commentaire->update($request->validated(
            [
                'content' => 'required|string',
                'user_id' => 'required|exists:users,id',
                'tache_id' => 'required|exists:taches,id',
            ]
        ));

        return response()->json(['commentaires'=>$commentaire,'success'=>true],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Commentaires $commentaire)
    {
        $commentaire->delete();

        return response()->json(null,204);
    }
}
