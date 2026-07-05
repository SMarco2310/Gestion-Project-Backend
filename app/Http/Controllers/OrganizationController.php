<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $organizations = $request->user()->organizations()->get();
            
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
                'reminder_days_before_start' => 'nullable|integer|min:0',
                'reminder_days_before_end' => 'nullable|integer|min:0',
                'reminder_time_start' => 'nullable|date_format:H:i',
                'reminder_time_end' => 'nullable|date_format:H:i',
            ]);

            $organization = Organization::create([
                'name' => $request->name,
                'description' => $request->description,
                'logo' => $request->logo,
                'reminder_days_before_start' => $request->reminder_days_before_start ?? 2,
                'reminder_days_before_end' => $request->reminder_days_before_end ?? 2,
                'reminder_time_start' => $request->reminder_time_start ?? '08:00:00',
                'reminder_time_end' => $request->reminder_time_end ?? '08:00:00',
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
            Gate::authorize('view', $organization);

            $organization->load(['teams', 'users', 'notifications', 'projets']);
            
            return response()->json([
                'success' => true,
                'message' => 'Organization retrieved successfully',
                'organization' => $organization
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this organization'
            ], 403);
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
            Gate::authorize('update', $organization);

            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'logo' => 'nullable|string',
                'reminder_days_before_start' => 'nullable|integer|min:0',
                'reminder_days_before_end' => 'nullable|integer|min:0',
                'reminder_time_start' => 'nullable|date_format:H:i',
                'reminder_time_end' => 'nullable|date_format:H:i',
            ]);

            $organization->update($request->only(['name', 'description', 'logo', 'reminder_days_before_start', 'reminder_days_before_end', 'reminder_time_start', 'reminder_time_end']));

            return response()->json([
                'success' => true,
                'message' => 'Organization updated successfully',
                'organization' => $organization
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this organization'
            ], 403);
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
            Gate::authorize('delete', $organization);

            $organization->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Organization deleted successfully'
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this organization'
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error deleting organization: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete organization',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload logo for the organization.
     */
    public function uploadLogo(Request $request, Organization $organization)
    {
        try {
            Gate::authorize('update', $organization);

            $request->validate([
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            ]);

            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('organizations', 'public');
                $organization->update(['logo' => '/storage/' . $path]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Organization logo uploaded successfully',
                'organization' => $organization->fresh()
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this organization'
            ], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Organization logo upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload organization logo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
