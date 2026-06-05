<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdAccountBulkDisconnectTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($this->tenant);
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($this->user->getKey(), ['role' => Role::Owner->value]);
    }

    private function acc(string $ext, ?string $biz): AdAccount
    {
        return AdAccount::create([
            'provider' => 'facebook', 'external_account_id' => $ext, 'business_id' => $biz,
            'currency' => 'VND', 'status' => 'active', 'access_token' => 'T',
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->id];
    }

    public function test_disconnect_by_ids(): void
    {
        $a = $this->acc('act_1', 'BM1');
        $b = $this->acc('act_2', 'BM1');
        $c = $this->acc('act_3', 'BM2');

        $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson('/api/v1/marketing/ad-accounts/disconnect-bulk', ['ids' => [$a->id, $b->id]])
            ->assertOk()->assertJsonPath('data.deleted', 2);

        $this->assertSoftDeleted('ad_accounts', ['id' => $a->id]);
        $this->assertDatabaseHas('ad_accounts', ['id' => $c->id, 'deleted_at' => null]);
    }

    public function test_disconnect_whole_bm(): void
    {
        $this->acc('act_1', 'BM1');
        $this->acc('act_2', 'BM1');
        $keep = $this->acc('act_3', 'BM2');

        $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson('/api/v1/marketing/ad-accounts/disconnect-bulk', ['business_id' => 'BM1'])
            ->assertOk()->assertJsonPath('data.deleted', 2);

        $this->assertDatabaseHas('ad_accounts', ['id' => $keep->id, 'deleted_at' => null]);
    }

    public function test_requires_a_filter(): void
    {
        $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson('/api/v1/marketing/ad-accounts/disconnect-bulk', [])
            ->assertStatus(422);
    }
}
