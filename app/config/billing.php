<?php

/*
|--------------------------------------------------------------------------
| Billing — gói thuê bao + hạn mức (SPEC 0018) + over-quota lock (SPEC 0020).
|--------------------------------------------------------------------------
| Mọi giá trị runtime tinh chỉnh được qua env; KHÔNG đụng DB nếu chỉ đổi config.
*/

return [

    // SPEC 0020 — số giờ ân hạn sau khi phát hiện over-quota trước khi middleware
    // `plan.over_quota_lock` khoá mọi POST/PATCH/DELETE. 48h = 2 ngày (mặc định).
    // Đặt 0 ⇒ khoá ngay khi phát hiện (chỉ dùng trong test).
    'over_quota_grace_hours' => (int) env('BILLING_OVER_QUOTA_GRACE_HOURS', 48),

    // SPEC 0020 — danh sách resource được middleware `plan.over_quota_lock` kiểm.
    // v1 chỉ `channel_accounts`. Khi thêm cấp bậc mới, append vào đây + thêm case
    // trong `OverQuotaCheckService::limitFor()`.
    'quota_resources' => ['channel_accounts'],

    // Phiên bản điều khoản hoàn tiền hiện hành — đổi khi cập nhật nội dung điều khoản
    // (dùng để lưu vết tenant đã đồng ý bản nào khi đăng ký trải nghiệm Pro).
    'refund_terms_version' => env('BILLING_REFUND_TERMS_VERSION', 'refund-v1'),

];
