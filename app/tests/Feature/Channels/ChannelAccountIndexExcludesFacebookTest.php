<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/v1/channel-accounts KHÔNG trả về tài khoản facebook_page.
 * Facebook là connector nhắn tin, quản lý ở /messaging/channels — không phải gian hàng sàn TMĐT.
 */
class ChannelAccountIndexExcludesFacebookTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'FbExcludeShop']);
        $this->activatePro();
    }

    private function activatePro(): void
    {
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now,
            'current_period_end' => $now->copy()->addMonth(),
        ]);
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($user->getKey(), ['role' => $role->value]);

        return $user;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_facebook_page_account_is_excluded_from_channel_accounts_index(): void
    {
        // Facebook page — chỉ dùng trong messaging, KHÔNG hiện ở Gian hàng.
        ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_100',
            'shop_name' => 'My FB Page',
            'status' => 'active',
            'messaging_enabled' => true,
        ]);

        // Lazada IM (app nhắn tin riêng) — cũng KHÔNG phải gian hàng sàn.
        ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'lazada_im',
            'external_shop_id' => 'LZ_IM_1',
            'shop_name' => 'Lazada IM',
            'status' => 'active',
        ]);

        // Gian hàng sàn TMĐT — phải xuất hiện.
        ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'tiktok',
            'external_shop_id' => 'TT_SHOP_1',
            'shop_name' => 'TikTok Shop VN',
            'status' => 'active',
        ]);

        $res = $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->getJson('/api/v1/channel-accounts')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame('tiktok', $res->json('data.0.provider'));
        $this->assertSame('TT_SHOP_1', $res->json('data.0.external_shop_id'));

        // Đảm bảo kênh nhắn tin (facebook_page/lazada_im) vắng mặt trong response.
        $providers = collect($res->json('data'))->pluck('provider')->all();
        $this->assertNotContains('facebook_page', $providers);
        $this->assertNotContains('lazada_im', $providers);
    }

    public function test_only_facebook_page_accounts_returns_empty_data(): void
    {
        ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_200',
            'shop_name' => 'Only FB',
            'status' => 'active',
        ]);

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->getJson('/api/v1/channel-accounts')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
