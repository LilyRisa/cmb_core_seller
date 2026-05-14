<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Payment;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6.4 / SPEC 0018 — webhook SePay flow:
 *  - Verify chữ ký (Authorization: Apikey …) — sai ⇒ 401, không ghi gì.
 *  - Đúng ⇒ ghi webhook_events + dispatch ProcessPaymentWebhook (queue sync trong test).
 *  - Payload SePay valid ⇒ tạo Payment + invoice paid + listener ActivateSubscription chạy.
 *  - Webhook trùng (cùng `id`) ⇒ chỉ 1 Payment row (idempotent).
 *  - Orphan reference ⇒ webhook_event status=`ignored`, không Payment.
 *  - Underpay (số tiền < total) ⇒ Payment ghi nhận nhưng invoice GIỮ pending.
 */
class SePayWebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'WebhookShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function createPendingInvoice(string $planCode = Plan::CODE_PRO, string $cycle = 'monthly'): Invoice
    {
        $plan = Plan::query()->where('code', $planCode)->first();
        $sub = Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_TRIALING,
            'billing_cycle' => Subscription::CYCLE_TRIAL,
            'trial_ends_at' => now()->addDays(14),
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(14),
        ]);

        return Invoice::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'subscription_id' => $sub->getKey(),
            'code' => 'INV-202605-0007',
            'status' => Invoice::STATUS_PENDING,
            'period_start' => now()->format('Y-m-d'),
            'period_end' => now()->addDays(30)->format('Y-m-d'),
            'subtotal' => $cycle === 'yearly' ? (int) $plan->price_yearly : (int) $plan->price_monthly,
            'tax' => 0,
            'total' => $cycle === 'yearly' ? (int) $plan->price_yearly : (int) $plan->price_monthly,
            'due_at' => now()->addDays(7),
            'meta' => ['plan_code' => $planCode, 'cycle' => $cycle],
        ]);
    }

    private function sepayPayload(string $memo, int $amount, string $txId = 'TX-001'): array
    {
        return [
            'id' => $txId,
            'gateway' => 'MBBank',
            'transactionDate' => now()->toIso8601String(),
            'accountNumber' => '9999999999',
            'subAccount' => '0123456789',
            'transferType' => 'in',
            'transferAmount' => $amount,
            'content' => $memo,
            'description' => 'CT TU '.$memo,
            'accumulated' => 0,
            'referenceCode' => 'REF',
        ];
    }

    public function test_webhook_rejects_missing_signature(): void
    {
        $this->postJson('/webhook/payments/sepay', $this->sepayPayload('INV-202605-0007', 199_000))
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'WEBHOOK_SIGNATURE_INVALID');

        $this->assertDatabaseCount('webhook_events', 0);
    }

    public function test_webhook_rejects_wrong_apikey(): void
    {
        $this->withHeaders(['Authorization' => 'Apikey wrong-key'])
            ->postJson('/webhook/payments/sepay', $this->sepayPayload('INV-202605-0007', 199_000))
            ->assertStatus(401);

        $this->assertDatabaseCount('webhook_events', 0);
    }

    public function test_valid_webhook_creates_payment_marks_invoice_paid_and_activates_subscription(): void
    {
        $invoice = $this->createPendingInvoice(Plan::CODE_PRO, 'monthly');

        $this->withHeaders(['Authorization' => 'Apikey test-webhook-key'])
            ->postJson('/webhook/payments/sepay', $this->sepayPayload($invoice->code, 199_000, 'TX-100'))
            ->assertOk();

        // Webhook event đã được xử lý.
        $event = WebhookEvent::query()->where('provider', 'payments.sepay')->latest('id')->first();
        $this->assertNotNull($event);
        $this->assertSame('processed', $event->status);

        // Payment đã insert.
        $payment = Payment::query()->withoutGlobalScope(TenantScope::class)
            ->where('gateway', 'sepay')->where('external_ref', 'TX-100')->first();
        $this->assertNotNull($payment);
        $this->assertSame(199_000, (int) $payment->amount);
        $this->assertSame('succeeded', $payment->status);
        // PII redact: subAccount + accountNumber không lưu lại.
        $this->assertArrayNotHasKey('subAccount', (array) $payment->raw_payload);
        $this->assertArrayNotHasKey('accountNumber', (array) $payment->raw_payload);

        // Invoice paid.
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);

        // ActivateSubscription đã chạy (queue=sync trong test) → tenant có subscription active Pro.
        $sub = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())
            ->where('status', Subscription::STATUS_ACTIVE)
            ->with('plan')
            ->latest('id')->first();
        $this->assertNotNull($sub);
        $this->assertSame(Plan::CODE_PRO, $sub->plan->code);
        $this->assertSame('monthly', $sub->billing_cycle);
        // Subscription trial cũ đã chuyển sang cancelled.
        $oldTrials = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())
            ->where('status', Subscription::STATUS_CANCELLED)->count();
        $this->assertGreaterThanOrEqual(1, $oldTrials);
    }

    public function test_duplicate_webhook_is_idempotent(): void
    {
        $invoice = $this->createPendingInvoice();

        $payload = $this->sepayPayload($invoice->code, 199_000, 'TX-DUP');
        $this->withHeaders(['Authorization' => 'Apikey test-webhook-key'])
            ->postJson('/webhook/payments/sepay', $payload)->assertOk();
        $this->withHeaders(['Authorization' => 'Apikey test-webhook-key'])
            ->postJson('/webhook/payments/sepay', $payload)->assertOk();

        $count = Payment::query()->withoutGlobalScope(TenantScope::class)
            ->where('gateway', 'sepay')->where('external_ref', 'TX-DUP')->count();
        $this->assertSame(1, $count, 'Webhook chạy 2 lần phải chỉ tạo 1 Payment.');
    }

    public function test_orphan_reference_marks_event_ignored(): void
    {
        $this->withHeaders(['Authorization' => 'Apikey test-webhook-key'])
            ->postJson('/webhook/payments/sepay', $this->sepayPayload('INV-NOT-EXIST', 100_000, 'TX-ORPHAN'))
            ->assertOk();

        $event = WebhookEvent::query()->where('provider', 'payments.sepay')->latest('id')->first();
        $this->assertSame('ignored', $event->status);
        $this->assertSame(0, Payment::query()->withoutGlobalScope(TenantScope::class)->count());
    }

    public function test_underpay_keeps_invoice_pending(): void
    {
        $invoice = $this->createPendingInvoice();

        // Chỉ trả 100k cho invoice 199k.
        $this->withHeaders(['Authorization' => 'Apikey test-webhook-key'])
            ->postJson('/webhook/payments/sepay', $this->sepayPayload($invoice->code, 100_000, 'TX-UNDER'))
            ->assertOk();

        // Payment ghi nhận.
        $this->assertDatabaseHas('payments', ['external_ref' => 'TX-UNDER', 'amount' => 100_000, 'status' => 'succeeded']);
        // Invoice vẫn pending — user phải chuyển thêm.
        $this->assertSame(Invoice::STATUS_PENDING, $invoice->fresh()->status);
    }

    public function test_checkout_endpoint_returns_real_sepay_session(): void
    {
        // Owner gọi checkout ⇒ trả CheckoutSession có qr_url + memo + account_no.
        $resp = $this->actingAs($this->owner)->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->postJson('/api/v1/billing/checkout', [
                'plan_code' => Plan::CODE_PRO, 'cycle' => 'monthly', 'gateway' => 'sepay',
            ]);

        $resp->assertCreated()
            ->assertJsonPath('data.checkout.method', 'bank_transfer')
            ->assertJsonPath('data.checkout.account_no', '9999999999')
            ->assertJsonPath('data.checkout.bank_code', 'MB');
        $memo = $resp->json('data.checkout.memo');
        $code = $resp->json('data.invoice.code');
        $this->assertSame($memo, $code, 'Memo phải bằng invoice code để webhook khớp.');
    }
}
