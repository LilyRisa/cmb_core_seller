<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug Critical (final review): transcript ghi âm khách được lưu (message_attachments.transcript)
 * nhưng AiSuggestionService chưa bao giờ đọc — AI trả lời "mù" với ghi âm + tốn credit vô ích.
 * Fix: tin inbound có audio attachment với transcript non-empty ⇒ context body =
 * "[Ghi âm khách]: <transcript>" thay vì placeholder rỗng "[audio]".
 */
class TranscriptInSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private function makeConversation(Tenant $tenant): Conversation
    {
        $account = ChannelAccount::query()->create([
            'tenant_id' => $tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'shop_1',
            'shop_name' => 'Shop',
            'status' => 'active',
            'messaging_enabled' => true,
        ]);

        return Conversation::query()->create([
            'tenant_id' => $tenant->getKey(),
            'channel_account_id' => $account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'conv_1',
            'buyer_external_id' => 'buyer_1',
        ]);
    }

    public function test_audio_transcript_becomes_context_body(): void
    {
        $tenant = Tenant::create(['name' => 'T1']);
        $conv = $this->makeConversation($tenant);

        $msg = Message::query()->create([
            'tenant_id' => $tenant->getKey(),
            'conversation_id' => $conv->id,
            'direction' => Message::DIRECTION_INBOUND,
            'kind' => 'audio',
            'external_message_id' => 'm1',
            'attachments_count' => 1,
        ]);

        MessageAttachment::query()->create([
            'tenant_id' => $tenant->getKey(),
            'message_id' => $msg->id,
            'kind' => MessageAttachment::KIND_AUDIO,
            'mime' => 'audio/mpeg',
            'status' => 'downloaded',
            'storage_path' => 'x',
            'transcript' => 'cho em hỏi ship',
        ]);

        $svc = app(AiSuggestionService::class);
        $this->assertSame('[Ghi âm khách]: cho em hỏi ship', $svc->transcriptFor($msg->fresh()));
        // Biến thể THÔ (không nhãn) cho phân loại ý định + RAG.
        $this->assertSame('cho em hỏi ship', $svc->transcriptFor($msg->fresh(), false));
    }

    public function test_no_transcript_returns_null(): void
    {
        $tenant = Tenant::create(['name' => 'T2']);
        $conv = $this->makeConversation($tenant);

        $msg = Message::query()->create([
            'tenant_id' => $tenant->getKey(),
            'conversation_id' => $conv->id,
            'direction' => Message::DIRECTION_INBOUND,
            'kind' => 'audio',
            'external_message_id' => 'm2',
            'attachments_count' => 1,
        ]);

        MessageAttachment::query()->create([
            'tenant_id' => $tenant->getKey(),
            'message_id' => $msg->id,
            'kind' => MessageAttachment::KIND_AUDIO,
            'mime' => 'audio/mpeg',
            'status' => 'downloaded',
            'storage_path' => 'y',
            'transcript' => null,
        ]);

        $this->assertNull(app(AiSuggestionService::class)->transcriptFor($msg->fresh()));
    }

    public function test_outbound_message_never_returns_transcript(): void
    {
        $tenant = Tenant::create(['name' => 'T3']);
        $conv = $this->makeConversation($tenant);

        $msg = Message::query()->create([
            'tenant_id' => $tenant->getKey(),
            'conversation_id' => $conv->id,
            'direction' => Message::DIRECTION_OUTBOUND,
            'kind' => 'audio',
            'external_message_id' => 'm3',
            'attachments_count' => 1,
        ]);

        MessageAttachment::query()->create([
            'tenant_id' => $tenant->getKey(),
            'message_id' => $msg->id,
            'kind' => MessageAttachment::KIND_AUDIO,
            'mime' => 'audio/mpeg',
            'status' => 'downloaded',
            'storage_path' => 'z',
            'transcript' => 'not for outbound',
        ]);

        $this->assertNull(app(AiSuggestionService::class)->transcriptFor($msg->fresh()));
    }
}
