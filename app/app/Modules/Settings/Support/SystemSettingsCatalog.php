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
 *   - group: 'ai' | 'branding' | 'fulfillment' | 'growth' | 'mail' | 'marketplace' | 'push' | 'sync'
 *   - type:  'string' | 'int' | 'bool' | 'float' | 'json'
 *   - is_secret: bool — true ⇒ encrypt khi store. ([TIKTOK-REVIEW-TEMP] Admin index hiển thị clear, không che —
 *     theo yêu cầu chủ dự án; reveal endpoint vẫn còn để truy vết audit khi cần.)
 *   - env: tên biến .env tương ứng (dùng cho seed lần đầu)
 *   - label: tiêu đề hiển thị UI
 *   - description?: hint hiển thị dưới label
 *
 * Nhóm: ai, branding, fulfillment, growth, mail (email/SMTP), marketplace, push, sync.
 * Email (mail.*) được SettingsServiceProvider::boot() override vào config('mail.*') lúc boot (fallback env).
 *
 * Key core KHÔNG cho vào catalog (giữ env tuyệt đối): APP_KEY, APP_ENV, DB_*,
 * REDIS_*, SESSION_*, SANCTUM_STATEFUL_DOMAINS, BCRYPT_ROUNDS, MAIL_URL/
 * MAIL_EHLO_DOMAIN, AWS_* (S3 internal), SENTRY_LARAVEL_DSN, BROADCAST/QUEUE/
 * CACHE drivers, INTEGRATIONS_CHANNELS.
 */
