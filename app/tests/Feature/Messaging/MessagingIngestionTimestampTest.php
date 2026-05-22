<?php

namespace Tests\Feature\Messaging;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `last_inbound_at`/`last_message_at` phải phản ánh giờ buyer NHẮN THẬT (sent_at),
 * không phải giờ ingest (created_at) — để OutboundWindowGuard tính đúng cửa sổ 24h.
 */
class MessagingIngestionTimestampTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_uses_sent_at_for_conversation_timestamps(): void
    {
        $tenant = Tenant::create(['name' => 'TS']);
        $account = ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'shop_ts_1',
            'shop_name' => 'TS Shop',
            'status' => 'active',
            'messaging_enabled' => true,
        ]);

        $sentAt = CarbonImmutable::now()->subDays(3)->startOfMinute();

        $dto = new MessageDTO(
            externalConversationId: 'PSID_TS',
            externalMessageId: 'm_ts_1',
            buyerExternalId: 'PSID_TS',
            direction: MessageDirection::Inbound,
            kind: MessageKind::Text,
            body: 'tin cũ 3 ngày',
            sentAt: $sentAt,
        );

        $res = app(MessageIngestionService::class)->ingest($account, $dto);
        $conv = $res['conversation'];

        $this->assertNotNull($conv->last_inbound_at);
        $this->assertSame($sentAt->toDateTimeString(), $conv->last_inbound_at->toDateTimeString());
        $this->assertSame($sentAt->toDateTimeString(), $conv->last_message_at->toDateTimeString());
    }
}
