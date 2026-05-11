<?php

namespace CMBcoreSeller\Integrations\Channels\TikTok;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use Symfony\Component\HttpFoundation\Request;

/**
 * Verifies & parses TikTok Shop webhook pushes.
 *
 * Body shape: { "type": <int>, "tts_notification_id": "...", "shop_id": "...",
 *               "timestamp": <unix>, "data": { ... } }.
 * Signature: the `Authorization` header is HMAC-SHA256(key=app_secret,
 *            message = app_key + rawBody), lowercase hex.
 *
 * The integer `type` -> normalized WebhookEventDTO type via
 * config('integrations.tiktok.webhook_event_types') (unknown -> "unknown" ->
 * recorded as ignored). Order events carry `data.order_id`; we always re-fetch
 * the order detail afterwards (the webhook is a signal, not a source of truth).
 * See docs/04-channels/tiktok-shop.md §4, docs/05-api/webhooks-and-oauth.md.
 */
class TikTokWebhookVerifier
{
    /** @var array<string,mixed> */
    protected array $cfg;

    public function __construct()
    {
        $this->cfg = (array) config('integrations.tiktok', []);
    }

    public function verify(Request $request): bool
    {
        $secret = (string) ($this->cfg['app_secret'] ?? '');
        $appKey = (string) ($this->cfg['app_key'] ?? '');
        if ($secret === '' || $appKey === '') {
            return false;
        }
        $provided = strtolower(trim((string) $request->headers->get('Authorization', '')));
        if ($provided === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $appKey.$request->getContent(), $secret);

        return hash_equals($expected, $provided);
    }

    public function parse(Request $request): WebhookEventDTO
    {
        /** @var array<string,mixed> $body */
        $body = json_decode($request->getContent() ?: '{}', true) ?: [];
        $rawType = isset($body['type']) ? (int) $body['type'] : null;
        $map = (array) ($this->cfg['webhook_event_types'] ?? []);
        $type = ($rawType !== null && isset($map[$rawType])) ? (string) $map[$rawType] : WebhookEventDTO::TYPE_UNKNOWN;

        $data = (array) ($body['data'] ?? []);
        $orderId = $data['order_id'] ?? $data['order_no'] ?? null;
        $occurredAt = isset($body['timestamp']) ? CarbonImmutable::createFromTimestamp((int) $body['timestamp']) : null;

        return new WebhookEventDTO(
            provider: 'tiktok',
            type: $type,
            externalShopId: $body['shop_id'] ?? null,
            externalOrderId: $orderId !== null ? (string) $orderId : null,
            externalIds: array_filter([
                'notification_id' => $body['tts_notification_id'] ?? null,
                'order_id' => $orderId !== null ? (string) $orderId : null,
                'return_id' => $data['return_id'] ?? null,
                'product_id' => $data['product_id'] ?? null,
            ], fn ($v) => $v !== null),
            occurredAt: $occurredAt,
            raw: $body + ['_raw_type' => $rawType],
        );
    }

    public function rawType(Request $request): ?int
    {
        $body = json_decode($request->getContent() ?: '{}', true) ?: [];

        return isset($body['type']) ? (int) $body['type'] : null;
    }
}
