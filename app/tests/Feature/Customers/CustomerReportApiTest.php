<?php

namespace Tests\Feature\Customers;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC 0038 v2 — POST /customers/reports: báo cáo "bom hàng" cho đơn thủ công đã
 * hoàn/thất bại; idempotent theo đơn; OrderResource phơi can_bad_report/bad_reported.
 */
class CustomerReportApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private const PHONE = '0395151515';

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop A']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeOrder(StandardOrderStatus $status, string $source = 'manual', string $number = 'M-1'): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => $source, 'customer_id' => null,
            'external_order_id' => null, 'order_number' => $number, 'status' => $status, 'raw_status' => 'X',
            'buyer_phone' => self::PHONE, 'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000,
            'placed_at' => now(), 'has_issue' => false, 'tags' => [], 'source_updated_at' => now(),
        ]);
    }

    private function report(int $orderId, string $reason = 'Bom hàng')
    {
        return $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/customers/reports', ['order_id' => $orderId, 'reason' => $reason]);
    }

    public function test_reports_returned_manual_order_and_marks_it(): void
    {
        $order = $this->makeOrder(StandardOrderStatus::ReturnedRefunded);

        $this->report($order->getKey())->assertCreated()->assertJsonPath('data.order_id', $order->getKey());

        $this->assertDatabaseHas('customer_reports', [
            'order_id' => $order->getKey(),
            'phone_hash' => CustomerPhoneNormalizer::hash(self::PHONE),
            'reason' => 'Bom hàng',
        ]);

        // OrderResource phản ánh trạng thái đã báo
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/orders/{$order->getKey()}")
            ->assertOk()->assertJsonPath('data.can_bad_report', true)->assertJsonPath('data.bad_reported', true);
    }

    public function test_duplicate_report_is_rejected(): void
    {
        $order = $this->makeOrder(StandardOrderStatus::DeliveryFailed);
        $this->report($order->getKey())->assertCreated();
        $this->report($order->getKey())->assertStatus(422);

        $this->assertDatabaseCount('customer_reports', 1);
    }

    public function test_completed_order_cannot_be_reported(): void
    {
        $order = $this->makeOrder(StandardOrderStatus::Completed);
        $this->report($order->getKey())->assertStatus(422);

        $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/orders/{$order->getKey()}")
            ->assertOk()->assertJsonPath('data.can_bad_report', false);
    }

    public function test_marketplace_order_cannot_be_reported(): void
    {
        $order = $this->makeOrder(StandardOrderStatus::ReturnedRefunded, source: 'tiktok', number: 'TT-1');
        $this->report($order->getKey())->assertStatus(422);
    }

    public function test_reason_is_required(): void
    {
        $order = $this->makeOrder(StandardOrderStatus::Returning);
        $this->report($order->getKey(), reason: '')->assertStatus(422);
    }
}
