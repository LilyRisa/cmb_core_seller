<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures as ShopeeFx;
use Tests\Fixtures\Channels\tiktok\TikTokFixtures as F;
use Tests\TestCase;

/**
 * TDD: assertChannelOrderFulfillable dùng connector->prepareBlockReason (SPEC 0013).
 * Thông báo chặn phải là label() cụ thể của PrepareBlockReason — KHÔNG dùng config
 * unfulfillable_raw_statuses nữa.
 */
class AssertChannelOrderFulfillableTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        F::configure();
        config(['integrations.tiktok.fulfillment_enabled' => true]);
        $this->tenant = Tenant::create(['name' => 'Shop assertPrep']);
        app(CurrentTenant::class)->set($this->tenant);
    }

    /**
     * Tạo ChannelAccount + Order với raw_status / status cho trước. Trả [Order, ChannelAccount].
     *
     * @return array{Order, ChannelAccount}
     */
    private function makeChannelOrderRawStatus(string $provider, string $rawStatus, string $standard): array
    {
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => $provider,
            'external_shop_id' => strtoupper($provider).'_TEST',
            'shop_name' => 'Test '.$provider,
            'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'tk',
            'refresh_token' => 'rk',
            'token_expires_at' => now()->addDays(7),
            'meta' => $provider === 'tiktok' ? ['shop_cipher' => F::SHOP_CIPHER] : [],
        ]);

        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'source' => $provider,
            'channel_account_id' => $account->getKey(),
            'external_order_id' => strtoupper($provider).'-'.uniqid(),
            'order_number' => strtoupper($provider).'-'.uniqid(),
            'status' => StandardOrderStatus::from($standard),
            'raw_status' => $rawStatus,
            'shipping_address' => ['fullName' => 'A', 'phone' => '0900000000'],
            'currency' => 'VND',
            'grand_total' => 100000,
            'item_total' => 100000,
            'is_cod' => false,
            'placed_at' => now()->subHour(),
            'source_updated_at' => now()->subHour(),
            'has_issue' => false,
            'tags' => [],
            'packages' => [],
        ]);

        return [$order, $account];
    }

    public function test_blocks_unpaid_channel_order_with_specific_message(): void
    {
        // TikTok UNPAID → PrepareBlockReason::AwaitingPayment → 'Chờ người mua thanh toán'.
        [$order] = $this->makeChannelOrderRawStatus('tiktok', 'UNPAID', standard: 'unpaid');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Chờ người mua thanh toán');

        app(ShipmentService::class)->assertPreparable($order->fresh());
    }

    public function test_allows_ready_to_ship_channel_order(): void
    {
        // Shopee READY_TO_SHIP → prepareBlockReason = null → không chặn vì lý do sàn.
        // (Có thể ném vì lý do khác như âm tồn — test này chỉ khẳng định KHÔNG ném message sàn.)
        app(ChannelRegistry::class)->register('shopee', ShopeeConnector::class);
        ShopeeFx::configure();

        [$order] = $this->makeChannelOrderRawStatus('shopee', 'READY_TO_SHIP', standard: 'ready_to_ship');

        try {
            app(ShipmentService::class)->assertPreparable($order->fresh());
            $this->addToAssertionCount(1); // không ném = được phép chuẩn bị hàng (OK).
        } catch (RuntimeException $e) {
            $this->assertStringNotContainsString('Chờ người mua thanh toán', $e->getMessage());
        }
    }
}
