<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_view_their_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertStatus(200)
                 ->assertJsonPath('user.email', $user->email);
    }

    public function test_a_user_can_update_their_profile()
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com'
        ]);

        $response = $this->actingAs($user)->putJson('/api/users/profile', [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'bio' => 'New bio'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('user.name', 'New Name');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
            'bio' => 'New bio'
        ]);
    }

    public function test_user_cannot_update_email_to_existing_email()
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'mine@example.com']);

        $response = $this->actingAs($user)->putJson('/api/users/profile', [
            'email' => 'taken@example.com',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_a_user_can_upload_profile_picture()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($user)->postJson('/api/users/profile-picture', [
            'profile_picture' => $file,
        ]);

        $response->assertStatus(200);
        
        // Assert file was saved
        $user->refresh();
        $this->assertNotNull($user->profile_picture);
        
        // Ensure string starts with /storage/
        $this->assertStringStartsWith('/storage/', $user->profile_picture);
    }
}
