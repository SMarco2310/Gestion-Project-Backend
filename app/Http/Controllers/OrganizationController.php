<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $organizations = auth()->user()->organizations;
            
            return response()->json([
                'success' => true,
                'message' => 'Organizations retrieved successfully',
                'data' => $organizations
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching organizations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch organizations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'logo' => 'nullable|string',
                'reminder_days_before_start' => 'nullable|integer|min:2',
                'reminder_days_before_end' => 'nullable|integer|min:2',
            ]);

            $organization = Organization::create([
                'name' => $request->name,
                'description' => $request->description,
                'logo' => $request->logo,
                'reminder_days_before_start' => $request->reminder_days_before_start ?? 2,
                'reminder_days_before_end' => $request->reminder_days_before_end ?? 2,
            ]);

            $organization->users()->attach(auth()->id(), [
                'role' => 'proprietaire',
                'joined_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Organization created successfully',
                'organization' => $organization
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating organization: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create organization',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Organization $organization)
    {
        try {
            $organization->load(['teams', 'users', 'notifications', 'projets']);
            
            return response()->json([
                'success' => true,
                'message' => 'Organization retrieved successfully',
                'organization' => $organization
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching organization: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch organization',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Organization $organization)
    {
        try {
            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'logo' => 'nullable|string',
                'reminder_days_before_start' => 'sometimes|integer|min:2',
                'reminder_days_before_end' => 'sometimes|integer|min:2',
            ]);

            $organization->update($request->only(['name', 'description', 'logo', 'reminder_days_before_start', 'reminder_days_before_end']));

            return response()->json([
                'success' => true,
                'message' => 'Organization updated successfully',
                'organization' => $organization
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating organization: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update organization',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organization $organization)
    {
        try {
            $organization->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Organization deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting organization: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete organization',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
