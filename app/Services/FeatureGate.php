<?php

namespace App\Services;

use App\Models\Attachment;
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

    /**
     * Total bytes currently stored in attachments belonging to $organization,
     * whether attached directly to a projet or to a tache within one of the
     * organization's projets. Used to enforce max_storage_mb.
     */
    public function storageUsedBytes(Organization $organization): int
    {
        $direct = (int) Attachment::whereHas('projet', function ($q) use ($organization) {
            $q->where('organization_id', $organization->id);
        })->sum('size');

        $viaTache = (int) Attachment::whereHas('tache.projet', function ($q) use ($organization) {
            $q->where('organization_id', $organization->id);
        })->sum('size');

        return $direct + $viaTache;
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
