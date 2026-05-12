<?php

namespace Tests\Feature\Customers;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerNote;
use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Tenant $other;

    private Customer $alice;     // watch, 1 note, this tenant

    private Customer $bob;       // ok, vip, this tenant

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop A']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->other = Tenant::create(['name' => 'Shop B']);

        $this->alice = $this->makeCustomer($this->tenant, '0987654321', 'Alice', score: 70, label: 'watch', stats: ['orders_total' => 6, 'orders_completed' => 4, 'orders_cancelled' => 2, 'revenue_completed' => 800000]);
        CustomerNote::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'customer_id' => $this->alice->getKey(), 'kind' => 'auto.cancel_streak', 'severity' => 'warning', 'note' => 'Đã có 2 đơn huỷ', 'dedupe_key' => 'auto.cancel_streak:warning', 'created_at' => now()]);

        $this->bob = $this->makeCustomer($this->tenant, '0911111111', 'Bob', score: 100, label: 'ok', stats: ['orders_total' => 12, 'orders_completed' => 12, 'orders_cancelled' => 0, 'revenue_completed' => 5000000], tags: ['vip']);

        // a customer in another tenant — must never leak
        $this->makeCustomer($this->other, '0987654321', 'Other Alice', score: 100, label: 'ok', stats: ['orders_total' => 1, 'orders_completed' => 1]);
    }

    private function makeCustomer(Tenant $t, string $phone, string $name, int $score, string $label, array $stats, array $tags = []): Customer
    {
        return Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t->getKey(),
            'phone_hash' => CustomerPhoneNormalizer::hash($phone),
            'phone' => $phone, 'name' => $name,
            'lifetime_stats' => $stats + ['orders_total' => 0, 'orders_completed' => 0, 'orders_cancelled' => 0, 'orders_returned' => 0, 'orders_delivery_failed' => 0, 'orders_in_progress' => 0, 'revenue_completed' => 0],
            'reputation_score' => $score, 'reputation_label' => $label, 'tags' => $tags,
            'first_seen_at' => now()->subDays(30), 'last_seen_at' => now()->subDays(1),
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_index_lists_only_current_tenant_customers(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/customers')->assertOk();
        $res->assertJsonCount(2, 'data')->assertJsonPath('meta.pagination.total', 2);
        $names = collect($res->json('data'))->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['Alice', 'Bob'], $names);
        // owner has customers.view_phone ⇒ full phone present
        $alice = collect($res->json('data'))->firstWhere('name', 'Alice');
        $this->assertSame('0987654321', $alice['phone']);
        $this->assertSame('watch', $alice['reputation']['label']);
    }

    public function test_index_filters_and_search(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/customers?reputation=watch')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Alice');
        // search by raw phone → hash lookup
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/customers?q=0987-654-321')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Alice');
        // search by name
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/customers?q=Bob')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Bob');
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/customers?tag=vip')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Bob');
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/customers?has_note=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Alice');
    }

    public function test_show_includes_notes_and_latest_warning(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/customers/{$this->alice->getKey()}")->assertOk();
        $res->assertJsonPath('data.name', 'Alice')->assertJsonPath('data.latest_warning_note.severity', 'warning');
        $this->assertCount(1, $res->json('data.notes'));
    }

    public function test_show_404_for_another_tenant_customer(): void
    {
        $other = Customer::withoutGlobalScope(TenantScope::class)->where('tenant_id', $this->other->getKey())->first();
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/customers/{$other->getKey()}")->assertNotFound();
    }

    public function test_staff_order_can_note_but_viewer_cannot(): void
    {
        $staff = User::factory()->create();
        $this->tenant->users()->attach($staff->getKey(), ['role' => Role::StaffOrder->value]);
        $viewer = User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => Role::Viewer->value]);

        $this->actingAs($staff)->withHeaders($this->h())->postJson("/api/v1/customers/{$this->alice->getKey()}/notes", ['note' => 'Đã gọi xác nhận, OK', 'severity' => 'info'])
            ->assertCreated()->assertJsonPath('data.kind', 'manual')->assertJsonPath('data.note', 'Đã gọi xác nhận, OK');
        $this->actingAs($viewer)->withHeaders($this->h())->postJson("/api/v1/customers/{$this->alice->getKey()}/notes", ['note' => 'x'])->assertForbidden();
        // viewer cannot see full phone
        $res = $this->actingAs($viewer)->withHeaders($this->h())->getJson("/api/v1/customers/{$this->alice->getKey()}")->assertOk();
        $this->assertNull($res->json('data.phone'));
        $this->assertNotNull($res->json('data.phone_masked'));
    }

    public function test_cannot_delete_auto_note(): void
    {
        $auto = CustomerNote::withoutGlobalScope(TenantScope::class)->where('customer_id', $this->alice->getKey())->first();
        $this->actingAs($this->owner)->withHeaders($this->h())->deleteJson("/api/v1/customers/{$this->alice->getKey()}/notes/{$auto->getKey()}")->assertStatus(422);
    }

    public function test_block_and_unblock(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/customers/{$this->bob->getKey()}/block", ['reason' => 'Bom hàng'])
            ->assertOk()->assertJsonPath('data.is_blocked', true)->assertJsonPath('data.reputation.label', 'blocked');
        $this->assertTrue($this->bob->fresh()->is_blocked);

        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/customers/{$this->bob->getKey()}/unblock")
            ->assertOk()->assertJsonPath('data.is_blocked', false)->assertJsonPath('data.reputation.label', 'ok');
    }

    public function test_tags(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/customers/{$this->alice->getKey()}/tags", ['add' => ['bom', 'goi-truoc']])
            ->assertOk()->assertJsonPath('data.tags', ['bom', 'goi-truoc']);
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/customers/{$this->alice->getKey()}/tags", ['remove' => ['bom']])
            ->assertOk()->assertJsonPath('data.tags', ['goi-truoc']);
    }

    public function test_merge_moves_orders_and_soft_deletes_removed(): void
    {
        // give Alice and a duplicate "Alice2" each one order
        $alice2 = $this->makeCustomer($this->tenant, '0322222222', 'Alice (cũ)', score: 100, label: 'ok', stats: ['orders_total' => 1, 'orders_completed' => 1, 'revenue_completed' => 200000]);
        $o1 = Order::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'source' => 'tiktok', 'customer_id' => $this->alice->getKey(), 'external_order_id' => 'A-1', 'order_number' => 'A-1', 'status' => StandardOrderStatus::Completed, 'raw_status' => 'X', 'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000, 'placed_at' => now(), 'has_issue' => false, 'tags' => [], 'source_updated_at' => now()]);
        $o2 = Order::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'source' => 'manual', 'customer_id' => $alice2->getKey(), 'external_order_id' => null, 'order_number' => 'M-1', 'status' => StandardOrderStatus::Completed, 'raw_status' => 'X', 'currency' => 'VND', 'grand_total' => 200000, 'item_total' => 200000, 'placed_at' => now(), 'has_issue' => false, 'tags' => [], 'source_updated_at' => now()]);

        $this->actingAs($this->owner)->withHeaders($this->h())->postJson('/api/v1/customers/merge', ['keep_id' => $this->alice->getKey(), 'remove_id' => $alice2->getKey()])
            ->assertOk()->assertJsonPath('data.id', $this->alice->getKey());

        $this->assertSame((int) $this->alice->getKey(), (int) $o2->fresh()->customer_id);
        $this->assertNotNull($alice2->fresh()->deleted_at);
        $this->assertSame((int) $this->alice->getKey(), (int) $alice2->fresh()->merged_into_customer_id);
        // merged customer's API → 404
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/customers/{$alice2->getKey()}")->assertNotFound();
        // recomputed stats of kept (2 completed orders, 300k revenue)
        $this->assertSame(2, $this->alice->fresh()->lifetime_stats['orders_completed']);
        $this->assertSame(300000, $this->alice->fresh()->lifetime_stats['revenue_completed']);
    }

    public function test_customer_orders_endpoint(): void
    {
        Order::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'source' => 'tiktok', 'customer_id' => $this->alice->getKey(), 'external_order_id' => 'A-1', 'order_number' => 'A-1', 'status' => StandardOrderStatus::Completed, 'raw_status' => 'X', 'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000, 'placed_at' => now(), 'has_issue' => false, 'tags' => [], 'source_updated_at' => now()]);
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/customers/{$this->alice->getKey()}/orders")->assertOk();
        $res->assertJsonCount(1, 'data')->assertJsonPath('data.0.order_number', 'A-1');
    }

    public function test_order_resource_includes_customer_card(): void
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->create(['tenant_id' => $this->tenant->getKey(), 'source' => 'tiktok', 'customer_id' => $this->alice->getKey(), 'external_order_id' => 'A-1', 'order_number' => 'A-1', 'status' => StandardOrderStatus::Pending, 'raw_status' => 'X', 'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000, 'placed_at' => now(), 'has_issue' => false, 'tags' => [], 'source_updated_at' => now()]);
        $res = $this->actingAs($this->owner)->withHeaders($this->h())->getJson("/api/v1/orders/{$order->getKey()}")->assertOk();
        $res->assertJsonPath('data.customer.id', $this->alice->getKey())
            ->assertJsonPath('data.customer.name', 'Alice')
            ->assertJsonPath('data.customer.reputation.label', 'watch')
            ->assertJsonPath('data.customer.latest_warning_note.severity', 'warning');
    }
}
