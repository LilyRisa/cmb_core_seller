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

class AdReconciliationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconciliation_endpoint_returns_daily_rows(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'S']);
        $today = now()->toDateString();
        AdInsightSnapshot::create([
            'ad_account_id' => $account->id, 'level' => 'account', 'external_id' => 'act_1',
            'date_start' => $today, 'date_stop' => $today, 'window' => 'today',
            'spend' => 60000, 'messaging_conversations' => 12, 'leads' => 5, 'fetched_at' => now(),
        ]);
        Order::create(['tenant_id' => $tenant->id, 'source' => 'manual', 'order_number' => 'O1', 'status' => 'pending', 'grand_total' => 200000]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $res = $this->actingAs($user)->withHeaders(['X-Tenant-Id' => (string) $tenant->id])
            ->getJson("/api/v1/marketing/ad-accounts/{$account->id}/reconciliation?days=7")
            ->assertOk()
            ->assertJsonPath('data.currency', 'VND');

        $rows = $res->json('data.rows');
        $todayRow = collect($rows)->firstWhere('date', $today);
        $this->assertSame(60000, $todayRow['spend']);
        $this->assertSame(12, $todayRow['conversations']);
        $this->assertSame(1, $todayRow['manual_orders']);
    }
}
