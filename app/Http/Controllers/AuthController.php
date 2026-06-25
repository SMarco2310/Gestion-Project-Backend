<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;


class AuthController extends Controller
{
    /**
     * Creating a  new user.
     */
    public function register(Request $request)
    {
       $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'])
        ]);

        // $user->notify(new  WelcomeNotfication($user));
         

        $token = $user->createToken('auth_token')-> plainTextToken;


        return  response()->json(['user' => $user, 'token' => $token], 201);
    }

    /**
     * Login the user in
     */
    public function login(Request $request)
    {
        
       $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email',$validated['email'])->first();

        if (!$user || ! Hash::check($validated['password'], $user->password)){

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);

        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['user'=>$user, 'token'=>$token, 'message'=> 'User created successfuly'],200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)

    {
        $validated = $request->validate();

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } 
        $request->user()->update($validated);


        
        return response()->json($request->user()->fresh());

    }

    /**
     * Logout.
     */
    public function logout(Request $request)

    {
        // this goes to fetch the user with the current token and deletes him
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message'=>'User is Logged Out'],200);
    }

    /**
     *  Profile.
     */


    public function profile(Request $request)
    {
        // this just return the current user's informations
        return response()-> json([$request->user()],200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
