<?php

namespace CMBcoreSeller\Modules\Settings\Support;

use InvalidArgumentException;

/**
 * Spec 2026-05-17 — single source of truth cho mọi setting mà super-admin có
 * thể quản lý qua UI `/admin/settings`. Key NGOÀI catalog không bao giờ được
 * lưu vào `system_settings` (controller chặn) và `system_setting()` cũng bỏ
 * qua khi đọc (luôn trả default).
 *
 * Cấu trúc một entry:
 *   - group: 'branding' | 'marketplace' | 'fulfillment' | 'sync'
 *   - type:  'string' | 'int' | 'bool' | 'float' | 'json'
 *   - is_secret: bool — true ⇒ encrypt khi store, mask khi GET, reveal có audit
 *   - env: tên biến .env tương ứng (dùng cho seed lần đầu)
 *   - label: tiêu đề hiển thị UI
 *   - description?: hint hiển thị dưới label
 *
 * Tổng: 39 key — 7 branding, 9 marketplace (6 secret), 16 fulfillment (2 secret),
 * 7 sync. ⇒ 8 secret.
 *
 * Key core KHÔNG cho vào catalog (giữ env tuyệt đối): APP_KEY, APP_ENV, DB_*,
 * REDIS_*, SESSION_*, SANCTUM_STATEFUL_DOMAINS, BCRYPT_ROUNDS, MAIL_HOST/USER/
 * PASS/PORT/SCHEME, AWS_* (S3 internal), SENTRY_LARAVEL_DSN, BROADCAST/QUEUE/
 * CACHE drivers, INTEGRATIONS_CHANNELS.
 */
