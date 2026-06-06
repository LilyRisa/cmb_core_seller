<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Services\ShopHealthAnalysisService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Phân tích AI Báo cáo sàn — rule-based (luôn có) + endpoint. SPEC 2026-06-06. */
class ShopHealthAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_rule_based_analysis_scores_and_recommends(): void
    {
        // tenant không subscription ⇒ aiEnabled=false ⇒ chỉ rule (không gọi LLM).
        $res = app(ShopHealthAnalysisService::class)->analyze(999999, [
            'metrics' => [
                ['name' => 'Tỉ lệ giao trễ', 'passed' => false, 'target' => 5, 'comparator' => '<='],
                ['name' => 'Đánh giá', 'passed' => true],
            ],
            'penalties' => [['points' => 3, 'violation_label' => 'Trễ giao']],
            'punishments' => [['type_label' => 'Ẩn listing', 'tier' => 2]],
        ]);

        // 100 - 8 (1 fail) - 9 (3đ×3) - 15 (1 phạt) = 68
        $this->assertSame(68, $res['score']);
        $this->assertSame('Khá', $res['label']);
        $this->assertSame('rule', $res['source']);
        $this->assertNotEmpty($res['recommendations']);
    }

    public function test_ai_insight_endpoint(): void
    {
        $this->seed(BillingPlanSeeder::class);
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop A']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $plan = Plan::query()->where('code', 'pro')->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->copy()->addMonth(),
        ]);
        config(['integrations.lazada.app_key' => 'lk', 'integrations.lazada.app_secret' => 'ls']);
        app(ChannelRegistry::class)->register('lazada', LazadaConnector::class);

        $shop = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 's1', 'shop_name' => 'S1', 'status' => 'active', 'access_token' => 'at',
        ]);
        Http::fake(['*/seller/performance/get*' => Http::response(['code' => '0', 'data' => ['indicators' => [
            ['type' => 'SHIP_ON_TIME', 'name' => 'Giao đúng hạn', 'score' => '80.0', 'score_format' => 'PERCENTAGE', 'target' => '95.0', 'target_format' => 'GREATER_THAN_PERCENTAGE', 'target_respected' => 'false'],
        ]]])]);

        $this->actingAs($owner)->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->postJson("/api/v1/channel-shop-report/{$shop->getKey()}/ai-insight")
            ->assertOk()
            ->assertJsonStructure(['data' => ['score', 'label', 'assessment', 'recommendations', 'source']]);
    }
}
