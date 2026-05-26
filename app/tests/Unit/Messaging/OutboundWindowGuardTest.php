<?php

namespace Tests\Unit\Messaging;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\OutboundWindowPolicyDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\Page;
use CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\OutboundWindowClosed;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\OutboundWindowGuard;
use Symfony\Component\HttpFoundation\Request;
use Tests\TestCase;

/**
 * Test `OutboundWindowGuard` — đảm bảo logic 24h Facebook window đúng.
 */
class OutboundWindowGuardTest extends TestCase
{
    public function test_passes_when_provider_has_no_window(): void
    {
        $connector = $this->fakeConnector(new OutboundWindowPolicyDTO(freeWindowHours: null));
        $conv = $this->fakeConversation(null);

        (new OutboundWindowGuard)->assertCanSend($connector, $conv);
        $this->assertTrue(true);
    }

    public function test_passes_within_24h_window(): void
    {
        $connector = $this->fakeConnector(new OutboundWindowPolicyDTO(freeWindowHours: 24, requiresTag: true));
        $conv = $this->fakeConversation(CarbonImmutable::now()->subHours(10));

        (new OutboundWindowGuard)->assertCanSend($connector, $conv);
        $this->assertTrue(true);
    }

    public function test_blocks_after_24h_without_tag(): void
    {
        $connector = $this->fakeConnector(new OutboundWindowPolicyDTO(
            freeWindowHours: 24,
            requiresTag: true,
            allowedTags: ['CONFIRMED_EVENT_UPDATE'],
        ));
        $conv = $this->fakeConversation(CarbonImmutable::now()->subHours(30));

        $this->expectException(OutboundWindowClosed::class);
        (new OutboundWindowGuard)->assertCanSend($connector, $conv);
    }

    public function test_passes_after_24h_with_valid_tag(): void
    {
        $connector = $this->fakeConnector(new OutboundWindowPolicyDTO(
            freeWindowHours: 24,
            requiresTag: true,
            allowedTags: ['CONFIRMED_EVENT_UPDATE', 'POST_PURCHASE_UPDATE'],
        ));
        $conv = $this->fakeConversation(CarbonImmutable::now()->subHours(30));

        (new OutboundWindowGuard)->assertCanSend($connector, $conv, [
            'message_tag' => 'POST_PURCHASE_UPDATE',
        ]);
        $this->assertTrue(true);
    }

    public function test_blocks_after_24h_with_invalid_tag(): void
    {
        $connector = $this->fakeConnector(new OutboundWindowPolicyDTO(
            freeWindowHours: 24,
            requiresTag: true,
            allowedTags: ['CONFIRMED_EVENT_UPDATE'],
        ));
        $conv = $this->fakeConversation(CarbonImmutable::now()->subHours(30));

        $this->expectException(OutboundWindowClosed::class);
        (new OutboundWindowGuard)->assertCanSend($connector, $conv, [
            'message_tag' => 'BOGUS_TAG',
        ]);
    }

    public function test_blocks_when_no_inbound_and_window_required(): void
    {
        // Conv chưa có inbound ⇒ coi như window closed để an toàn.
        $connector = $this->fakeConnector(new OutboundWindowPolicyDTO(
            freeWindowHours: 24,
            requiresTag: true,
            allowedTags: ['CONFIRMED_EVENT_UPDATE'],
        ));
        $conv = $this->fakeConversation(null);

        $this->expectException(OutboundWindowClosed::class);
        (new OutboundWindowGuard)->assertCanSend($connector, $conv);
    }

    private function fakeConnector(OutboundWindowPolicyDTO $policy): MessagingConnector
    {
        return new class($policy) implements MessagingConnector
        {
            public function __construct(public OutboundWindowPolicyDTO $policy) {}

            public function code(): string
            {
                return 'fake';
            }

            public function displayName(): string
            {
                return 'Fake';
            }

            public function capabilities(): array
            {
                return [];
            }

            public function supports(string $capability): bool
            {
                return false;
            }

            public function buildAuthorizationUrl(string $state, array $opts = []): string
            {
                return '';
            }

            public function exchangeCodeForToken(string $code): TokenDTO
            {
                return new TokenDTO('x');
            }

            public function refreshToken(string $refreshToken): TokenDTO
            {
                return new TokenDTO('x');
            }

            public function registerWebhooks(MessagingAuthContext $auth): void {}

            public function verifyWebhookSignature(Request $request): bool
            {
                return true;
            }

            public function parseWebhook(Request $request): MessagingWebhookEventDTO
            {
                return new MessagingWebhookEventDTO('fake', 'unknown');
            }

            public function parseWebhookEvents(Request $request): array
            {
                return [$this->parseWebhook($request)];
            }

            public function fetchConversations(MessagingAuthContext $auth, array $query = []): Page
            {
                return new Page([]);
            }

            public function fetchMessages(MessagingAuthContext $auth, string $externalConversationId, array $query = []): Page
            {
                return new Page([]);
            }

            public function sendText(MessagingAuthContext $auth, string $externalConversationId, string $body, array $opts = []): SendResultDTO
            {
                return new SendResultDTO('x');
            }

            public function sendMedia(MessagingAuthContext $auth, string $externalConversationId, MediaRefDTO $media, array $opts = []): SendResultDTO
            {
                return new SendResultDTO('x');
            }

            public function sendTemplate(MessagingAuthContext $auth, string $externalConversationId, string $templateKey, array $vars = [], array $opts = []): SendResultDTO
            {
                return new SendResultDTO('x');
            }

            public function outboundWindow(): OutboundWindowPolicyDTO
            {
                return $this->policy;
            }

            public function fetchPageProfile(MessagingAuthContext $auth): array
            {
                return ['name' => null, 'avatar_url' => null];
            }

            public function fetchUserProfile(MessagingAuthContext $auth, string $externalUserId): array
            {
                return ['name' => null, 'avatar_url' => null];
            }

            public function hideComment(MessagingAuthContext $auth, string $commentId, bool $hidden): void
            {
                throw new \RuntimeException('not supported');
            }

            public function deleteComment(MessagingAuthContext $auth, string $commentId): void
            {
                throw new \RuntimeException('not supported');
            }

            public function replyToComment(MessagingAuthContext $auth, string $commentId, string $message, array $attachments = []): string
            {
                throw new \RuntimeException('not supported');
            }

            public function privateReplyToComment(MessagingAuthContext $auth, string $commentId, string $message, array $attachments = []): void
            {
                throw new \RuntimeException('not supported');
            }
        };
    }

    private function fakeConversation(?CarbonImmutable $lastInboundAt): Conversation
    {
        $c = new Conversation;
        $c->last_inbound_at = $lastInboundAt;

        return $c;
    }
}
