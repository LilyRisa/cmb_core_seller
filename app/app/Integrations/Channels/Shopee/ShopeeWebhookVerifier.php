<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shopee push verification + parsing. Signature: Authorization header == HMAC-SHA256(push_url|raw_body, push_key).
 * `push_key` = Shopee "Push Partner Key" (RIÊNG với partner_key API; lấy ở Push Mechanism), fallback partner_key
 * khi không cấu hình. Đọc qua system_setting (admin /admin/system-settings đè env). Body:
 * { code:int, shop_id:int, timestamp:int, data: "<json-string>" }. See docs/04-channels/shopee.md §4.
 */
class ShopeeWebhookVerifier
{
    public function verify(Request $request): bool
    {
        $cfg = (array) config('integrations.shopee', []);
        $mode = strtolower((string) ($cfg['webhook_verify_mode'] ?? 'strict'));
        $pushKey = $this->resolveKey($cfg);
        $provided = strtolower(trim((string) $request->headers->get('Authorization', '')));

        if ($pushKey === '' || $provided === '') {
            if ($pushKey === '') {
                Log::warning('shopee.webhook.push_key_not_configured');
            }

            return $this->resolve($mode, ['reason' => $pushKey === '' ? 'no_key' : 'no_header', 'has_header' => $provided !== '']);
        }

        // Shopee Push Mechanism: Authorization = hex(HMAC-SHA256(push_key, url . '|' . rawBody)), trong đó `url`
        // là URL công khai mà Shopee gửi tới (= URL đã khai trong console). Sau reverse proxy, `push_url` cấu
        // hình / `$request->url()` có thể lệch scheme/host ⇒ thử nhiều ứng viên URL, khớp 1 cái là PASS.
        // (Đây là root cause của `shopee.webhook.signature_mismatch` — scheme http nội bộ vs https công khai.)
        $raw = $request->getContent();
        $urls = $this->candidateUrls($request, $cfg);
        foreach ($urls as $u) {
            if (hash_equals(hash_hmac('sha256', $u.'|'.$raw, $pushKey), $provided)) {
                return true;
            }
        }

        return $this->resolve($mode, [
            'has_header' => true,
            'body_len' => strlen($raw),
            'request_url' => $request->getUri(),         // URL nội bộ Laravel thấy — đối chiếu với URL khai ở console
            'urls_tried' => $urls,                       // public, không phải secret — giúp operator chỉnh SHOPEE_PUSH_URL
            'push_partner_key_set' => (string) (system_setting('marketplace.shopee.push_partner_key', $cfg['push_partner_key'] ?? null) ?: '') !== '',
        ]);
    }

    /** Push key = "Push Partner Key" (Shopee cấp riêng); fallback partner_key. DB (admin) đè env. */
    private function resolveKey(array $cfg): string
    {
        return (string) (
            system_setting('marketplace.shopee.push_partner_key', $cfg['push_partner_key'] ?? null)
            ?: system_setting('marketplace.shopee.partner_key', $cfg['partner_key'] ?? null)
            ?: ''
        );
    }

    /**
     * Các URL ứng viên để dựng chữ ký: push_url cấu hình + URL của chính request (full & không query),
     * cộng biến thể https (proxy thường hạ http nội bộ). Dedup, bỏ rỗng.
     *
     * @param  array<string,mixed>  $cfg
     * @return list<string>
     */
    private function candidateUrls(Request $request, array $cfg): array
    {
        $configured = (string) (system_setting('marketplace.shopee.push_url', $cfg['push_url'] ?? null) ?: '');
        $fullUri = $request->getUri();                 // scheme://host/path[?query]
        $noQuery = explode('?', $fullUri, 2)[0];        // Shopee push thường không kèm query, nhưng phòng hờ
        $base = array_values(array_filter([
            $configured,
            $fullUri,
            $noQuery,
        ], fn ($u) => $u !== ''));
        $all = $base;
        foreach ($base as $u) {
            if (str_starts_with($u, 'http://')) {
                $all[] = 'https://'.substr($u, 7);   // Shopee khai URL https; reconstruct nội bộ có thể là http
            }
        }

        return array_values(array_unique($all));
    }

    /**
     * Strict ⇒ false (caller trả 401, Shopee retry). Lenient ⇒ true + log (tránh kẹt khi chưa chốt URL/secret).
     *
     * @param  array<string,mixed>  $ctx
     */
    private function resolve(string $mode, array $ctx): bool
    {
        if ($mode === 'lenient') {
            Log::warning('shopee.webhook.signature_mismatch_but_accepted', ['mode' => 'lenient'] + $ctx);

            return true;
        }
        Log::warning('shopee.webhook.signature_invalid', ['mode' => 'strict'] + $ctx);

        return false;
    }

    public function parse(Request $request): WebhookEventDTO
    {
        $body = (array) ($request->json()?->all() ?? json_decode($request->getContent() ?: '[]', true) ?? []);
        $code = (int) ($body['code'] ?? -1);
        $map = (array) config('integrations.shopee.webhook_event_types', []);
        $type = (string) ($map[$code] ?? WebhookEventDTO::TYPE_UNKNOWN);

        $data = $body['data'] ?? [];
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }
        $orderSn = isset($data['ordersn']) ? (string) $data['ordersn'] : (isset($data['order_sn']) ? (string) $data['order_sn'] : null);

        return new WebhookEventDTO(
            provider: 'shopee',
            type: $type,
            externalShopId: isset($body['shop_id']) ? (string) $body['shop_id'] : null,
            externalOrderId: $orderSn,
            occurredAt: isset($body['timestamp']) ? CarbonImmutable::createFromTimestamp((int) $body['timestamp']) : null,
            orderRawStatus: isset($data['status']) ? (string) $data['status'] : null,
            raw: $body,
        );
    }
}
