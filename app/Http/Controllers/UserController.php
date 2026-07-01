<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;


class UserController extends Controller
{

    /**
     * Get a list of users (useful for searching people to invite)
     */

    public function getUsers(Request $request)
    {
        // Example: Search users by email or name
        $query = User::query();

        if ($request->has('search')) {
            $query->where('email', 'like', '%' . $request->search . '%')
                  ->orWhere('name', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->select('id', 'name', 'email')->take(10)->get());
    }



    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return User::all();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'bio' => 'sometimes|string|max:500',
        ]);

        $user->update($validated);
        
        return response()->json(['user' => $user->fresh(), 'message' => 'User updated successfully'], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);
        // if (!$user) {
        //     return response()->json(['message' => 'User not found'], 404);
        // }
        $user->delete();
        return response()->json(['message' => 'User deleted successfully'], 200);
    }

     /**
     *  Profile.
     */
    public function profile(Request $request)
    {
        // this just return the current user's informations
        return response()-> json(['user' => $request->user(), 'message' => 'Profile retrieved successfully'],200);
    }


    /**
     * Get a specific user's public profile
     */
    public function show(User $user)
    {
        return response()->json($user->only(['id', 'name', 'email']));
    }

    /**
     * Upload profile picture for the user.
     */
    public function uploadProfilePicture(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profiles', 'public');
            $user->update(['profile_picture' => '/storage/' . $path]);
        }

        return response()->json(['user' => $user->fresh(), 'success' => true], 200);
    }
}
