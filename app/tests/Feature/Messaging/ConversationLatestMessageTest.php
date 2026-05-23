<?php

namespace Tests\Feature\Messaging;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Header hội thoại (last_message_at + preview) phải phản ánh tin MỚI NHẤT — kể cả
 * khi backfill ingest nhiều tin theo thứ tự newest→oldest (Graph trả vậy). Tin cũ
 * KHÔNG được ghi đè preview/last_message_at ⇒ inbox sắp xếp đúng + hiện tin cuối.
 */
class ConversationLatestMessageTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'LatestMsgTenant']);
        app(CurrentTenant::class)->set($this->tenant);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_L',
            'shop_name' => 'Trang L',
            'status' => ChannelAccount::STATUS_ACTIVE,
            'messaging_enabled' => true,
        ]);
    }

    private function dto(string $mid, string $body, CarbonImmutable $sentAt): MessageDTO
    {
        return new MessageDTO(
            externalConversationId: 'PSID_L',
            externalMessageId: $mid,
            buyerExternalId: 'PSID_L',
            direction: MessageDirection::Inbound,
            kind: MessageKind::Text,
            body: $body,
            sentAt: $sentAt,
        );
    }

    public function test_older_message_ingested_after_newer_does_not_clobber_preview(): void
    {
        $ingest = app(MessageIngestionService::class);
        $newest = CarbonImmutable::now();
        $older = $newest->subHour();

        // Ingest newest FIRST, then older (mô phỏng backfill newest→oldest).
        $ingest->ingest($this->account, $this->dto('A', 'TIN MỚI NHẤT', $newest));
        $ingest->ingest($this->account, $this->dto('B', 'tin cũ hơn', $older));

        $conv = Conversation::withoutGlobalScope(TenantScope::class)
            ->where('external_conversation_id', 'PSID_L')->first();

        $this->assertSame('TIN MỚI NHẤT', $conv->last_message_preview, 'preview phải là tin mới nhất');
        $this->assertSame($newest->timestamp, $conv->last_message_at->timestamp, 'last_message_at = tin mới nhất');
        $this->assertSame(2, $conv->message_count);
    }

    /** Re-sync bổ sung nội dung cho tin RỖNG đã lưu (vd template fetch được sau khi sửa query). */
    public function test_resync_heals_empty_message_with_template_content(): void
    {
        $ingest = app(MessageIngestionService::class);
        $now = CarbonImmutable::now();

        // Lần 1: tin rỗng (chưa parse được template ⇒ body null).
        $ingest->ingest($this->account, new MessageDTO(
            externalConversationId: 'PSID_L', externalMessageId: 'TPL', buyerExternalId: 'PSID_L',
            direction: MessageDirection::Outbound, kind: MessageKind::Text, body: null, sentAt: $now,
        ));

        // Lần 2 (re-sync): cùng mid, giờ có title + nút bấm.
        $ingest->ingest($this->account, new MessageDTO(
            externalConversationId: 'PSID_L', externalMessageId: 'TPL', buyerExternalId: 'PSID_L',
            direction: MessageDirection::Outbound, kind: MessageKind::Text, body: 'Ưu đãi hôm nay',
            sentAt: $now, meta: ['buttons' => [['title' => 'Đặt hàng ngay']]],
        ));

        $msg = Message::withoutGlobalScope(TenantScope::class)->where('external_message_id', 'TPL')->first();
        $this->assertSame('Ưu đãi hôm nay', $msg->body, 'body rỗng được bổ sung');
        $this->assertSame('Đặt hàng ngay', $msg->meta['buttons'][0]['title']);
        $this->assertSame(1, Message::withoutGlobalScope(TenantScope::class)->where('external_message_id', 'TPL')->count(), 'không tạo tin trùng');
    }

    /** KHÔNG ghi đè tin đã có nội dung khi re-sync. */
    public function test_resync_does_not_overwrite_existing_body(): void
    {
        $ingest = app(MessageIngestionService::class);
        $now = CarbonImmutable::now();

        $ingest->ingest($this->account, new MessageDTO(
            externalConversationId: 'PSID_L', externalMessageId: 'M1', buyerExternalId: 'PSID_L',
            direction: MessageDirection::Inbound, kind: MessageKind::Text, body: 'nội dung gốc', sentAt: $now,
        ));
        $ingest->ingest($this->account, new MessageDTO(
            externalConversationId: 'PSID_L', externalMessageId: 'M1', buyerExternalId: 'PSID_L',
            direction: MessageDirection::Inbound, kind: MessageKind::Text, body: 'nội dung KHÁC', sentAt: $now,
        ));

        $msg = Message::withoutGlobalScope(TenantScope::class)->where('external_message_id', 'M1')->first();
        $this->assertSame('nội dung gốc', $msg->body, 'không ghi đè tin đã có nội dung');
    }

    private function makeConversation(): Conversation
    {
        return Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->getKey(),
            'provider' => 'facebook_page',
            'external_conversation_id' => 'PSID_L',
            'buyer_external_id' => 'PSID_L',
            'status' => Conversation::STATUS_OPEN,
            'last_message_at' => now(),
        ]);
    }

    /** Tin sticker CŨ lỡ lưu URL vào body ⇒ re-sync xoá link (chỉ còn ảnh sticker). */
    public function test_resync_clears_sticker_url_from_body(): void
    {
        $conv = $this->makeConversation();
        $msg = Message::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'conversation_id' => $conv->getKey(),
            'external_message_id' => 'STK', 'direction' => Message::DIRECTION_INBOUND,
            'kind' => Message::KIND_IMAGE, 'body' => 'https://cdn.fb/sticker.png',
            'attachments_count' => 1, 'delivery_status' => Message::STATUS_SENT, 'sent_at' => now(),
        ]);
        MessageAttachment::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'message_id' => $msg->getKey(),
            'kind' => 'image', 'mime' => 'image/png', 'external_url' => 'https://cdn.fb/sticker.png',
            'filename' => 'sticker', 'status' => MessageAttachment::STATUS_DOWNLOADED,
        ]);

        app(MessageIngestionService::class)->ingest($this->account, new MessageDTO(
            externalConversationId: 'PSID_L', externalMessageId: 'STK', buyerExternalId: 'PSID_L',
            direction: MessageDirection::Inbound, kind: MessageKind::Image, body: null,
            attachments: [new MediaRefDTO(kind: MessageKind::Image, mime: 'image/png', externalUrl: 'https://cdn.fb/sticker.png', filename: 'sticker')],
            sentAt: CarbonImmutable::now(),
        ));

        $fresh = Message::withoutGlobalScope(TenantScope::class)->where('external_message_id', 'STK')->first();
        $this->assertNull($fresh->body, 'link sticker bị xoá khỏi body');
    }

    /** Tin tự động CŨ (body rỗng + attachment "file" rác) ⇒ re-sync điền body + xoá rác. */
    public function test_resync_fills_body_and_removes_junk_attachment(): void
    {
        $conv = $this->makeConversation();
        $msg = Message::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'conversation_id' => $conv->getKey(),
            'external_message_id' => 'TPL2', 'direction' => Message::DIRECTION_OUTBOUND,
            'kind' => Message::KIND_FILE, 'body' => null,
            'attachments_count' => 1, 'delivery_status' => Message::STATUS_SENT, 'sent_at' => now(),
        ]);
        MessageAttachment::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'message_id' => $msg->getKey(),
            'kind' => 'file', 'mime' => 'application/octet-stream',
            'external_url' => null, 'storage_path' => null, 'status' => MessageAttachment::STATUS_FAILED,
        ]);

        app(MessageIngestionService::class)->ingest($this->account, new MessageDTO(
            externalConversationId: 'PSID_L', externalMessageId: 'TPL2', buyerExternalId: 'PSID_L',
            direction: MessageDirection::Outbound, kind: MessageKind::Text, body: 'Ưu đãi hôm nay',
            sentAt: CarbonImmutable::now(), meta: ['buttons' => [['title' => 'Đặt hàng ngay']]],
        ));

        $fresh = Message::withoutGlobalScope(TenantScope::class)->where('external_message_id', 'TPL2')->first();
        $this->assertSame('Ưu đãi hôm nay', $fresh->body);
        $this->assertSame('Đặt hàng ngay', $fresh->meta['buttons'][0]['title']);
        $this->assertSame(0, (int) $fresh->attachments_count, 'attachment rác đã xoá');
        $this->assertSame(0, MessageAttachment::withoutGlobalScope(TenantScope::class)->where('message_id', $fresh->getKey())->count());
    }
}
