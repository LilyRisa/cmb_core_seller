<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Đơn của gian hàng đã HỦY KẾT NỐI (status=revoked) coi như đã xóa: không hiện ở list, không cộng vào mọi
 * facet/count. Đơn của gian hàng đang kết nối + đơn manual vẫn giữ. Reconnect ⇒ account active ⇒ đơn hiện lại.
 */
class RevokedAccountOrdersHiddenTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->user->getKey(), ['role' => Role::Owner->value]);
    }

    private function header(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function account(string $status, string $shop): ChannelAccount
    {
        return ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'shopee',
            'external_shop_id' => $shop, 'shop_name' => $shop, 'status' => $status,
        ]);
    }

    private function order(ChannelAccount $acc, string $ext): void
    {
        Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'shopee',
            'channel_account_id' => $acc->getKey(), 'external_order_id' => $ext, 'order_number' => $ext,
            'status' => StandardOrderStatus::Processing, 'raw_status' => 'PROCESSED',
            'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000,
            'placed_at' => now()->subHour(), 'tags' => [], 'source_updated_at' => now()->subHour(),
        ]);
    }

    public function test_orders_from_revoked_account_are_hidden_from_list_and_counts(): void
    {
        $active = $this->account(ChannelAccount::STATUS_ACTIVE, 'ACTIVE-SHOP');
        $revoked = $this->account(ChannelAccount::STATUS_REVOKED, 'DEAD-SHOP');
        $this->order($active, 'A-1');
        $this->order($active, 'A-2');
        $this->order($revoked, 'R-1');   // gian hàng đã hủy kết nối ⇒ phải ẩn
        $this->order($revoked, 'R-2');

        // list: chỉ 2 đơn của shop active
        $list = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders?status=processing')->assertOk();
        $this->assertSame(2, $list->json('meta.pagination.total'));
        $exts = collect($list->json('data'))->pluck('external_order_id')->all();
        sort($exts);
        $this->assertSame(['A-1', 'A-2'], $exts);

        // stats: by_status / by_source / by_shop chỉ đếm shop active (2), không cộng 2 đơn revoked
        $stats = $this->actingAs($this->user)->withHeaders($this->header())
            ->getJson('/api/v1/orders/stats')->assertOk();
        $this->assertSame(2, $stats->json('data.by_status.processing'));
        $shopee = collect($stats->json('data.by_source'))->firstWhere('source', 'shopee');
        $this->assertSame(2, $shopee['count']);
        $shopIds = collect($stats->json('data.by_shop'))->pluck('channel_account_id')->all();
        $this->assertContains($active->getKey(), $shopIds);
        $this->assertNotContains($revoked->getKey(), $shopIds, 'gian hàng revoked không xuất hiện trong chip');
    }
}
