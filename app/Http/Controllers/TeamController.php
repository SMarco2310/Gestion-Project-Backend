<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\Organization;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Organization $organization)
    {
        return response()->json($organization->teams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Organization $organization)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $team = $organization->teams()->create([
            'name' => $request->name,
        ]);

        $team->members()->attach(auth()->id(), [
            'joined_at' => now(),
        ]);

        return response()->json([
            'message' => 'Team created successfully',
            'team' => $team
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Organization $organization, Team $team)
    {
        $team->load('members');
        return response()->json($team);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Organization $organization, Team $team)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
        ]);

        $team->update($request->only('name'));

        return response()->json([
            'message' => 'Team updated successfully',
            'team' => $team
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organization $organization, Team $team)
    {
        $team->delete();
        return response()->json(['message' => 'Team deleted successfully']);
    }
}
