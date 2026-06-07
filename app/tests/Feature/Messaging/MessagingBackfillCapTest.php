<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel;
use CMBcoreSeller\Modules\Messaging\Jobs\DownloadInboundMedia;
use CMBcoreSeller\Modules\Messaging\Jobs\RelayMessagingAvatar;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Backfill bị giới hạn 90 ngày HOẶC 500 hội thoại (đủ 500 thì dừng), và self-chain theo budget trang
 * để không 1 job nào chạy quá timeout 600s (nguyên nhân kênh kẹt 'running'). Xem BackfillMessagingChannel.
 */
class MessagingBackfillCapTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:Tenant,1:ChannelAccount} */
    private function fbAccount(): array
    {
        $tenant = Tenant::create(['name' => 'CapShop']);
        $this->app->make(CurrentTenant::class)->set($tenant);
        $account = ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_CAP', 'status' => 'active',
            'access_token' => 'TOKEN', 'messaging_enabled' => true,
        ]);
        MessagingAccountMeta::query()->create([
            'channel_account_id' => $account->id, 'tenant_id' => $tenant->getKey(), 'messaging_enabled' => true,
            // giả lập đã sync avatar page để backfill không gọi fetchPageProfile (đỡ phải fake).
            'page_avatar_synced_at' => now()->subDay(), 'page_avatar_url' => 'https://cdn.fb/p.jpg',
        ]);
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_id' => 'APP',
            'integrations.messaging_facebook_page.app_secret' => 'S',
            'messaging.backfill.days' => 90,
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);

        return [$tenant, $account];
    }

    /** 1 trang gồm N hội thoại "mới" (trong 90 ngày), không có trang kế. */
    private function fakeConversationsPage(array $psids, ?string $afterCursor = null): void
    {
        $items = array_map(fn ($p) => [
            'id' => 't_'.$p,
            'updated_time' => now()->subDay()->toIso8601String(),
            'message_count' => 0,
            'participants' => ['data' => [['id' => 'PAGE_CAP', 'name' => 'Page'], ['id' => $p, 'name' => $p]]],
        ], $psids);
        $paging = $afterCursor ? ['cursors' => ['after' => $afterCursor], 'next' => 'https://graph.facebook.com/next'] : [];

        Http::fake([
            'graph.facebook.com/*/conversations*' => Http::response(['data' => $items, 'paging' => $paging], 200),
            // messages mỗi thread rỗng + profile fallback.
            'graph.facebook.com/*' => Http::response(['id' => 'x', 'messages' => ['data' => []], 'name' => 'Page'], 200),
        ]);
    }

    public function test_stops_at_500_conversation_cap_even_within_90_days(): void
    {
        Bus::fake([RelayMessagingAvatar::class, DownloadInboundMedia::class]);
        [$tenant, $account] = $this->fbAccount();
        config(['messaging.backfill.max_conversations' => 2, 'messaging.backfill.max_pages_per_run' => 10]);

        // 3 hội thoại "mới" trong 1 trang; cap = 2 ⇒ chỉ 2 cái đầu được nạp, cái thứ 3 bị dừng.
        $this->fakeConversationsPage(['PSID_1', 'PSID_2', 'PSID_3']);

        BackfillMessagingChannel::dispatchSync($account->id);

        $this->assertDatabaseHas('conversations', ['external_conversation_id' => 'PSID_1']);
        $this->assertDatabaseHas('conversations', ['external_conversation_id' => 'PSID_2']);
        $this->assertDatabaseMissing('conversations', ['external_conversation_id' => 'PSID_3']);

        $meta = MessagingAccountMeta::query()->find($account->id);
        $this->assertSame('done', $meta->sync_status, 'Đủ cap 500 (ở đây 2) ⇒ phải DONE, không kẹt running.');
    }

    public function test_self_chains_when_page_budget_reached(): void
    {
        Bus::fake([BackfillMessagingChannel::class, RelayMessagingAvatar::class, DownloadInboundMedia::class]);
        [$tenant, $account] = $this->fbAccount();
        config(['messaging.backfill.max_conversations' => 500, 'messaging.backfill.max_pages_per_run' => 1]);

        // 1 trang có hội thoại + còn trang kế (after=C2) ⇒ hết budget 1 trang ⇒ self-chain.
        $this->fakeConversationsPage(['PSID_A'], afterCursor: 'C2');

        // Gọi handle trực tiếp (static::dispatch bị Bus::fake bắt, không chạy đệ quy).
        (new BackfillMessagingChannel($account->id))->handle(
            app(MessagingRegistry::class), app(MessageIngestionService::class)
        );

        // Phải tự dispatch mắt xích kế: isContinuation=true, processed mang số đã xử lý (1).
        Bus::assertDispatched(BackfillMessagingChannel::class, fn ($job) => $job->channelAccountId === $account->id
            && $job->isContinuation === true
            && $job->processed === 1);

        $meta = MessagingAccountMeta::query()->find($account->id);
        $this->assertSame('running', $meta->sync_status, 'Còn trang ⇒ giữ running, chưa DONE (để chuỗi tiếp).');
    }
}
