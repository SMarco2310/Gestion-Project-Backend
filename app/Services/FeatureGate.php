<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationEntitlement;

class FeatureGate
{
    /**
     * Numeric quota for $code, or null if unlimited. Reads the org's active
     * entitlement snapshot if one exists and hasn't expired; otherwise falls
     * back to the hardcoded Free config. This is the ONLY place Free-tier
     * defaults are defined — never query Klea live here.
     */
    public function limit(Organization $organization, string $code): ?int
    {
        $entitlement = $this->activeEntitlement($organization);

        if (! $entitlement) {
            return config("entitlements.free.{$code}") ?? null;
        }

        $feature = collect($entitlement->features ?? [])->firstWhere('code', $code);

        if (! $feature) {
            return config("entitlements.free.{$code}") ?? null;
        }

        return $feature['limit']; // null means unlimited, matches Klea's own convention
    }

    /**
     * Boolean flag for $code (e.g. advanced_analytics).
     */
    public function has(Organization $organization, string $code): bool
    {
        $entitlement = $this->activeEntitlement($organization);

        if (! $entitlement) {
            return (bool) config("entitlements.free.{$code}", false);
        }

        $feature = collect($entitlement->features ?? [])->firstWhere('code', $code);

        if ($feature) {
            return true; // presence in the active plan's feature list means granted
        }

        return (bool) config("entitlements.free.{$code}", false);
    }

    protected function activeEntitlement(Organization $organization): ?OrganizationEntitlement
    {
        $entitlement = OrganizationEntitlement::where('organization_id', $organization->id)->first();

        if ($entitlement && $entitlement->isActive()) {
            return $entitlement;
        }

        return null;
    }
}
