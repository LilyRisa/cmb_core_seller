<?php

namespace CMBcoreSeller\Integrations\Channels\Lazada;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

/**
 * Verifies & parses Lazada "push message" webhooks.
 *
 * Body shape: { "message_type": <int>, "timestamp": <ms>, "site": "lazada_vn",
 *               "data": { "trade_order_id": "...", "trade_order_line_id": "...",
 *                         "order_item_status": "...", "seller_id": "...", ... } }.
 *
 * Lazada Open Platform "App Push" mô tả 2 dạng ký tuỳ region/version:
 *   (A) HMAC-SHA256(key=app_secret, message=rawBody) → hex; đặt ở header
 *       (`X-Lazop-Sign` / `Lazop-Sign` / `X-Lzd-Sign` / `X-Signature`).
 *   (B) Body có key `sign` ở top-level; HMAC-SHA256(key=app_secret, message=sorted("{k}{v}"…))
 *       trên các key còn lại; hex. (Dạng "có sẵn sign trong body" — cùng họ với ký request API.)
 * Ta thử **cả hai** ⇒ khớp 1 trong 2 là PASS. Order events kích `fetchOrderDetail` re-fetch
 * nên body chỉ là tín hiệu (rule 1 — re-fetch luôn).
 *
 * `webhook_verify_mode` (`integrations.lazada.webhook_verify_mode`):
 *   - `strict` (mặc định): sai chữ ký ⇒ false ⇒ `WebhookIngestService` trả 401 (Lazada retry).
 *   - `lenient`: sai chữ ký ⇒ verifier trả true + log; tránh kẹt event lúc scheme chưa khớp
 *     với sandbox. KHÔNG dùng cho production.
 *
 * `message_type` → WebhookEventDTO type qua `config('integrations.lazada.webhook_message_types')`;
 * unknown nhưng có order id ⇒ coi như `order_status_update`. See docs/04-channels/lazada.md §4.
 */
class LazadaWebhookVerifier
{
    /** @var array<string,mixed> */
    protected array $cfg;

    /** Tên header Lazada hay dùng cho chữ ký push (case-insensitive). `Authorization` KHÔNG dùng — bỏ. */
    private const SIG_HEADERS = ['X-Lazop-Sign', 'Lazop-Sign', 'X-Lzd-Sign', 'X-Signature'];

    public function __construct()
    {
        $this->cfg = (array) config('integrations.lazada', []);
    }

    public function verify(Request $request): bool
    {
        $secret = (string) ($this->cfg['app_secret'] ?? '');
        $mode = strtolower((string) ($this->cfg['webhook_verify_mode'] ?? 'strict'));
        if ($secret === '') {
            return $mode !== 'strict';   // không có secret thì lenient/disabled vẫn cho qua (record signature_ok=false ở caller)
        }
        $body = (string) $request->getContent();
        $ok = $this->matchesHeaderHmac($request, $secret, $body) || $this->matchesBodySign($secret, $body);
        if (! $ok && $mode !== 'strict') {
            Log::warning('lazada.webhook.signature_mismatch_but_accepted', [
                'mode' => $mode, 'body_len' => strlen($body),
                'sig_header_present' => $this->presentSigHeader($request) !== null,
                'body_has_sign_field' => is_array($j = json_decode($body, true)) && isset($j['sign']),
            ]);

            return true;
        }

        return $ok;
    }

    /** Header chứa chữ ký (lấy header đầu tiên có giá trị) — hoặc null nếu không có. */
    private function presentSigHeader(Request $request): ?string
    {
        foreach (self::SIG_HEADERS as $h) {
            if (((string) $request->headers->get($h, '')) !== '') {
                return $h;
            }
        }

        return null;
    }

    /** Dạng A: HMAC-SHA256(rawBody, app_secret) == header (so sánh không phân biệt hoa thường). */
    private function matchesHeaderHmac(Request $request, string $secret, string $body): bool
    {
        $h = $this->presentSigHeader($request);
        if ($h === null) {
            return false;
        }
        $provided = strtolower(trim((string) $request->headers->get($h)));
        $expected = strtolower(hash_hmac('sha256', $body, $secret));

        return hash_equals($expected, $provided);
    }

    /** Dạng B: body top-level có key `sign`; ký trên các key còn lại đã sort & concat `{k}{v}`. */
    private function matchesBodySign(string $secret, string $body): bool
    {
        $json = json_decode($body, true);
        if (! is_array($json) || ! isset($json['sign']) || ! is_string($json['sign'])) {
            return false;
        }
        $provided = strtolower(trim($json['sign']));
        unset($json['sign']);
        ksort($json, SORT_STRING);
        $str = '';
        foreach ($json as $k => $v) {
            $str .= $k.(is_scalar($v) ? (string) $v : (string) json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
        $expected = strtolower(hash_hmac('sha256', $str, $secret));

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
