<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Payment;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Lịch sử thanh toán hóa đơn (admin, xuyên tenant) — 2026-07-18.
 */
class AdminInvoiceHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private Subscription $subA;

    private Subscription $subB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $plan = Plan::query()->where('code', 'starter')->first();

        $ownerA = User::factory()->create();
        $this->tenantA = Tenant::create(['name' => 'Shop A']);
        $this->tenantA->users()->attach($ownerA->getKey(), ['role' => Role::Owner->value]);
        $this->subA = Subscription::query()->create([
            'tenant_id' => $this->tenantA->getKey(), 'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => 'monthly',
            'current_period_start' => now(), 'current_period_end' => now()->addDays(30),
        ]);

        $ownerB = User::factory()->create();
        $this->tenantB = Tenant::create(['name' => 'Shop B']);
        $this->tenantB->users()->attach($ownerB->getKey(), ['role' => Role::Owner->value]);
        $this->subB = Subscription::query()->create([
            'tenant_id' => $this->tenantB->getKey(), 'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => 'monthly',
            'current_period_start' => now(), 'current_period_end' => now()->addDays(30),
        ]);
    }

    private function makeInvoice(Tenant $tenant, Subscription $sub, string $code, string $status, ?Carbon $createdAt = null): Invoice
    {
        $invoice = Invoice::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'subscription_id' => $sub->getKey(),
            'code' => $code, 'status' => $status,
            'period_start' => now()->toDateString(), 'period_end' => now()->addDays(30)->toDateString(),
            'subtotal' => 190_000, 'tax' => 0, 'total' => 190_000, 'currency' => 'VND',
            'due_at' => now()->addDays(3),
        ]);
        if ($createdAt) {
            $invoice->forceFill(['created_at' => $createdAt])->save();
        }

        return $invoice;
    }

    public function test_admin_lists_invoices_across_all_tenants(): void
    {
        $admin = AdminUser::factory()->create();
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-A-1', Invoice::STATUS_PENDING);
        $this->makeInvoice($this->tenantB, $this->subB, 'INV-B-1', Invoice::STATUS_PAID);

        $res = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/invoices')->assertOk();

        $codes = collect($res->json('data'))->pluck('code')->all();
        $this->assertContains('INV-A-1', $codes);
        $this->assertContains('INV-B-1', $codes);
        $this->assertSame(2, $res->json('meta.pagination.total'));
    }

    public function test_regular_user_cannot_access(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web')->getJson('/api/v1/admin/invoices')->assertStatus(401);
    }

    public function test_filters_by_status(): void
    {
        $admin = AdminUser::factory()->create();
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-PENDING', Invoice::STATUS_PENDING);
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-PAID', Invoice::STATUS_PAID);

        $res = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/invoices?status=pending')->assertOk();

        $codes = collect($res->json('data'))->pluck('code')->all();
        $this->assertSame(['INV-PENDING'], $codes);
    }

    public function test_filters_by_tenant_id(): void
    {
        $admin = AdminUser::factory()->create();
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-A-ONLY', Invoice::STATUS_PENDING);
        $this->makeInvoice($this->tenantB, $this->subB, 'INV-B-ONLY', Invoice::STATUS_PENDING);

        $res = $this->actingAs($admin, 'admin_web')
            ->getJson('/api/v1/admin/invoices?tenant_id='.$this->tenantA->getKey())->assertOk();

        $codes = collect($res->json('data'))->pluck('code')->all();
        $this->assertSame(['INV-A-ONLY'], $codes);
        $this->assertSame($this->tenantA->getKey(), $res->json('data.0.tenant.id'));
        $this->assertSame('Shop A', $res->json('data.0.tenant.name'));
    }

    public function test_filters_by_code_search(): void
    {
        $admin = AdminUser::factory()->create();
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-202607-0001', Invoice::STATUS_PENDING);
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-202606-0001', Invoice::STATUS_PENDING);

        $res = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/invoices?q=202607')->assertOk();

        $codes = collect($res->json('data'))->pluck('code')->all();
        $this->assertSame(['INV-202607-0001'], $codes);
    }

    public function test_filters_by_created_date_range(): void
    {
        $admin = AdminUser::factory()->create();
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-OLD', Invoice::STATUS_PENDING, now()->subDays(30));
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-NEW', Invoice::STATUS_PENDING, now());

        $res = $this->actingAs($admin, 'admin_web')
            ->getJson('/api/v1/admin/invoices?date_from='.now()->subDays(2)->toIso8601String())->assertOk();

        $codes = collect($res->json('data'))->pluck('code')->all();
        $this->assertSame(['INV-NEW'], $codes);
    }

    public function test_payments_are_scoped_to_their_own_invoice_not_leaked_across_tenants(): void
    {
        $admin = AdminUser::factory()->create();
        $invoiceA = $this->makeInvoice($this->tenantA, $this->subA, 'INV-A-PAY', Invoice::STATUS_PAID);
        $invoiceB = $this->makeInvoice($this->tenantB, $this->subB, 'INV-B-PAY', Invoice::STATUS_PAID);

        Payment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenantA->getKey(), 'invoice_id' => $invoiceA->getKey(),
            'gateway' => Payment::GATEWAY_SEPAY, 'external_ref' => 'REF-A', 'amount' => 190_000,
            'status' => Payment::STATUS_SUCCEEDED, 'occurred_at' => now(),
        ]);
        Payment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenantB->getKey(), 'invoice_id' => $invoiceB->getKey(),
            'gateway' => Payment::GATEWAY_VNPAY, 'external_ref' => 'REF-B', 'amount' => 190_000,
            'status' => Payment::STATUS_SUCCEEDED, 'occurred_at' => now(),
        ]);

        $res = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/invoices')->assertOk();

        $rows = collect($res->json('data'))->keyBy('code');
        $this->assertCount(1, $rows['INV-A-PAY']['payments']);
        $this->assertSame(Payment::GATEWAY_SEPAY, $rows['INV-A-PAY']['payments'][0]['gateway']);
        $this->assertCount(1, $rows['INV-B-PAY']['payments']);
        $this->assertSame(Payment::GATEWAY_VNPAY, $rows['INV-B-PAY']['payments'][0]['gateway']);
    }

    public function test_pagination_meta_shape(): void
    {
        $admin = AdminUser::factory()->create();
        for ($i = 1; $i <= 3; $i++) {
            $this->makeInvoice($this->tenantA, $this->subA, 'INV-PAGE-'.$i, Invoice::STATUS_PENDING);
        }

        $res = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/invoices?per_page=2')->assertOk();

        $this->assertCount(2, $res->json('data'));
        $this->assertSame(1, $res->json('meta.pagination.page'));
        $this->assertSame(2, $res->json('meta.pagination.per_page'));
        $this->assertSame(3, $res->json('meta.pagination.total'));
        $this->assertSame(2, $res->json('meta.pagination.total_pages'));
    }
}
