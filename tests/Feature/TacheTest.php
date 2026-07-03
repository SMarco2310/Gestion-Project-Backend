<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\Projet;
use App\Models\Tache;

class TacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_task()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $projet = Projet::factory()->create(['organization_id' => $org->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/taches', [
            'title' => 'Design API',
            'description' => 'Create the API documentation',
            'status' => 'à faire',
            'priority' => 'élevé',
            'due_date' => '2026-08-10',
            'projet_id' => $projet->id
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('tache.title', 'Design API');

        $this->assertDatabaseHas('taches', [
            'title' => 'Design API',
            'projet_id' => $projet->id
        ]);
    }

    public function test_user_can_update_task_status()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $projet = Projet::factory()->create(['organization_id' => $org->id, 'user_id' => $user->id]);
        $tache = Tache::factory()->create(['projet_id' => $projet->id, 'status' => 'à faire']);

        $response = $this->actingAs($user)->putJson("/api/taches/{$tache->id}", [
            'status' => 'en cours'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('taches', [
            'id' => $tache->id,
            'status' => 'en cours'
        ]);
    }
}
