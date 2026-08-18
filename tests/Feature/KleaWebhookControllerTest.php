<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Organization;
use App\Models\OrganizationEntitlement;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class KleaWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.klea.webhook_secret', 'testsecret');
        Config::set('services.klea.base_url', 'https://klea.test/api');
        Config::set('services.klea.api_key', 'pub.sec');
    }

    protected function signedPayload(array $payload): array
    {
        $signature = hash_hmac('sha256', (string) $payload['transaction']['id'], 'testsecret');
        return [$payload, $signature];
    }

    public function test_rejects_missing_signature()
    {
        $org = Organization::factory()->create();
        $payload = [
            'event' => 'subscription.payment_result',
            'status' => 'successful',
            'subscription_id' => 1,
            'subscriber_external_id' => $org->id,
            'plan_id' => 1,
            'features' => [],
            'transaction' => ['id' => 100, 'amount' => 15000, 'currency' => 'XOF'],
        ];

        $response = $this->postJson('/api/webhooks/klea', $payload);

        $response->assertStatus(401);
    }

    public function test_rejects_when_webhook_secret_is_not_configured()
    {
        Config::set('services.klea.webhook_secret', '');

        $org = Organization::factory()->create();
        $payload = [
            'event' => 'subscription.payment_result',
            'status' => 'successful',
            'subscription_id' => 1,
            'subscriber_external_id' => $org->id,
            'plan_id' => 1,
            'features' => [],
            'transaction' => ['id' => 100, 'amount' => 15000, 'currency' => 'XOF'],
        ];

        // What an attacker could produce themselves: HMAC computed with an empty key.
        $forgedSignature = hash_hmac('sha256', (string) $payload['transaction']['id'], '');

        $response = $this->postJson('/api/webhooks/klea', $payload, ['X-Klea-Signature' => $forgedSignature]);

        $response->assertStatus(401);

        $this->assertDatabaseMissing('organization_entitlements', [
            'organization_id' => $org->id,
            'status' => 'active',
        ]);
    }

    public function test_rejects_invalid_signature()
    {
        $org = Organization::factory()->create();
        $payload = [
            'event' => 'subscription.payment_result',
            'status' => 'successful',
            'subscription_id' => 1,
            'subscriber_external_id' => $org->id,
            'plan_id' => 1,
            'features' => [],
            'transaction' => ['id' => 100, 'amount' => 15000, 'currency' => 'XOF'],
        ];

        $response = $this->postJson('/api/webhooks/klea', $payload, ['X-Klea-Signature' => 'wrongsignature']);

        $response->assertStatus(401);
    }

    public function test_activates_entitlement_on_successful_payment()
    {
        Http::fake([
            '*/public/plans' => Http::response([
                'data' => [['id' => 1, 'name' => 'Pro', 'duration_days' => 30]],
                'success' => true, 'message' => 'ok',
            ], 200),
        ]);

        $org = Organization::factory()->create();
        $payload = [
            'event' => 'subscription.payment_result',
            'status' => 'successful',
            'subscription_id' => 1,
            'subscriber_external_id' => $org->id,
            'plan_id' => 1,
            'features' => [['id' => 1, 'code' => 'max_workspaces', 'limit' => 10]],
            'transaction' => ['id' => 100, 'amount' => 15000, 'currency' => 'XOF'],
        ];
        [$body, $signature] = $this->signedPayload($payload);

        $response = $this->postJson('/api/webhooks/klea', $body, ['X-Klea-Signature' => $signature]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('organization_entitlements', [
            'organization_id' => $org->id,
            'status' => 'active',
            'plan_name' => 'Pro',
        ]);

        $entitlement = OrganizationEntitlement::where('organization_id', $org->id)->first();
        $this->assertNotNull($entitlement->expires_at);
        $this->assertCount(1, $entitlement->features);
    }

    public function test_rejects_successful_webhook_with_mismatched_subscription_id()
    {
        $org = Organization::factory()->create();
        OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'klea_subscription_id' => 999, // the subscription id recorded when subscribe() was called
            'status' => 'pending',
        ]);

        $payload = [
            'event' => 'subscription.payment_result',
            'status' => 'successful',
            'subscription_id' => 1, // an attacker-controlled/replayed subscription id that does not match
            'subscriber_external_id' => $org->id,
            'plan_id' => 1,
            'features' => [['id' => 1, 'code' => 'max_workspaces', 'limit' => 10]],
            'transaction' => ['id' => 100, 'amount' => 15000, 'currency' => 'XOF'],
        ];
        [$body, $signature] = $this->signedPayload($payload);

        $response = $this->postJson('/api/webhooks/klea', $body, ['X-Klea-Signature' => $signature]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('organization_entitlements', [
            'organization_id' => $org->id,
            'status' => 'pending', // must not have been activated
            'klea_subscription_id' => 999,
        ]);
    }

    public function test_replaying_the_same_successful_webhook_only_activates_once()
    {
        Http::fake([
            '*/public/plans' => Http::response([
                'data' => [['id' => 1, 'name' => 'Pro', 'duration_days' => 30]],
                'success' => true, 'message' => 'ok',
            ], 200),
        ]);

        $org = Organization::factory()->create();
        $payload = [
            'event' => 'subscription.payment_result',
            'status' => 'successful',
            'subscription_id' => 1,
            'subscriber_external_id' => $org->id,
            'plan_id' => 1,
            'features' => [['id' => 1, 'code' => 'max_workspaces', 'limit' => 10]],
            'transaction' => ['id' => 100, 'amount' => 15000, 'currency' => 'XOF'],
        ];
        [$body, $signature] = $this->signedPayload($payload);

        $first = $this->postJson('/api/webhooks/klea', $body, ['X-Klea-Signature' => $signature]);
        $first->assertStatus(200);

        $entitlementAfterFirst = OrganizationEntitlement::where('organization_id', $org->id)->first();
        $expiresAtAfterFirst = $entitlementAfterFirst->expires_at;

        $second = $this->postJson('/api/webhooks/klea', $body, ['X-Klea-Signature' => $signature]);
        $second->assertStatus(200);

        $entitlementAfterSecond = OrganizationEntitlement::where('organization_id', $org->id)->first();

        $this->assertTrue($expiresAtAfterFirst->equalTo($entitlementAfterSecond->expires_at));
        $this->assertEquals(1, OrganizationEntitlement::where('organization_id', $org->id)->count());
    }

    public function test_failed_payment_does_not_revoke_existing_active_entitlement()
    {
        $org = Organization::factory()->create();
        OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'active',
            'plan_name' => 'Pro',
            'expires_at' => now()->addDays(20),
            'features' => [['code' => 'max_workspaces', 'limit' => 10]],
        ]);

        $payload = [
            'event' => 'subscription.payment_result',
            'status' => 'failed',
            'subscription_id' => 2,
            'subscriber_external_id' => $org->id,
            'plan_id' => 1,
            'features' => [],
            'transaction' => ['id' => 200, 'amount' => 15000, 'currency' => 'XOF'],
        ];
        [$body, $signature] = $this->signedPayload($payload);

        $response = $this->postJson('/api/webhooks/klea', $body, ['X-Klea-Signature' => $signature]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('organization_entitlements', [
            'organization_id' => $org->id,
            'status' => 'active', // unchanged — a failed renewal must not revoke current access
        ]);
    }

    public function test_pending_status_is_a_noop()
    {
        $org = Organization::factory()->create();
        OrganizationEntitlement::create([
            'organization_id' => $org->id,
            'status' => 'pending',
        ]);

        $payload = [
            'event' => 'subscription.payment_result',
            'status' => 'pending',
            'subscription_id' => 3,
            'subscriber_external_id' => $org->id,
            'plan_id' => 1,
            'features' => [],
            'transaction' => ['id' => 300, 'amount' => 15000, 'currency' => 'XOF'],
        ];
        [$body, $signature] = $this->signedPayload($payload);

        $response = $this->postJson('/api/webhooks/klea', $body, ['X-Klea-Signature' => $signature]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('organization_entitlements', [
            'organization_id' => $org->id,
            'status' => 'pending',
        ]);
    }
}
