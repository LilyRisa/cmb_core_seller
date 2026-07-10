<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\OverQuotaCheckService;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerPlatformOverQuotaTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'Shop']);
        Subscription::create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => Plan::query()->where('code', Plan::CODE_STARTER)->value('id'),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
    }

    private function addTiktok(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            ChannelAccount::query()->create([
                'tenant_id' => $this->tenant->getKey(),
                'provider' => 'tiktok',
                'external_shop_id' => 'shop'.$i.uniqid(),
                'status' => ChannelAccount::STATUS_ACTIVE,
            ]);
        }
    }

    private function addFacebookPages(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            ChannelAccount::query()->create([
                'tenant_id' => $this->tenant->getKey(),
                'provider' => 'facebook_page',
                'external_shop_id' => 'fb'.$i.uniqid(),
                'status' => ChannelAccount::STATUS_ACTIVE,
            ]);
        }
    }

    private function sub(): Subscription
    {
        return Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->firstOrFail();
    }

    public function test_third_tiktok_shop_is_over_per_platform_limit_after_downgrade(): void
    {
        // Starter cho 2 gian hàng/nền tảng; shop có 3 tiktok (vd vừa hạ từ Pro) ⇒ vượt.
        $this->addTiktok(3);

        $over = app(OverQuotaCheckService::class)->overResources($this->sub());

        $this->assertNotEmpty($over);
        $platform = collect($over)->firstWhere('provider', 'tiktok');
        $this->assertNotNull($platform);
        $this->assertSame(3, $platform['used']);
        $this->assertSame(2, $platform['limit']);
    }

    public function test_within_per_platform_limit_is_not_over(): void
    {
        $this->addTiktok(2);

        $over = app(OverQuotaCheckService::class)->overResources($this->sub());

        $this->assertEmpty(collect($over)->whereNotNull('provider')->all());
    }

    public function test_facebook_pages_over_per_platform_limit(): void
    {
        // Yêu cầu: starter tối đa 2 page Facebook; có 3 page ⇒ vượt. facebook_page nay được tính vào
        // hạn mức "/ nền tảng" (dù KHÔNG tính vào tổng channel_accounts marketplace).
        $this->addFacebookPages(3);

        $over = app(OverQuotaCheckService::class)->overResources($this->sub());

        $platform = collect($over)->firstWhere('provider', 'facebook_page');
        $this->assertNotNull($platform, 'Facebook Page vượt 2 phải bị coi là over-quota per-platform.');
        $this->assertSame(3, $platform['used']);
        $this->assertSame(2, $platform['limit']);
    }
}
