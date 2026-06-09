<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin: cột xác minh email (users list) + tài khoản quảng cáo (FB/TikTok) trong chi tiết tenant.
 */
class AdminTenantDetailExtrasTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');
    }

    public function test_users_index_exposes_email_verified_status(): void
    {
        $this->admin();
        User::factory()->create(['email' => 'verified@x.vn', 'email_verified_at' => now()]);
        User::factory()->create(['email' => 'fake@x.vn', 'email_verified_at' => null]);

        $rows = collect($this->getJson('/api/v1/admin/users')->assertOk()->json('data'));

        $verified = $rows->firstWhere('email', 'verified@x.vn');
        $fake = $rows->firstWhere('email', 'fake@x.vn');
        $this->assertNotNull($verified['email_verified_at']);
        $this->assertNull($fake['email_verified_at']); // chưa xác minh
        $this->assertArrayHasKey('suspended_at', $fake);
    }

    public function test_tenant_detail_lists_ad_accounts_by_provider_and_member_verify(): void
    {
        $this->admin();
        $tenant = Tenant::create(['name' => 'Shop']);
        $owner = User::factory()->create(['email_verified_at' => null]);
        $tenant->users()->attach($owner->id, ['role' => Role::Owner->value]);

        AdAccount::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'provider' => 'facebook', 'external_account_id' => 'act_1', 'status' => 'active', 'access_token' => 'F']);
        AdAccount::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'provider' => 'tiktok', 'external_account_id' => '123', 'status' => 'active', 'access_token' => 'T']);

        $data = $this->getJson("/api/v1/admin/tenants/{$tenant->id}")->assertOk()->json('data');

        $providers = collect($data['ad_accounts'])->pluck('provider')->sort()->values()->all();
        $this->assertSame(['facebook', 'tiktok'], $providers);
        // Member chưa xác minh email phải lộ ra để admin thấy.
        $this->assertNull($data['members'][0]['email_verified_at']);
    }
}
