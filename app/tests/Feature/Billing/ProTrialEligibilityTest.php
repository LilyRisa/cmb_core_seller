<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\ProTrialGrant;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProTrialEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(SystemSettingService::class)->set('billing.pro_trial.enabled', true);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_eligible_when_enabled_and_not_used(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', true)
            ->assertJsonPath('data.duration_days', 30);
    }

    public function test_not_eligible_when_disabled(): void
    {
        app(SystemSettingService::class)->set('billing.pro_trial.enabled', false);
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.reason', 'mode_off');
    }

    public function test_not_eligible_when_already_used(): void
    {
        ProTrialGrant::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'granted_at' => now(),
            'expires_at' => now()->addMonth(), 'terms_accepted_at' => now(), 'terms_version' => 'refund-v1',
        ]);
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.reason', 'already_used');
    }

    public function test_not_eligible_when_window_closed(): void
    {
        app(SystemSettingService::class)->set('billing.pro_trial.window_start', now()->subDays(10)->toDateString());
        app(SystemSettingService::class)->set('billing.pro_trial.window_end', now()->subDays(1)->toDateString());

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.reason', 'window_closed');
    }

    public function test_not_eligible_when_plan_too_high(): void
    {
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->first();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.reason', 'plan_too_high');
    }

    public function test_not_eligible_when_on_paid_starter(): void
    {
        $plan = Plan::query()->where('code', Plan::CODE_STARTER)->first();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.reason', 'plan_too_high');
    }

    public function test_show_popup_false_when_never_offered(): void
    {
        // Tenant đủ điều kiện eligible NHƯNG chưa có row pro_trial_offers (tenant "cũ",
        // tạo trước khi tính năng popup này tồn tại) ⇒ không tự hiện popup.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', true)
            ->assertJsonPath('data.show_popup', false);
    }

    public function test_show_popup_true_when_offered_and_not_declined(): void
    {
        app(\CMBcoreSeller\Modules\Billing\Services\ProTrialService::class)->offer($this->tenant->getKey());

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', true)
            ->assertJsonPath('data.show_popup', true);
    }

    public function test_show_popup_false_when_declined(): void
    {
        $service = app(\CMBcoreSeller\Modules\Billing\Services\ProTrialService::class);
        $service->offer($this->tenant->getKey());
        $service->decline($this->tenant->getKey());

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', true)
            ->assertJsonPath('data.show_popup', false);
    }

    public function test_show_popup_false_when_not_eligible_even_if_offered(): void
    {
        $service = app(\CMBcoreSeller\Modules\Billing\Services\ProTrialService::class);
        $service->offer($this->tenant->getKey());
        app(\CMBcoreSeller\Modules\Settings\Services\SystemSettingService::class)->set('billing.pro_trial.enabled', false);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.show_popup', false);
    }
}
