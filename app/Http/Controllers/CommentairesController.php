<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentairesRequest;
use App\Http\Requests\UpdateCommentairesRequest;
use App\Models\Commentaires;
use Illuminate\Http\Request;

class CommentairesController extends Controller
{
    /**
     * Display a listing of the resource.
     * Optionally filter by tache_id query parameter.
     */

    public function index(Request $request)
    {
        $query = Commentaires::query();

        // If tache_id is provided, filter comments for that specific task
        if ($request->has('tache_id')) {
            $query->where('tache_id', $request->query('tache_id'));
        } else {
            // Otherwise, return all comments belonging to the authenticated user's projects
            $query->whereHas('tache.projet', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        $commentaires = $query->with('user:id,name')->latest()->get();

        return response()->json(['commentaires' => $commentaires, 'success' => true], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommentairesRequest $request)
    {
        // Always use the authenticated user's ID for security
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $commentaire = Commentaires::create($data);
        $commentaire->load('user:id,name');

        return response()->json(['commentaire' => $commentaire, 'success' => true], 201);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommentairesRequest $request, Commentaires $commentaire)
    {
        if ($commentaire->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $commentaire->update($request->validated());
        $commentaire->load('user:id,name');

        return response()->json(['commentaire' => $commentaire, 'success' => true], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Commentaires $commentaire)
    {
        if ($commentaire->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $commentaire->delete();

        return response()->json(null, 204);
    }
}

