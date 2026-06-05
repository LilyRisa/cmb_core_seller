<?php

namespace Tests\Feature\Finance;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Finance\Jobs\FetchSettlementsForShop;
use CMBcoreSeller\Modules\Finance\Services\SettlementService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FetchSettlementsUnsupportedTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_skips_channel_without_settlements_api(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = ChannelAccount::create([
            'tenant_id' => $tenant->id, 'provider' => 'tiktok', 'external_shop_id' => 's1',
            'shop_name' => 'Shop', 'status' => 'active', 'access_token' => 'T',
        ]);

        // Provider has no settlements API → service throws UnsupportedOperation.
        $this->app->bind(SettlementService::class, fn () => new class extends SettlementService
        {
            public function __construct() {}

            public function fetchForShop(ChannelAccount $account, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null, ?int $userId = null): array
            {
                throw UnsupportedOperation::for('tiktok', 'fetchSettlements');
            }
        });

        // Must NOT throw (otherwise the job fails + retries uselessly).
        (new FetchSettlementsForShop($account->id))->handle(app(SettlementService::class));

        $this->assertTrue(true);
    }
}
