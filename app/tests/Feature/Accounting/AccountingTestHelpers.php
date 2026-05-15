<?php

namespace Tests\Feature\Accounting;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Helper trait dùng cho mọi feature test trong tests/Feature/Accounting.
 * Set up tenant + owner + subscription Pro (feature `accounting_basic` enable).
 */
trait AccountingTestHelpers
{
    protected User $owner;

    protected User $accountant;

    protected User $staffOrder;

    protected User $viewer;

    protected Tenant $tenant;

    protected function setUpAccountingTenant(): void
    {
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'AcctShop']);

        $this->owner = User::factory()->create(['email' => 'owner-'.uniqid().'@a.test']);
        $this->accountant = User::factory()->create(['email' => 'kt-'.uniqid().'@a.test']);
        $this->staffOrder = User::factory()->create(['email' => 'so-'.uniqid().'@a.test']);
        $this->viewer = User::factory()->create(['email' => 'v-'.uniqid().'@a.test']);

        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->tenant->users()->attach($this->accountant->getKey(), ['role' => Role::Accountant->value]);
        $this->tenant->users()->attach($this->staffOrder->getKey(), ['role' => Role::StaffOrder->value]);
        $this->tenant->users()->attach($this->viewer->getKey(), ['role' => Role::Viewer->value]);

        $this->activatePlan(Plan::CODE_PRO);
    }

    protected function activatePlan(string $planCode): Subscription
    {
        // Bypass tenant scope — test code chạy ngoài request context, CurrentTenant chưa set.
        Subscription::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();
        $now = now();

        return Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now,
            'current_period_end' => $now->copy()->addMonth(),
        ]);
    }

    protected function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }
}
