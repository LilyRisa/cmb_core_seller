<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\CommentDmLink;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\CommentDmLinker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CommentDmLinker — liên kết comment→DM theo bài viết (SPEC 2026-06-09).
 */
class CommentDmLinkerTest extends TestCase
{
    use RefreshDatabase;

    private function account(): ChannelAccount
    {
        return ChannelAccount::create([
            'tenant_id' => 1, 'provider' => 'facebook_page', 'external_shop_id' => 'page1',
            'status' => ChannelAccount::STATUS_ACTIVE, 'name' => 'Page',
        ]);
    }

    private function dmConv(ChannelAccount $acc, string $psid, array $meta = []): Conversation
    {
        return Conversation::create([
            'tenant_id' => 1, 'channel_account_id' => $acc->id, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_MESSAGE, 'external_conversation_id' => $psid,
            'buyer_external_id' => $psid, 'status' => 'open', 'message_count' => 1, 'meta' => $meta,
        ]);
    }

    private function inbound(string $psid): MessageDTO
    {
        return new MessageDTO(
            externalConversationId: $psid, externalMessageId: 'm1', buyerExternalId: $psid,
            direction: MessageDirection::Inbound, kind: MessageKind::Text, body: 'hi',
        );
    }

    public function test_record_upserts_latest_wins(): void
    {
        $linker = app(CommentDmLinker::class);
        $acc = $this->account();

        $linker->record(1, $acc->id, 'psidA', 'POST_1', 'c1');
        $linker->record(1, $acc->id, 'psidA', 'POST_2', 'c2'); // mới nhất thắng

        $this->assertSame(1, CommentDmLink::withoutGlobalScopes()->where('channel_account_id', $acc->id)->where('psid', 'psidA')->count());
        $link = CommentDmLink::withoutGlobalScopes()->where('channel_account_id', $acc->id)->where('psid', 'psidA')->first();
        $this->assertSame('POST_2', $link->fb_post_id);
    }

    public function test_stamp_inbound_sets_post_id_from_link(): void
    {
        $linker = app(CommentDmLinker::class);
        $acc = $this->account();
        $linker->record(1, $acc->id, 'psidB', 'POST_9', 'c9');
        $conv = $this->dmConv($acc, 'psidB');

        $linker->stampInbound($acc, $conv, $this->inbound('psidB'));

        $this->assertSame('POST_9', $conv->fresh()->meta['fb_post_id']);
        $this->assertSame('comment_dm_link', $conv->fresh()->meta['fb_post_source']);
    }

    public function test_stamp_inbound_first_touch_does_not_overwrite(): void
    {
        $linker = app(CommentDmLinker::class);
        $acc = $this->account();
        $linker->record(1, $acc->id, 'psidC', 'POST_NEW', 'c');
        $conv = $this->dmConv($acc, 'psidC', ['fb_post_id' => 'POST_OLD']);

        $linker->stampInbound($acc, $conv, $this->inbound('psidC'));

        $this->assertSame('POST_OLD', $conv->fresh()->meta['fb_post_id']); // giữ bài viết của hội thoại đang chạy
    }

    public function test_stamp_inbound_noop_without_link(): void
    {
        $linker = app(CommentDmLinker::class);
        $acc = $this->account();
        $conv = $this->dmConv($acc, 'psidD');

        $linker->stampInbound($acc, $conv, $this->inbound('psidD'));

        $this->assertArrayNotHasKey('fb_post_id', (array) $conv->fresh()->meta);
    }
}
