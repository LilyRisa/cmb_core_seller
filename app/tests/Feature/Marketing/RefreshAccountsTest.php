<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RefreshAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_updates_existing_and_discovers_new_accounts(): void
    {
        Queue::fake(); // the new-account entity sync is dispatched, not run inline
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);

        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $existing = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'name' => 'Cũ', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK', 'fb_account_status' => 1]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        // FB now returns the existing account (status disabled) + a brand-new one.
        Http::fake(['graph.facebook.com/*/me/adaccounts*' => Http::response(['data' => [
            ['id' => 'act_1', 'name' => 'Tên mới', 'currency' => 'VND', 'account_status' => 2, 'disable_reason' => 1, 'business' => ['id' => 'BM1', 'name' => 'My BM']],
            ['id' => 'act_2', 'name' => 'Tài khoản 2', 'currency' => 'VND', 'account_status' => 1],
        ]], 200)]);

        $this->actingAs($user)->withHeaders(['X-Tenant-Id' => (string) $tenant->id])
            ->postJson('/api/v1/marketing/ad-accounts/refresh-accounts')
            ->assertOk()
            ->assertJsonPath('data.updated', 1)
            ->assertJsonPath('data.created', 1);

        // Existing updated: new name + disabled status + BM.
        $this->assertDatabaseHas('ad_accounts', ['id' => $existing->id, 'name' => 'Tên mới', 'fb_account_status' => 2, 'disable_reason' => 1, 'business_id' => 'BM1']);
        // New account discovered with the same token.
        $this->assertDatabaseHas('ad_accounts', ['tenant_id' => $tenant->id, 'external_account_id' => 'act_2', 'name' => 'Tài khoản 2']);
    }
}
