<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Models\Voucher;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC 0023 — voucher CRUD + grant + checkout redemption.
 */
class AdminVoucherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
    }

    public function test_super_admin_creates_percent_voucher(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($admin)->postJson('/api/v1/admin/vouchers', [
            'code' => 'SUMMER20', 'name' => 'Khuyến mãi hè', 'kind' => 'percent', 'value' => 20,
        ])->assertCreated()->assertJsonPath('data.code', 'SUMMER20')->assertJsonPath('data.kind', 'percent');

        $this->assertDatabaseHas('vouchers', ['code' => 'SUMMER20', 'kind' => 'percent', 'value' => 20]);
    }

    public function test_regular_user_cannot_create_voucher(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/admin/vouchers', [
            'code' => 'X', 'name' => 'X', 'kind' => 'percent', 'value' => 10,
        ])->assertStatus(403);
    }

    public function test_voucher_percent_value_validates_range(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($admin)->postJson('/api/v1/admin/vouchers', [
            'code' => 'BIG', 'name' => 'Sai', 'kind' => 'percent', 'value' => 200,
        ])->assertStatus(422)->assertJsonPath('error.code', 'INVALID_VALUE');
    }

    public function test_grant_free_days_extends_subscription_period(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'A']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        // Seed trial sub
        $plan = Plan::query()->where('code', 'starter')->first();
        $sub = Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => 'monthly',
            'current_period_start' => now(), 'current_period_end' => now()->addDays(10),
        ]);

        $voucher = Voucher::query()->create([
            'code' => 'FREE15', 'name' => 'Tặng 15 ngày', 'kind' => 'free_days', 'value' => 15,
        ]);

        $this->actingAs($admin)->postJson("/api/v1/admin/vouchers/{$voucher->id}/grant", [
            'tenant_id' => $tenant->getKey(),
            'reason' => 'Khách VIP thân thiết — đặt tay',
        ])->assertOk();

        $sub->refresh();
        $this->assertEqualsWithDelta(25, now()->diffInDays($sub->current_period_end, false), 1, 'period_end should be ~25 days from now after +15');
        $this->assertSame(1, Voucher::query()->find($voucher->id)->redemption_count);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.voucher.grant', 'tenant_id' => $tenant->getKey()]);
    }

    public function test_grant_plan_upgrade_swaps_to_target_plan(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $tenant = Tenant::create(['name' => 'B']);
        $targetPlan = Plan::query()->where('code', 'pro')->first();
        $voucher = Voucher::query()->create([
            'code' => 'VIPGIFT', 'name' => 'Tặng Pro 30d', 'kind' => 'plan_upgrade', 'value' => $targetPlan->id,
            'meta' => ['duration_days' => 30],
        ]);

        $this->actingAs($admin)->postJson("/api/v1/admin/vouchers/{$voucher->id}/grant", [
            'tenant_id' => $tenant->getKey(),
            'reason' => 'Đối tác chiến lược — quà tặng',
        ])->assertOk()->assertJsonPath('data.applied.plan_code', 'pro');

        $alive = Subscription::query()->withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->getKey())
            ->whereIn('status', Subscription::ALIVE_STATUSES)->first();
        $this->assertNotNull($alive);
        $this->assertSame($targetPlan->id, $alive->plan_id);
    }

    public function test_voucher_redeem_at_checkout_applies_discount_line(): void
    {
        // Set up tenant + owner + trial sub
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'C']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);

        // Voucher 20% off
        Voucher::query()->create(['code' => 'OFF20', 'name' => '20% off', 'kind' => 'percent', 'value' => 20]);

        $this->actingAs($owner)->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->postJson('/api/v1/billing/checkout', [
                'plan_code' => 'pro', 'cycle' => 'monthly', 'gateway' => 'sepay', 'voucher_code' => 'OFF20',
            ])->assertCreated()->assertJsonPath('data.invoice.status', 'pending');

        $invoice = Invoice::query()->withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->getKey())->latest('id')->first();
        $proPlan = Plan::query()->where('code', 'pro')->first();
        $expectedDiscount = intdiv($proPlan->price_monthly * 20, 100);
        $this->assertSame($proPlan->price_monthly - $expectedDiscount, $invoice->total);

        $this->assertDatabaseHas('invoice_lines', ['invoice_id' => $invoice->id, 'kind' => 'discount', 'amount' => -$expectedDiscount]);
        $this->assertDatabaseHas('voucher_redemptions', ['tenant_id' => $tenant->getKey(), 'invoice_id' => $invoice->id, 'discount_amount' => $expectedDiscount]);
        $this->assertSame(1, Voucher::query()->where('code', 'OFF20')->first()->redemption_count);
    }

    public function test_voucher_validate_endpoint_returns_preview(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'D']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);

        Voucher::query()->create(['code' => 'DEAL50', 'name' => 'Giảm 50k', 'kind' => 'fixed', 'value' => 50000]);

        $this->actingAs($owner)->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->postJson('/api/v1/billing/vouchers/validate', [
                'code' => 'DEAL50', 'plan_code' => 'pro', 'cycle' => 'monthly',
            ])->assertOk()->assertJsonPath('data.discount', 50000)->assertJsonPath('data.valid', true);
    }

    public function test_voucher_invalid_returns_specific_code(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'E']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);

        $this->actingAs($owner)->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->postJson('/api/v1/billing/vouchers/validate', [
                'code' => 'NONEXISTENT', 'plan_code' => 'pro', 'cycle' => 'monthly',
            ])->assertStatus(422)->assertJsonPath('error.code', 'INVALID_VOUCHER');
    }

    public function test_voucher_exhausted_rejects(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'F']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        Voucher::query()->create([
            'code' => 'ONESHOT', 'name' => 'One use', 'kind' => 'percent', 'value' => 10,
            'max_redemptions' => 1, 'redemption_count' => 1,
        ]);

        $this->actingAs($owner)->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->postJson('/api/v1/billing/vouchers/validate', [
                'code' => 'ONESHOT', 'plan_code' => 'pro', 'cycle' => 'monthly',
            ])->assertStatus(422)->assertJsonPath('error.code', 'VOUCHER_EXHAUSTED');
    }
}
