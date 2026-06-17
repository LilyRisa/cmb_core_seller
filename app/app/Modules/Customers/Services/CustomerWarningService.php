<?php

namespace CMBcoreSeller\Modules\Customers\Services;

use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerReport;

/**
 * Gộp dữ liệu cảnh báo khách cho màn tạo đơn thủ công (SPEC 0038 v2): tỷ lệ đơn
 * thành công/hoàn + danh sách cảnh báo. **Ưu tiên nội bộ, thiếu mới Pancake** —
 * có dữ liệu nội bộ (khách trong sổ hoặc đã có report) thì KHÔNG gọi Pancake.
 */
class CustomerWarningService
{
    public function __construct(private readonly CustomerBadReportService $pancake) {}

    /**
     * Cộng dồn baseline Pancake (nạp 1 lần) + dữ liệu nội bộ (đơn của khách + report).
     * Pancake CHỈ gọi API khi số hoàn toàn mới (chưa có khách); đã thành khách thì
     * chỉ đọc baseline đã nạp (không gọi lại).
     *
     * @return array{success_count:int,fail_count:int,warnings:array<int,array{reason:string,reported_at:?string,source:string}>,has_warning:bool}|null
     */
    public function buildSummary(?Customer $customer, string $phoneHash, string $phone): ?array
    {
        $reports = CustomerReport::query()->where('phone_hash', $phoneHash)
            ->orderByDesc('reported_at')->orderByDesc('id')->get();

        // Số hoàn toàn mới (chưa có khách) ⇒ nạp baseline Pancake 1 lần; đã có khách ⇒ chỉ đọc baseline đã nạp.
        $pancake = $customer === null
            ? $this->pancake->fetchOnce($phoneHash, $phone)
            : $this->pancake->cached($phoneHash);

        $pSuccess = $pancake !== null ? $pancake->orderSuccess : 0;
        $pFail = $pancake !== null ? $pancake->orderFail : 0;
        $pWarnings = $pancake !== null ? $pancake->warnings : [];

        $stats = $customer !== null ? (array) $customer->lifetime_stats : [];
        $success = (int) ($stats['orders_completed'] ?? 0) + $pSuccess;
        $fail = (int) ($stats['orders_returned'] ?? 0) + (int) ($stats['orders_delivery_failed'] ?? 0) + $pFail;

        $warnings = [];
        if ($customer !== null && $customer->is_blocked) {
            $warnings[] = [
                'reason' => $customer->block_reason ?: 'Khách đang bị chặn',
                'reported_at' => $customer->blocked_at->toIso8601String(),
                'source' => 'blocked',
            ];
        }
        foreach ($reports as $r) {
            $warnings[] = [
                'reason' => $r->reason,
                'reported_at' => $r->reported_at->toIso8601String(),
                'source' => 'internal',
            ];
        }
        foreach ($pWarnings as $w) {
            $warnings[] = ['reason' => $w['reason'], 'reported_at' => $w['reported_at'], 'source' => 'pancake'];
        }

        // Chỉ trả khi có tín hiệu (tỷ lệ hoặc cảnh báo); ngược lại không hiển thị gì.
        if ($success === 0 && $fail === 0 && $warnings === []) {
            return null;
        }

        return [
            'success_count' => $success,
            'fail_count' => $fail,
            'warnings' => $warnings,
            'has_warning' => $warnings !== [],
        ];
    }
}
