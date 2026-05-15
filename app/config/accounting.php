<?php

/*
 |--------------------------------------------------------------------------
 | Module Accounting — config gốc (Phase 7+ / SPEC 0019).
 |--------------------------------------------------------------------------
 |
 | Phần lớn quyết định là per-tenant qua bảng `accounting_post_rules` (UI Settings).
 | Config ở đây chỉ là *system defaults* — không phải nơi tenant chỉnh.
 */

return [
    // Cho phép PeriodService::resolveForDate tự tạo period tháng cách hiện tại bao nhiêu tháng.
    // Bảo vệ chống nhập sai date cách 100 năm.
    'auto_create_periods_back_months' => (int) env('ACCOUNTING_AUTO_PERIODS_BACK', 24),
    'auto_create_periods_forward_months' => (int) env('ACCOUNTING_AUTO_PERIODS_FORWARD', 24),

    // Chính sách lưu trữ chứng từ kế toán (Luật Kế toán: tối thiểu 10 năm).
    // Phase 7+ — chưa implement archive, ghi sẵn để policy doc reference.
    'retention_years' => 10,
];
