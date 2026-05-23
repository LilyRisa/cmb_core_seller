<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test REST inbox API + Billing gating + RBAC cho Messaging.
 *
 * Scenarios:
 *   - Tenant gói trial (không có feature messaging_inbox) ⇒ 402 PLAN_FEATURE_LOCKED
 *   - Tenant gói Pro: list conversations OK
 *   - StaffWarehouse không có `messaging.view` ⇒ 403
 *   - Send text → tạo message pending + dispatch SendMessage job
 *   - Mark read → reset unread_count
 */
class MessagingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'MsgShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->activateSubscription(Plan::CODE_PRO);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'shop_api_1',
            'shop_name' => 'API Shop',
            'status' => 'active',
            'messaging_enabled' => true,
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function activateSubscription(string $planCode): void
    {
        // withoutGlobalScopes: TenantScope chưa bind current tenant trong setUp ⇒
        // delete có scope sẽ không khớp row nào ⇒ unique(tenant_id) vỡ khi re-activate.
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

    private function seedConversation(): Conversation
    {
        return Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'conv_api_1',
            'buyer_external_id' => 'buyer_1',
            'buyer_name' => 'Anh Khách',
            'status' => Conversation::STATUS_OPEN,
            'unread_count' => 2,
            'message_count' => 2,
            'last_message_at' => now(),
            'last_message_preview' => 'Hello',
            'last_inbound_at' => now()->subMinutes(5),
        ]);
    }

    public function test_starter_plan_cannot_access_inbox(): void
    {
        $this->activateSubscription(Plan::CODE_STARTER);

        $this->actingAs($this->owner)
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations')
            ->assertStatus(402)
            ->assertJsonPath('error.code', 'PLAN_FEATURE_LOCKED');
    }

    public function test_pro_plan_can_list_conversations(): void
    {
        $this->seedConversation();

        $this->actingAs($this->owner)
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.buyer_name', 'Anh Khách')
            ->assertJsonPath('data.0.unread_count', 2)
            // Nguồn gốc hội thoại: tên shop/page + nhóm kênh.
            ->assertJsonPath('data.0.channel_account_name', 'API Shop')
            ->assertJsonPath('data.0.channel_group', 'internal')
            // meta.pagination để FE infinite-scroll đọc total_pages.
            ->assertJsonPath('meta.pagination.total_pages', 1)
            ->assertJsonPath('meta.pagination.page', 1);
    }

    public function test_filter_by_multiple_channel_accounts_sorted_newest_first(): void
    {
        $pageA = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_A', 'shop_name' => 'Trang A', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        $pageB = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_B', 'shop_name' => 'Trang B', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        // Buyer A (trang A) cũ hơn; Buyer B (trang B) mới nhất.
        Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $pageA->id, 'provider' => 'facebook_page',
            'external_conversation_id' => 'psid_a', 'buyer_external_id' => 'psid_a', 'buyer_name' => 'Buyer A',
            'status' => Conversation::STATUS_OPEN, 'last_message_at' => now()->subMinutes(10),
        ]);
        Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $pageB->id, 'provider' => 'facebook_page',
            'external_conversation_id' => 'psid_b', 'buyer_external_id' => 'psid_b', 'buyer_name' => 'Buyer B',
            'status' => Conversation::STATUS_OPEN, 'last_message_at' => now(),
        ]);

        // Lọc 1 trang → chỉ trang đó.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?channel_account_id='.$pageA->id)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.buyer_name', 'Buyer A');

        // Lọc nhiều trang (CSV) → cả hai, sắp xếp mới→cũ (Buyer B trước).
        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?channel_account_id='.$pageA->id.','.$pageB->id)
            ->assertOk()->assertJsonCount(2, 'data');
        $this->assertSame('Buyer B', $res->json('data.0.buyer_name'), 'phải sắp xếp theo last_message_at mới nhất trước');
        $this->assertSame('Buyer A', $res->json('data.1.buyer_name'));
    }

    public function test_push_subscribe_then_heartbeat(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/messaging/push/subscribe', [
                'endpoint' => 'https://push.example/abc',
                'keys' => ['p256dh' => 'PKEY', 'auth' => 'AUTH'],
            ])->assertOk();

        $this->assertDatabaseHas('messaging_push_subscriptions', [
            'endpoint' => 'https://push.example/abc',
            'user_id' => $this->owner->id,
            'tenant_id' => $this->tenant->getKey(),
            'p256dh' => 'PKEY',
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/messaging/push/heartbeat', ['endpoint' => 'https://push.example/abc'])
            ->assertOk();
    }

    public function test_filter_by_thread_type_message_vs_comment(): void
    {
        $page = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_T', 'shop_name' => 'Trang T', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $page->id, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_MESSAGE, 'external_conversation_id' => 'dm_1', 'buyer_external_id' => 'dm_1',
            'buyer_name' => 'DM Buyer', 'status' => Conversation::STATUS_OPEN, 'last_message_at' => now(),
        ]);
        Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $page->id, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_COMMENT, 'external_conversation_id' => 'cmt_1', 'buyer_external_id' => 'cmt_1',
            'buyer_name' => 'Commenter', 'status' => Conversation::STATUS_OPEN, 'last_message_at' => now(),
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?provider=facebook_page&thread_type=comment')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.buyer_name', 'Commenter');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?provider=facebook_page&thread_type=message')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.buyer_name', 'DM Buyer');
    }

    public function test_inbox_separates_marketplace_and_facebook(): void
    {
        // 1 hội thoại Facebook + 1 hội thoại sàn (tiktok) — list lọc theo provider phải tách đúng.
        $fbAccount = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_9', 'shop_name' => 'Shop FB', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $fbAccount->id, 'provider' => 'facebook_page',
            'external_conversation_id' => 'psid_1', 'buyer_external_id' => 'psid_1', 'buyer_name' => 'FB Buyer',
            'status' => Conversation::STATUS_OPEN, 'last_message_at' => now(),
        ]);
        // ADR-0019: chat sàn dùng CHUNG channel_account với orders ⇒ account.provider='tiktok'
        // nhưng conversation.provider='tiktok_chat'. channel_group đọc từ conversation.provider.
        $ttAccount = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'tiktok',
            'external_shop_id' => 'TT_1', 'shop_name' => 'Shop TikTok', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $ttAccount->id, 'provider' => 'tiktok_chat',
            'external_conversation_id' => 'conv_tt', 'buyer_external_id' => 'b', 'buyer_name' => 'TT Buyer',
            'status' => Conversation::STATUS_OPEN, 'last_message_at' => now()->subMinute(),
        ]);

        // Lọc Facebook
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?provider=facebook_page')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.channel_group', 'facebook')
            ->assertJsonPath('data.0.channel_account_name', 'Shop FB');

        // Lọc sàn
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations?provider=tiktok_chat,shopee_chat,lazada_chat')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.channel_group', 'marketplace');
    }

    public function test_staff_warehouse_cannot_view_inbox(): void
    {
        $sw = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($sw->getKey(), ['role' => Role::StaffWarehouse->value]);

        $this->actingAs($sw)
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations')
            ->assertStatus(403);
    }

    public function test_staff_cs_can_view_and_reply(): void
    {
        $cs = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($cs->getKey(), ['role' => Role::StaffCs->value]);

        $conv = $this->seedConversation();

        $this->actingAs($cs)
            ->withHeaders($this->h())
            ->getJson('/api/v1/messaging/conversations')
            ->assertOk();

        $this->actingAs($cs)
            ->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/messages", ['body' => 'Em xin chào'])
            ->assertStatus(202)
            ->assertJsonPath('data.body', 'Em xin chào');
    }

    public function test_send_text_creates_message_pending(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $conv = $this->seedConversation();

        $this->actingAs($this->owner)
            ->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/messages", ['body' => 'Test reply'])
            ->assertStatus(202)
            ->assertJsonPath('data.direction', Message::DIRECTION_OUTBOUND)
            ->assertJsonPath('data.delivery_status', Message::STATUS_PENDING);

        $this->assertSame(1, Message::query()
            ->where('conversation_id', $conv->id)
            ->where('direction', Message::DIRECTION_OUTBOUND)
            ->count());

        \Illuminate\Support\Facades\Queue::assertPushed(\CMBcoreSeller\Modules\Messaging\Jobs\SendMessage::class);
    }

    public function test_filter_by_channel_account_id_returns_only_that_channel(): void
    {
        // Tạo 2 channel accounts cho 2 Facebook page khác nhau.
        $pageA = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_A', 'shop_name' => 'Page A', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        $pageB = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_B', 'shop_name' => 'Page B', 'status' => 'active', 'messaging_enabled' => true,
        ]);

        Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $pageA->id, 'provider' => 'facebook_page',
            'external_conversation_id' => 'psid_a1', 'buyer_external_id' => 'psid_a1', 'buyer_name' => 'Buyer A',
            'status' => Conversation::STATUS_OPEN, 'last_message_at' => now(),
        ]);
        Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $pageB->id, 'provider' => 'facebook_page',
            'external_conversation_id' => 'psid_b1', 'buyer_external_id' => 'psid_b1', 'buyer_name' => 'Buyer B',
            'status' => Conversation::STATUS_OPEN, 'last_message_at' => now()->subMinute(),
        ]);

        // Lọc theo channel_account_id của page A — chỉ trả về hội thoại của page A.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson("/api/v1/messaging/conversations?provider=facebook_page&channel_account_id={$pageA->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.buyer_name', 'Buyer A');

        // Lọc theo channel_account_id của page B — chỉ trả về hội thoại của page B.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson("/api/v1/messaging/conversations?provider=facebook_page&channel_account_id={$pageB->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.buyer_name', 'Buyer B');
    }

    public function test_mark_read_resets_unread_count(): void
    {
        $conv = $this->seedConversation();
        Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $conv->id,
            'external_message_id' => 'msg_inbound_1',
            'direction' => Message::DIRECTION_INBOUND,
            'kind' => Message::KIND_TEXT,
            'body' => 'Buyer msg',
            'delivery_status' => Message::STATUS_SENT,
        ]);

        $this->actingAs($this->owner)
            ->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/read")
            ->assertOk();

        $this->assertSame(0, $conv->fresh()->unread_count);
        $this->assertNotNull(Message::query()->where('conversation_id', $conv->id)->where('direction', 'inbound')->first()->read_at);
    }
}
