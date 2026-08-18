<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationEntitlement;
use Illuminate\Support\Facades\Route;

class EnsureFeatureMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('auth:sanctum')->get('/api/_test/organizations/{organization}/gated', function () {
            return response()->json(['success' => true]);
        })->middleware('feature:advanced_analytics');
    }

    public function test_blocks_when_feature_not_granted()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => 'proprietaire']);

        $response = $this->actingAs($user)->getJson("/api/_test/organizations/{$org->id}/gated");

        $response->assertStatus(403)
            ->assertJson(['upgrade_required' => true, 'feature' => 'advanced_analytics']);
    }

    public function test_allows_when_feature_granted()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => 'proprietaire']);

        OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'expires_at' => now()->addDay(),
            'features' => [['code' => 'advanced_analytics', 'limit' => null]],
        ]);

        $response = $this->actingAs($user)->getJson("/api/_test/organizations/{$org->id}/gated");

        $response->assertStatus(200);
    }
}
