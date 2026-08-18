<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationEntitlement;
use Illuminate\Support\Facades\Http;

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_plans_endpoint_requires_no_auth()
    {
        Http::fake([
            '*/public/plans' => Http::response(['data' => [['id' => 1, 'name' => 'Pro']], 'success' => true, 'message' => 'ok'], 200),
        ]);

        $response = $this->getJson('/api/public-plans');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Pro');
    }

    public function test_only_proprietaire_or_admin_can_subscribe()
    {
        Http::fake([
            '*/public/subscribe' => Http::response([
                'data' => ['subscription_id' => 1, 'transaction_id' => 1, 'amount' => 15000, 'currency' => 'XOF', 'payment_url' => 'https://pay.example', 'qrcode_url' => null],
                'success' => true, 'message' => 'ok',
            ], 201),
        ]);

        $org = Organization::factory()->create();
        $member = User::factory()->create();
        $org->users()->attach($member->id, ['role' => 'membre']);

        $response = $this->actingAs($member)->postJson("/api/organizations/{$org->id}/subscribe", [
            'plan_id' => 1,
            'phone_number' => '+22500000000',
        ]);

        $response->assertStatus(403);
    }

    public function test_proprietaire_can_subscribe_and_entitlement_row_created_pending()
    {
        Http::fake([
            '*/public/subscribe' => Http::response([
                'data' => ['subscription_id' => 55, 'transaction_id' => 100, 'amount' => 15000, 'currency' => 'XOF', 'payment_url' => 'https://pay.example/x', 'qrcode_url' => null],
                'success' => true, 'message' => 'ok',
            ], 201),
        ]);

        $org = Organization::factory()->create();
        $owner = User::factory()->create();
        $org->users()->attach($owner->id, ['role' => 'proprietaire']);

        $response = $this->actingAs($owner)->postJson("/api/organizations/{$org->id}/subscribe", [
            'plan_id' => 1,
            'phone_number' => '+22500000000',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.payment_url', 'https://pay.example/x');

        $this->assertDatabaseHas('organization_entitlements', [
            'organization_id' => $org->id,
            'klea_subscription_id' => 55,
            'status' => 'pending',
        ]);
    }

    public function test_entitlement_endpoint_returns_current_status_and_usage()
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();
        $org->users()->attach($user->id, ['role' => 'membre']);

        OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'plan_name' => 'Pro',
            'expires_at' => now()->addDay(),
            'features' => [['code' => 'max_workspaces', 'limit' => 10]],
        ]);

        $response = $this->actingAs($user)->getJson("/api/organizations/{$org->id}/entitlement");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.plan_name', 'Pro')
            ->assertJsonStructure(['data' => ['status', 'plan_name', 'features', 'expires_at', 'usage' => ['workspaces', 'members']]]);
    }

    public function test_entitlement_endpoint_returns_free_defaults_when_no_row()
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();
        $org->users()->attach($user->id, ['role' => 'membre']);

        $response = $this->actingAs($user)->getJson("/api/organizations/{$org->id}/entitlement");

        $response->assertStatus(200)
            ->assertJsonPath('data.plan_name', 'Free');
    }
}
