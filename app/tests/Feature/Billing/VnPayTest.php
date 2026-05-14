<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Integrations\Payments\DTO\CheckoutRequest;
use CMBcoreSeller\Integrations\Payments\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Payments\PaymentRegistry;
use CMBcoreSeller\Integrations\Payments\VnPay\VnPayConnector;
use CMBcoreSeller\Integrations\Payments\VnPay\VnPaySigner;
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
use Tests\TestCase;

/**
 * Phase 6.4 / SPEC 0018 — VNPay redirect + IPN.
 */
class VnPayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
    }

    public function test_signer_produces_deterministic_signature(): void
    {
        $signer = new VnPaySigner('TESTSECRET123');
        $params = [
            'vnp_TmnCode' => 'M01',
            'vnp_Amount' => '19900000',
            'vnp_TxnRef' => 'INV-202605-0001',
            'vnp_OrderInfo' => 'Test',
        ];
        $sig1 = $signer->sign($params);
        $sig2 = $signer->sign($params);

        $this->assertSame($sig1, $sig2, 'Signature deterministic với cùng input.');
        $this->assertSame(128, strlen($sig1), 'HMAC-SHA512 = 128 hex chars.');
        $this->assertTrue($signer->verify(array_merge($params, ['vnp_SecureHash' => $sig1]), $sig1));
    }

    public function test_signer_rejects_wrong_signature(): void
    {
        $signer = new VnPaySigner('TESTSECRET123');
        $params = ['vnp_TmnCode' => 'M01', 'vnp_Amount' => '100', 'vnp_TxnRef' => 'A'];
        $this->assertFalse($signer->verify($params, 'WRONGHASH'));
        $this->assertFalse($signer->verify($params, ''));
    }

    public function test_vnpay_checkout_builds_signed_redirect_url(): void
    {
        /** @var VnPayConnector $connector */
        $connector = $this->app->make(PaymentRegistry::class)->for('vnpay');

        $session = $connector->checkout(new CheckoutRequest(
            tenantId: 1,
            invoiceId: 100,
            reference: 'INV-202605-0042',
            amount: 199_000,
            description: 'Pro monthly',
        ));

        $this->assertSame('redirect', $session->method);
        $this->assertNotNull($session->redirectUrl);
        $this->assertStringContainsString('vnp_TmnCode=TESTMERCHANT', $session->redirectUrl);
        $this->assertStringContainsString('vnp_Amount=19900000', $session->redirectUrl);   // ×100
        $this->assertStringContainsString('vnp_TxnRef=INV-202605-0042', $session->redirectUrl);
        $this->assertStringContainsString('vnp_SecureHash=', $session->redirectUrl);
    }

    public function test_vnpay_webhook_verifies_signature_and_marks_invoice_paid(): void
    {
        // Tạo tenant + invoice pending.
        $tenant = Tenant::create(['name' => 'VnPayShop']);
        $owner = User::factory()->create();
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->first();
        $sub = Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_TRIALING, 'billing_cycle' => Subscription::CYCLE_TRIAL,
            'trial_ends_at' => now()->addDays(14), 'current_period_start' => now(),
            'current_period_end' => now()->addDays(14),
        ]);
        $invoice = Invoice::query()->create([
            'tenant_id' => $tenant->getKey(), 'subscription_id' => $sub->getKey(),
            'code' => 'INV-202605-VNP1', 'status' => Invoice::STATUS_PENDING,
            'period_start' => now()->format('Y-m-d'), 'period_end' => now()->addMonth()->format('Y-m-d'),
            'subtotal' => 199_000, 'tax' => 0, 'total' => 199_000, 'due_at' => now()->addDays(7),
            'meta' => ['plan_code' => Plan::CODE_PRO, 'cycle' => 'monthly'],
        ]);

        // Build VNPay IPN payload với chữ ký đúng.
        $params = [
            'vnp_TmnCode' => 'TESTMERCHANT',
            'vnp_Amount' => '19900000',
            'vnp_TxnRef' => 'INV-202605-VNP1',
            'vnp_TransactionNo' => '14000123',
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_PayDate' => now()->format('YmdHis'),
        ];
        $signer = new VnPaySigner('TESTSECRET123');
        $params['vnp_SecureHash'] = $signer->sign($params);

        $this->postJson('/webhook/payments/vnpay', $params)->assertOk();

        // Payment + invoice paid + subscription active.
        $payment = Payment::query()->withoutGlobalScope(TenantScope::class)
            ->where('gateway', 'vnpay')->where('external_ref', '14000123')->first();
        $this->assertNotNull($payment);
        $this->assertSame(199_000, (int) $payment->amount);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);

        $activeSub = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())->where('status', Subscription::STATUS_ACTIVE)
            ->latest('id')->with('plan')->first();
        $this->assertSame(Plan::CODE_PRO, $activeSub->plan->code);
    }

    public function test_vnpay_webhook_rejects_invalid_signature(): void
    {
        $params = [
            'vnp_TmnCode' => 'TESTMERCHANT', 'vnp_Amount' => '100000', 'vnp_TxnRef' => 'X',
            'vnp_ResponseCode' => '00', 'vnp_SecureHash' => 'WRONG',
        ];
        $this->postJson('/webhook/payments/vnpay', $params)
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'WEBHOOK_SIGNATURE_INVALID');
    }

    public function test_vnpay_webhook_with_failure_response_code_does_not_mark_paid(): void
    {
        $tenant = Tenant::create(['name' => 'VnPayFailShop']);
        $owner = User::factory()->create();
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->first();
        $sub = Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_TRIALING, 'billing_cycle' => Subscription::CYCLE_TRIAL,
            'trial_ends_at' => now()->addDays(14), 'current_period_start' => now(),
            'current_period_end' => now()->addDays(14),
        ]);
        $invoice = Invoice::query()->create([
            'tenant_id' => $tenant->getKey(), 'subscription_id' => $sub->getKey(),
            'code' => 'INV-202605-FAIL', 'status' => Invoice::STATUS_PENDING,
            'period_start' => now()->format('Y-m-d'), 'period_end' => now()->addMonth()->format('Y-m-d'),
            'subtotal' => 199_000, 'tax' => 0, 'total' => 199_000, 'due_at' => now()->addDays(7),
            'meta' => ['plan_code' => Plan::CODE_PRO, 'cycle' => 'monthly'],
        ]);

        $params = [
            'vnp_TmnCode' => 'TESTMERCHANT', 'vnp_Amount' => '19900000',
            'vnp_TxnRef' => 'INV-202605-FAIL', 'vnp_TransactionNo' => 'FAIL-1',
            'vnp_ResponseCode' => '24', 'vnp_TransactionStatus' => '24',           // 24 = user cancel
        ];
        $params['vnp_SecureHash'] = (new VnPaySigner('TESTSECRET123'))->sign($params);

        $this->postJson('/webhook/payments/vnpay', $params)->assertOk();

        $this->assertSame(Invoice::STATUS_PENDING, $invoice->fresh()->status);
        $this->assertSame(0, Payment::query()->withoutGlobalScope(TenantScope::class)->count());
    }

    public function test_momo_skeleton_throws_unsupported_on_checkout(): void
    {
        $connector = $this->app->make(PaymentRegistry::class)->for('momo');
        $this->assertFalse($connector->supports('checkout'));

        $this->expectException(UnsupportedOperation::class);
        $connector->checkout(new CheckoutRequest(
            tenantId: 1, invoiceId: 1, reference: 'INV-X', amount: 1000, description: 'x'
        ));
    }

    public function test_checkout_endpoint_via_vnpay_returns_redirect_url(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'VnPayCheckoutShop']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);

        $resp = $this->actingAs($owner)->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->postJson('/api/v1/billing/checkout', [
                'plan_code' => Plan::CODE_PRO, 'cycle' => 'monthly', 'gateway' => 'vnpay',
            ]);

        $resp->assertCreated()
            ->assertJsonPath('data.checkout.method', 'redirect');
        $this->assertStringContainsString('sandbox.vnpayment.vn', (string) $resp->json('data.checkout.redirect_url'));
    }

    public function test_checkout_endpoint_via_momo_returns_unavailable(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'MomoCheckoutShop']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);

        $this->actingAs($owner)->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->postJson('/api/v1/billing/checkout', [
                'plan_code' => Plan::CODE_PRO, 'cycle' => 'monthly', 'gateway' => 'momo',
            ])->assertStatus(422)
            ->assertJsonPath('error.code', 'GATEWAY_UNAVAILABLE');
    }
}
