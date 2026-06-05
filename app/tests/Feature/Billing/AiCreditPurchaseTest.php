<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\ActivateSubscriptionService;
use CMBcoreSeller\Modules\Billing\Services\AiCreditService;
use CMBcoreSeller\Modules\Billing\Services\BillingService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AiCreditPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'Shop']);
    }

    private function subscribe(string $code): void
    {
        Subscription::query()->withoutGlobalScopes()->where('tenant_id', $this->tenant->getKey())->delete();
        Subscription::create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => Plan::query()->where('code', $code)->value('id'),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
    }

    public function test_buy_credits_creates_invoice_and_grants_on_payment(): void
    {
        $this->subscribe(Plan::CODE_PRO);
        $tid = (int) $this->tenant->getKey();

        $invoice = app(BillingService::class)->createAiCreditInvoice($tid, 700);
        $this->assertSame(700, (int) $invoice->meta['ai_credits']);
        $this->assertSame(70_000, (int) $invoice->total); // 700 × 100đ

        // Thanh toán ⇒ cộng credit (idempotent).
        app(ActivateSubscriptionService::class)->activate($invoice->fresh());
        $this->assertSame(700, app(AiCreditService::class)->wallet($tid)->purchased_balance);
        app(ActivateSubscriptionService::class)->activate($invoice->fresh());
        $this->assertSame(700, app(AiCreditService::class)->wallet($tid)->purchased_balance);
    }

    public function test_amount_rules_min_step_and_max(): void
    {
        $this->subscribe(Plan::CODE_PRO);
        $tid = (int) $this->tenant->getKey();
        $svc = app(BillingService::class);

        foreach ([450, 550] as $bad) { // < 500 hoặc không chia hết 100
            try {
                $svc->createAiCreditInvoice($tid, $bad);
                $this->fail("amount {$bad} phải bị từ chối");
            } catch (ValidationException) {
            }
        }

        // Vượt trần 5000 tổng đã mua.
        app(AiCreditService::class)->grantPurchase($tid, 5000);
        $this->expectException(ValidationException::class);
        $svc->createAiCreditInvoice($tid, 500);
    }

    public function test_requires_ai_plan(): void
    {
        $this->subscribe(Plan::CODE_TRIAL); // không có AI
        $this->expectException(ValidationException::class);
        app(BillingService::class)->createAiCreditInvoice((int) $this->tenant->getKey(), 500);
    }
}
