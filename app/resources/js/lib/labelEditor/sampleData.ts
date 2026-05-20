import type { DataKey, SampleProfile } from '@/lib/shippingLabelTypes';

export type SampleContext = Record<DataKey | 'items_count', string> & { items: Array<{ name: string; sku: string | null; qty: number }> };

export const SAMPLE_DATA: Record<SampleProfile, SampleContext> = {
    one_item_short_address: {
        carrier_logo: 'GHN', carrier_name: 'GIAO HÀNG NHANH',
        sender_name: 'Shop CMBcore', sender_phone: '0901234567', sender_address: '12 Lê Lợi, Q1, TP.HCM',
        recipient_name: 'Nguyễn Văn A', recipient_phone: '0911111111',
        recipient_address: '50 Hai Bà Trưng, Bến Nghé, Q1, TP.HCM',
        recipient_address_detail: '50 Hai Bà Trưng', recipient_address_admin: 'Bến Nghé, Q1, TP.HCM',
        order_number: 'M-2026-001', tracking_no: 'AWB-SHORT-77',
        cod: '—', weight: '250g', print_note: '', created_at: '18/05/2026 09:00',
        total_qty: '1', items_count: '1',
        items: [{ name: 'Bút bi xanh', sku: 'BB-X', qty: 1 }],
    },
    three_items_long_address: {
        carrier_logo: 'GHN', carrier_name: 'GIAO HÀNG NHANH',
        sender_name: 'Shop CMBcore', sender_phone: '0901234567', sender_address: '123 Đường Lê Lợi Nối Dài, Bến Nghé, Quận 1, TP.HCM',
        recipient_name: 'Trần Thị Hoa Hồng Phương Lan', recipient_phone: '0987654321',
        recipient_address: '456/12 Đường Nguyễn Trãi, Phường 7, Quận 5, TP.HCM',
        recipient_address_detail: '456/12 Đường Nguyễn Trãi', recipient_address_admin: 'Phường 7, Quận 5, TP.HCM',
        order_number: 'M-2026-002', tracking_no: 'AWB-LONG-1234567',
        cod: '450.000 đ', weight: '800g', print_note: 'Đóng gói cẩn thận, hàng dễ vỡ',
        created_at: '18/05/2026 10:30', total_qty: '5', items_count: '3',
        items: [
            { name: 'Áo thun nam basic màu đen size L', sku: 'AT-BLK-L', qty: 2 },
            { name: 'Quần short kaki', sku: 'QS-01', qty: 1 },
            { name: 'Nón lưỡi trai', sku: null, qty: 2 },
        ],
    },
    cod_with_print_note: {
        carrier_logo: 'GHTK', carrier_name: 'GIAO HÀNG TIẾT KIỆM',
        sender_name: 'Shop CMBcore', sender_phone: '0901234567', sender_address: '12 Lê Lợi, Q1, TP.HCM',
        recipient_name: 'Lê Văn C', recipient_phone: '0912345678',
        recipient_address: '78 Nguyễn Huệ, Bến Nghé, Q1, TP.HCM',
        recipient_address_detail: '78 Nguyễn Huệ', recipient_address_admin: 'Bến Nghé, Q1, TP.HCM',
        order_number: 'M-2026-003', tracking_no: 'AWB-COD-555',
        cod: '500.000 đ', weight: '300g',
        print_note: 'Cảm ơn quý khách! Đổi/trả 7 ngày kèm hộp nguyên seal. Hotline: 0901234567',
        created_at: '18/05/2026 11:00', total_qty: '1', items_count: '1',
        items: [{ name: 'Đồng hồ thông minh', sku: 'SW-1', qty: 1 }],
    },
};
