<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    /**
     * Creating a new user.
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name'  => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
            ]);

            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password'])
            ]);

            event(new Registered($user));
            $user->notify(new WelcomeNotification());
            
            // Handle invitation if token is provided
            if ($request->has('invite_token')) {
                $invitation = \App\Models\Invitation::where('token', $request->invite_token)
                                ->where('status', 'pending')
                                ->where('email', $user->email)
                                ->first();

                if ($invitation && !$invitation->expires_at->isPast()) {
                    // Attach to organization
                    $user->organizations()->attach($invitation->organization_id, [
                        'role' => $invitation->role,
                        'joined_at' => now(),
                    ]);

                    // Attach to team if present
                    if ($invitation->team_id) {
                        $user->teams()->attach($invitation->team_id, [
                            'joined_at' => now(),
                        ]);
                    }

                    // Attach to project if present
                    if ($invitation->projet_id) {
                        $user->projets_collaborated()->attach($invitation->projet_id, [
                            'joined_at' => now(),
                        ]);
                    }

                    // Mark invitation as accepted
                    $invitation->update(['status' => 'accepted']);
                }
            }
            
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => $user,
                'token' => $token
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to register user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login the user in
     */
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)){
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User logged in successfully',
                'user' => $user,
                'token' => $token
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to login',
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
                'password' => 'sometimes|string|min:8',
                'current_password' => 'required_with:password|string',
            ]);

            if (isset($validated['password'])) {
                if (!Hash::check($validated['current_password'], $user->password)) {
                    throw ValidationException::withMessages([
                        'current_password' => ['Le mot de passe actuel est incorrect.'],
                    ]);
                }
                $validated['password'] = Hash::make($validated['password']);
                unset($validated['current_password']);
            } 
            
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
            Log::error('Update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'User is Logged Out'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout',
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
