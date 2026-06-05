<?php

namespace Tests\Feature\Finance;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Finance\Jobs\FetchSettlementsForShop;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * SPEC 0016 — `settlements:fetch-daily` xếp job kéo đối soát cho mọi gian hàng active
 * của các sàn ĐÃ bật finance, đẩy lên queue `finance`. Sàn tắt / manual / inactive bị bỏ.
 */
class FetchDailySettlementsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Shop']);
    }

    private function account(string $provider, string $status, string $ext): ChannelAccount
    {
        return ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => $provider,
            'external_shop_id' => $ext, 'shop_name' => strtoupper($provider), 'status' => $status,
            'access_token' => 'T',
        ]);
    }

    public function test_dispatches_only_active_shops_of_enabled_providers_on_finance_queue(): void
    {
        config(['integrations.tiktok.finance_enabled' => true, 'integrations.lazada.finance_enabled' => false]);
        Queue::fake();

        $tt1 = $this->account('tiktok', 'active', 's1');
        $tt2 = $this->account('tiktok', 'active', 's2');
        $this->account('tiktok', 'inactive', 's3');   // inactive ⇒ bỏ
        $this->account('lazada', 'active', 's4');      // lazada finance off ⇒ bỏ
        $this->account('manual', 'active', 's5');      // manual ⇒ bỏ

        $this->artisan('settlements:fetch-daily')->assertSuccessful();

        Queue::assertPushed(FetchSettlementsForShop::class, 2);
        foreach ([$tt1->id, $tt2->id] as $id) {
            Queue::assertPushed(FetchSettlementsForShop::class, fn ($job) => $job->channelAccountId === $id
                && $job->queue === 'finance'
                && $job->from !== null && $job->to !== null);
        }
    }

    public function test_dispatches_nothing_when_no_provider_enabled(): void
    {
        config(['integrations.tiktok.finance_enabled' => false, 'integrations.lazada.finance_enabled' => false, 'integrations.shopee.finance_enabled' => false]);
        Queue::fake();

        $this->account('tiktok', 'active', 's1');

        $this->artisan('settlements:fetch-daily')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_provider_option_limits_to_one_marketplace(): void
    {
        config(['integrations.tiktok.finance_enabled' => true, 'integrations.shopee.finance_enabled' => true]);
        Queue::fake();

        $this->account('tiktok', 'active', 's1');
        $this->account('shopee', 'active', 's2');

        $this->artisan('settlements:fetch-daily --provider=shopee')->assertSuccessful();

        Queue::assertPushed(FetchSettlementsForShop::class, 1);
        Queue::assertPushed(FetchSettlementsForShop::class, fn ($job) => $job->from !== null);
    }
}
