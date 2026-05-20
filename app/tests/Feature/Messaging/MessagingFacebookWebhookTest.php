<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Messaging\Jobs\ProcessMessagingWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Test pipeline webhook Facebook E2E (SPEC-0024 S2): GET hub.challenge verify +
 * POST batch (nhiều message / 1 POST) fan-out đúng (không mất tin). Đây là điểm
 * "chạy ổn định" then chốt cho Messenger.
 */
class MessagingFacebookWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'fb-app-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_secret' => self::SECRET,
            'integrations.messaging_facebook_page.verify_token' => 'VTOKEN',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
    }

    public function test_get_verify_echoes_challenge_when_token_matches(): void
    {
        $this->get('/webhook/messaging/facebook?hub.mode=subscribe&hub.verify_token=VTOKEN&hub.challenge=CHALLENGE_42')
            ->assertOk()
            ->assertSee('CHALLENGE_42');
    }

    public function test_get_verify_rejects_wrong_token(): void
    {
        $this->get('/webhook/messaging/facebook?hub.mode=subscribe&hub.verify_token=WRONG&hub.challenge=X')
            ->assertStatus(403);
    }

    public function test_post_batch_fans_out_each_message(): void
    {
        Queue::fake();

        $payload = json_encode([
            'object' => 'page',
            'entry' => [[
                'id' => 'PAGE_1',
                'messaging' => [
                    ['sender' => ['id' => 'U1'], 'message' => ['mid' => 'mid_1', 'text' => 'a']],
                    ['sender' => ['id' => 'U2'], 'message' => ['mid' => 'mid_2', 'text' => 'b']],
                ],
            ]],
        ]);
        $sig = 'sha256='.hash_hmac('sha256', $payload, self::SECRET);

        $this->call('POST', '/webhook/messaging/facebook', [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => $sig, 'CONTENT_TYPE' => 'application/json'], $payload)
            ->assertOk()
            ->assertJsonPath('stored', 2);

        $this->assertSame(2, WebhookEvent::query()->where('provider', 'messaging.facebook_page')->count());
        Queue::assertPushed(ProcessMessagingWebhook::class, 2);
    }

    public function test_post_invalid_signature_rejected(): void
    {
        $payload = '{"object":"page","entry":[]}';
        $this->call('POST', '/webhook/messaging/facebook', [], [], [],
            ['HTTP_X_HUB_SIGNATURE_256' => 'sha256=bad', 'CONTENT_TYPE' => 'application/json'], $payload)
            ->assertStatus(401);
    }
}
