// Nhãn tiếng Việt cho các hằng số Facebook (KHÔNG hiển thị mã gốc EN ra UI).

export const CTA_LABELS: Record<string, string> = {
    MESSAGE_PAGE: 'Gửi tin nhắn',
    SEND_MESSAGE: 'Gửi tin nhắn',
    LEARN_MORE: 'Tìm hiểu thêm',
    SHOP_NOW: 'Mua ngay',
    ORDER_NOW: 'Đặt hàng',
    SIGN_UP: 'Đăng ký',
    SUBSCRIBE: 'Đăng ký',
    GET_OFFER: 'Nhận ưu đãi',
    GET_OFFER_VIEW: 'Xem ưu đãi',
    GET_QUOTE: 'Nhận báo giá',
    GET_PROMOTIONS: 'Nhận khuyến mãi',
    CONTACT_US: 'Liên hệ',
    CONTACT: 'Liên hệ',
    CALL_NOW: 'Gọi ngay',
    APPLY_NOW: 'Đăng ký ngay',
    BOOK_TRAVEL: 'Đặt ngay',
    BOOK_NOW: 'Đặt ngay',
    DOWNLOAD: 'Tải xuống',
    WHATSAPP_MESSAGE: 'Nhắn WhatsApp',
    NO_BUTTON: 'Không có nút',
};

export function ctaLabel(value?: string | null): string {
    if (value == null || value === '') return 'Không có nút';
    return CTA_LABELS[value] ?? value;
}

export interface ConversionEventOption {
    value: string;
    label: string;
}

/** Sự kiện chuyển đổi (custom_event_type) phổ biến — nhãn tiếng Việt. */
export const CONVERSION_EVENTS: ConversionEventOption[] = [
    { value: 'COMPLETE_REGISTRATION', label: 'Hoàn tất đăng ký' },
    { value: 'LEAD', label: 'Khách hàng tiềm năng' },
    { value: 'PURCHASE', label: 'Mua hàng' },
    { value: 'ADD_TO_CART', label: 'Thêm vào giỏ hàng' },
    { value: 'INITIATE_CHECKOUT', label: 'Bắt đầu thanh toán' },
    { value: 'CONTACT', label: 'Liên hệ' },
    { value: 'SUBMIT_APPLICATION', label: 'Nộp đơn/biểu mẫu' },
    { value: 'SUBSCRIBE', label: 'Đăng ký gói/dịch vụ' },
    { value: 'VIEW_CONTENT', label: 'Xem nội dung' },
    { value: 'SCHEDULE', label: 'Đặt lịch hẹn' },
];

export function conversionEventLabel(value?: string | null): string {
    return CONVERSION_EVENTS.find((e) => e.value === value)?.label ?? (value ?? '');
}
