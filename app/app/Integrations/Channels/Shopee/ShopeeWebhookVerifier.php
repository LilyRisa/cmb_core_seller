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
        // Push được ký bằng "Push Partner Key" (nếu Shopee cấp riêng); fallback partner_key. DB (admin) đè env.
        $pushKey = (string) (
            system_setting('marketplace.shopee.push_partner_key', $cfg['push_partner_key'] ?? null)
            ?: system_setting('marketplace.shopee.partner_key', $cfg['partner_key'] ?? null)
            ?: ''
        );
        $pushUrl = (string) ($cfg['push_url'] ?? url('/webhook/shopee'));
        $raw = $request->getContent();
        $provided = trim((string) $request->headers->get('Authorization', ''));
        if ($pushKey === '' || $provided === '') {
            if ((string) ($cfg['webhook_verify_mode'] ?? 'strict') === 'lenient') {
                Log::warning('shopee.webhook.signature_mismatch_but_accepted', ['mode' => 'lenient', 'has_header' => $provided !== '']);

                return true;
            }
            Log::warning('shopee.webhook.push_key_not_configured');

            return false;
        }
        $expected = hash_hmac('sha256', $pushUrl.'|'.$raw, $pushKey);
        $ok = hash_equals($expected, strtolower($provided));
        if (! $ok) {
            // TEMP DIAGNOSTIC (gỡ sau khi fix): thử mọi ứng viên key xem cái nào Shopee thực sự ký.
            // KHÔNG log secret — chỉ log fingerprint key + cờ khớp. provided/expected là MAC, không bí mật.
            $partnerKey = (string) (system_setting('marketplace.shopee.partner_key', $cfg['partner_key'] ?? null) ?: '');
            $base = $pushUrl.'|'.$raw;
            $cands = [
                'push_key_asis' => $pushKey,
                'partner_key_asis' => $partnerKey,
                'push_key_hex2bin' => (ctype_xdigit($pushKey) && strlen($pushKey) % 2 === 0) ? hex2bin($pushKey) : null,
                'partner_key_hex2bin' => (ctype_xdigit($partnerKey) && strlen($partnerKey) % 2 === 0) ? hex2bin($partnerKey) : null,
            ];
            $matches = [];
            foreach ($cands as $name => $k) {
                if ($k === null || $k === '') {
                    continue;
                }
                $matches[$name] = hash_equals(hash_hmac('sha256', $base, $k), strtolower($provided));
            }
            Log::warning('shopee.webhook.signature_keytest', [
                'push_url' => $pushUrl,
                'raw_len' => strlen($raw),
                'raw_sha' => substr(hash('sha256', $raw), 0, 12),
                'provided' => substr(strtolower($provided), 0, 16),
                'push_key_fp' => substr(hash('sha256', $pushKey), 0, 8),
                'partner_key_fp' => substr(hash('sha256', $partnerKey), 0, 8),
                'match' => $matches, // cái nào = true chính là key/định dạng Shopee dùng
            ]);
        }
        if (! $ok && (string) ($cfg['webhook_verify_mode'] ?? 'strict') === 'lenient') {
            Log::warning('shopee.webhook.signature_mismatch_but_accepted', ['mode' => 'lenient', 'has_header' => $provided !== '']);

            return true;
        }

        return $ok;
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
