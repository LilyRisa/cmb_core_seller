/**
 * Danh sách biến mẫu tin dùng chung (editor chip + gợi ý). Nhãn tiếng Việt để
 * người dùng không phải nhìn cú pháp `{{buyer.name}}`. Khoá (`key`) khớp với
 * context do backend dựng (TemplateContextBuilder) — xem
 * app/Modules/Messaging/Services/TemplateContextBuilder.php.
 */
export interface TemplateVar {
    /** Cú pháp lưu trong body, vd `{{buyer.name}}`. */
    token: string;
    /** Khoá dotted, vd `buyer.name`. */
    key: string;
    /** Nhãn tiếng Việt hiển thị trên chip. */
    label: string;
}

export const TEMPLATE_VARS: TemplateVar[] = [
    { token: '{{buyer.name}}', key: 'buyer.name', label: 'Tên người mua' },
    { token: '{{shop.name}}', key: 'shop.name', label: 'Tên shop / trang' },
    { token: '{{customer.name}}', key: 'customer.name', label: 'Tên khách hàng' },
    { token: '{{customer.phone}}', key: 'customer.phone', label: 'SĐT khách' },
    { token: '{{customer.reputation}}', key: 'customer.reputation', label: 'Đánh giá khách' },
    { token: '{{order.code}}', key: 'order.code', label: 'Mã đơn hàng' },
    { token: '{{order.status}}', key: 'order.status', label: 'Trạng thái đơn' },
    { token: '{{order.total}}', key: 'order.total', label: 'Tổng tiền đơn' },
];

const LABEL_BY_KEY: Record<string, string> = Object.fromEntries(TEMPLATE_VARS.map((v) => [v.key, v.label]));

/** Nhãn tiếng Việt cho 1 khoá biến; undefined nếu không phải biến đã biết. */
export function labelForVarKey(key: string): string | undefined {
    return LABEL_BY_KEY[key];
}
