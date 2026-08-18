<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Organization;
use App\Models\OrganizationEntitlement;
use App\Services\FeatureGate;

class FeatureGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_falls_back_to_free_when_no_entitlement_row()
    {
        $org = Organization::factory()->create();
        $gate = new FeatureGate();

        $this->assertEquals(2, $gate->limit($org, 'max_workspaces'));
        $this->assertFalse($gate->has($org, 'advanced_analytics'));
    }

    public function test_falls_back_to_free_when_entitlement_not_active()
    {
        $org = Organization::factory()->create();
        OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'pending',
            'features' => [['code' => 'max_workspaces', 'limit' => 10]],
        ]);

        $gate = new FeatureGate();

        $this->assertEquals(2, $gate->limit($org, 'max_workspaces'));
    }

    public function test_falls_back_to_free_when_entitlement_expired()
    {
        $org = Organization::factory()->create();
        OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'expires_at' => now()->subDay(),
            'features' => [['code' => 'max_workspaces', 'limit' => 10]],
        ]);

        $gate = new FeatureGate();

        $this->assertEquals(2, $gate->limit($org, 'max_workspaces'));
    }

    public function test_reads_limit_from_active_entitlement()
    {
        $org = Organization::factory()->create();
        OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'expires_at' => now()->addDay(),
            'features' => [
                ['code' => 'max_workspaces', 'limit' => 10],
                ['code' => 'advanced_analytics', 'limit' => null],
            ],
        ]);

        $gate = new FeatureGate();

        $this->assertEquals(10, $gate->limit($org, 'max_workspaces'));
        $this->assertTrue($gate->has($org, 'advanced_analytics'));
    }

    public function test_limit_returns_null_for_unlimited_feature_not_in_free_config()
    {
        $org = Organization::factory()->create();
        OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'expires_at' => now()->addDay(),
            'features' => [['code' => 'max_workspaces', 'limit' => null]],
        ]);

        $gate = new FeatureGate();

        $this->assertNull($gate->limit($org, 'max_workspaces'));
    }

    public function test_has_returns_false_for_unknown_code()
    {
        $org = Organization::factory()->create();
        $gate = new FeatureGate();

        $this->assertFalse($gate->has($org, 'nonexistent_feature'));
    }
}
