<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationEntitlement;
use App\Models\Workspace;

class WorkspaceQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocks_workspace_creation_past_free_limit()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => 'proprietaire']);

        // Free limit is 2 — create 2 existing workspaces first
        Workspace::factory()->count(2)->create(['organization_id' => $org->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/workspaces', [
            'name' => 'One too many',
            'organization_id' => $org->id,
        ]);

        $response->assertStatus(422)
            ->assertJson(['upgrade_required' => true, 'feature' => 'max_workspaces']);
    }

    public function test_allows_workspace_creation_under_limit()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => 'proprietaire']);

        Workspace::factory()->count(1)->create(['organization_id' => $org->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/workspaces', [
            'name' => 'Second workspace',
            'organization_id' => $org->id,
        ]);

        $response->assertStatus(201);
    }

    public function test_unlimited_plan_allows_unlimited_workspaces()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => 'proprietaire']);

        OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'expires_at' => now()->addDay(),
            'features' => [['code' => 'max_workspaces', 'limit' => null]],
        ]);

        Workspace::factory()->count(5)->create(['organization_id' => $org->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/workspaces', [
            'name' => 'Sixth workspace',
            'organization_id' => $org->id,
        ]);

        $response->assertStatus(201);
    }
}
