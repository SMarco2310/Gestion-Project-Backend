<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizationLogoUploadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A brand-new organization has no entitlement row, so it resolves to the
     * Free tier where custom_branding is false. Uploading the org's own logo
     * must still work — it happens during organization creation.
     */
    public function test_free_tier_organization_can_upload_its_logo()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => 'proprietaire', 'joined_at' => now()]);

        $this->actingAs($user)
            ->postJson("/api/organizations/{$org->id}/logo", [
                'logo' => UploadedFile::fake()->create('logo.png', 20, 'image/png'),
            ])
            ->assertStatus(200);
    }

    public function test_a_plain_member_still_cannot_upload_a_logo()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => 'membre', 'joined_at' => now()]);

        $this->actingAs($user)
            ->postJson("/api/organizations/{$org->id}/logo", [
                'logo' => UploadedFile::fake()->create('logo.png', 20, 'image/png'),
            ])
            ->assertStatus(403);
    }
}
