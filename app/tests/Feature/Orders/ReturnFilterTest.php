<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderReturn;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC 0025 — tab "Trả/hoàn" ở Orders. Sàn (TikTok/Shopee/Lazada) KHÔNG map order.status sang
 * returning ⇒ chỉ set has_return + tạo order_returns. Filter `has_return` phải bắt được đơn có
 * bản ghi trả/hoàn (kind return|refund) HOẶC status returning/returned_refunded.
 */
class ReturnFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'shopee',
            'external_shop_id' => 's1', 'shop_name' => 'Shop', 'status' => ChannelAccount::STATUS_ACTIVE,
        ]);
    }

    private function order(string $number, string $status): Order
    {
        return Order::query()->forceCreate([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'shopee',
            'channel_account_id' => $this->account->id, 'external_order_id' => $number,
            'order_number' => $number, 'status' => $status, 'currency' => 'VND', 'grand_total' => 100000,
        ]);
    }

    private function header(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_has_return_filter_matches_orders_with_return_record_or_returning_status(): void
    {
        // A: đơn Shopee đã giao xong nhưng có yêu cầu TRẢ HÀNG (status đơn KHÔNG đổi).
        $a = $this->order('SP-A', 'completed');
        OrderReturn::query()->forceCreate([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->account->id,
            'order_id' => $a->id, 'source' => 'shopee', 'external_return_id' => 'r-a',
            'external_order_id' => 'SP-A', 'kind' => 'return', 'status' => 'requested',
            'refund_amount' => 50000, 'currency' => 'VND',
        ]);
        // B: đơn có HOÀN TIỀN.
        $b = $this->order('SP-B', 'delivered');
        OrderReturn::query()->forceCreate([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->account->id,
            'order_id' => $b->id, 'source' => 'shopee', 'external_return_id' => 'r-b',
            'external_order_id' => 'SP-B', 'kind' => 'refund', 'status' => 'completed',
            'refund_amount' => 100000, 'currency' => 'VND',
        ]);
        // C: đơn có status returning (trường hợp sàn có map — vẫn phải lọt).
        $c = $this->order('SP-C', 'returning');
        // D: đơn thường — KHÔNG được lọt.
        $this->order('SP-D', 'completed');

        $res = $this->actingAs($this->owner)->withHeaders($this->header())
            ->getJson('/api/v1/orders?has_return=1')->assertOk();

        $numbers = collect($res->json('data'))->pluck('order_number')->sort()->values()->all();
        $this->assertSame(['SP-A', 'SP-B', 'SP-C'], $numbers);

        // stats.has_return = 3 (A,B,C); has_issue/by_status không đổi.
        $this->actingAs($this->owner)->withHeaders($this->header())
            ->getJson('/api/v1/orders/stats')->assertOk()
            ->assertJsonPath('data.has_return', 3);

        // Không có cờ ⇒ trả tất cả 4 đơn.
        $this->actingAs($this->owner)->withHeaders($this->header())
            ->getJson('/api/v1/orders')->assertOk()
            ->assertJsonCount(4, 'data');

        unset($c);
    }
}
