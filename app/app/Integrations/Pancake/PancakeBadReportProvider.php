<?php

namespace CMBcoreSeller\Integrations\Pancake;

use CMBcoreSeller\Modules\Customers\Contracts\BadReportData;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerBadReportProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tra cứu báo cáo "bom hàng" từ Pancake POS (SPEC 0038):
 *   GET {base}/shops/{shop_id}/orders/bad_report_info?access_token=..&phone_number=..
 *
 * Cấu hình GLOBAL (toàn hệ thống): `shop_id` + `access_token` super-admin khai ở
 * /admin/settings (system_setting), fallback config/env. Hằng số kỹ thuật ở
 * `config/integrations.php:pancake`.
 *
 * FAIL-SOFT tuyệt đối: mọi lỗi (tắt/thiếu credential/HTTP/timeout/parse) ⇒ trả
 * `null`, log warning — KHÔNG bao giờ ném để chặn việc tra cứu khách / tạo đơn.
 * Đối chiếu số qua 9 chữ số cuối; không log access_token / số điện thoại thô.
 */
class PancakeBadReportProvider implements CustomerBadReportProvider
{
    /** @param array<string,mixed> $config config('integrations.pancake') */
    public function __construct(private readonly array $config) {}

    public function lookup(string $phone): ?BadReportData
    {
        if (! (bool) $this->setting('enabled', false)) {
            return null;
        }
        $shopId = trim((string) $this->setting('shop_id', ''));
        $token = trim((string) $this->setting('access_token', ''));
        if ($shopId === '' || $token === '') {
            return null;
        }

        $queryPhone = $this->toPancakePhone($phone);

        try {
            $base = rtrim((string) ($this->config['api_base_url'] ?? 'https://pos.pancake.vn/api/v1'), '/');
            // Pancake POS xác thực bằng query `api_key` (KHÔNG phải `access_token` — token gửi qua
            // access_token bị từ chối error_code 102). Đã verify thực tế shop 1720000852.
            $resp = $this->http()->get($base.'/shops/'.rawurlencode($shopId).'/orders/bad_report_info', [
                'api_key' => $token,
                'phone_number' => $queryPhone,
            ]);

            if (! $resp->successful()) {
                Log::warning('pancake.bad_report.http_error', ['status' => $resp->status()]);

                return null;
            }
            $json = (array) ($resp->json() ?? []);
            if (($json['success'] ?? false) !== true) {
                Log::warning('pancake.bad_report.unsuccessful', ['status' => $resp->status()]);

                return null;
            }

            return $this->map((array) ($json['data'] ?? []), $queryPhone);
        } catch (Throwable $e) {
            Log::warning('pancake.bad_report.exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Map response Pancake → DTO chuẩn. Không khớp số ⇒ {@see BadReportData::clean()}
     * (gọi thành công nhưng sạch) để lớp cache phân biệt với lỗi.
     *
     * @param  array<string,mixed>  $data
     */
    private function map(array $data, string $queryPhone): BadReportData
    {
        $tail = $this->tail($queryPhone);

        // reports_by_phone: { "+84..": {order_fail, order_success, warning} }
        $report = null;
        foreach ((array) ($data['reports_by_phone'] ?? []) as $key => $row) {
            if ($this->tail((string) $key) === $tail) {
                $report = (array) $row;
                break;
            }
        }

        // warning_phone_number: [{reason, inserted_at, phone_number, ...}] — chỉ lấy reason + ngày tạo.
        $warnings = [];
        foreach ((array) ($data['warning_phone_number'] ?? []) as $w) {
            $w = (array) $w;
            if ($this->tail((string) ($w['phone_number'] ?? '')) !== $tail) {
                continue;
            }
            $reason = trim((string) ($w['reason'] ?? ''));
            if ($reason === '') {
                continue;
            }
            $warnings[] = [
                'reason' => $reason,
                'reported_at' => ($w['inserted_at'] ?? null) ? (string) $w['inserted_at'] : null,
            ];
        }

        if ($report === null && $warnings === []) {
            return BadReportData::clean($queryPhone);
        }

        return new BadReportData(
            orderFail: (int) ($report['order_fail'] ?? 0),
            orderSuccess: (int) ($report['order_success'] ?? 0),
            warningCount: (int) ($report['warning'] ?? 0),
            warnings: $warnings,
            matchedPhone: $queryPhone,
            matched: true,
        );
    }

    protected function http(): PendingRequest
    {
        $http = (array) ($this->config['http'] ?? []);

        return Http::timeout((int) ($http['timeout'] ?? 15))
            ->connectTimeout((int) ($http['connect_timeout'] ?? 8))
            ->retry((int) ($http['retries'] ?? 1), (int) ($http['retry_sleep_ms'] ?? 500), throw: false)
            ->acceptJson();
    }

    /** Admin (system_setting) ưu tiên, fallback config (env). */
    private function setting(string $key, mixed $default): mixed
    {
        return system_setting('integrations.pancake.'.$key, $this->config[$key] ?? $default);
    }

    /** Số chuẩn hoá nội bộ (0xxxxxxxxx) → dạng Pancake nhận (+84xxxxxxxxx). */
    private function toPancakePhone(string $normalized): string
    {
        if (str_starts_with($normalized, '0') && strlen($normalized) === 10) {
            return '+84'.substr($normalized, 1);
        }

        return $normalized; // số quốc tế đã ở dạng +<digits>, hoặc best-effort
    }

    /** 9 chữ số cuối (phần thuê bao) để đối chiếu bất kể tiền tố 0/84/+84. */
    private function tail(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return strlen($digits) <= 9 ? $digits : substr($digits, -9);
    }
}
