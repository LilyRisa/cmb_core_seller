<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZaloOaChannelsListTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'ZaloTestShop']);
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

    /** ?provider=zalo_oa phải trả đúng kênh Zalo, KHÔNG trả facebook_page. */
    public function test_channels_filtered_by_provider(): void
    {
        ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'facebook_page',
            'external_shop_id' => 'FB_1',
            'shop_name' => 'facebook_page',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
        ]);
        ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'zalo_oa',
            'external_shop_id' => 'OA_9',
            'shop_name' => 'zalo_oa',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
        ]);

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/channels?provider=zalo_oa')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.provider', 'zalo_oa');
    }

    /** Không có ?provider thì trả tất cả kênh messaging_enabled — ít nhất facebook vẫn có mặt. */
    public function test_index_without_provider_returns_all_messaging_enabled(): void
    {
        ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'facebook_page',
            'external_shop_id' => 'FB_2',
            'shop_name' => 'facebook_page',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
        ]);
        ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'zalo_oa',
            'external_shop_id' => 'OA_2',
            'shop_name' => 'zalo_oa',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
        ]);
        // Kênh thương mại (messaging_enabled=false) KHÔNG xuất hiện.
        ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'lazada',
            'external_shop_id' => 'LZ_X',
            'shop_name' => 'lazada',
            'status' => ChannelAccount::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/channels')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
