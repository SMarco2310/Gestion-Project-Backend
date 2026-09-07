<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_create_their_first_organization()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/organizations', ['name' => 'First Org'])
            ->assertStatus(201);
    }

    public function test_a_user_cannot_create_a_second_organization()
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => 'proprietaire', 'joined_at' => now()]);

        $this->actingAs($user)
            ->postJson('/api/organizations', ['name' => 'Second Org'])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('organizations', ['name' => 'Second Org']);
    }

    public function test_the_block_is_per_user_not_global()
    {
        $existing = User::factory()->create();
        $org = Organization::factory()->create();
        $org->users()->attach($existing->id, ['role' => 'proprietaire', 'joined_at' => now()]);

        $newcomer = User::factory()->create();

        $this->actingAs($newcomer)
            ->postJson('/api/organizations', ['name' => 'Newcomer Org'])
            ->assertStatus(201);
    }
}
