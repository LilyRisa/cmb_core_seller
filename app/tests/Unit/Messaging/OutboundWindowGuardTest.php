<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\OutboundWindowPolicyDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\OutboundWindowClosed;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\OutboundWindowGuard;
use Carbon\CarbonImmutable;
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
            public function code(): string { return 'fake'; }
            public function displayName(): string { return 'Fake'; }
            public function capabilities(): array { return []; }
            public function supports(string $capability): bool { return false; }
            public function buildAuthorizationUrl(string $state, array $opts = []): string { return ''; }
            public function exchangeCodeForToken(string $code): \CMBcoreSeller\Integrations\Channels\DTO\TokenDTO { return new \CMBcoreSeller\Integrations\Channels\DTO\TokenDTO('x'); }
            public function refreshToken(string $refreshToken): \CMBcoreSeller\Integrations\Channels\DTO\TokenDTO { return new \CMBcoreSeller\Integrations\Channels\DTO\TokenDTO('x'); }
            public function registerWebhooks(\CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext $auth): void {}
            public function verifyWebhookSignature(\Symfony\Component\HttpFoundation\Request $request): bool { return true; }
            public function parseWebhook(\Symfony\Component\HttpFoundation\Request $request): \CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO
            {
                return new \CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO('fake', 'unknown');
            }
            public function parseWebhookEvents(\Symfony\Component\HttpFoundation\Request $request): array
            {
                return [$this->parseWebhook($request)];
            }
            public function fetchConversations(\CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext $auth, array $query = []): \CMBcoreSeller\Integrations\Messaging\DTO\Page
            { return new \CMBcoreSeller\Integrations\Messaging\DTO\Page([]); }
            public function fetchMessages(\CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext $auth, string $externalConversationId, array $query = []): \CMBcoreSeller\Integrations\Messaging\DTO\Page
            { return new \CMBcoreSeller\Integrations\Messaging\DTO\Page([]); }
            public function sendText(\CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext $auth, string $externalConversationId, string $body, array $opts = []): \CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO
            { return new \CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO('x'); }
            public function sendMedia(\CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext $auth, string $externalConversationId, \CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO $media, array $opts = []): \CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO
            { return new \CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO('x'); }
            public function sendTemplate(\CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext $auth, string $externalConversationId, string $templateKey, array $vars = [], array $opts = []): \CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO
            { return new \CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO('x'); }
            public function outboundWindow(): OutboundWindowPolicyDTO { return $this->policy; }
        };
    }

    private function fakeConversation(?CarbonImmutable $lastInboundAt): Conversation
    {
        $c = new Conversation;
        $c->last_inbound_at = $lastInboundAt;
        return $c;
    }
}
