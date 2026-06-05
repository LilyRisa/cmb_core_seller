<?php

namespace CMBcoreSeller\Modules\Marketing\Support;

/**
 * Maps Facebook ad-account `account_status` + `disable_reason` codes to a
 * human-readable Vietnamese label + severity (ok|warning|error), so the dashboard
 * can surface disabled / unsettled-payment / policy-violation accounts.
 *
 * @see https://developers.facebook.com/docs/marketing-api/reference/ad-account
 */
final class AccountHealth
{
    /** account_status → [label, severity]. */
    private const STATUS = [
        1 => ['Đang hoạt động', 'ok'],
        2 => ['Bị vô hiệu hoá', 'error'],
        3 => ['Chưa thanh toán (nợ phí)', 'error'],
        7 => ['Đang xem xét rủi ro', 'warning'],
        8 => ['Chờ quyết toán', 'warning'],
        9 => ['Trong thời gian gia hạn', 'warning'],
        100 => ['Chờ đóng tài khoản', 'error'],
        101 => ['Đã đóng', 'error'],
        201 => ['Đang hoạt động', 'ok'],
        202 => ['Đã đóng', 'error'],
    ];

    /** disable_reason → VN label (only when the account is disabled). */
    private const DISABLE_REASON = [
        1 => 'Vi phạm chính sách quảng cáo',
        2 => 'Đang xem xét vi phạm sở hữu trí tuệ',
        3 => 'Rủi ro thanh toán',
        4 => 'Tài khoản bị đóng (gray account)',
        5 => 'Đang xem xét AFC',
        6 => 'Xem xét tính toàn vẹn doanh nghiệp',
        7 => 'Đóng vĩnh viễn',
        8 => 'Tài khoản đại lý không dùng',
        9 => 'Tài khoản không hoạt động',
    ];

    /**
     * @return array{label:string, severity:string, ok:bool}|null null when status unknown
     */
    public static function describe(?int $accountStatus, ?int $disableReason): ?array
    {
        if ($accountStatus === null) {
            return null;
        }
        [$label, $severity] = self::STATUS[$accountStatus] ?? ['Trạng thái #'.$accountStatus, 'warning'];

        $reason = ($disableReason !== null && $disableReason > 0) ? (self::DISABLE_REASON[$disableReason] ?? null) : null;
        if ($reason !== null) {
            $label .= ' — '.$reason;
        }

        return ['label' => $label, 'severity' => $severity, 'ok' => $severity === 'ok'];
    }
}
