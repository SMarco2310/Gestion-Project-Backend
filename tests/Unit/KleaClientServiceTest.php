<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Organization;
use App\Models\User;
use App\Services\KleaClientService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class KleaClientServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.klea.api_key', 'testpublic.testsecret');
        Config::set('services.klea.base_url', 'https://klea.test/api');
    }

    public function test_listPlans_calls_klea_and_returns_data()
    {
        Http::fake([
            'klea.test/api/public/plans' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'Pro', 'price' => 15000, 'currency' => 'XOF', 'duration_days' => 30, 'features' => []],
                ],
                'success' => true,
                'message' => 'ok',
            ], 200),
        ]);

        $service = new KleaClientService();
        $plans = $service->listPlans();

        $this->assertCount(1, $plans);
        $this->assertEquals('Pro', $plans->first()['name']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://klea.test/api/public/plans'
                && $request->hasHeader('Authorization', 'Bearer testpublic.testsecret');
        });
    }

    public function test_listPlans_is_cached()
    {
        Http::fake([
            'klea.test/api/public/plans' => Http::response(['data' => [], 'success' => true, 'message' => 'ok'], 200),
        ]);

        $service = new KleaClientService();
        $service->listPlans();
        $service->listPlans();

        Http::assertSentCount(1);
    }

    public function test_subscribe_sends_organization_id_as_external_id()
    {
        Http::fake([
            'klea.test/api/public/subscribe' => Http::response([
                'data' => [
                    'subscription_id' => 42,
                    'transaction_id' => 100,
                    'amount' => 15000,
                    'currency' => 'XOF',
                    'payment_url' => 'https://pay.example/x',
                    'qrcode_url' => null,
                ],
                'success' => true,
                'message' => 'ok',
            ], 201),
        ]);

        $org = Organization::factory()->create();
        $user = User::factory()->create(['email' => 'buyer@example.com']);

        $service = new KleaClientService();
        $result = $service->subscribe($org, 1, $user, '+22500000000');

        $this->assertEquals(42, $result['subscription_id']);
        $this->assertEquals('https://pay.example/x', $result['payment_url']);

        Http::assertSent(function ($request) use ($org) {
            return $request['external_id'] === $org->id
                && $request['plan_id'] === 1
                && $request['email'] === 'buyer@example.com'
                && $request['environment'] === 'live';
        });
    }

    public function test_subscribe_throws_on_error_response()
    {
        Http::fake([
            'klea.test/api/public/subscribe' => Http::response(['success' => false, 'message' => 'Plan not found'], 422),
        ]);

        $org = Organization::factory()->create();
        $user = User::factory()->create();

        $service = new KleaClientService();

        $this->expectException(\RuntimeException::class);
        $service->subscribe($org, 999, $user, '+22500000000');
    }
}
