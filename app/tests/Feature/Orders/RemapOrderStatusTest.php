<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaConnector;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus as S;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `orders:remap-status` — tính lại trạng thái từ raw_status theo map hiện tại (áp fix cho đơn cũ), CHỈ tiến/
 * sang nhánh huỷ-hoàn, không kéo lùi đơn đã tiến nội bộ.
 */
class RemapOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        app(ChannelRegistry::class)->register('lazada', LazadaConnector::class);
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'LZ', 'shop_name' => 'Lazada', 'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
    }

    private function order(string $ext, S $status, string $rawStatus): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'lazada',
            'channel_account_id' => $this->account->getKey(), 'external_order_id' => $ext, 'order_number' => $ext,
            'status' => $status, 'raw_status' => $rawStatus, 'currency' => 'VND', 'grand_total' => 1, 'item_total' => 1,
            'placed_at' => now()->subDay(), 'tags' => [], 'source_updated_at' => now()->subDay(),
        ]);
    }

    public function test_remaps_returned_order_forward_but_not_regress(): void
    {
        // Đơn trả về bị map nhầm Shipped (bug cũ) ⇒ remap về returned_refunded (tiến tới — sang Trả/hoàn).
        $ret = $this->order('LZ-RET', S::Shipped, 'shipped_back_success');
        // Đơn đã tiến nội bộ (ready_to_ship) nhưng raw còn 'packed' ⇒ KHÔNG được kéo lùi về processing.
        $adv = $this->order('LZ-ADV', S::ReadyToShip, 'packed');
        // Không đổi: raw map ra đúng trạng thái hiện tại.
        $same = $this->order('LZ-SAME', S::Shipped, 'shipped');

        $this->artisan('orders:remap-status', ['--source' => 'lazada'])->assertSuccessful();

        $this->assertSame(S::ReturnedRefunded, $ret->refresh()->status, 'shipped_back_success → Trả/hoàn.');
        $this->assertSame(S::ReadyToShip, $adv->refresh()->status, 'Đơn đã tiến nội bộ KHÔNG bị kéo lùi.');
        $this->assertSame(S::Shipped, $same->refresh()->status);
    }

    public function test_dry_run_does_not_write(): void
    {
        $o = $this->order('LZ-DRY', S::Shipped, 'shipped_back_success');
        $this->artisan('orders:remap-status', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(S::Shipped, $o->refresh()->status, 'dry-run không ghi.');
    }
}
