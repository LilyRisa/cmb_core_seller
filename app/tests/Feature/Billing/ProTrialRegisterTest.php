<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Events\ProTrialActivated;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\ProTrialService;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PDOException;
use Tests\TestCase;

class ProTrialRegisterTest extends TestCase
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

    public function test_register_activates_pro_and_records_grant(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/pro-trial/register', ['terms_accepted' => true, 'terms_version' => 'refund-v1'])
            ->assertOk()
            ->assertJsonPath('data.plan_code', 'pro');

        $sub = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->where('status', Subscription::STATUS_ACTIVE)
            ->with('plan')->latest('id')->first();
        $this->assertSame('pro', $sub->plan->code);
        $this->assertTrue((bool) ($sub->meta['pro_trial'] ?? false));
        $this->assertDatabaseHas('pro_trial_grants', ['tenant_id' => $this->tenant->getKey(), 'terms_version' => 'refund-v1']);
    }

    public function test_register_rejects_without_terms(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/pro-trial/register', ['terms_accepted' => false, 'terms_version' => 'refund-v1'])
            ->assertStatus(422);
    }

    public function test_register_only_once(): void
    {
        $payload = ['terms_accepted' => true, 'terms_version' => 'refund-v1'];
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/pro-trial/register', $payload)->assertOk();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/pro-trial/register', $payload)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'PRO_TRIAL_NOT_ELIGIBLE');
    }

    /**
     * Double-submit đua nhau: cả 2 request qua được eligibility re-check trong transaction, request thứ 2
     * vấp unique `tenant_id` khi INSERT pro_trial_grants (Postgres/SQLite driver). Không dựng race thật
     * (không deterministic) — thay vào đó ép ProTrialService::register() ném thẳng
     * UniqueConstraintViolationException để xác nhận controller map đúng về 422 PRO_TRIAL_NOT_ELIGIBLE
     * thay vì để lộ 500.
     */
    public function test_register_maps_unique_constraint_race_to_422(): void
    {
        $mock = \Mockery::mock(ProTrialService::class);
        $mock->shouldReceive('register')->once()->andThrow(new UniqueConstraintViolationException(
            'sqlite',
            'insert into "pro_trial_grants" ("tenant_id", ...) values (?, ...)',
            [$this->tenant->getKey()],
            new PDOException('SQLSTATE[23505]: Unique violation: tenant_id', 23505),
        ));
        $this->app->instance(ProTrialService::class, $mock);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/pro-trial/register', ['terms_accepted' => true, 'terms_version' => 'refund-v1'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'PRO_TRIAL_NOT_ELIGIBLE');
    }

    public function test_register_fires_pro_trial_activated_event(): void
    {
        Event::fake();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/pro-trial/register', ['terms_accepted' => true, 'terms_version' => 'refund-v1'])
            ->assertOk();

        // First assert the event was dispatched at all
        Event::assertDispatched(ProTrialActivated::class);

        Event::assertDispatched(
            ProTrialActivated::class,
            fn ($e) => $e->tenantId === $this->tenant->getKey()
                && abs($e->expiresAt->diffInDays($e->grantedAt)) == 30,
        );
    }
}
