<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel;
use CMBcoreSeller\Modules\Messaging\Jobs\DownloadInboundMedia;
use CMBcoreSeller\Modules\Messaging\Jobs\RelayMessagingAvatar;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Lưới an toàn: đối soát INCREMENTAL (messaging:reconcile-sync → BackfillMessagingChannel
 * với sinceIso != null) phải fire MessageReceived cho tin MỚI của khách mà webhook lọt,
 * để AI vẫn auto-reply khi webhook chết. Nhưng KHÔNG được trả lời tin CŨ:
 *   - backfill lịch sử đầy đủ (sinceIso === null) ⇒ im,
 *   - tin cũ hơn trần độ-mới ⇒ im,
 *   - hội thoại đã có người/AI trả lời sau tin ⇒ im (chống trùng).
 */
class MessagingReconcileAutoReplyTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Tenant, 1: ChannelAccount} */
    private function fbAccount(): array
    {
        $tenant = Tenant::create(['name' => 'ReconcileShop']);
        $this->app->make(CurrentTenant::class)->set($tenant);
        $account = ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_123', 'status' => 'active',
            'access_token' => 'PAGE_TOKEN', 'messaging_enabled' => true,
        ]);
        MessagingAccountMeta::query()->create([
            'channel_account_id' => $account->id, 'tenant_id' => $tenant->getKey(),
            'messaging_enabled' => true, 'page_avatar_synced_at' => now()->subDay(),
            'page_avatar_url' => 'https://cdn.fb/page.jpg',
        ]);
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_id' => 'APP',
            'integrations.messaging_facebook_page.app_secret' => 'S',
            'messaging.backfill.days' => 90,
            'messaging.reconcile.autoreply_max_age_minutes' => 60,
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);

        return [$tenant, $account];
    }

    /**
     * @param  string  $msgCreatedTime  ISO time của tin inbound
     */
    private function fakeGraph(string $msgCreatedTime, string $convUpdated = 'now'): void
    {
        $updated = $convUpdated === 'now' ? now()->toIso8601String() : $convUpdated;
        Http::fake([
            'graph.facebook.com/*/conversations*' => Http::response([
                'data' => [[
                    'id' => 't_rt', 'updated_time' => $updated, 'message_count' => 1,
                    'participants' => ['data' => [
                        ['id' => 'PAGE_123', 'name' => 'Page'], ['id' => 'PSID_RT', 'name' => 'Khach'],
                    ]],
                ]],
                'paging' => [],
            ], 200),
            'graph.facebook.com/*t_rt*' => Http::response([
                'id' => 't_rt',
                'messages' => ['data' => [
                    ['id' => 'm_rt', 'message' => 'shop oi con hang khong', 'created_time' => $msgCreatedTime, 'from' => ['id' => 'PSID_RT']],
                ]],
            ], 200),
            'graph.facebook.com/*' => Http::response(['name' => 'Page'], 200),
        ]);
    }

    private function runIncremental(int $accountId, ?string $sinceIso): void
    {
        (new BackfillMessagingChannel($accountId, $sinceIso))
            ->handle(app(MessagingRegistry::class), app(MessageIngestionService::class));
    }

    public function test_incremental_reconcile_fires_message_received_for_new_inbound(): void
    {
        Bus::fake([RelayMessagingAvatar::class, DownloadInboundMedia::class]);
        Event::fake([MessageReceived::class]);
        [, $account] = $this->fbAccount();
        $this->fakeGraph(now()->subMinutes(2)->toIso8601String());

        $this->runIncremental($account->id, now()->subMinutes(30)->toIso8601String());

        Event::assertDispatched(MessageReceived::class);
    }

    public function test_full_backfill_does_not_fire_message_received(): void
    {
        Bus::fake([RelayMessagingAvatar::class, DownloadInboundMedia::class]);
        Event::fake([MessageReceived::class]);
        [, $account] = $this->fbAccount();
        $this->fakeGraph(now()->subMinutes(2)->toIso8601String());

        $this->runIncremental($account->id, null); // sinceIso null = full history backfill

        Event::assertNotDispatched(MessageReceived::class);
    }

    public function test_incremental_reconcile_does_not_fire_for_old_message(): void
    {
        Bus::fake([RelayMessagingAvatar::class, DownloadInboundMedia::class]);
        Event::fake([MessageReceived::class]);
        [, $account] = $this->fbAccount();
        // Hội thoại bị bump gần đây nhưng tin cũ (>60' trần) — không được auto-reply.
        $this->fakeGraph(now()->subMinutes(180)->toIso8601String());

        $this->runIncremental($account->id, now()->subMinutes(30)->toIso8601String());

        Event::assertNotDispatched(MessageReceived::class);
    }

    public function test_incremental_reconcile_does_not_fire_when_already_replied(): void
    {
        Bus::fake([RelayMessagingAvatar::class, DownloadInboundMedia::class]);
        Event::fake([MessageReceived::class]);
        [$tenant, $account] = $this->fbAccount();

        // Hội thoại đã tồn tại + đã có tin OUTBOUND (người/AI/tool trả lời) lúc now.
        $conv = Conversation::query()->create([
            'tenant_id' => $tenant->getKey(), 'channel_account_id' => $account->id,
            'provider' => 'facebook_page', 'external_conversation_id' => 'PSID_RT',
            'buyer_external_id' => 'PSID_RT', 'status' => 'open', 'last_message_at' => now(),
        ]);
        Message::query()->create([
            'tenant_id' => $tenant->getKey(), 'conversation_id' => $conv->id,
            'direction' => Message::DIRECTION_OUTBOUND, 'external_message_id' => 'out_existing',
            'kind' => Message::KIND_TEXT, 'body' => 'da rep', 'sent_at' => now(),
        ]);

        // Tin inbound MỚI nhưng tới TRƯỚC tin outbound đã có ⇒ đã được trả lời ⇒ im.
        $this->fakeGraph(now()->subMinutes(2)->toIso8601String());

        $this->runIncremental($account->id, now()->subMinutes(30)->toIso8601String());

        Event::assertNotDispatched(MessageReceived::class);
    }
}
