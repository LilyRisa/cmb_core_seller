<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6.4 — SPEC 0018 §6: API endpoints /api/v1/billing/*.
 */
class BillingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'BillingShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_plans_endpoint_returns_active_plans(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/plans')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.code', 'trial');
    }

    public function test_subscription_endpoint_auto_creates_trial_fallback_when_missing(): void
    {
        // Tenant chưa có subscription ⇒ middleware/service tự tạo fallback (status=active, trial vĩnh viễn).
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/subscription')
            ->assertOk()
            ->assertJsonPath('data.plan_code', Plan::CODE_TRIAL)
            ->assertJsonPath('meta.usage.channel_accounts.used', 0)
            ->assertJsonPath('meta.usage.channel_accounts.limit', 2);
    }

    public function test_usage_endpoint_returns_channel_accounts_used_and_limit(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/usage')
            ->assertOk()
            ->assertJsonPath('data.channel_accounts.used', 0)
            ->assertJsonPath('data.channel_accounts.limit', 2);
    }

    public function test_checkout_creates_pending_invoice_with_plan_line(): void
    {
        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/checkout', [
                'plan_code' => Plan::CODE_PRO,
                'cycle' => 'monthly',
                'gateway' => 'sepay',
            ]);

        $resp->assertCreated()
            ->assertJsonPath('data.invoice.status', Invoice::STATUS_PENDING)
            ->assertJsonPath('data.invoice.total', 199_000)
            ->assertJsonPath('data.invoice.currency', 'VND')
            ->assertJsonPath('data.gateway', 'sepay');

        $code = $resp->json('data.invoice.code');
        $this->assertMatchesRegularExpression('/^INV-\d{6}-\d{4}$/', $code);
        $this->assertDatabaseHas('invoices', ['code' => $code, 'total' => 199_000, 'status' => 'pending']);
        $this->assertDatabaseHas('invoice_lines', ['amount' => 199_000, 'quantity' => 1]);
    }

    public function test_checkout_yearly_uses_yearly_price(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/checkout', [
                'plan_code' => Plan::CODE_PRO,
                'cycle' => 'yearly',
                'gateway' => 'sepay',
            ])->assertCreated()
            ->assertJsonPath('data.invoice.total', 1_990_000);
    }

    public function test_checkout_rejects_trial_plan(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/checkout', [
                'plan_code' => Plan::CODE_TRIAL,
                'cycle' => 'monthly',
                'gateway' => 'sepay',
            ])->assertStatus(422);
    }

    public function test_checkout_rejects_momo_for_now(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/checkout', [
                'plan_code' => Plan::CODE_PRO,
                'cycle' => 'monthly',
                'gateway' => 'momo',
            ])->assertStatus(422)
            ->assertJsonPath('error.code', 'GATEWAY_UNAVAILABLE');
    }

    public function test_invoices_index_returns_only_own_tenant(): void
    {
        // Tenant khác có invoice nhưng không lộ.
        $other = Tenant::create(['name' => 'Other']);
        $otherOwner = User::factory()->create();
        $other->users()->attach($otherOwner->getKey(), ['role' => Role::Owner->value]);

        $plan = Plan::query()->where('code', Plan::CODE_PRO)->first();
        $sub = Subscription::query()->create([
            'tenant_id' => $other->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
        Invoice::query()->create([
            'tenant_id' => $other->getKey(),
            'subscription_id' => $sub->getKey(),
            'code' => 'INV-OTHER',
            'status' => 'pending',
            'period_start' => now()->format('Y-m-d'),
            'period_end' => now()->addMonth()->format('Y-m-d'),
            'subtotal' => 199_000, 'tax' => 0, 'total' => 199_000,
            'due_at' => now()->addDays(7),
        ]);

        // Tenant của owner gọi list ⇒ 0 invoice (chưa tạo cho mình).
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/invoices')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_cancel_subscription_sets_cancel_at_for_paid_subscription(): void
    {
        // Đặt subscription active gói pro.
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->first();
        $sub = Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now()->subDays(3),
            'current_period_end' => now()->addDays(27),
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/subscription/cancel')
            ->assertOk();

        $this->assertNotNull($sub->fresh()->cancel_at);
        $this->assertNotNull($sub->fresh()->cancelled_at);
    }

    public function test_cancel_subscription_blocks_trial(): void
    {
        // Tenant không có sub ⇒ fallback tạo subscription `active` (fallback trial), không phải trialing → cancel sẽ chạy.
        // Để test "cancel trial", em tạo trial explicit.
        $plan = Plan::query()->where('code', Plan::CODE_TRIAL)->first();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_TRIALING,
            'billing_cycle' => Subscription::CYCLE_TRIAL,
            'trial_ends_at' => now()->addDays(14),
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(14),
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/subscription/cancel')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'CANNOT_CANCEL_TRIAL');
    }

    public function test_accountant_can_view_but_not_checkout(): void
    {
        $accountant = User::factory()->create();
        $this->tenant->users()->attach($accountant->getKey(), ['role' => Role::Accountant->value]);

        // View OK.
        $this->actingAs($accountant)->withHeaders($this->h())
            ->getJson('/api/v1/billing/subscription')->assertOk();

        // Checkout forbid.
        $this->actingAs($accountant)->withHeaders($this->h())
            ->postJson('/api/v1/billing/checkout', [
                'plan_code' => Plan::CODE_PRO, 'cycle' => 'monthly', 'gateway' => 'sepay',
            ])->assertForbidden();
    }

    public function test_staff_order_cannot_access_billing(): void
    {
        $staff = User::factory()->create();
        $this->tenant->users()->attach($staff->getKey(), ['role' => Role::StaffOrder->value]);

        $this->actingAs($staff)->withHeaders($this->h())
            ->getJson('/api/v1/billing/subscription')->assertForbidden();
    }

    public function test_billing_profile_update_persists_data(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->patchJson('/api/v1/billing/billing-profile', [
                'company_name' => 'Cty TNHH ABC',
                'tax_code' => '0123456789',
                'contact_email' => 'kt@example.com',
            ])->assertOk()
            ->assertJsonPath('data.company_name', 'Cty TNHH ABC')
            ->assertJsonPath('data.tax_code', '0123456789');

        $this->assertDatabaseHas('billing_profiles', [
            'tenant_id' => $this->tenant->getKey(),
            'tax_code' => '0123456789',
        ]);
    }
}
