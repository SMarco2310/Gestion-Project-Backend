<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentairesRequest;
use App\Http\Requests\UpdateCommentairesRequest;
use App\Models\Commentaires;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class CommentairesController extends Controller
{
    /**
     * Display a listing of the resource.
     * Optionally filter by tache_id query parameter.
     */
    public function index(Request $request)
    {
        try {
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

            $commentaires = $query->with('user:id,first_name,last_name,profile_picture')->latest()->get();

            return response()->json([
                'success' => true,
                'message' => 'Comments retrieved successfully',
                'data' => $commentaires
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching comments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommentairesRequest $request)
    {
        try {
            $data = $request->validated();
            $data['user_id'] = $request->user()->id;

            $tache = \App\Models\Tache::findOrFail($data['tache_id']);
            Gate::authorize('view', $tache);

            $commentaire = Commentaires::create([
                'content' => $data['content'],
                'tache_id' => $data['tache_id'],
                'user_id' => $data['user_id']
            ]);
            $commentaire->load('user:id,first_name,last_name,profile_picture');

            if (!empty($data['mentions'])) {
                $mentionerName = $request->user()->last_name . ' ' . $request->user()->first_name;
                $mentionedUsers = \App\Models\User::whereIn('id', $data['mentions'])
                    ->where('id', '!=', $request->user()->id)
                    ->get();
                foreach ($mentionedUsers as $user) {
                    $user->notify(new \App\Notifications\CommentMentionNotification($commentaire, $mentionerName));
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Comment created successfully',
                'commentaire' => $commentaire
            ], 201);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to comment on this task'
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error creating comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommentairesRequest $request, $id)
    {
        try {
            $commentaire = Commentaires::findOrFail($id);
            Gate::authorize('update', $commentaire);

            $commentaire->update($request->validated());
            $commentaire->load('user:id,first_name,last_name,profile_picture');

            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully',
                'commentaire' => $commentaire
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error updating comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update comment',
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
            $commentaire = Commentaires::findOrFail($id);
            Gate::authorize('delete', $commentaire);

            $commentaire->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error deleting comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
