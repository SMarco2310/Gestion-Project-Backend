<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Get a list of users (useful for searching people to invite)
     */
    public function getUsers(Request $request)
    {
        try {
            $query = User::query();

            if ($request->has('search')) {
                $query->where('email', 'like', '%' . $request->search . '%')
                      ->orWhere('name', 'like', '%' . $request->search . '%');
            }

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'data' => $query->select('id', 'name', 'email')->take(10)->get()
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'data' => User::all()
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching all users: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'bio' => 'sometimes|string|max:500',
            ]);

            $user->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => $user->fresh()
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);
            Gate::authorize('delete', $user);
            $user->delete();
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this user'
            ], 403);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     *  Profile.
     */
    public function profile(Request $request)
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'user' => $request->user()
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching profile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific user's public profile
     */
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully',
                'user' => $user->only(['id', 'name', 'email'])
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload profile picture for the user.
     */
    public function uploadProfilePicture(Request $request)
    {
        try {
            $user = $request->user();

            $request->validate([
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            ]);

            if ($request->hasFile('profile_picture')) {
                $path = $request->file('profile_picture')->store('profiles', 'public');
                $user->update(['profile_picture' => '/storage/' . $path]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile picture uploaded successfully',
                'user' => $user->fresh()
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Profile picture upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload profile picture',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
