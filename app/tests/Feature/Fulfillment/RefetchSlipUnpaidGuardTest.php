<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Services\ShipmentService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Fixtures\Channels\tiktok\TikTokFixtures as F;
use Tests\TestCase;

/**
 * TDD – FIX E: refetchSlip() phải ném lý do cụ thể (vd "Chờ người mua thanh toán")
 * thay vì lỗi chung chung "Chưa lấy được phiếu giao hàng từ sàn lần này..."
 * khi đơn sàn đang ở raw_status không cho phép arrange (UNPAID).
 */
class RefetchSlipUnpaidGuardTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        F::configure();
        config(['integrations.tiktok.fulfillment_enabled' => true]);
        $this->tenant = Tenant::create(['name' => 'Shop refetch slip guard']);
        app(CurrentTenant::class)->set($this->tenant);
    }

    public function test_refetch_slip_throws_specific_reason_for_unpaid_order(): void
    {
        // RED → GREEN after adding assertChannelOrderFulfillable() before the arrange try-block
        // in refetchSlip(). TikTok UNPAID → PrepareBlockReason::AwaitingPayment → "Chờ người mua thanh toán".
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'tiktok',
            'external_shop_id' => F::SHOP_ID,
            'shop_name' => 'TikTok Refetch Test',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'tk',
            'refresh_token' => 'rk',
            'token_expires_at' => now()->addDays(7),
            'meta' => ['shop_cipher' => F::SHOP_CIPHER],
        ]);

        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'source' => 'tiktok',
            'channel_account_id' => $account->getKey(),
            'external_order_id' => 'TT-UNPAID-'.uniqid(),
            'order_number' => 'TT-UNPAID-1',
            'status' => StandardOrderStatus::Unpaid,
            'raw_status' => 'UNPAID',
            'shipping_address' => ['fullName' => 'A', 'phone' => '0900000000'],
            'currency' => 'VND',
            'grand_total' => 100000,
            'item_total' => 100000,
            'is_cod' => false,
            'placed_at' => now()->subHour(),
            'source_updated_at' => now()->subHour(),
            'has_issue' => true,
            // issue_reason phải chứa 'phiếu giao hàng' để $issueAboutChannel = true → vào block arrange
            'issue_reason' => 'Chưa lấy được phiếu giao hàng từ sàn lần này — bấm "Nhận phiếu giao hàng" để thử lại.',
            'tags' => [],
            'packages' => [],
        ]);

        // Tạo vận đơn mở để refetchSlip() không trả 'no_shipment' ngay lập tức
        Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'order_id' => $order->getKey(),
            'carrier' => '',
            'tracking_no' => null,
            'status' => Shipment::STATUS_CREATED,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Chờ người mua thanh toán');

        app(ShipmentService::class)->refetchSlip($order->fresh());
    }
}
