<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering;

/**
 * Build DataContext mẫu cho preview PDF (không cần đơn thật) và golden snapshot test.
 */
class SampleDataFactory
{
    public const PROFILES = ['one_item_short_address', 'three_items_long_address', 'cod_with_print_note'];

    public function build(string $profile = 'one_item_short_address'): DataContext
    {
        return match ($profile) {
            'three_items_long_address' => new DataContext(
                'M-2026-002', 'AWB-LONG-1234567', 'ghn',
                'Shop CMBcore', '0901234567',
                '123 Đường Lê Lợi Nối Dài, Bến Nghé, Quận 1, TP.HCM',
                'Trần Thị Hoa Hồng Phương Lan',
                '0987654321',
                '456/12 Đường Nguyễn Trãi, Phường 7, Quận 5, TP.HCM',
                '456/12 Đường Nguyễn Trãi',
                'Phường 7, Quận 5, TP.HCM',
                450000, 800, 5, 'Đóng gói cẩn thận, hàng dễ vỡ',
                '18/05/2026 10:30',
                [
                    ['name' => 'Áo thun nam basic màu đen size L', 'sku' => 'AT-BLK-L', 'qty' => 2],
                    ['name' => 'Quần short kaki', 'sku' => 'QS-01', 'qty' => 1],
                    ['name' => 'Nón lưỡi trai', 'sku' => null, 'qty' => 2],
                ]
            ),
            'cod_with_print_note' => new DataContext(
                'M-2026-003', 'AWB-COD-555', 'ghtk',
                'Shop CMBcore', '0901234567', '12 Lê Lợi, Q1, TP.HCM',
                'Lê Văn C', '0912345678',
                '78 Nguyễn Huệ, Bến Nghé, Q1, TP.HCM',
                '78 Nguyễn Huệ', 'Bến Nghé, Q1, TP.HCM',
                500000, 300, 1,
                'Cảm ơn quý khách! Đổi/trả 7 ngày kèm hộp nguyên seal. Hotline: 0901234567',
                '18/05/2026 11:00',
                [['name' => 'Đồng hồ thông minh', 'sku' => 'SW-1', 'qty' => 1]]
            ),
            default => new DataContext(
                'M-2026-001', 'AWB-SHORT-77', 'ghn',
                'Shop CMBcore', '0901234567', '12 Lê Lợi, Q1, TP.HCM',
                'Nguyễn Văn A', '0911111111',
                '50 Hai Bà Trưng, Bến Nghé, Q1, TP.HCM',
                '50 Hai Bà Trưng', 'Bến Nghé, Q1, TP.HCM',
                0, 250, 1, '', '18/05/2026 09:00',
                [['name' => 'Bút bi xanh', 'sku' => 'BB-X', 'qty' => 1]]
            ),
        };
    }
}
