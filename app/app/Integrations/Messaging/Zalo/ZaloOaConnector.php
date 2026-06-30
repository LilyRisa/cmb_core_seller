<?php

namespace CMBcoreSeller\Integrations\Messaging\Zalo;

use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Messaging\Contracts\InteractiveMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingWebhookEventDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\OutboundWindowPolicyDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\Page;
use CMBcoreSeller\Integrations\Messaging\DTO\SendResultDTO;
use CMBcoreSeller\Integrations\Messaging\Exceptions\UnsupportedOperation;
use Symfony\Component\HttpFoundation\Request;

class ZaloOaConnector implements InteractiveMessagingConnector, MessagingConnector
{
    /** @param array<string,mixed> $config */
    public function __construct(
        private array $config,
        private ZaloSignatureVerifier $verifier,
        private ZaloClient $client,
    ) {}

    public function code(): string
    {
        return 'zalo_oa';
    }

    public function displayName(): string
    {
        return 'Zalo OA';
    }

    /** @return array<string,bool> */
    public function capabilities(): array
    {
        return [
            'inbound.webhook' => true,
            'inbound.polling' => false,
            'inbound.postback' => true,
            'outbound.text' => true,
            'outbound.image' => true,
            'outbound.file' => true,
            'outbound.video' => false,            // Phase 1 tắt cho an toàn
            'outbound.template' => false,
            'outbound.interactive' => true,        // nút ≤5
            'outbound.utility_template' => false,  // bật ở Phase ZNS
            'read_receipt' => true,
            'typing' => false,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function outboundWindow(): OutboundWindowPolicyDTO
    {
        // CS window: Zalo enforce server-side, lộ qua error codes. Không free-window cứng ở client.
        return new OutboundWindowPolicyDTO(freeWindowHours: null, requiresTag: false);
    }

    // --- OAuth (Task 6) ---
    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        throw UnsupportedOperation::for($this->code(), 'buildAuthorizationUrl'); // replaced in Task 6
    }

    public function exchangeCodeForToken(string $code): TokenDTO
    {
        throw UnsupportedOperation::for($this->code(), 'exchangeCodeForToken'); // Task 6
    }

    public function refreshToken(string $refreshToken): TokenDTO
    {
        throw UnsupportedOperation::for($this->code(), 'refreshToken'); // Task 6
    }

    public function registerWebhooks(MessagingAuthContext $auth): void
    {
        // Zalo OA webhook cấu hình trên Zalo Developer Console (URL cố định), không gọi API ở đây.
    }

    /** @return array{name: ?string, avatar_url: ?string} */
    public function fetchPageProfile(MessagingAuthContext $auth): array
    {
        throw UnsupportedOperation::for($this->code(), 'fetchPageProfile'); // Task 6
    }

    /** @return array{name: ?string, avatar_url: ?string} */
    public function fetchUserProfile(MessagingAuthContext $auth, string $externalUserId): array
    {
        throw UnsupportedOperation::for($this->code(), 'fetchUserProfile'); // Task 6
    }

    // --- Inbound (Task 4-5) ---
    public function verifyWebhookSignature(Request $request): bool
    {
        return $this->verifier->verify($request, (string) $this->cfg('app_id'), (string) $this->cfg('oa_secret'));
    }

    public function parseWebhook(Request $request): MessagingWebhookEventDTO
    {
        throw UnsupportedOperation::for($this->code(), 'parseWebhook'); // Task 5
    }

    /** @return list<MessagingWebhookEventDTO> */
    public function parseWebhookEvents(Request $request): array
    {
        return [$this->parseWebhook($request)];
    }

    public function fetchConversations(MessagingAuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchConversations');
    }

    public function fetchMessages(MessagingAuthContext $auth, string $externalConversationId, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchMessages');
    }

    // --- Outbound (Task 7-9) ---
    public function sendText(MessagingAuthContext $auth, string $externalConversationId, string $body, array $opts = []): SendResultDTO
    {
        throw UnsupportedOperation::for($this->code(), 'sendText'); // Task 7
    }

    public function sendMedia(MessagingAuthContext $auth, string $externalConversationId, MediaRefDTO $media, array $opts = []): SendResultDTO
    {
        throw UnsupportedOperation::for($this->code(), 'sendMedia'); // Task 8
    }

    public function sendTemplate(MessagingAuthContext $auth, string $externalConversationId, string $templateKey, array $vars = [], array $opts = []): SendResultDTO
    {
        throw UnsupportedOperation::for($this->code(), 'sendTemplate'); // ZNS — Phase 3
    }

    public function sendInteractive(MessagingAuthContext $auth, string $externalConversationId, array $structure, array $opts = []): SendResultDTO
    {
        throw UnsupportedOperation::for($this->code(), 'sendInteractive'); // Task 9
    }

    // --- Comment moderation: Zalo OA không có comment feed ---
    public function hideComment(MessagingAuthContext $auth, string $commentId, bool $hidden): void
    {
        throw UnsupportedOperation::for($this->code(), 'hideComment');
    }

    public function deleteComment(MessagingAuthContext $auth, string $commentId): void
    {
        throw UnsupportedOperation::for($this->code(), 'deleteComment');
    }

    public function replyToComment(MessagingAuthContext $auth, string $commentId, string $message, array $attachments = []): string
    {
        throw UnsupportedOperation::for($this->code(), 'replyToComment');
    }

    public function privateReplyToComment(MessagingAuthContext $auth, string $commentId, string $message, array $attachments = []): void
    {
        throw UnsupportedOperation::for($this->code(), 'privateReplyToComment');
    }

    private function cfg(string $key): mixed
    {
        return $this->config[$key] ?? '';
    }
}
