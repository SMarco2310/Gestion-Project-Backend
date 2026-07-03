<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_create_an_organization()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/organizations', [
            'name' => 'My Org',
            'description' => 'Test org',
            'reminder_time_start' => '09:00',
            'reminder_time_end' => '17:00'
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('organization.name', 'My Org');

        $this->assertDatabaseHas('organizations', [
            'name' => 'My Org'
        ]);

        $this->assertDatabaseHas('organization_user', [
            'user_id' => $user->id,
            'role' => 'proprietaire'
        ]);
    }

    public function test_a_user_can_view_their_organizations()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => 'proprietaire']);

        $response = $this->actingAs($user)->getJson('/api/organizations');

        $response->assertStatus(200)
                 ->assertJsonPath('data.data.0.id', $org->id);
    }

    public function test_only_proprietaires_and_admins_can_update_organization()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $org = Organization::factory()->create();
        
        $org->users()->attach($user1->id, ['role' => 'proprietaire']);
        $org->users()->attach($user2->id, ['role' => 'membre']);

        // User 2 (membre) cannot update
        $response1 = $this->actingAs($user2)->putJson("/api/organizations/{$org->id}", [
            'name' => 'Updated Name'
        ]);
        $response1->assertStatus(403);

        // User 1 (proprietaire) can update
        $response2 = $this->actingAs($user1)->putJson("/api/organizations/{$org->id}", [
            'name' => 'Updated Name'
        ]);
        $response2->assertStatus(200);
        
        $this->assertDatabaseHas('organizations', [
            'id' => $org->id,
            'name' => 'Updated Name'
        ]);
    }
}
