<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Jobs\GenerateAdForecast;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdForecast;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdForecastApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_returns_cached_forecast_and_post_within_cooldown_does_not_redispatch(): void
    {
        Queue::fake();

        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'S']);
        AdForecast::create([
            'ad_account_id' => $account->id,
            'payload' => ['forecast' => ['next_7d' => ['orders' => 5]]],
            'provider_code' => 'openai',
            'model' => 'gpt-4o',
            'generated_at' => now(),
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $h = ['X-Tenant-Id' => (string) $tenant->id];

        // GET shows cached forecast
        $this->actingAs($user)->withHeaders($h)
            ->getJson("/api/v1/marketing/ad-accounts/{$account->id}/forecast")
            ->assertOk()
            ->assertJsonPath('data.generated_at', fn ($v) => $v !== null);

        // POST within cooldown returns cached, no dispatch
        $this->actingAs($user)->withHeaders($h)
            ->postJson("/api/v1/marketing/ad-accounts/{$account->id}/forecast")
            ->assertOk()
            ->assertJsonPath('queued', false)
            ->assertJsonPath('status', 'cached');

        Queue::assertNothingPushed();
    }

    public function test_generate_dispatches_job_when_no_cache(): void
    {
        Queue::fake();

        $tenant = Tenant::create(['name' => 'T2']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_2', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'S']);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $h = ['X-Tenant-Id' => (string) $tenant->id];

        $this->actingAs($user)->withHeaders($h)
            ->postJson("/api/v1/marketing/ad-accounts/{$account->id}/forecast")
            ->assertOk()
            ->assertJsonPath('status', 'generating')
            ->assertJsonPath('queued', true);

        Queue::assertPushed(GenerateAdForecast::class, fn ($j) => $j->adAccountId === (int) $account->id);
    }

    public function test_generate_returns_cache_within_cooldown_without_dispatch(): void
    {
        Queue::fake();

        $tenant = Tenant::create(['name' => 'T3']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_3', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'S']);
        AdForecast::create([
            'ad_account_id' => $account->id,
            'payload' => ['forecast' => []],
            'provider_code' => 'x',
            'model' => 'x',
            'generated_at' => now(),
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $h = ['X-Tenant-Id' => (string) $tenant->id];

        $this->actingAs($user)->withHeaders($h)
            ->postJson("/api/v1/marketing/ad-accounts/{$account->id}/forecast")
            ->assertOk()
            ->assertJsonPath('queued', false);

        Queue::assertNothingPushed();
    }
}
