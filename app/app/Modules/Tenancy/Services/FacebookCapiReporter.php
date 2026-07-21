<?php

namespace CMBcoreSeller\Modules\Tenancy\Services;

use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Báo sự kiện `CompleteRegistration` về Meta Conversions API khi tenant mới đăng ký
 * (SPEC 2026-07-22-facebook-pixel-capi-growth-attribution-design.md).
 *
 * Xác minh qua tài liệu chính chủ Meta (Playwright, 2026-07-22):
 *   - Endpoint: POST https://graph.facebook.com/v25.0/{pixel_id}/events, body {data:[...]},
 *     `access_token` gửi kèm trong body (cùng convention với FacebookPageConnector::reportPurchase
 *     đã có trong Messaging module).
 *   - `em` (email) bắt buộc hash SHA-256(lowercase(trim(email))), dạng list<string>.
 *   - Dedup với Pixel qua cặp (event_id, event_name) khớp `fbq('track', name, data, {eventID})`.
 *
 * Cấu hình 100% qua `system_setting('growth.facebook.*')` (KHÔNG đọc .env) — chưa cấu hình
 * (tắt hoặc thiếu pixel_id/token) ⇒ no-op, trả false. Idempotent qua
 * `tenant->acquisition['capi_reported_at']`. Best-effort: lỗi HTTP chỉ log warning, không throw
 * (không được làm hỏng luồng đăng ký).
 */
class FacebookCapiReporter
{
    private const GRAPH_VERSION = 'v25.0';

    public function reportCompleteRegistration(Tenant $tenant, string $email): bool
    {
        if (! (bool) system_setting('growth.facebook.enabled', false)) {
            return false;
        }
        $pixelId = (string) system_setting('growth.facebook.pixel_id', '');
        $token = (string) system_setting('growth.facebook.capi_access_token', '');
        if ($pixelId === '' || $token === '') {
            return false;
        }

        $acquisition = (array) ($tenant->acquisition ?? []);
        if (! empty($acquisition['capi_reported_at'])) {
            return true; // đã gửi trước đó — idempotent no-op.
        }

        $eventId = (string) (($acquisition['event_id'] ?? null) ?: 'tenant-'.$tenant->getKey());
        $event = [
            'event_name' => 'CompleteRegistration',
            'event_time' => $tenant->created_at->getTimestamp(),
            'event_id' => $eventId,
            'event_source_url' => (string) (($acquisition['landing_page'] ?? null) ?: config('app.url')),
            'action_source' => 'website',
            'user_data' => array_filter([
                'em' => [hash('sha256', mb_strtolower(trim($email)))],
                'client_ip_address' => $acquisition['ip'] ?? null,
                'client_user_agent' => $acquisition['user_agent'] ?? null,
                'fbp' => $acquisition['fbp'] ?? null,
                'fbc' => $acquisition['fbc'] ?? null,
            ], fn ($v) => $v !== null),
        ];

        $payload = ['data' => [$event], 'access_token' => $token];
        $testCode = (string) system_setting('growth.facebook.test_event_code', '');
        if ($testCode !== '') {
            $payload['test_event_code'] = $testCode;
        }

        $res = Http::post('https://graph.facebook.com/'.self::GRAPH_VERSION."/{$pixelId}/events", $payload);

        if (! $res->successful()) {
            Log::warning('tenancy.growth.fb_capi_report.failed', [
                'tenant_id' => $tenant->getKey(), 'status' => $res->status(), 'body' => $res->body(),
            ]);

            return false;
        }

        $tenant->forceFill([
            'acquisition' => array_merge($acquisition, ['capi_reported_at' => now()->toIso8601String()]),
        ])->save();

        return true;
    }
}
