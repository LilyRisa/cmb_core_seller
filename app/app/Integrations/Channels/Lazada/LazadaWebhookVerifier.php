<?php

namespace CMBcoreSeller\Integrations\Channels\Lazada;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use Symfony\Component\HttpFoundation\Request;

/**
 * Verifies & parses Lazada "push message" webhooks.
 *
 * Body shape: { "message_type": <int>, "timestamp": <ms>, "site": "lazada_vn",
 *               "data": { "trade_order_id": "...", "trade_order_line_id": "...",
 *                         "order_item_status": "...", "seller_id": "...", ... } }.
 * Signature: HMAC-SHA256(key=app_secret, message = rawBody), hex — Lazada sends it in a
 *            header (name varies by app; we check a few common ones). The raw body is the
 *            source of truth only as a *signal* — order events trigger a re-fetch.
 *
 * `message_type` -> normalized WebhookEventDTO type via config('integrations.lazada.webhook_message_types')
 * (unknown -> "unknown" -> recorded as ignored). See docs/04-channels/lazada.md §4.
 */
class LazadaWebhookVerifier
{
    /** @var array<string,mixed> */
    protected array $cfg;

    private const SIG_HEADERS = ['X-Lazop-Sign', 'X-Lzd-Sign', 'X-Signature', 'Authorization'];

    public function __construct()
    {
        $this->cfg = (array) config('integrations.lazada', []);
    }

    public function verify(Request $request): bool
    {
        $secret = (string) ($this->cfg['app_secret'] ?? '');
        if ($secret === '') {
            return false;
        }
        $provided = '';
        foreach (self::SIG_HEADERS as $h) {
            $v = (string) $request->headers->get($h, '');
            if ($v !== '') {
                $provided = strtolower(trim($v));
                break;
            }
        }
        if ($provided === '') {
            return false;
        }
        $expected = strtolower(hash_hmac('sha256', $request->getContent(), $secret));

        return hash_equals($expected, $provided);
    }

    public function parse(Request $request): WebhookEventDTO
    {
        /** @var array<string,mixed> $body */
        $body = json_decode($request->getContent() ?: '{}', true) ?: [];
        $rawType = isset($body['message_type']) ? (int) $body['message_type'] : null;
        $map = (array) ($this->cfg['webhook_message_types'] ?? []);
        $type = ($rawType !== null && isset($map[$rawType])) ? (string) $map[$rawType] : WebhookEventDTO::TYPE_UNKNOWN;

        $data = (array) ($body['data'] ?? []);
        $orderId = $data['trade_order_id'] ?? $data['order_id'] ?? null;
        // unknown type but the payload clearly carries an order id -> treat as a status update (re-fetch handles it)
        if ($type === WebhookEventDTO::TYPE_UNKNOWN && $orderId !== null) {
            $type = WebhookEventDTO::TYPE_ORDER_STATUS_UPDATE;
        }
        $occurredAt = isset($body['timestamp']) ? CarbonImmutable::createFromTimestampMs((int) $body['timestamp']) : null;
        $rawStatus = $data['order_item_status'] ?? $data['status'] ?? null;

        return new WebhookEventDTO(
            provider: 'lazada',
            type: $type,
            externalShopId: isset($data['seller_id']) ? (string) $data['seller_id'] : (isset($body['seller_id']) ? (string) $body['seller_id'] : null),
            externalOrderId: $orderId !== null ? (string) $orderId : null,
            externalIds: array_filter([
                'order_id' => $orderId !== null ? (string) $orderId : null,
                'order_item_id' => isset($data['trade_order_line_id']) ? (string) $data['trade_order_line_id'] : null,
            ], fn ($v) => $v !== null),
            occurredAt: $occurredAt,
            orderRawStatus: $rawStatus !== null ? (string) $rawStatus : null,
            raw: $body + ['_raw_type' => $rawType],
        );
    }
}
