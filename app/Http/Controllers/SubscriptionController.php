<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationEntitlement;
use App\Services\FeatureGate;
use App\Services\KleaClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    public function __construct(protected KleaClientService $klea, protected FeatureGate $gate)
    {
    }

    /**
     * Public plan catalog — same data for the marketing /pricing page and
     * the in-app billing page, no per-organization variation, so one route.
     */
    public function plans()
    {
        try {
            $plans = $this->klea->listPlans();

            return response()->json([
                'success' => true,
                'message' => 'Plans retrieved successfully',
                'data' => $plans,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching Klea plans: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch plans',
            ], 500);
        }
    }

    public function subscribe(Request $request, Organization $organization)
    {
        try {
            $request->validate([
                'plan_id' => 'required|integer',
                'phone_number' => 'required|string',
            ]);

            $result = $this->klea->subscribe(
                $organization,
                (int) $request->plan_id,
                $request->user(),
                $request->phone_number
            );

            $entitlement = OrganizationEntitlement::firstOrNew(['organization_id' => $organization->id]);

            $entitlement->klea_subscription_id = $result['subscription_id'];
            $entitlement->klea_plan_id = (int) $request->plan_id;

            // Only claim 'pending' when there is no live entitlement to protect — a renewal
            // or upgrade must not revoke the current plan before the new payment settles.
            if (! $entitlement->exists || ! $entitlement->isActive()) {
                $entitlement->status = 'pending';
            }

            $entitlement->save();

            return response()->json([
                'success' => true,
                'message' => 'Subscription created, complete payment via payment_url',
                'data' => $result,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error subscribing organization: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription',
            ], 500);
        }
    }

    public function entitlement(Organization $organization)
    {
        try {
            $entitlement = OrganizationEntitlement::where('organization_id', $organization->id)->first();

            $active = $entitlement && $entitlement->isActive();

            $data = [
                'status' => $entitlement ? ($entitlement->isActive() ? 'active' : $entitlement->status) : 'active', // no row = permanently-active Free tier
                'plan_name' => $active ? ($entitlement->plan_name ?? 'Paid') : 'Free',
                'features' => $active ? $entitlement->features : null,
                'expires_at' => $active ? $entitlement->expires_at : null,
                'usage' => [
                    'workspaces' => $organization->workspaces()->count(),
                    'members' => $organization->users()->count(),
                ],
                'limits' => [
                    'max_workspaces' => $this->gate->limit($organization, 'max_workspaces'),
                    'max_members' => $this->gate->limit($organization, 'max_members'),
                    'max_storage_mb' => $this->gate->limit($organization, 'max_storage_mb'),
                    'max_attachment_size_mb' => $this->gate->limit($organization, 'max_attachment_size_mb'),
                ],
            ];

            return response()->json([
                'success' => true,
                'message' => 'Entitlement retrieved successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching entitlement: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch entitlement',
            ], 500);
        }
    }
}
