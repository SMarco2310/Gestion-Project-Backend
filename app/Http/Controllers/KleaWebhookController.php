<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationEntitlement;
use App\Services\KleaClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KleaWebhookController extends Controller
{
    public function __construct(protected KleaClientService $klea)
    {
    }

    public function handle(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('X-Klea-Signature');

        $secret = config('services.klea.webhook_secret');

        if (empty($secret)) {
            // Fail closed: an empty/unset secret would make the HMAC below
            // computable by anyone (empty-key HMAC), granting free paid
            // access. This is a misconfiguration, not a bad signature.
            Log::error('Klea webhook secret is not configured; rejecting webhook');
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        // HMAC is over the transaction ID only, not the JSON body — this is
        // the exact scheme Klea's SemoaCallbackController::notifyExternalApp() uses.
        $transactionId = (string) ($payload['transaction']['id'] ?? '');
        $expected = hash_hmac('sha256', $transactionId, $secret);

        if (! $signature || ! hash_equals($expected, $signature)) {
            Log::warning('Klea webhook signature mismatch', ['payload' => $payload]);
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        $organization = Organization::find($payload['subscriber_external_id'] ?? null);

        if (! $organization) {
            Log::warning('Klea webhook: organization not found', ['external_id' => $payload['subscriber_external_id'] ?? null]);
            return response()->json(['success' => false, 'message' => 'Organization not found'], 404);
        }

        $status = $payload['status'] ?? 'pending';

        if ($status === 'successful') {
            $entitlement = OrganizationEntitlement::where('organization_id', $organization->id)->first();

            // Bind the transaction to the org that initiated it — the HMAC only covers
            // transaction.id, so a captured (transaction_id, signature) pair could
            // otherwise be replayed with a different subscriber_external_id to grant a
            // paid entitlement to any organization.
            if ($entitlement && $entitlement->klea_subscription_id !== null) {
                $incomingSubscriptionId = (int) ($payload['subscription_id'] ?? 0);
                if ($incomingSubscriptionId !== (int) $entitlement->klea_subscription_id) {
                    Log::warning('Klea webhook: subscription_id mismatch for organization', [
                        'organization_id' => $organization->id,
                        'expected_subscription_id' => (int) $entitlement->klea_subscription_id,
                        'incoming_subscription_id' => $incomingSubscriptionId,
                    ]);

                    return response()->json(['success' => false, 'message' => 'Subscription mismatch'], 422);
                }
            }

            // Reject replays of an already-processed transaction.
            $incomingTransactionId = (int) ($payload['transaction']['id'] ?? 0);
            if ($entitlement && $entitlement->last_transaction_id !== null
                && (int) $entitlement->last_transaction_id === $incomingTransactionId) {
                Log::warning('Klea webhook: duplicate transaction replay ignored', [
                    'organization_id' => $organization->id,
                    'transaction_id' => $incomingTransactionId,
                ]);

                return response()->json(['success' => true, 'message' => 'Webhook already processed'], 200);
            }

            $planId = $payload['plan_id'] ?? null;
            $planName = null;
            $durationDays = 30; // safe fallback if the plan can't be resolved from the cached list

            try {
                $plan = $this->klea->listPlans()->firstWhere('id', $planId);
                if ($plan) {
                    $planName = $plan['name'] ?? null;
                    $durationDays = $plan['duration_days'] ?? $durationDays;
                }
            } catch (\Exception $e) {
                Log::warning('Klea webhook: could not resolve plan for duration lookup: ' . $e->getMessage());
            }

            OrganizationEntitlement::updateOrCreate(
                ['organization_id' => $organization->id],
                [
                    'klea_subscription_id' => $payload['subscription_id'] ?? null,
                    'klea_plan_id' => $planId,
                    'plan_name' => $planName,
                    'status' => 'active',
                    'features' => $payload['features'] ?? [],
                    'starts_at' => now(),
                    'expires_at' => now()->addDays($durationDays),
                    'last_webhook_at' => now(),
                    'last_transaction_id' => $incomingTransactionId,
                ]
            );
        } elseif ($status === 'failed') {
            // A failed renewal must not revoke a pre-existing active entitlement —
            // only update the row if it exists, and only touch last_webhook_at
            // plus status, never expires_at/features on an active row.
            $entitlement = OrganizationEntitlement::where('organization_id', $organization->id)->first();

            if ($entitlement && $entitlement->status === 'active') {
                $entitlement->update(['last_webhook_at' => now()]);
            } else {
                OrganizationEntitlement::updateOrCreate(
                    ['organization_id' => $organization->id],
                    ['status' => 'failed', 'last_webhook_at' => now()]
                );
            }
        } else {
            // pending — already pending from the subscribe call, just record the ping
            OrganizationEntitlement::where('organization_id', $organization->id)
                ->update(['last_webhook_at' => now()]);
        }

        return response()->json(['success' => true, 'message' => 'Webhook processed'], 200);
    }
}
