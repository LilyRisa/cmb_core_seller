<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Endpoint GET /api/v1/channel-shop-report — gating theo gói + tổng hợp per-shop. SPEC 2026-06-06.
 */
class ShopReportEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop A']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        config(['integrations.lazada.app_key' => 'lk', 'integrations.lazada.app_secret' => 'ls']);
        app(ChannelRegistry::class)->register('lazada', LazadaConnector::class);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function activate(string $planCode): void
    {
        Subscription::query()->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->copy()->addMonth(),
        ]);
    }

    private function lazadaShop(): void
    {
        ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 's1', 'shop_name' => 'Shop Lazada', 'status' => 'active', 'access_token' => 'at',
        ]);
    }

    public function test_blocked_when_plan_lacks_feature(): void
    {
        $this->activate('trial');   // trial: shop_health_reports = false
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/channel-shop-report')
            ->assertStatus(402)
            ->assertJsonPath('error.code', 'PLAN_FEATURE_LOCKED');
    }

    public function test_returns_aggregated_health_for_pro(): void
    {
        $this->activate('pro');     // pro: shop_health_reports = true
        $this->lazadaShop();
        Http::fake(['*/seller/performance/get*' => Http::response(['code' => '0', 'data' => [
            'indicators' => [[
                'type' => 'POSITIVE_SELLER_RATING', 'name' => 'Đánh giá tích cực',
                'score' => '92.0', 'score_format' => 'PERCENTAGE',
                'target' => '85.0', 'target_format' => 'GREATER_THAN_PERCENTAGE', 'target_respected' => 'true',
            ]],
        ]])]);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/channel-shop-report')
            ->assertOk()
            ->assertJsonPath('data.0.provider', 'lazada')
            ->assertJsonPath('data.0.available', true)
            ->assertJsonPath('data.0.kind', 'health');

        $this->assertNotEmpty($res->json('data.0.metrics'));
        $this->assertSame(true, $res->json('data.0.metrics.0.passed'));
    }
}
