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
            $query = Tag::where('user_id', $request->user()->id)
                        ->orWhere('is_default', true);

            if ($request->has('organization_id')) {
                $query->orWhere('organization_id', $request->organization_id);
            } else {
                $organizationIds = $request->user()->organizations()->pluck('organizations.id');
                $query->orWhereIn('organization_id', $organizationIds);
            }

            $tags = $query->get();

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
                'organization_id' => 'nullable|exists:organizations,id',
            ]);

            $tag = Tag::create([
                'name' => $validated['name'],
                'color' => $validated['color'] ?? null,
                'is_default' => false,
                'user_id' => $request->user()->id,
                'organization_id' => $validated['organization_id'] ?? null,
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
     * Update the specified tag in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $tag = Tag::findOrFail($id);

            // Check ownership or if they are admin of the organization
            $isOwner = $tag->user_id === $request->user()->id;
            $isOrgAdmin = false;

            if ($tag->organization_id) {
                $isOrgAdmin = $request->user()->organizations()
                                    ->wherePivot('organization_id', $tag->organization_id)
                                    ->wherePivotIn('role', ['admin', 'proprietaire'])
                                    ->exists();
            }

            if (!$isOwner && !$isOrgAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to edit this tag.'
                ], 403);
            }

            if ($tag->is_default) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit default tags.'
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'color' => 'nullable|string|max:50',
            ]);

            $tag->update([
                'name' => $validated['name'],
                'color' => $validated['color'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tag updated successfully',
                'tag' => $tag
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating tag: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tag',
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
            
            // Check ownership or if they are admin of the organization
            $isOwner = $tag->user_id === $request->user()->id;
            $isOrgAdmin = false;

            if ($tag->organization_id) {
                $isOrgAdmin = $request->user()->organizations()
                                    ->wherePivot('organization_id', $tag->organization_id)
                                    ->wherePivotIn('role', ['admin', 'proprietaire'])
                                    ->exists();
            }

            if (!$isOwner && !$isOrgAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete this tag.'
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
