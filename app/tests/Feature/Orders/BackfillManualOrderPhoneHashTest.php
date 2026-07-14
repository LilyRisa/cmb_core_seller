<?php

namespace Tests\Feature\Orders;

use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillManualOrderPhoneHashTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(Tenant $tenant, array $attrs): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create(array_merge([
            'tenant_id' => $tenant->getKey(), 'source' => 'manual', 'raw_status' => 'X',
            'status' => StandardOrderStatus::Pending, 'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000,
            'tags' => [], 'shipping_address' => [],
        ], $attrs));
    }

    public function test_backfills_null_hash_for_existing_orders(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $order = $this->makeOrder($tenant, [
            'order_number' => 'M-1', 'buyer_phone' => '0912345678',
            'shipping_address' => ['phone' => '0987654321'],
        ]);
        $this->assertNull($order->buyer_phone_hash);

        $this->artisan('orders:backfill-phone-hash')->assertExitCode(0);

        $order->refresh();
        $this->assertSame(CustomerPhoneNormalizer::normalizeAndHash('0912345678'), $order->buyer_phone_hash);
        $this->assertSame(CustomerPhoneNormalizer::normalizeAndHash('0987654321'), $order->recipient_phone_hash);
    }

    public function test_skips_orders_already_hashed(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $customHash = str_repeat('a', 64);
        $order = $this->makeOrder($tenant, [
            'order_number' => 'M-2', 'buyer_phone' => '0912345678',
            'buyer_phone_hash' => $customHash,
        ]);

        $this->artisan('orders:backfill-phone-hash')->assertExitCode(0);

        // Đã có hash từ trước ⇒ KHÔNG bị ghi đè (idempotent, không tốn công tính lại).
        $this->assertSame($customHash, $order->fresh()->buyer_phone_hash);
    }

    public function test_ignores_non_manual_orders(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $order = $this->makeOrder($tenant, [
            'order_number' => 'TT-1', 'source' => 'tiktok', 'buyer_phone' => '0912345678',
        ]);

        $this->artisan('orders:backfill-phone-hash')->assertExitCode(0);

        $this->assertNull($order->fresh()->buyer_phone_hash);
    }
}
