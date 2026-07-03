<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\Team;

class ProjetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_project_with_teams()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $team1 = Team::factory()->create(['organization_id' => $org->id]);
        $team2 = Team::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->postJson('/api/projets', [
            'name' => 'New Project',
            'description' => 'A great project',
            'status' => 'à faire',
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-01',
            'organization_id' => $org->id,
            'team_ids' => [$team1->id, $team2->id]
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('projet.name', 'New Project');

        $this->assertDatabaseHas('projets', [
            'name' => 'New Project',
            'organization_id' => $org->id
        ]);

        $projetId = $response->json('projet.id');
        $this->assertDatabaseHas('projet_team', [
            'projet_id' => $projetId,
            'team_id' => $team1->id
        ]);
        
        $this->assertDatabaseHas('projet_team', [
            'projet_id' => $projetId,
            'team_id' => $team2->id
        ]);
    }
}