class SystemSettingsCatalog
{
    /** @return array<string, array{group:string,type:string,is_secret:bool,env:string,label:string,description?:string}> */
    public static function all(): array
    {
        return [
            // ── Branding (5) ────────────────────────────────────────────────
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

            // ── Email / SMTP (8, 1 secret) ──────────────────────────────────
            // Cấu hình email động: SettingsServiceProvider::boot() override config('mail.*') từ các key này
            // (fallback env). Sai cấu hình ⇒ email KHÔNG gửi được (không làm hỏng app) — xoá setting để về env.
            'mail.from_address' => [
                'group' => 'mail', 'type' => 'string', 'is_secret' => false,
                'env' => 'MAIL_FROM_ADDRESS', 'label' => 'Email gửi từ (from address)',
            ],
            'mail.from_name' => [
                'group' => 'mail', 'type' => 'string', 'is_secret' => false,
                'env' => 'MAIL_FROM_NAME', 'label' => 'Tên người gửi (from name)',
            ],
            'mail.mailer' => [
                'group' => 'mail', 'type' => 'string', 'is_secret' => false,
                'env' => 'MAIL_MAILER', 'label' => 'Mailer',
                'description' => '`smtp` để gửi thật qua SMTP, `log` để chỉ ghi log (không gửi).',
            ],
            'mail.host' => [
                'group' => 'mail', 'type' => 'string', 'is_secret' => false,
                'env' => 'MAIL_HOST', 'label' => 'SMTP host',
            ],
            'mail.port' => [
                'group' => 'mail', 'type' => 'int', 'is_secret' => false,
                'env' => 'MAIL_PORT', 'label' => 'SMTP port',
            ],
            'mail.username' => [
                'group' => 'mail', 'type' => 'string', 'is_secret' => false,
                'env' => 'MAIL_USERNAME', 'label' => 'SMTP username',
            ],
            'mail.password' => [
                'group' => 'mail', 'type' => 'string', 'is_secret' => true,
                'env' => 'MAIL_PASSWORD', 'label' => 'SMTP password',
            ],
            'mail.scheme' => [
                'group' => 'mail', 'type' => 'string', 'is_secret' => false,
                'env' => 'MAIL_SCHEME', 'label' => 'SMTP scheme (tls/ssl)',
                'description' => 'Để trống nếu không mã hoá tường minh; thường `tls` (587) hoặc `ssl` (465).',
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

            // ── Pancake POS — báo cáo "bom hàng" (SPEC 0038) ────────────────
            // Cấu hình GLOBAL dùng chung để bù đắp dữ liệu khách khi tạo đơn thủ công.
            'integrations.pancake.enabled' => [
                'group' => 'marketplace', 'type' => 'bool', 'is_secret' => false,
                'env' => 'PANCAKE_ENABLED', 'label' => 'Pancake POS — Bật tra cứu bom hàng',
                'description' => 'Bật để khi tạo đơn thủ công tự tra số điện thoại khách qua Pancake POS (cảnh báo bom hàng).',
            ],
            'integrations.pancake.shop_id' => [
                'group' => 'marketplace', 'type' => 'string', 'is_secret' => false,
                'env' => 'PANCAKE_SHOP_ID', 'label' => 'Pancake POS — Shop ID',
                'description' => 'Mã shop trên Pancake POS (vd 1720000852).',
            ],
            'integrations.pancake.access_token' => [
                'group' => 'marketplace', 'type' => 'string', 'is_secret' => false,
                'env' => 'PANCAKE_ACCESS_TOKEN', 'label' => 'Pancake POS — API Key',
                'description' => 'API key của shop trên Pancake POS (gửi qua query api_key — KHÔNG phải access_token).',
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
            'carriers.ghtk.base_url' => [
                'group' => 'fulfillment', 'type' => 'string', 'is_secret' => false,
                'env' => 'GHTK_BASE_URL', 'label' => 'GHTK base URL',
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

            // ── Sync / throttle / billing (11) ──────────────────────────────
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
            'billing.pro_trial.enabled' => [
                'group' => 'sync', 'type' => 'bool', 'is_secret' => false,
                'env' => 'BILLING_PRO_TRIAL_ENABLED', 'label' => 'Chế độ trải nghiệm Pro — Bật',
                'description' => 'Bật để thành viên (cũ & mới) tự đăng ký trải nghiệm gói Pro. Mỗi tenant chỉ 1 lần vĩnh viễn.',
            ],
            'billing.pro_trial.duration_days' => [
                'group' => 'sync', 'type' => 'int', 'is_secret' => false,
                'env' => 'BILLING_PRO_TRIAL_DURATION_DAYS', 'label' => 'Trải nghiệm Pro — Số ngày',
                'description' => 'Thời lượng mỗi tenant được dùng Pro trải nghiệm (mặc định 30).',
            ],
            'billing.pro_trial.window_start' => [
                'group' => 'sync', 'type' => 'string', 'is_secret' => false,
                'env' => 'BILLING_PRO_TRIAL_WINDOW_START', 'label' => 'Trải nghiệm Pro — Mở từ (YYYY-MM-DD)',
                'description' => 'Ngày bắt đầu mở đăng ký. Trống = không giới hạn cạnh này.',
            ],
            'billing.pro_trial.window_end' => [
                'group' => 'sync', 'type' => 'string', 'is_secret' => false,
                'env' => 'BILLING_PRO_TRIAL_WINDOW_END', 'label' => 'Trải nghiệm Pro — Đóng đến (YYYY-MM-DD)',
                'description' => 'Ngày kết thúc mở đăng ký. Trống = không giới hạn cạnh này.',
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

            // ── AI (3) ──────────────────────────────────────────────────────
            'messaging.ai.system_prompt' => [
                'group' => 'ai', 'type' => 'string', 'is_secret' => false,
                'env' => 'MESSAGING_AI_SYSTEM_PROMPT', 'label' => 'Prompt chung cho AI (chèn trước khi gửi)',
                'description' => 'Chỉ dẫn bổ sung ghép vào SAU persona CSKH mặc định, áp cho MỌI AI provider khi sinh câu trả lời. '
                    .'KHÔNG áp cho bước phân loại ý định (giữ guardrail). Giới hạn 4096 byte (~1.500 ký tự tiếng Việt).',
            ],
            // ── Trợ lý Hỏi AI (Support, SPEC-0028) — credentials RIÊNG, TỰ CHỨA ─
            // KHÔNG dùng bảng ai_providers/registry. CHAT (sinh câu trả lời) + EMBEDDING
            // (tạo vector RAG) cấu hình độc lập → chat=OpenRouter, embedding=OpenAI được.
            // Cấu hình ở /admin/ai-support.
            'help_assistant.chat_base_url' => [
                'group' => 'ai', 'type' => 'string', 'is_secret' => false,
                'env' => 'HELP_ASSISTANT_BASE_URL', 'label' => 'Hỏi AI — Chat: Base URL',
                'description' => 'GỐC host OpenAI-compatible cho CHAT (vd https://openrouter.ai/api). KHÔNG kèm /v1.',
            ],
            'help_assistant.chat_api_key' => [
                'group' => 'ai', 'type' => 'string', 'is_secret' => true,
                'env' => 'HELP_ASSISTANT_API_KEY', 'label' => 'Hỏi AI — Chat: API key',
                'description' => 'API key cho provider chat (mã hoá khi lưu).',
            ],
            'help_assistant.chat_model' => [
                'group' => 'ai', 'type' => 'string', 'is_secret' => false,
                'env' => 'HELP_ASSISTANT_MODEL', 'label' => 'Hỏi AI — Chat: Model',
                'description' => 'Model chat (vd google/gemini-2.0-flash-lite-001 cho OpenRouter).',
            ],
            'help_assistant.embedding_base_url' => [
                'group' => 'ai', 'type' => 'string', 'is_secret' => false,
                'env' => 'HELP_ASSISTANT_EMBEDDING_BASE_URL', 'label' => 'Hỏi AI — Embedding: Base URL',
                'description' => 'GỐC host cho EMBEDDING (vd https://api.openai.com hoặc https://openrouter.ai/api). '
                    .'Để trống ⇒ tắt vector, chạy keyword. Phải dùng MODEL embedding hợp lệ của provider đó '
                    .'(vd openai/text-embedding-3-small trên OpenRouter, text-embedding-3-small trên OpenAI).',
            ],
            'help_assistant.embedding_api_key' => [
                'group' => 'ai', 'type' => 'string', 'is_secret' => true,
                'env' => 'HELP_ASSISTANT_EMBEDDING_API_KEY', 'label' => 'Hỏi AI — Embedding: API key',
                'description' => 'API key cho provider embedding (mã hoá khi lưu).',
            ],
            'help_assistant.embedding_model' => [
                'group' => 'ai', 'type' => 'string', 'is_secret' => false,
                'env' => 'HELP_ASSISTANT_EMBEDDING_MODEL', 'label' => 'Hỏi AI — Embedding: Model',
                'description' => 'Model embedding (vd text-embedding-3-small). Đổi model ⇒ chạy lại `php artisan help:index --fresh`.',
            ],

            // ── Visual re-rank (SPEC 2026-07-05) — provider AI RIÊNG cho bước chấm ảnh ─
            // Rỗng ⇒ fallback provider chat. Trỏ tới `code` một provider trong ai_providers.
            // Cấu hình ở /admin/ai-visual-rerank.
            'visual_search.rerank.provider_code' => [
                'group' => 'ai', 'type' => 'string', 'is_secret' => false,
                'env' => 'VISUAL_SEARCH_RERANK_PROVIDER_CODE', 'label' => 'AI chấm ảnh — Provider',
                'description' => 'Code provider AI dùng cho bước chấm ảnh (vision re-rank). Rỗng ⇒ dùng model chat.',
            ],

            // ── Transcribe ghi âm (STT, SPEC 2026-07-05) — provider AI RIÊNG cho voice khách ─
            // Rỗng ⇒ tắt (job TranscribeInboundAudio no-op). Trỏ tới `code` một provider
            // OpenAI-compatible trong ai_providers (vd Groq whisper).
            'messaging.transcription.provider_code' => [
                'group' => 'ai', 'type' => 'string', 'is_secret' => false,
                'env' => 'MESSAGING_TRANSCRIPTION_PROVIDER_CODE', 'label' => 'AI chuyển giọng nói — Provider',
                'description' => 'Code provider AI (OpenAI-compatible, vd Groq whisper) để transcribe ghi âm khách. Rỗng ⇒ tắt.',
            ],

            // ── Tăng trưởng — Facebook Pixel + Conversions API (SPEC 2026-07-22) ──
            // Nhúng Pixel ở app.blade.php (mọi trang) + báo CompleteRegistration lúc đăng ký
            // (Tenancy\Listeners\ReportSignupToMetaCapi). KHÔNG dùng .env ở prod — admin nhập
            // tay qua /admin/settings (tab "Tăng trưởng").
            'growth.facebook.enabled' => [
                'group' => 'growth', 'type' => 'bool', 'is_secret' => false,
                'env' => 'FACEBOOK_PIXEL_ENABLED', 'label' => 'Facebook Pixel — Bật',
                'description' => 'Bật để nhúng Pixel vào mọi trang + gửi sự kiện CompleteRegistration qua Conversions API.',
            ],
            'growth.facebook.pixel_id' => [
                'group' => 'growth', 'type' => 'string', 'is_secret' => false,
                'env' => 'FACEBOOK_PIXEL_ID', 'label' => 'Facebook Pixel ID',
            ],
            'growth.facebook.capi_access_token' => [
                'group' => 'growth', 'type' => 'string', 'is_secret' => true,
                'env' => 'FACEBOOK_CAPI_ACCESS_TOKEN', 'label' => 'Conversions API — Access Token',
            ],
            'growth.facebook.test_event_code' => [
                'group' => 'growth', 'type' => 'string', 'is_secret' => false,
                'env' => 'FACEBOOK_CAPI_TEST_EVENT_CODE', 'label' => 'Conversions API — Test Event Code',
                'description' => 'Điền tạm khi soi ở tab "Test Events" trên Meta Events Manager, xoá khi chạy thật.',
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
