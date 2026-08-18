<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Organization;
use App\Models\OrganizationEntitlement;

class SweepExpiredEntitlementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_flips_expired_active_entitlements_to_expired()
    {
        $org = Organization::factory()->create();
        $entitlement = OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('entitlements:sweep-expired')->assertExitCode(0);

        $this->assertEquals('expired', $entitlement->fresh()->status);
    }

    public function test_does_not_touch_active_non_expired_entitlements()
    {
        $org = Organization::factory()->create();
        $entitlement = OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'expires_at' => now()->addDay(),
        ]);

        $this->artisan('entitlements:sweep-expired')->assertExitCode(0);

        $this->assertEquals('active', $entitlement->fresh()->status);
    }

    public function test_does_not_touch_non_active_statuses()
    {
        $org = Organization::factory()->create();
        $entitlement = OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'pending',
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('entitlements:sweep-expired')->assertExitCode(0);

        $this->assertEquals('pending', $entitlement->fresh()->status);
    }
}
