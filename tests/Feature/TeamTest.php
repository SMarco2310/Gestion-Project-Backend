<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\Team;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_team()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->postJson("/api/organizations/{$org->id}/teams", [
            'name' => 'Development Team',
            'description' => 'The dev team'
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('team.name', 'Development Team');

        $this->assertDatabaseHas('teams', [
            'name' => 'Development Team',
            'organization_id' => $org->id
        ]);

        $teamId = $response->json('team.id');
        $this->assertDatabaseHas('team_user', [
            'team_id' => $teamId,
            'user_id' => $user->id
        ]);
    }

    public function test_membre_cannot_create_a_team()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => 'membre']);

        $response = $this->actingAs($user)->postJson("/api/organizations/{$org->id}/teams", [
            'name' => 'Development Team'
        ]);

        $response->assertStatus(403);
    }
}
