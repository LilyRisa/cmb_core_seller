<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\ProTrialGrant;
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
}
