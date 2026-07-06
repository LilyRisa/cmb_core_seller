<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Messaging\Http\Resources\ConversationResource;
use CMBcoreSeller\Modules\Messaging\Jobs\ProcessMessagingWebhook;
use CMBcoreSeller\Modules\Messaging\Jobs\SyncConversationProfile;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Services\CommentConversationUpserter;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Messaging\Services\MessagingAvatarRelay;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Fixtures\Channels\Shopee\ShopeeFixtures;
use Tests\TestCase;

/**
 * Buyer profile sync (tên + avatar) cho hội thoại tạo từ webhook realtime.
 *
 * Gap: chỉ BackfillMessagingChannel fetch profile buyer; path webhook
 * (MessageIngestionService::ensureConversation) tạo conversation với
 * buyer_name=null, buyer_avatar_url=null ⇒ FE không có avatar.
 *
 * Fix: ProcessMessagingWebhook dispatch SyncConversationProfile cho conversation
 * DM còn thiếu avatar; job gọi connector.fetchUserProfile + relay avatar về storage.
 */
class BuyerProfileSyncTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'ProfileSyncTenant']);
        app(CurrentTenant::class)->set($this->tenant);
    }

    /** Webhook tạo conversation DM mới ⇒ dispatch SyncConversationProfile. */
    public function test_process_webhook_dispatches_profile_sync_for_new_dm_conversation(): void
    {
        Queue::fake();

        ShopeeFixtures::configure();
        config([
            'integrations.messaging' => ['shopee_chat'],
            'integrations.channels' => ['manual', 'shopee'],
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);

        $account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'shopee',
            'external_shop_id' => '88',
            'shop_name' => 'Profile Shop',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
            'access_token' => 'TOK',
        ]);

        $event = WebhookEvent::query()->create([
            'provider' => 'messaging.shopee_chat',
            'event_type' => MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED,
            'external_id' => 'MSG_P1',
            'external_shop_id' => '88',
            'status' => WebhookEvent::STATUS_PENDING,
            'signature_ok' => true,
            'headers' => [],
            'received_at' => now(),
            'attempts' => 0,
            'payload' => [
                'external_conversation_id' => 'CONV_P1',
                'external_message_id' => 'MSG_P1',
                'buyer_external_id' => 'BUYER_P1',
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

        $conv = Conversation::withoutGlobalScopes()->where('external_conversation_id', 'CONV_P1')->first();
        $this->assertNotNull($conv);

        Queue::assertPushed(
            SyncConversationProfile::class,
            fn (SyncConversationProfile $job) => $job->conversationId === (int) $conv->id,
        );
    }

    private function fbConversation(array $overrides = []): Conversation
    {
        $account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_77',
            'shop_name' => 'FB Page',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
            'access_token' => 'PAGE_TOKEN',
        ]);

        return Conversation::query()->create(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $account->getKey(),
            'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_MESSAGE,
            'external_conversation_id' => 'PSID_77',
            'buyer_external_id' => 'PSID_77',
            'buyer_name' => null,
            'buyer_avatar_url' => null,
            'buyer_avatar_path' => null,
            'status' => Conversation::STATUS_OPEN,
            'last_inbound_at' => now(),
        ], $overrides));
    }

    /** @return MessagingRegistry registry giả trả connector có fetchUserProfile cho test. */
    private function fakeRegistry(array $profile, bool $expectCall = true): MessagingRegistry
    {
        $connector = \Mockery::mock(MessagingConnector::class);
        if ($expectCall) {
            $connector->shouldReceive('fetchUserProfile')->andReturn($profile);
        } else {
            $connector->shouldReceive('fetchUserProfile')->never();
        }

        $registry = \Mockery::mock(MessagingRegistry::class);
        $registry->shouldReceive('has')->andReturn(true);
        $registry->shouldReceive('for')->andReturn($connector);

        return $registry;
    }

    /** Job set buyer_name + relay avatar về storage (buyer_avatar_path). */
    public function test_job_sets_buyer_name_and_relays_avatar(): void
    {
        Storage::fake(config('messaging.media_disk'));
        Http::fake([
            'scontent.fbcdn.net/*' => Http::response('fake-jpeg-bytes', 200),
        ]);

        $conv = $this->fbConversation();
        $registry = $this->fakeRegistry([
            'name' => 'Nguyễn Văn A',
            'avatar_url' => 'https://scontent.fbcdn.net/v/pic.jpg',
        ]);

        (new SyncConversationProfile((int) $conv->id))->handle($registry, app(MessagingAvatarRelay::class));

        $fresh = Conversation::withoutGlobalScope(TenantScope::class)->find($conv->id);
        $this->assertSame('Nguyễn Văn A', $fresh->buyer_name);
        $this->assertNotNull($fresh->buyer_avatar_path, 'avatar phải được relay về storage');
        Storage::disk(config('messaging.media_disk'))->assertExists($fresh->buyer_avatar_path);
        $this->assertSame('https://scontent.fbcdn.net/v/pic.jpg', $fresh->buyer_avatar_url, 'giữ URL CDN làm fallback');
    }

    /**
     * BUG prod 2026-07-02: job queued chạy KHÔNG có CurrentTenant. `SyncConversationProfile` nạp account qua
     * eager relation `$conv->channelAccount` (dính TenantScope) ⇒ null ⇒ job no-op ⇒ avatar realtime CHƯA
     * TỪNG chạy cho mọi page. Phải resolve account bỏ TenantScope như mọi job messaging khác.
     */
    public function test_job_resolves_account_without_current_tenant(): void
    {
        Storage::fake(config('messaging.media_disk'));
        Http::fake(['scontent.fbcdn.net/*' => Http::response('fake-jpeg-bytes', 200)]);

        $conv = $this->fbConversation();
        $registry = $this->fakeRegistry([
            'name' => 'Không Tenant',
            'avatar_url' => 'https://scontent.fbcdn.net/v/pic.jpg',
        ]);

        app(CurrentTenant::class)->clear();   // mô phỏng job queued: KHÔNG có tenant context

        (new SyncConversationProfile((int) $conv->id))->handle($registry, app(MessagingAvatarRelay::class));

        $fresh = Conversation::withoutGlobalScope(TenantScope::class)->find($conv->id);
        $this->assertNotNull($fresh->buyer_avatar_path, 'Job phải resolve account bỏ TenantScope & relay avatar dù không có CurrentTenant.');
        $this->assertSame('Không Tenant', $fresh->buyer_name);
    }

    /** Relay thất bại (storage chưa cấu hình / lỗi) ⇒ vẫn giữ URL CDN Facebook để FE hiển thị. */
    public function test_job_keeps_facebook_url_when_relay_fails(): void
    {
        Storage::fake(config('messaging.media_disk'));
        Http::fake(['scontent.fbcdn.net/*' => Http::response('nope', 500)]);

        $conv = $this->fbConversation();
        $registry = $this->fakeRegistry([
            'name' => 'Lê B',
            'avatar_url' => 'https://scontent.fbcdn.net/v/pic2.jpg',
        ]);

        (new SyncConversationProfile((int) $conv->id))->handle($registry, app(MessagingAvatarRelay::class));

        $fresh = Conversation::withoutGlobalScope(TenantScope::class)->find($conv->id);
        $this->assertNull($fresh->buyer_avatar_path, 'relay lỗi ⇒ không có storage path');
        $this->assertSame('https://scontent.fbcdn.net/v/pic2.jpg', $fresh->buyer_avatar_url, 'fallback URL CDN vẫn được lưu');
    }

    /** URL CDN Facebook (>512 ký tự) phải lưu được — cột đã nới sang TEXT (tránh 22001). */
    public function test_long_facebook_cdn_url_persists(): void
    {
        $longUrl = 'https://scontent.fhan18-1.fna.fbcdn.net/v/t39.30808-1/444139864_'.str_repeat('x', 700).'.jpg';
        $this->assertGreaterThan(512, strlen($longUrl));

        $conv = $this->fbConversation(['buyer_avatar_url' => $longUrl]);
        MessagingAccountMeta::query()->create([
            'channel_account_id' => $conv->channel_account_id,
            'tenant_id' => $this->tenant->getKey(),
            'messaging_enabled' => true,
            'page_avatar_url' => $longUrl,
        ]);

        $this->assertSame($longUrl, Conversation::withoutGlobalScope(TenantScope::class)->find($conv->id)->buyer_avatar_url);
        $this->assertSame($longUrl, MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find($conv->channel_account_id)->page_avatar_url);
    }

    /** Resource lộ avatar page (cho tin outbound) — fallback URL CDN khi chưa relay. */
    public function test_resource_exposes_page_avatar_url(): void
    {
        $conv = $this->fbConversation();
        MessagingAccountMeta::query()->create([
            'channel_account_id' => $conv->channel_account_id,
            'tenant_id' => $this->tenant->getKey(),
            'messaging_enabled' => true,
            'page_avatar_path' => null,
            'page_avatar_url' => 'https://scontent.fbcdn.net/page.jpg',
        ]);

        $conv->load('pageMeta');
        $data = (new ConversationResource($conv))->toArray(Request::create('/'));

        $this->assertSame('https://scontent.fbcdn.net/page.jpg', $data['channel_account_avatar_url']);
    }

    /** Resource: chưa relay (path null) ⇒ trả URL CDN Facebook thay vì rỗng. */
    public function test_resource_falls_back_to_facebook_url_when_no_storage_path(): void
    {
        $conv = $this->fbConversation([
            'buyer_avatar_path' => null,
            'buyer_avatar_url' => 'https://scontent.fbcdn.net/v/pic3.jpg',
        ]);

        $data = (new ConversationResource($conv))->toArray(Request::create('/'));

        $this->assertSame('https://scontent.fbcdn.net/v/pic3.jpg', $data['buyer_avatar_url']);
    }

    /** Đã có avatar rồi ⇒ job không gọi lại Graph (idempotent). */
    public function test_job_skips_when_avatar_already_present(): void
    {
        $conv = $this->fbConversation([
            'buyer_name' => 'Đã có tên',
            'buyer_avatar_path' => 'tenants/x/messaging/avatars/existing.jpg',
        ]);

        $registry = $this->fakeRegistry(['name' => 'X', 'avatar_url' => 'https://scontent.fbcdn.net/v/pic.jpg'], expectCall: false);

        (new SyncConversationProfile((int) $conv->id))->handle($registry, app(MessagingAvatarRelay::class));

        $fresh = Conversation::withoutGlobalScope(TenantScope::class)->find($conv->id);
        $this->assertSame('Đã có tên', $fresh->buyer_name);
        $this->assertSame('tenants/x/messaging/avatars/existing.jpg', $fresh->buyer_avatar_path);
    }

    /**
     * BUG prod 2026-07-06 (PSID 27380737668278107): job ghi `profile_attempted_at` TRƯỚC khi
     * fetch ⇒ 1 cú lỗi thoáng qua (timeout/5xx) lúc tạo hội thoại khoá đồng bộ 24h dù Graph vốn
     * trả đủ tên/avatar. Fix: lỗi transient (`attempted=false`) ⇒ KHÔNG ghi mốc ⇒ tin sau thử lại.
     */
    public function test_job_does_not_throttle_on_transient_failure(): void
    {
        $conv = $this->fbConversation();
        $registry = $this->fakeRegistry(['name' => null, 'avatar_url' => null, 'attempted' => false]);

        (new SyncConversationProfile((int) $conv->id))->handle($registry, app(MessagingAvatarRelay::class));

        $fresh = Conversation::withoutGlobalScope(TenantScope::class)->find($conv->id);
        $this->assertArrayNotHasKey('profile_attempted_at', (array) $fresh->meta,
            'Lỗi thoáng qua ⇒ KHÔNG throttle để tin sau còn thử lại (không đầu độc 24h)');
        $this->assertNull($fresh->buyer_name);
    }

    /** Trả lời dứt khoát nhưng rỗng (thiếu quyền / #100) ⇒ throttle 24h tránh spam Graph. */
    public function test_job_throttles_on_definitive_empty_profile(): void
    {
        $conv = $this->fbConversation();
        $registry = $this->fakeRegistry(['name' => null, 'avatar_url' => null, 'attempted' => true]);

        (new SyncConversationProfile((int) $conv->id))->handle($registry, app(MessagingAvatarRelay::class));

        $fresh = Conversation::withoutGlobalScope(TenantScope::class)->find($conv->id);
        $this->assertArrayHasKey('profile_attempted_at', (array) $fresh->meta,
            'Câu trả lời dứt khoát (kể cả rỗng) ⇒ ghi mốc throttle');
    }

    /** Comment thread không sync profile theo PSID (commenter khác ngữ nghĩa). */
    public function test_job_skips_comment_thread(): void
    {
        $conv = $this->fbConversation(['thread_type' => Conversation::THREAD_COMMENT, 'external_conversation_id' => 'COMMENT_1']);
        $registry = $this->fakeRegistry(['name' => 'X', 'avatar_url' => 'https://scontent.fbcdn.net/v/pic.jpg'], expectCall: false);

        (new SyncConversationProfile((int) $conv->id))->handle($registry, app(MessagingAvatarRelay::class));

        $fresh = Conversation::withoutGlobalScope(TenantScope::class)->find($conv->id);
        $this->assertNull($fresh->buyer_avatar_path);
    }
}
