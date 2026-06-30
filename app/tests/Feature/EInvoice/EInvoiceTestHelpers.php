<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Integrations\EInvoice\EInvoiceRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

trait EInvoiceTestHelpers
{
    protected Tenant $tenant;

    protected User $owner;

    protected User $viewer;

    protected function setUpEInvoiceTenant(): void
    {
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'EInvShop']);
        $this->owner = User::factory()->create(['email' => 'owner-'.uniqid().'@e.test']);
        $this->viewer = User::factory()->create(['email' => 'view-'.uniqid().'@e.test']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->tenant->users()->attach($this->viewer->getKey(), ['role' => Role::Viewer->value]);
        $this->activatePlan(Plan::CODE_PRO);
        config(['integrations.einvoice.enabled' => ['misa'], 'integrations.einvoice.misa.base_url' => 'https://testapi.meinvoice.vn/api/v3']);
        $this->app->forgetInstance(EInvoiceRegistry::class);
    }

    protected function activatePlan(string $code): void
    {
        Subscription::withoutGlobalScope(TenantScope::class)->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', $code)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->copy()->addMonth(),
        ]);
    }

    protected function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }
}
