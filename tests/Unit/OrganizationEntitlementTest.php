<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Organization;
use App\Models\OrganizationEntitlement;
use Carbon\Carbon;

class OrganizationEntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_isActive_is_false_when_status_is_not_active()
    {
        $org = Organization::factory()->create();
        $entitlement = OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'pending',
        ]);

        $this->assertFalse($entitlement->isActive());
    }

    public function test_isActive_is_false_when_expired()
    {
        $org = Organization::factory()->create();
        $entitlement = OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $this->assertFalse($entitlement->isActive());
    }

    public function test_isActive_is_true_when_active_and_not_expired()
    {
        $org = Organization::factory()->create();
        $entitlement = OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'expires_at' => Carbon::now()->addDay(),
        ]);

        $this->assertTrue($entitlement->isActive());
    }

    public function test_isActive_is_true_when_active_and_no_expiry_set()
    {
        $org = Organization::factory()->create();
        $entitlement = OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'expires_at' => null,
        ]);

        $this->assertTrue($entitlement->isActive());
    }
}
