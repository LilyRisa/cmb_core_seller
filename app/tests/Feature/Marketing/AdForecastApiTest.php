<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdInsightSnapshot;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdForecastApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_then_get_cached_forecast(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'S']);
        $today = now()->toDateString();
        AdInsightSnapshot::create(['ad_account_id' => $account->id, 'level' => 'account', 'external_id' => 'act_1', 'date_start' => $today, 'date_stop' => $today, 'window' => 'today', 'spend' => 70000, 'messaging_conversations' => 14, 'leads' => 4, 'fetched_at' => now()]);
        Order::create(['tenant_id' => $tenant->id, 'source' => 'manual', 'order_number' => 'O1', 'status' => 'pending', 'grand_total' => 250000]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $h = ['X-Tenant-Id' => (string) $tenant->id];

        $this->actingAs($user)->withHeaders($h)
            ->postJson("/api/v1/marketing/ad-accounts/{$account->id}/forecast")
            ->assertOk()
            ->assertJsonPath('data.payload.forecast.next_7d.orders', fn ($v) => $v !== null);
        $this->assertDatabaseCount('ad_forecasts', 1);

        $this->actingAs($user)->withHeaders($h)
            ->getJson("/api/v1/marketing/ad-accounts/{$account->id}/forecast")
            ->assertOk()
            ->assertJsonPath('data.generated_at', fn ($v) => $v !== null);
    }
}
