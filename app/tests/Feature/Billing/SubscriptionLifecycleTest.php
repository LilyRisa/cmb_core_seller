<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6.4 — SPEC 0018 §3.4: scheduler `subscriptions:check-expiring`.
 *
 *  - Trial hết hạn ⇒ expired ngay (không grace) + fallback trial vĩnh viễn.
 *  - Active quá hạn ⇒ past_due.
 *  - Past_due quá 7 ngày ⇒ expired + fallback.
 *  - Cancelled chạy hết cancel_at ⇒ expired + fallback.
 */
class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'LifecycleShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    /** Helper: lấy plan theo code. */
    private function plan(string $code): Plan
    {
        return Plan::query()->where('code', $code)->firstOrFail();
    }

    public function test_expired_trial_creates_fallback_trial_subscription(): void
    {
        // Trial đã hết hạn — trial_ends_at = hôm qua.
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $this->plan(Plan::CODE_TRIAL)->getKey(),
            'status' => Subscription::STATUS_TRIALING,
            'billing_cycle' => Subscription::CYCLE_TRIAL,
            'trial_ends_at' => now()->subDay(),
            'current_period_start' => now()->subDays(15),
            'current_period_end' => now()->subDay(),
        ]);

        $this->artisan('subscriptions:check-expiring')->assertSuccessful();

        $subs = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->orderBy('id')->get();
        $this->assertCount(2, $subs, '1 expired + 1 fallback');
        $this->assertSame(Subscription::STATUS_EXPIRED, $subs[0]->status);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subs[1]->status);
        $this->assertSame(Plan::CODE_TRIAL, $subs[1]->plan->code);
        $this->assertTrue($subs[1]->current_period_end->isFuture());
    }

    public function test_active_subscription_past_due_marks_status_correctly(): void
    {
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $this->plan(Plan::CODE_PRO)->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now()->subDays(35),
            'current_period_end' => now()->subDays(2),   // quá hạn 2 ngày, chưa đủ grace 7 ngày
        ]);

        $this->artisan('subscriptions:check-expiring')->assertSuccessful();

        $sub = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->latest('id')->first();
        $this->assertSame(Subscription::STATUS_PAST_DUE, $sub->status);
    }

    public function test_past_due_over_grace_period_expires_and_falls_back_to_trial(): void
    {
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $this->plan(Plan::CODE_PRO)->getKey(),
            'status' => Subscription::STATUS_PAST_DUE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now()->subDays(40),
            'current_period_end' => now()->subDays(10),  // past_due 10 ngày — quá grace 7 ngày
        ]);

        $this->artisan('subscriptions:check-expiring')->assertSuccessful();

        $subs = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->orderBy('id')->get();
        $this->assertCount(2, $subs);
        $this->assertSame(Subscription::STATUS_EXPIRED, $subs[0]->status);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subs[1]->status);
        $this->assertSame(Plan::CODE_TRIAL, $subs[1]->plan->code);
    }

    public function test_cancelled_subscription_runs_to_cancel_at_then_expires(): void
    {
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $this->plan(Plan::CODE_PRO)->getKey(),
            'status' => Subscription::STATUS_CANCELLED,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now()->subDays(30),
            'current_period_end' => now()->subDay(),
            'cancel_at' => now()->subDay(),
            'cancelled_at' => now()->subDays(10),
        ]);

        $this->artisan('subscriptions:check-expiring')->assertSuccessful();

        $subs = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->orderBy('id')->get();
        $this->assertCount(2, $subs);
        $this->assertSame(Subscription::STATUS_EXPIRED, $subs[0]->status);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subs[1]->status);
        $this->assertSame(Plan::CODE_TRIAL, $subs[1]->plan->code);
    }

    public function test_command_is_idempotent(): void
    {
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $this->plan(Plan::CODE_TRIAL)->getKey(),
            'status' => Subscription::STATUS_TRIALING,
            'billing_cycle' => Subscription::CYCLE_TRIAL,
            'trial_ends_at' => now()->subDay(),
            'current_period_start' => now()->subDays(15),
            'current_period_end' => now()->subDay(),
        ]);

        $this->artisan('subscriptions:check-expiring')->assertSuccessful();
        $countAfterFirst = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->count();

        $this->artisan('subscriptions:check-expiring')->assertSuccessful();
        $countAfterSecond = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->count();

        $this->assertSame($countAfterFirst, $countAfterSecond, 'Chạy lại không tạo trùng.');
    }
}
