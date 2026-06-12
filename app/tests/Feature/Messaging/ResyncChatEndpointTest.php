<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\SyncConversationsForShop;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures;
use Tests\TestCase;

/**
 * TDD — POST /api/v1/channel-accounts/{id}/resync-chat (Phase C1).
 *
 * Scenarios:
 *  - Lazada shop (supports polling) + owner role → 200 queued, job dispatched.
 *  - Shopee shop (polling backfill, SPEC-0024 Phase C follow-up) → 200 queued.
 *  - TikTok shop (no polling yet) → 422.
 *  - User without messaging.connect permission → 403.
 */
class ResyncChatEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'ResyncChatShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->activateSubscription(Plan::CODE_PRO);

        // Lazada connector config (needed for registry to resolve it)
        config([
            'integrations.messaging' => ['lazada_chat'],
            'integrations.lazada' => [
                'app_key' => 'K',
                'app_secret' => 'S',
                'base_url' => 'https://api.lazada.vn/rest',
            ],
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function activateSubscription(string $planCode): void
    {
        Subscription::withoutGlobalScopes()->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();
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

    private function lazadaAccount(): ChannelAccount
    {
        return ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'lazada_im',
            'external_shop_id' => 'LAZ_SHOP_1',
            'shop_name' => 'Lazada VN',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'T',
            'messaging_enabled' => true,
        ]);
    }

    /**
     * Lazada account (polling supported) + owner → 200, SyncConversationsForShop queued.
     */
    public function test_lazada_active_and_enabled_queues_job(): void
    {
        Queue::fake();
        $acct = $this->lazadaAccount();

        $this->actingAs($this->owner)
            ->withHeaders($this->h())
            ->postJson("/api/v1/channel-accounts/{$acct->id}/resync-chat")
            ->assertOk()
            ->assertJsonPath('data.queued', true)
            ->assertJsonPath('data.channel_account_id', $acct->id);

        Queue::assertPushed(SyncConversationsForShop::class, function ($job) use ($acct) {
            return $job->channelAccountId === (int) $acct->id;
        });
    }

    /**
     * Shopee chat là webhook-only (polling TẮT để tránh gọi `sellerchat/get_*` fail) →
     * resync-chat trả 422, KHÔNG dispatch job.
     */
    public function test_shopee_is_webhook_only_resync_chat_returns_422(): void
    {
        Queue::fake();

        // Wire shopee_chat into the registry
        ShopeeFixtures::configure();
        config(['integrations.messaging' => ['shopee_chat']]);
        $this->app->forgetInstance(MessagingRegistry::class);

        $shopeeAcct = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'shopee',
            'external_shop_id' => 'SHOPEE_SHOP_1',
            'shop_name' => 'Shopee VN',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'T',
            'messaging_enabled' => true,
        ]);

        $this->actingAs($this->owner)
            ->withHeaders($this->h())
            ->postJson("/api/v1/channel-accounts/{$shopeeAcct->id}/resync-chat")
            ->assertStatus(422);

        Queue::assertNotPushed(SyncConversationsForShop::class);
    }

    /**
     * TikTok account (inbound.polling=false — chưa bật) → 422 (nhận chat qua webhook).
     */
    public function test_tiktok_returns_422_no_polling(): void
    {
        Queue::fake();

        config(['integrations.messaging' => ['tiktok_chat']]);
        $this->app->forgetInstance(MessagingRegistry::class);

        $tiktokAcct = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'tiktok',
            'external_shop_id' => 'TIKTOK_SHOP_1',
            'shop_name' => 'TikTok VN',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'access_token' => 'T',
            'messaging_enabled' => true,
        ]);

        $this->actingAs($this->owner)
            ->withHeaders($this->h())
            ->postJson("/api/v1/channel-accounts/{$tiktokAcct->id}/resync-chat")
            ->assertStatus(422);

        Queue::assertNotPushed(SyncConversationsForShop::class);
    }

    /**
     * User role without messaging.connect → 403.
     */
    public function test_role_without_messaging_connect_gets_403(): void
    {
        Queue::fake();
        $acct = $this->lazadaAccount();

        // StaffWarehouse does not have messaging.connect
        $warehouseUser = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($warehouseUser->getKey(), ['role' => Role::StaffWarehouse->value]);

        $this->actingAs($warehouseUser)
            ->withHeaders($this->h())
            ->postJson("/api/v1/channel-accounts/{$acct->id}/resync-chat")
            ->assertForbidden();

        Queue::assertNotPushed(SyncConversationsForShop::class);
    }
}
