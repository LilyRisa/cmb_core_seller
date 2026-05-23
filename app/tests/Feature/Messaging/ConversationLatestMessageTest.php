<?php

namespace Tests\Feature\Messaging;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
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
}
