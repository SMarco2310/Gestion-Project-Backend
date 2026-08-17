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
        return Cache::remember('klea.plans', now()->addMinutes(5), function () {
            $response = Http::withToken($this->apiKey)
                ->get("{$this->baseUrl}/public/plans");

            if (! $response->successful()) {
                throw new \RuntimeException('Klea listPlans failed: ' . $response->body());
            }

            return collect($response->json('data', []));
        });
    }

    /**
     * external_id is always the organization's own UUID — this is how Klea's
     * webhook later tells us which organization a payment result belongs to.
     */
    public function subscribe(Organization $organization, int $planId, User $actingUser, string $phoneNumber): array
    {
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/public/subscribe", [
                'plan_id' => $planId,
                'external_id' => $organization->id,
                'email' => $actingUser->email,
                'phone_number' => $phoneNumber,
                'environment' => 'live',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Klea subscribe failed: ' . $response->body());
        }

        return $response->json('data');
    }
}
