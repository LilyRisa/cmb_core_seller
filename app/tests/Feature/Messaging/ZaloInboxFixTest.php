<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Messaging\Http\Resources\ConversationResource;
use CMBcoreSeller\Modules\Messaging\Jobs\ProcessMessagingWebhook;
use CMBcoreSeller\Modules\Messaging\Jobs\SyncConversationProfile;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\CommentConversationUpserter;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Zalo OA inbox fixes (3 small fixes):
 *
 * Fix 1 — maybeSyncBuyerProfile re-dispatches when buyer_name is empty even if
 *          buyer_avatar_path is already set. Zalo webhooks don't carry a display
 *          name so the avatar can be stored without a name.
 * Fix 2 — FE convDisplayName shows "Khách Zalo ·{last4}" when provider=zalo_oa
 *          and buyer_name is empty (FE-only; verified by typecheck / lint).
 * Fix 3 — ConversationResource::groupFor('zalo_oa') returns 'zalo' (consistent
 *          with INBOX_GROUP_PROVIDERS in lib/messaging.tsx).
 */
class ZaloInboxFixTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        app(CurrentTenant::class)->set($this->tenant);

        config()->set('integrations.messaging', ['zalo_oa']);
        config()->set('integrations.messaging_zalo_oa', [
            'app_id' => 'app_zfix',
            'app_secret' => 'secret_zfix',
            'oa_secret' => 'oa_secret_zfix',
            'redirect_uri' => 'https://test.local/cb',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
    }

    // -------------------------------------------------------------------------
    // Fix 1 — profile re-dispatch when avatar present but name empty
    // -------------------------------------------------------------------------

    /**
     * Conversation Zalo đã có avatar (buyer_avatar_path != null) nhưng buyer_name
     * rỗng → webhook mới phải dispatch SyncConversationProfile (không bị guard sớm).
     *
     * Trước khi fix: guard `buyer_avatar_path !== null` return sớm → không dispatch.
     * Sau khi fix:   guard kép, chỉ skip khi CẢ HAI avatar VÀ name đều đã có.
     */
    public function test_webhook_dispatches_profile_sync_when_avatar_present_but_name_empty(): void
    {
        Queue::fake();

        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->id,
            'provider' => 'zalo_oa',
            'external_shop_id' => 'OA_ZFIX_1',
            'shop_name' => 'Zalo Fix Shop',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
            'access_token' => 'TKN_ZFIX',
        ]);

        // Pre-existing conversation: có avatar (đã sync lần trước) nhưng name vẫn rỗng.
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->id,
            'channel_account_id' => $account->id,
            'provider' => 'zalo_oa',
            'thread_type' => Conversation::THREAD_MESSAGE,
            'external_conversation_id' => 'ZUSER_FIX_1',
            'buyer_external_id' => 'ZUSER_FIX_1',
            'buyer_name' => null,                                        // tên vẫn rỗng
            'buyer_avatar_path' => 'tenants/t/messaging/avatars/zalo_fix.jpg',  // avatar đã có
            'status' => Conversation::STATUS_OPEN,
            'last_inbound_at' => now(),
            'meta' => [],                                          // chưa throttle
        ]);

        $event = WebhookEvent::query()->create([
            'provider' => 'messaging.zalo_oa',
            'event_type' => MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED,
            'external_id' => 'MSG_ZFIX_1',
            'external_shop_id' => 'OA_ZFIX_1',
            'status' => WebhookEvent::STATUS_PENDING,
            'signature_ok' => true,
            'headers' => [],
            'received_at' => now(),
            'attempts' => 0,
            'payload' => [
                'external_conversation_id' => 'ZUSER_FIX_1',
                'external_message_id' => 'MSG_ZFIX_1',
                'buyer_external_id' => 'ZUSER_FIX_1',
                '_kind' => 'text',
                '_body' => 'Xin chào shop',
                '_attachments' => [],
            ],
        ]);

        (new ProcessMessagingWebhook($event->id))->handle(
            app(MessagingRegistry::class),
            app(MessageIngestionService::class),
            app(CommentConversationUpserter::class),
        );

        Queue::assertPushed(
            SyncConversationProfile::class,
            fn (SyncConversationProfile $job) => $job->conversationId === (int) $conv->id,
        );
    }

    /**
     * Conversation đã có CẢ avatar VÀ name → KHÔNG dispatch (guard vẫn giữ đúng).
     */
    public function test_webhook_skips_profile_sync_when_both_avatar_and_name_present(): void
    {
        Queue::fake();

        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->id,
            'provider' => 'zalo_oa',
            'external_shop_id' => 'OA_ZFIX_2',
            'shop_name' => 'Zalo Fix Shop 2',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
            'access_token' => 'TKN_ZFIX2',
        ]);

        Conversation::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->id,
            'channel_account_id' => $account->id,
            'provider' => 'zalo_oa',
            'thread_type' => Conversation::THREAD_MESSAGE,
            'external_conversation_id' => 'ZUSER_FIX_2',
            'buyer_external_id' => 'ZUSER_FIX_2',
            'buyer_name' => 'Nguyễn Văn A',                             // name đã có
            'buyer_avatar_path' => 'tenants/t/messaging/avatars/zalo_full.jpg', // avatar đã có
            'status' => Conversation::STATUS_OPEN,
            'last_inbound_at' => now(),
        ]);

        $event = WebhookEvent::query()->create([
            'provider' => 'messaging.zalo_oa',
            'event_type' => MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED,
            'external_id' => 'MSG_ZFIX_2',
            'external_shop_id' => 'OA_ZFIX_2',
            'status' => WebhookEvent::STATUS_PENDING,
            'signature_ok' => true,
            'headers' => [],
            'received_at' => now(),
            'attempts' => 0,
            'payload' => [
                'external_conversation_id' => 'ZUSER_FIX_2',
                'external_message_id' => 'MSG_ZFIX_2',
                'buyer_external_id' => 'ZUSER_FIX_2',
                '_kind' => 'text',
                '_body' => 'Xin chào lại',
                '_attachments' => [],
            ],
        ]);

        (new ProcessMessagingWebhook($event->id))->handle(
            app(MessagingRegistry::class),
            app(MessageIngestionService::class),
            app(CommentConversationUpserter::class),
        );

        Queue::assertNotPushed(SyncConversationProfile::class);
    }

    // -------------------------------------------------------------------------
    // Fix 3 — ConversationResource::groupFor('zalo_oa') = 'zalo'
    // -------------------------------------------------------------------------

    /**
     * ConversationResource trả channel_group='zalo' cho conversation zalo_oa,
     * khớp key 'zalo' trong INBOX_GROUP_PROVIDERS ở lib/messaging.tsx.
     *
     * Trước khi fix: default branch trả 'internal'.
     * Sau khi fix:   'zalo_oa' => 'zalo'.
     */
    public function test_conversation_resource_returns_zalo_group_for_zalo_oa(): void
    {
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->id,
            'provider' => 'zalo_oa',
            'external_shop_id' => 'OA_ZRES_1',
            'shop_name' => 'Zalo Res Shop',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
            'access_token' => 'TKN_ZRES',
        ]);

        $conv = Conversation::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->id,
            'channel_account_id' => $account->id,
            'provider' => 'zalo_oa',
            'thread_type' => Conversation::THREAD_MESSAGE,
            'external_conversation_id' => 'ZUSER_RES_1',
            'buyer_external_id' => 'ZUSER_RES_1',
            'status' => Conversation::STATUS_OPEN,
        ]);

        $data = (new ConversationResource($conv))->toArray(Request::create('/'));

        $this->assertSame('zalo', $data['channel_group']);
    }

    /** groupFor static helper — đơn vị đơn giản không cần DB. */
    public function test_group_for_returns_correct_groups(): void
    {
        $this->assertSame('facebook', ConversationResource::groupFor('facebook_page'));
        $this->assertSame('marketplace', ConversationResource::groupFor('tiktok_chat'));
        $this->assertSame('marketplace', ConversationResource::groupFor('shopee_chat'));
        $this->assertSame('marketplace', ConversationResource::groupFor('lazada_chat'));
        $this->assertSame('zalo', ConversationResource::groupFor('zalo_oa'));
        $this->assertSame('internal', ConversationResource::groupFor('manual'));
        $this->assertSame('internal', ConversationResource::groupFor('unknown_provider'));
    }
}
