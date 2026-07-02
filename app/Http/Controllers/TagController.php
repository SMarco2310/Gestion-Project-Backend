<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TagController extends Controller
{
    /**
     * Display a listing of the tags.
     */
    public function index(Request $request)
    {
        try {
            $tags = Tag::where('user_id', $request->user()->id)
                        ->orWhere('is_default', true)
                        ->get();

            return response()->json([
                'success' => true,
                'message' => 'Tags retrieved successfully',
                'tags' => $tags
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching tags: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tags',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created tag in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'color' => 'nullable|string|max:50',
            ]);

            $tag = Tag::create([
                'name' => $validated['name'],
                'color' => $validated['color'] ?? null,
                'is_default' => false,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tag created successfully',
                'tag' => $tag
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating tag: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tag',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified tag from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $tag = Tag::findOrFail($id);
            
            // Check ownership
            if ($tag->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not own this tag.'
                ], 403);
            }

            if ($tag->is_default) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete default tags.'
                ], 403);
            }

            $tag->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tag deleted successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting tag: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tag',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
