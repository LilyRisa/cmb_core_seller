<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Bug: FB voice (attachment type='audio') bị map kind=File (thiếu case audio ở connector)
 * ⇒ transcription job (guard kind=audio) không chạy, AI thấy [file]. Fix: type='audio' → Audio.
 */
class FacebookVoiceAttachmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_secret' => 'test-secret',
            'integrations.messaging_facebook_page.verify_token' => 'VTOKEN',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
    }

    public function test_voice_attachment_parsed_as_audio_kind(): void
    {
        /** @var FacebookPageConnector $connector */
        $connector = $this->app->make(MessagingRegistry::class)->for('facebook_page');

        $payload = json_encode([
            'object' => 'page',
            'entry' => [[
                'id' => 'PAGE_001',
                'messaging' => [[
                    'sender' => ['id' => 'PSID_BUYER'],
                    'recipient' => ['id' => 'PAGE_001'],
                    'timestamp' => 1_700_000_000_000,
                    'message' => [
                        'mid' => 'm_voice_1',
                        'attachments' => [[
                            'type' => 'audio',
                            'payload' => ['url' => 'https://cdn.fb/voice.mp4'],
                        ]],
                    ],
                ]],
            ]],
        ]);

        $request = Request::create('/webhook/messaging/facebook', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);

        $events = $connector->parseWebhookEvents($request);
        $msg = collect($events)->firstWhere('type', MessagingWebhookEventDTO::TYPE_MESSAGE_RECEIVED);

        $this->assertNotNull($msg, 'expected a message_received event');
        $this->assertCount(1, $msg->attachments);
        $this->assertSame(MessageKind::Audio, $msg->attachments[0]->kind);
        $this->assertSame('audio/mpeg', $msg->attachments[0]->mime);
    }
}
