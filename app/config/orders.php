<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Biểu phí ƯỚC TÍNH mặc định theo sàn (VN, ~06/2026 — nguồn chính chủ Shopee/
    | TikTok/Lazada). Dùng khi tenant CHƯA cấu hình `settings.platform_fee_pct[source]`
    | (giữ tương thích) — khi đó OrderProfitService dựng breakdown chi tiết từ đây.
    | Tenant có thể override từng khoản qua `settings.fee_rates[source]`.
    |
    | Cơ sở tính (xem OrderProfitService):
    |   - commission_pct  : % HOA HỒNG/phí cố định trên GIÁ HÀNG sau giảm giá seller (KHÔNG gồm ship).
    |   - transaction_pct : % PHÍ GIAO DỊCH trên TỔNG khách trả (gồm ship) — TikTok/Lazada gồm ship.
    |   - service_pct     : % PHÍ DỊCH VỤ tùy chọn (Voucher/Freeship Xtra…) trên giá hàng — mặc định 0 (seller tự bật).
    |   - fixed_fee       : phí cố định/đơn (đồng) — phí hạ tầng/xử lý đơn ~3.000đ.
    | Tất cả đã gồm VAT theo công bố của sàn. Số mặc định là ƯỚC TÍNH (hoa hồng thực tế thay đổi theo ngành hàng).
    */
    'fee_rates' => [
        'tiktok' => [
            'commission_pct' => 14.0, 'transaction_pct' => 6.0, 'service_pct' => 0.0, 'fixed_fee' => 3000,
            // Phí chương trình TÙY CHỌN — chỉ tính khi shop bật (enabled). rate do shop tự đặt theo chiến dịch.
            'programs' => [
                ['key' => 'affiliate', 'label' => 'Hoa hồng Affiliate', 'kind' => 'pct', 'rate' => 0.0, 'base' => 'item', 'enabled' => false],
                ['key' => 'sfp_service', 'label' => 'Phí dịch vụ SFP', 'kind' => 'pct', 'rate' => 0.0, 'base' => 'item', 'enabled' => false],
            ],
        ],
        'shopee' => [
            'commission_pct' => 12.5, 'transaction_pct' => 6.0, 'service_pct' => 0.0, 'fixed_fee' => 3000,
            'programs' => [
                ['key' => 'voucher_xtra', 'label' => 'Gói Voucher Xtra', 'kind' => 'pct', 'rate' => 5.5, 'cap_per_item' => 50000, 'base' => 'item', 'enabled' => false],
                ['key' => 'freeship_xtra', 'label' => 'Gói Freeship Xtra', 'kind' => 'pct', 'rate' => 7.0, 'base' => 'item', 'enabled' => false],
                ['key' => 'piship', 'label' => 'Gói PiShip', 'kind' => 'fixed', 'rate' => 2700, 'enabled' => false],
            ],
        ],
        'lazada' => [
            'commission_pct' => 4.0, 'transaction_pct' => 6.0, 'service_pct' => 0.0, 'fixed_fee' => 3000,
            'programs' => [
                ['key' => 'freeship_program', 'label' => 'Gói Freeship/Voucher', 'kind' => 'pct', 'rate' => 0.0, 'base' => 'item', 'enabled' => false],
            ],
        ],
    ],
];
