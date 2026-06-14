<?php

namespace Tests\Feature\Inventory;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Events\StockPushed;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockToListing;
use CMBcoreSeller\Modules\Inventory\Models\StockPushLog;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StockPushLogTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelListing $listing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(CurrentTenant::class)->set($this->tenant);

        $account = ChannelAccount::create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'shopee', 'external_shop_id' => 's1',
            'shop_name' => 'Shop A', 'shop_region' => 'VN', 'status' => 'active', 'access_token' => 'tok',
        ]);
        $this->listing = ChannelListing::create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $account->getKey(),
            'external_product_id' => 'p1', 'external_sku_id' => 'sku1', 'seller_sku' => 'S1',
            'sync_status' => 'error', 'sync_error' => 'Shopee API 500: lỗi',
        ]);
    }

    public function test_failed_push_is_logged_and_listed(): void
    {
        StockPushed::dispatch($this->listing, 7, false);

        $this->assertDatabaseHas('stock_push_logs', [
            'channel_listing_id' => $this->listing->getKey(), 'status' => 'failed', 'desired_qty' => 7,
            'error' => 'Shopee API 500: lỗi',
        ]);

        $res = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->getJson('/api/v1/inventory/stock-push-logs?status=failed');

        $res->assertOk();
        $res->assertJsonCount(1, 'data');
        $this->assertSame('S1', $res->json('data.0.seller_sku'));
        $this->assertSame('Shop A', $res->json('data.0.shop_name'));
    }

    public function test_retry_queues_a_push(): void
    {
        StockPushed::dispatch($this->listing, 5, false);
        $logId = (int) StockPushLog::query()->first()->getKey();

        Queue::fake();

        $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->postJson("/api/v1/inventory/stock-push-logs/{$logId}/retry")
            ->assertOk()
            ->assertJsonPath('data.queued', true);

        Queue::assertPushed(PushStockToListing::class);
    }
}
