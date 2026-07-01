<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Display a listing of the tags.
     */
    public function index(Request $request)
    {
        $tags = Tag::where('user_id', $request->user()->id)
                    ->orWhere('is_default', true)
                    ->get();

        return response()->json(['tags' => $tags, 'success' => true], 200);
    }

    /**
     * Store a newly created tag in storage.
     */
    public function store(Request $request)
    {
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

        return response()->json(['tag' => $tag, 'success' => true], 201);
    }

    /**
     * Remove the specified tag from storage.
     */
    public function destroy(Request $request, Tag $tag)
    {
        // Check ownership
        if ($tag->user_id !== $request->user()->id) {
            abort(403, 'You do not own this tag.');
        }

        if ($tag->is_default) {
            abort(403, 'Cannot delete default tags.');
        }

        $tag->delete();

        return response()->json(null, 204);
    }
}
