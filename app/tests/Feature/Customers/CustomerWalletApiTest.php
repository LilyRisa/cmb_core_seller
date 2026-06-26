<?php

namespace Tests\Feature\Customers;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerWalletApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_topup_requires_amount_and_invoice_then_updates_balance(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        app(AccountingSetupService::class)->run((int) $tenant->getKey(), 2026);
        $c = Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'phone_hash' => str_repeat('c', 64), 'phone' => '0900000000', 'name' => 'K',
            'lifetime_stats' => ['orders_total' => 0], 'tags' => [], 'addresses_meta' => [], 'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);
        $h = ['X-Tenant-Id' => (string) $tenant->getKey()];

        // Thiếu hóa đơn ⇒ 422.
        $this->actingAs($owner)->withHeaders($h)
            ->postJson("/api/v1/customers/{$c->getKey()}/wallet/topup", ['amount' => 250000, 'payment_method' => 'cash'])
            ->assertStatus(422);

        // Đủ số tiền + hóa đơn ⇒ OK + balance.
        $this->actingAs($owner)->withHeaders($h)
            ->postJson("/api/v1/customers/{$c->getKey()}/wallet/topup", ['amount' => 250000, 'payment_method' => 'cash', 'invoice_ref' => 'HD-2026-001'])
            ->assertOk()->assertJsonPath('data.balance', 250000)
            ->assertJsonPath('data.transaction.invoice_ref', 'HD-2026-001');

        $this->actingAs($owner)->withHeaders($h)->getJson("/api/v1/customers/{$c->getKey()}")
            ->assertOk()->assertJsonPath('data.prepaid_balance', 250000);

        $this->actingAs($owner)->withHeaders($h)->getJson("/api/v1/customers/{$c->getKey()}/wallet/transactions")
            ->assertOk()->assertJsonPath('meta.pagination.total', 1);
    }
}
