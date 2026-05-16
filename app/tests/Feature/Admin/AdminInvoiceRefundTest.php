<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Payment;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC 0023 — manual invoice + mark-paid + refund.
 */
class AdminInvoiceRefundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
    }

    public function test_admin_creates_manual_invoice(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $tenant = Tenant::create(['name' => 'M']);

        $this->actingAs($admin)->postJson("/api/v1/admin/tenants/{$tenant->id}/invoices", [
            'plan_code' => 'pro', 'cycle' => 'monthly', 'amount' => 500000,
            'note' => 'Khách CK Vietcombank ngày 15/05',
        ])->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.total', 500000);

        $this->assertDatabaseHas('invoices', ['tenant_id' => $tenant->id, 'total' => 500000, 'status' => 'pending']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.invoice.create_manual']);
    }

    public function test_mark_paid_creates_payment_and_fires_invoice_paid(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $tenant = Tenant::create(['name' => 'N']);

        // Create manual invoice first
        $this->actingAs($admin)->postJson("/api/v1/admin/tenants/{$tenant->id}/invoices", [
            'plan_code' => 'pro', 'cycle' => 'monthly', 'amount' => 500000,
        ])->assertCreated();

        $invoice = Invoice::query()->withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->id)->latest('id')->first();

        $this->actingAs($admin)->postJson("/api/v1/admin/invoices/{$invoice->id}/mark-paid", [
            'payment_method' => 'bank_transfer', 'reference' => 'VCB-TEST-123',
        ])->assertOk()->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('payments', ['invoice_id' => $invoice->id, 'gateway' => 'manual', 'status' => 'succeeded']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.invoice.mark_paid']);

        // ActivateSubscription listener (queue=sync trong test) đã chạy ⇒ sub active mới
        $alive = Subscription::query()->withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->id)
            ->whereIn('status', Subscription::ALIVE_STATUSES)->first();
        $this->assertNotNull($alive);
        $this->assertSame(Subscription::STATUS_ACTIVE, $alive->status);
    }

    public function test_mark_paid_idempotent_on_already_paid_invoice(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $tenant = Tenant::create(['name' => 'O']);

        $this->actingAs($admin)->postJson("/api/v1/admin/tenants/{$tenant->id}/invoices", [
            'plan_code' => 'pro', 'cycle' => 'monthly', 'amount' => 100000,
        ])->assertCreated();

        $invoice = Invoice::query()->withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->id)->latest('id')->first();

        $this->actingAs($admin)->postJson("/api/v1/admin/invoices/{$invoice->id}/mark-paid", [])->assertOk();
        // 2nd call — idempotent
        $this->actingAs($admin)->postJson("/api/v1/admin/invoices/{$invoice->id}/mark-paid", [])->assertOk();

        $this->assertSame(1, Payment::query()->withoutGlobalScope(TenantScope::class)->where('invoice_id', $invoice->id)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.invoice.mark_paid.noop']);
    }

    public function test_refund_payment_marks_refunded_and_optionally_rolls_back_subscription(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $tenant = Tenant::create(['name' => 'P']);
        $owner = User::factory()->create();
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);

        // Manual invoice + mark paid (sets up sub active on Pro)
        $this->actingAs($admin)->postJson("/api/v1/admin/tenants/{$tenant->id}/invoices", [
            'plan_code' => 'pro', 'cycle' => 'monthly', 'amount' => 500000,
        ])->assertCreated();
        $invoice = Invoice::query()->withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->id)->latest('id')->first();
        $this->actingAs($admin)->postJson("/api/v1/admin/invoices/{$invoice->id}/mark-paid", [])->assertOk();

        $payment = Payment::query()->withoutGlobalScope(TenantScope::class)->where('invoice_id', $invoice->id)->first();

        $this->actingAs($admin)->postJson("/api/v1/admin/payments/{$payment->id}/refund", [
            'reason' => 'Khách xin hoàn trong vòng 7 ngày — case 1234',
            'rollback_subscription' => true,
        ])->assertOk()->assertJsonPath('data.status', 'refunded');

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'refunded']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'refunded']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.payment.refund']);

        // Rolled back ⇒ trial fallback (SubscriptionService::ensureTrialFallback tạo
        // sub cycle=trial, plan=trial, status=active — đó là "trial vĩnh viễn lưới an toàn",
        // không khoá data như cam kết "Dữ liệu của bạn là của bạn").
        $alive = Subscription::query()->withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->id)
            ->whereIn('status', Subscription::ALIVE_STATUSES)->orderByDesc('id')->first();
        $this->assertNotNull($alive);
        $this->assertSame(Subscription::CYCLE_TRIAL, $alive->billing_cycle);
    }

    public function test_refund_idempotent_returns_already_refunded(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $tenant = Tenant::create(['name' => 'Q']);

        $this->actingAs($admin)->postJson("/api/v1/admin/tenants/{$tenant->id}/invoices", [
            'plan_code' => 'pro', 'cycle' => 'monthly', 'amount' => 100000,
        ])->assertCreated();
        $invoice = Invoice::query()->withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->id)->latest('id')->first();
        $this->actingAs($admin)->postJson("/api/v1/admin/invoices/{$invoice->id}/mark-paid", [])->assertOk();
        $payment = Payment::query()->withoutGlobalScope(TenantScope::class)->where('invoice_id', $invoice->id)->first();

        $this->actingAs($admin)->postJson("/api/v1/admin/payments/{$payment->id}/refund", [
            'reason' => 'Hoàn lần 1 — đúng quy trình.',
        ])->assertOk();

        $this->actingAs($admin)->postJson("/api/v1/admin/payments/{$payment->id}/refund", [
            'reason' => 'Hoàn lần 2 — đáng lẽ phải 422.',
        ])->assertStatus(422)->assertJsonPath('error.code', 'ALREADY_REFUNDED');
    }
}
