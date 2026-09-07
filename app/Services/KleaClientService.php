<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class KleaClientService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.klea.base_url'), '/');
        $this->apiKey = config('services.klea.api_key');
    }

    /**
     * Klea has no plan-change webhook, so a short cache is a cheap way to
     * avoid a live round-trip on every pricing-page view without ever
     * going stale for long.
     */
    public function listPlans(): Collection
    {
        // Cache the raw array, not a Collection: serializing cache stores
        // (database, redis, file) hand back __PHP_Incomplete_Class on a cache
        // hit rather than a real Collection, which then fails this method's
        // return type. Only the array-store used in tests survives that.
        $plans = Cache::remember('klea.plans', now()->addMinutes(5), function () {
            $response = Http::withToken($this->apiKey)
                ->get("{$this->baseUrl}/public/plans");

            if (! $response->successful()) {
                throw new \RuntimeException('Klea listPlans failed: ' . $response->body());
            }

            return $response->json('data', []);
        });

        return collect($plans);
    }

    /**
     * Payment channels the subscriber can choose from before checkout.
     * Cached briefly: the list changes rarely and every checkout view hits it.
     */
    public function listGateways(): Collection
    {
        // Cache the raw array, not a Collection — serializing cache stores hand
        // back __PHP_Incomplete_Class on a hit, which would break the return type.
        $gateways = Cache::remember('klea.gateways', now()->addMinutes(10), function () {
            $response = Http::withToken($this->apiKey)
                ->get("{$this->baseUrl}/public/gateways");

            if (! $response->successful()) {
                throw new \RuntimeException('Klea listGateways failed: ' . $response->body());
            }

            return $response->json('data', []);
        });

        return collect($gateways);
    }

    /**
     * external_id is always the organization's own UUID — this is how Klea's
     * webhook later tells us which organization a payment result belongs to.
     */
    public function subscribe(Organization $organization, int $planId, User $actingUser, ?string $phoneNumber, ?int $gatewayId = null): array
    {
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/public/subscribe", [
                'plan_id' => $planId,
                'external_id' => $organization->id,
                'email' => $actingUser->email,
                ...($phoneNumber !== null && $phoneNumber !== '' ? ['phone_number' => $phoneNumber] : []),
                'environment' => 'live',
                // Preselected payment channel (CashPay gateway id). Optional —
                // without it the subscriber picks on the hosted page.
                ...($gatewayId ? ['gateway_id' => $gatewayId] : []),
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Klea subscribe failed: ' . $response->body());
        }

        return $response->json('data');
    }
}
