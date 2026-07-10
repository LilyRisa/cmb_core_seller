<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Contracts\ChannelQuotaInspector;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Hạn mức số Facebook Page còn lại theo gói — dùng cho chặn kết nối (Messaging). */
class ChannelQuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'Shop']);
    }

    private function subscribe(string $planCode): void
    {
        Subscription::create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => Plan::query()->where('code', $planCode)->value('id'),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
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

    private function remaining(): ?int
    {
        return app(ChannelQuotaInspector::class)->remainingForProvider((int) $this->tenant->getKey(), 'facebook_page');
    }

    public function test_starter_allows_two_facebook_pages(): void
    {
        $this->subscribe(Plan::CODE_STARTER); // per-platform = 2
        $this->assertSame(2, $this->remaining());
        $this->addFacebookPages(1);
        $this->assertSame(1, $this->remaining());
        $this->addFacebookPages(1);
        $this->assertSame(0, $this->remaining());
    }

    public function test_pro_is_unlimited(): void
    {
        $this->subscribe(Plan::CODE_PRO); // per-platform = -1
        $this->addFacebookPages(5);
        $this->assertNull($this->remaining());
    }

    public function test_no_subscription_is_not_gated(): void
    {
        $this->assertNull($this->remaining());
    }
}
