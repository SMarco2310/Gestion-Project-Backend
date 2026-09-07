<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationEntitlement;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\Invitation;

class PlanQuotaEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrgWithOwner(): array
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $org->users()->attach($user->id, ['role' => 'proprietaire']);

        return [$user, $org];
    }

    // ---- max_members: invitation store() ----

    public function test_inviting_past_max_members_is_rejected()
    {
        [$owner, $org] = $this->makeOrgWithOwner();

        // Free limit is 5. Owner already counts as 1 member.
        // Fill remaining slots with members so count = 5.
        for ($i = 0; $i < 4; $i++) {
            $member = User::factory()->create();
            $org->users()->attach($member->id, ['role' => 'membre']);
        }

        $response = $this->actingAs($owner)->postJson('/api/invitations', [
            'email' => 'newperson@example.com',
            'organization_id' => $org->id,
        ]);

        $response->assertStatus(422)
            ->assertJson(['upgrade_required' => true, 'feature' => 'max_members']);
    }

    public function test_invite_under_limit_succeeds()
    {
        [$owner, $org] = $this->makeOrgWithOwner();

        $response = $this->actingAs($owner)->postJson('/api/invitations', [
            'email' => 'newperson@example.com',
            'organization_id' => $org->id,
        ]);

        $response->assertStatus(201);
    }

    // ---- max_members: invitation accept() ----

    public function test_accepting_invitation_into_full_org_is_rejected()
    {
        [$owner, $org] = $this->makeOrgWithOwner();

        // Fill org to the free limit (5) including owner.
        for ($i = 0; $i < 4; $i++) {
            $member = User::factory()->create();
            $org->users()->attach($member->id, ['role' => 'membre']);
        }

        $invitee = User::factory()->create();
        $invitation = Invitation::create([
            'email' => $invitee->email,
            'token' => 'test-token-full-org',
            'organization_id' => $org->id,
            'role' => 'member',
            'status' => 'pending',
            'expires_at' => now()->addDays(2),
            'invited_by' => $owner->id,
        ]);

        $response = $this->actingAs($invitee)->postJson("/api/invitations/{$invitation->token}/accept");

        $response->assertStatus(422)
            ->assertJson(['upgrade_required' => true, 'feature' => 'max_members']);

        $this->assertFalse($invitee->organizations()->where('organizations.id', $org->id)->exists());
    }

    public function test_accepting_invitation_under_limit_succeeds()
    {
        [$owner, $org] = $this->makeOrgWithOwner();

        $invitee = User::factory()->create();
        $invitation = Invitation::create([
            'email' => $invitee->email,
            'token' => 'test-token-ok',
            'organization_id' => $org->id,
            'role' => 'member',
            'status' => 'pending',
            'expires_at' => now()->addDays(2),
            'invited_by' => $owner->id,
        ]);

        $response = $this->actingAs($invitee)->postJson("/api/invitations/{$invitation->token}/accept");

        $response->assertStatus(200);
        $this->assertTrue($invitee->organizations()->where('organizations.id', $org->id)->exists());
    }

    // ---- max_attachment_size_mb ----

    public function test_attachment_larger_than_size_limit_is_rejected()
    {
        Storage::fake('public');
        [$owner, $org] = $this->makeOrgWithOwner();
        $projet = Projet::factory()->create(['organization_id' => $org->id, 'user_id' => $owner->id]);

        // Free limit is 10MB. Upload 11MB (under the 10240KB hard validation ceiling... )
        // Note: absolute ceiling is also 10MB (10240KB) so we need a plan with a bigger
        // absolute limit but smaller feature limit to distinguish. Instead, raise the
        // hard ceiling scenario by giving an entitlement with a larger absolute size
        // is not possible (hard cap fixed at 10240KB). So test size limit under free tier
        // using a file just below the hard cap but above a lowered plan limit.
        OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'expires_at' => now()->addDay(),
            'features' => [['code' => 'max_attachment_size_mb', 'limit' => 2]],
        ]);

        $file = UploadedFile::fake()->create('doc.pdf', 3 * 1024); // 3MB > 2MB limit

        $response = $this->actingAs($owner)->postJson("/api/projets/{$projet->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJson(['upgrade_required' => true, 'feature' => 'max_attachment_size_mb']);
    }

    public function test_attachment_under_size_limit_succeeds()
    {
        Storage::fake('public');
        [$owner, $org] = $this->makeOrgWithOwner();
        $projet = Projet::factory()->create(['organization_id' => $org->id, 'user_id' => $owner->id]);

        $file = UploadedFile::fake()->create('doc.pdf', 1024); // 1MB, under free 10MB limit

        $response = $this->actingAs($owner)->postJson("/api/projets/{$projet->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(201);
    }

    public function test_tache_attachment_larger_than_size_limit_is_rejected()
    {
        Storage::fake('public');
        [$owner, $org] = $this->makeOrgWithOwner();
        $projet = Projet::factory()->create(['organization_id' => $org->id, 'user_id' => $owner->id]);
        // Built directly (not via TacheFactory) to sidestep the factory's stale
        // tag_id column, which is a pre-existing schema/factory mismatch unrelated
        // to plan-quota enforcement (also seen failing in TacheTest.php).
        $tache = Tache::create([
            'title' => 'Test task',
            'status' => 'à faire',
            'priority' => 'moyen',
            'due_date' => now()->addWeek(),
            'projet_id' => $projet->id,
        ]);

        OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'expires_at' => now()->addDay(),
            'features' => [['code' => 'max_attachment_size_mb', 'limit' => 2]],
        ]);

        $file = UploadedFile::fake()->create('doc.pdf', 3 * 1024); // 3MB > 2MB limit

        $response = $this->actingAs($owner)->postJson("/api/taches/{$tache->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJson(['upgrade_required' => true, 'feature' => 'max_attachment_size_mb']);
    }

    // ---- max_storage_mb ----

    public function test_upload_exceeding_storage_limit_is_rejected()
    {
        Storage::fake('public');
        [$owner, $org] = $this->makeOrgWithOwner();
        $projet = Projet::factory()->create(['organization_id' => $org->id, 'user_id' => $owner->id]);

        OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'expires_at' => now()->addDay(),
            'features' => [
                ['code' => 'max_storage_mb', 'limit' => 5],
                ['code' => 'max_attachment_size_mb', 'limit' => null],
            ],
        ]);

        // Existing attachment already using 4MB of the 5MB quota.
        $projet->attachments()->create([
            'user_id' => $owner->id,
            'file_name' => 'existing.pdf',
            'file_path' => 'attachments/existing.pdf',
            'mime_type' => 'application/pdf',
            'size' => 4 * 1024 * 1024,
        ]);

        // New 2MB upload would push total to 6MB > 5MB limit.
        $file = UploadedFile::fake()->create('doc2.pdf', 2 * 1024);

        $response = $this->actingAs($owner)->postJson("/api/projets/{$projet->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJson(['upgrade_required' => true, 'feature' => 'max_storage_mb']);

        Storage::disk('public')->assertMissing('attachments/' . $file->hashName());
    }

    public function test_upload_under_storage_limit_succeeds()
    {
        Storage::fake('public');
        [$owner, $org] = $this->makeOrgWithOwner();
        $projet = Projet::factory()->create(['organization_id' => $org->id, 'user_id' => $owner->id]);

        $file = UploadedFile::fake()->create('doc.pdf', 1024); // 1MB, well under free 200MB

        $response = $this->actingAs($owner)->postJson("/api/projets/{$projet->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(201);
    }

    // ---- unlimited plan (null limit) is not blocked ----

    public function test_active_entitlement_with_null_limit_is_not_blocked()
    {
        Storage::fake('public');
        [$owner, $org] = $this->makeOrgWithOwner();

        OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'expires_at' => now()->addDay(),
            'features' => [
                ['code' => 'max_members', 'limit' => null],
                ['code' => 'max_storage_mb', 'limit' => null],
                ['code' => 'max_attachment_size_mb', 'limit' => null],
            ],
        ]);

        // Fill well past the free member limit — should still be allowed.
        for ($i = 0; $i < 6; $i++) {
            $member = User::factory()->create();
            $org->users()->attach($member->id, ['role' => 'membre']);
        }

        $response = $this->actingAs($owner)->postJson('/api/invitations', [
            'email' => 'unlimited-invite@example.com',
            'organization_id' => $org->id,
        ]);

        $response->assertStatus(201);

        // Large attachment should also be allowed (still under the 10240KB hard cap).
        $projet = Projet::factory()->create(['organization_id' => $org->id, 'user_id' => $owner->id]);
        $file = UploadedFile::fake()->create('big.pdf', 9 * 1024); // 9MB

        $uploadResponse = $this->actingAs($owner)->postJson("/api/projets/{$projet->id}/attachments", [
            'file' => $file,
        ]);

        $uploadResponse->assertStatus(201);
    }
}
