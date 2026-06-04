<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Jobs\SyncAdInsights;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdAccountApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'AdShop']);
    }

    private function owner(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        return $user;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeAccount(): AdAccount
    {
        app(CurrentTenant::class)->set($this->tenant);

        return AdAccount::create([
            'provider' => 'facebook', 'external_account_id' => 'act_'.uniqid(),
            'name' => 'Shop', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'SECRET',
        ]);
    }

    public function test_list_returns_tenant_accounts_without_token(): void
    {
        $a = $this->makeAccount();
        // other tenant's account must not appear
        $other = Tenant::create(['name' => 'Other']);
        app(CurrentTenant::class)->set($other);
        AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_other', 'status' => 'active', 'access_token' => 'X']);

        $res = $this->actingAs($this->owner())->withHeaders($this->h())
            ->getJson('/api/v1/marketing/ad-accounts')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.external_account_id', $a->external_account_id);

        $this->assertStringNotContainsString('SECRET', $res->getContent());
        $this->assertStringNotContainsString('access_token', $res->getContent());
    }

    public function test_disconnect_soft_deletes(): void
    {
        $a = $this->makeAccount();
        $this->actingAs($this->owner())->withHeaders($this->h())
            ->deleteJson("/api/v1/marketing/ad-accounts/{$a->id}")
            ->assertOk();
        $this->assertSoftDeleted('ad_accounts', ['id' => $a->id]);
    }

    public function test_refresh_queues_insights_job(): void
    {
        Queue::fake();
        $a = $this->makeAccount();
        $this->actingAs($this->owner())->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-accounts/{$a->id}/refresh")
            ->assertOk()
            ->assertJsonPath('data.queued', true);
        Queue::assertPushed(SyncAdInsights::class, fn ($j) => $j->adAccountId === (int) $a->id);
    }
}