class SystemSettingsCatalog
{
    /** @return array<string, array{group:string,type:string,is_secret:bool,env:string,label:string,description?:string}> */
    public static function all(): array
    {
        return [
            // ── Branding (7) ────────────────────────────────────────────────
            'notifications.brand_name' => [
                'group' => 'branding', 'type' => 'string', 'is_secret' => false,
                'env' => 'NOTIFICATIONS_BRAND_NAME', 'label' => 'Tên thương hiệu',
            ],
            'notifications.brand_tagline' => [
                'group' => 'branding', 'type' => 'string', 'is_secret' => false,
                'env' => 'NOTIFICATIONS_BRAND_TAGLINE', 'label' => 'Tagline',
            ],
            'notifications.support_email' => [
                'group' => 'branding', 'type' => 'string', 'is_secret' => false,
                'env' => 'NOTIFICATIONS_SUPPORT_EMAIL', 'label' => 'Email hỗ trợ',
            ],
            'notifications.primary_color' => [
                'group' => 'branding', 'type' => 'string', 'is_secret' => false,
                'env' => 'NOTIFICATIONS_PRIMARY_COLOR', 'label' => 'Màu chính (hex)',
            ],
            'notifications.accent_color' => [
                'group' => 'branding', 'type' => 'string', 'is_secret' => false,
                'env' => 'NOTIFICATIONS_ACCENT_COLOR', 'label' => 'Màu nhấn (hex)',
            ],
            'mail.from_address' => [
                'group' => 'branding', 'type' => 'string', 'is_secret' => false,
                'env' => 'MAIL_FROM_ADDRESS', 'label' => 'Email gửi từ',
            ],
            'mail.from_name' => [
                'group' => 'branding', 'type' => 'string', 'is_secret' => false,
                'env' => 'MAIL_FROM_NAME', 'label' => 'Tên người gửi (mail)',
            ],

            // ── Marketplace (11) ────────────────────────────────────────────
            'marketplace.tiktok.app_key' => [
                'group' => 'marketplace', 'type' => 'string', 'is_secret' => true,
                'env' => 'TIKTOK_APP_KEY', 'label' => 'TikTok App Key',
            ],
            'marketplace.tiktok.app_secret' => [
                'group' => 'marketplace', 'type' => 'string', 'is_secret' => true,
                'env' => 'TIKTOK_APP_SECRET', 'label' => 'TikTok App Secret',
            ],
            'marketplace.tiktok.service_id' => [
                'group' => 'marketplace', 'type' => 'string', 'is_secret' => false,
                'env' => 'TIKTOK_SERVICE_ID', 'label' => 'TikTok Service ID',
            ],
            'marketplace.tiktok.sandbox' => [
                'group' => 'marketplace', 'type' => 'bool', 'is_secret' => false,
                'env' => 'TIKTOK_SANDBOX', 'label' => 'TikTok Sandbox',
            ],
            'marketplace.lazada.app_key' => [
                'group' => 'marketplace', 'type' => 'string', 'is_secret' => true,
                'env' => 'LAZADA_APP_KEY', 'label' => 'Lazada App Key',
            ],
            'marketplace.lazada.app_secret' => [
                'group' => 'marketplace', 'type' => 'string', 'is_secret' => true,
                'env' => 'LAZADA_APP_SECRET', 'label' => 'Lazada App Secret',
            ],
            'marketplace.lazada.sandbox' => [
                'group' => 'marketplace', 'type' => 'bool', 'is_secret' => false,
                'env' => 'LAZADA_SANDBOX', 'label' => 'Lazada Sandbox',
            ],
            'marketplace.shopee.partner_id' => [
                'group' => 'marketplace', 'type' => 'string', 'is_secret' => true,
                'env' => 'SHOPEE_PARTNER_ID', 'label' => 'Shopee Partner ID',
            ],
            'marketplace.shopee.partner_key' => [
                'group' => 'marketplace', 'type' => 'string', 'is_secret' => true,
                'env' => 'SHOPEE_PARTNER_KEY', 'label' => 'Shopee Partner Key',
            ],
            'marketplace.shopee.push_partner_key' => [
                'group' => 'marketplace', 'type' => 'string', 'is_secret' => true,
                'env' => 'SHOPEE_PUSH_PARTNER_KEY', 'label' => 'Shopee Push Partner Key (webhook)',
            ],
            'marketplace.shopee.sandbox' => [
                'group' => 'marketplace', 'type' => 'bool', 'is_secret' => false,
                'env' => 'SHOPEE_SANDBOX', 'label' => 'Shopee Sandbox',
            ],

            // ── Fulfillment & storage (15) ──────────────────────────────────
            'fulfillment.deduct_on' => [
                'group' => 'fulfillment', 'type' => 'string', 'is_secret' => false,
                'env' => 'FULFILLMENT_DEDUCT_ON', 'label' => 'Thời điểm trừ tồn',
                'description' => '`shipped` (trừ khi giao) hoặc `created` (trừ khi tạo).',
            ],
            'fulfillment.default_weight_grams' => [
                'group' => 'fulfillment', 'type' => 'int', 'is_secret' => false,
                'env' => 'FULFILLMENT_DEFAULT_WEIGHT_GRAMS', 'label' => 'Cân nặng mặc định (g)',
            ],
            'fulfillment.tiktok_arrange_shipment' => [
                'group' => 'fulfillment', 'type' => 'bool', 'is_secret' => false,
                'env' => 'INTEGRATIONS_TIKTOK_FULFILLMENT', 'label' => 'TikTok arrange-shipment',
            ],
            'fulfillment.print_label_size' => [
                'group' => 'fulfillment', 'type' => 'string', 'is_secret' => false,
                'env' => 'PRINT_LABEL_SIZE', 'label' => 'Khổ tem in mặc định',
            ],
            'fulfillment.expose_technical_errors' => [
                'group' => 'fulfillment', 'type' => 'bool', 'is_secret' => false,
                'env' => 'FULFILLMENT_EXPOSE_TECHNICAL_ERRORS', 'label' => 'Hiện chi tiết lỗi kỹ thuật',
                'description' => 'Bật để hiện thông tin lỗi kỹ thuật khi xử lý đơn (debug). Prod nên TẮT — chỉ hiện câu tiếng Việt.',
            ],
            'carriers.enabled_csv' => [
                'group' => 'fulfillment', 'type' => 'string', 'is_secret' => false,
                'env' => 'INTEGRATIONS_CARRIERS', 'label' => 'ĐVVC đã bật (CSV)',
            ],
            'carriers.default' => [
                'group' => 'fulfillment', 'type' => 'string', 'is_secret' => false,
                'env' => 'INTEGRATIONS_DEFAULT_CARRIER', 'label' => 'ĐVVC mặc định',
            ],
            'carriers.ghn.base_url' => [
                'group' => 'fulfillment', 'type' => 'string', 'is_secret' => false,
                'env' => 'GHN_BASE_URL', 'label' => 'GHN base URL',
            ],
            'storage.media_disk' => [
                'group' => 'fulfillment', 'type' => 'string', 'is_secret' => false,
                'env' => 'MEDIA_DISK', 'label' => 'Disk media (public | r2)',
            ],
            'storage.media_image_max_kb' => [
                'group' => 'fulfillment', 'type' => 'int', 'is_secret' => false,
                'env' => 'MEDIA_IMAGE_MAX_KB', 'label' => 'Giới hạn ảnh upload (KB)',
            ],
            'storage.r2.bucket' => [
                'group' => 'fulfillment', 'type' => 'string', 'is_secret' => false,
                'env' => 'R2_BUCKET', 'label' => 'R2 bucket',
            ],
            'storage.r2.endpoint' => [
                'group' => 'fulfillment', 'type' => 'string', 'is_secret' => false,
                'env' => 'R2_ENDPOINT', 'label' => 'R2 endpoint',
            ],
            'storage.r2.public_url' => [
                'group' => 'fulfillment', 'type' => 'string', 'is_secret' => false,
                'env' => 'R2_URL', 'label' => 'R2 public URL',
            ],
            'storage.r2.access_key_id' => [
                'group' => 'fulfillment', 'type' => 'string', 'is_secret' => true,
                'env' => 'R2_ACCESS_KEY_ID', 'label' => 'R2 Access Key',
            ],
            'storage.r2.secret_access_key' => [
                'group' => 'fulfillment', 'type' => 'string', 'is_secret' => true,
                'env' => 'R2_SECRET_ACCESS_KEY', 'label' => 'R2 Secret Key',
            ],
            'pdf.gotenberg_url' => [
                'group' => 'fulfillment', 'type' => 'string', 'is_secret' => false,
                'env' => 'GOTENBERG_URL', 'label' => 'Gotenberg URL',
            ],

            // ── Sync / throttle / billing (7) ───────────────────────────────
            'throttle.tiktok_per_min' => [
                'group' => 'sync', 'type' => 'int', 'is_secret' => false,
                'env' => 'THROTTLE_TIKTOK_PER_MIN', 'label' => 'Throttle TikTok (req/phút)',
            ],
            'throttle.shopee_per_min' => [
                'group' => 'sync', 'type' => 'int', 'is_secret' => false,
                'env' => 'THROTTLE_SHOPEE_PER_MIN', 'label' => 'Throttle Shopee (req/phút)',
            ],
            'throttle.lazada_per_min' => [
                'group' => 'sync', 'type' => 'int', 'is_secret' => false,
                'env' => 'THROTTLE_LAZADA_PER_MIN', 'label' => 'Throttle Lazada (req/phút)',
            ],
            'sync.poll_interval_minutes' => [
                'group' => 'sync', 'type' => 'int', 'is_secret' => false,
                'env' => 'SYNC_POLL_INTERVAL_MINUTES', 'label' => 'Poll interval (phút)',
            ],
            'sync.poll_overlap_minutes' => [
                'group' => 'sync', 'type' => 'int', 'is_secret' => false,
                'env' => 'SYNC_POLL_OVERLAP_MINUTES', 'label' => 'Poll overlap (phút)',
            ],
            'sync.backfill_days' => [
                'group' => 'sync', 'type' => 'int', 'is_secret' => false,
                'env' => 'SYNC_BACKFILL_DAYS', 'label' => 'Backfill (ngày)',
            ],
            'billing.over_quota_grace_hours' => [
                'group' => 'sync', 'type' => 'int', 'is_secret' => false,
                'env' => 'BILLING_OVER_QUOTA_GRACE_HOURS', 'label' => 'Over-quota grace (giờ)',
            ],

            // ── Push notifications / Web Push (3, 1 secret) ─────────────────
            'push.vapid_public_key' => [
                'group' => 'push', 'type' => 'string', 'is_secret' => false,
                'env' => 'VAPID_PUBLIC_KEY', 'label' => 'VAPID Public Key',
                'description' => 'Khoá công khai Web Push — FE dùng để subscribe. Tạo cặp khoá bằng `web-push generate-vapid-keys` hoặc trang VAPID generator.',
            ],
            'push.vapid_private_key' => [
                'group' => 'push', 'type' => 'string', 'is_secret' => true,
                'env' => 'VAPID_PRIVATE_KEY', 'label' => 'VAPID Private Key',
            ],
            'push.vapid_subject' => [
                'group' => 'push', 'type' => 'string', 'is_secret' => false,
                'env' => 'VAPID_SUBJECT', 'label' => 'VAPID Subject',
                'description' => 'mailto:admin@domain hoặc URL trang — định danh người gửi push.',
            ],
        ];
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    /** @return array{group:string,type:string,is_secret:bool,env:string,label:string,description?:string} */
    public static function require(string $key): array
    {
        $all = self::all();
        if (! isset($all[$key])) {
            throw new InvalidArgumentException("Key [{$key}] is not in SystemSettingsCatalog.");
        }

        return $all[$key];
    }

    public static function validate(string $key, mixed $value): bool
    {
        $meta = self::require($key);

        return match ($meta['type']) {
            'string' => is_string($value) && strlen($value) <= 4096,
            'int' => is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1),
            'float' => is_int($value) || is_float($value) || (is_string($value) && is_numeric($value)),
            'bool' => is_bool($value) || in_array(strtolower((string) $value), ['true', 'false', '0', '1'], true),
            'json' => is_array($value) || (is_string($value) && json_validate($value)),
            default => false,
        };
    }
}
