/** Nhãn tính năng gói (tiếng Việt) — dùng chung cho bảng giá public + trong dashboard. SPEC 2026-06-26. */
export const PLAN_FEATURE_LABELS: { key: string; label: string }[] = [
    { key: 'mass_listing', label: 'Đăng bán đa sàn' },
    { key: 'messaging_inbox', label: 'Nhắn tin Facebook Page + sàn' },
    { key: 'messaging_zalo', label: 'Nhắn tin Zalo OA' },
    { key: 'messaging_ai', label: 'AI hỗ trợ trả lời tin nhắn' },
    { key: 'marketing_facebook', label: 'Quảng cáo Facebook' },
    { key: 'marketing_tiktok', label: 'Quảng cáo TikTok' },
    { key: 'shop_health_reports', label: 'Báo cáo sàn (sức khỏe / điểm phạt)' },
    { key: 'ai', label: 'Trợ lý & phân tích AI' },
    { key: 'accounting_basic', label: 'Kế toán cơ bản' },
    { key: 'accounting_advanced', label: 'Kế toán nâng cao' },
    { key: 'procurement', label: 'Mua hàng & nhà cung cấp' },
    { key: 'fifo_cogs', label: 'Giá vốn FIFO' },
    { key: 'profit_reports', label: 'Báo cáo lợi nhuận' },
    { key: 'finance_settlements', label: 'Đối soát sàn' },
    { key: 'demand_planning', label: 'Đề xuất nhập hàng' },
    { key: 'automation_rules', label: 'Tự động hoá' },
    { key: 'priority_support', label: 'Hỗ trợ ưu tiên' },
    { key: 'einvoice', label: 'Hóa đơn điện tử' },
];

/** Tính năng nền tảng luôn có ở mọi gói (kể cả miễn phí). */
export const PLAN_BASE_FEATURES: string[] = [
    'Đồng bộ đơn hàng đa sàn',
    'Quản lý tồn kho master SKU',
    'Tạo đơn thủ công',
    'Xử lý đơn & in phiếu / tem',
    'Đẩy đơn vị vận chuyển (GHN, GHTK, ViettelPost, J&T)',
];

/** Liệt kê tính năng (tiếng Việt) bật trong 1 gói — base + feature flags true. */
export function planFeatureList(features: Record<string, unknown> | unknown[] | undefined): string[] {
    const f = (features ?? {}) as Record<string, unknown>;
    const flagged = Array.isArray(features) ? [] : PLAN_FEATURE_LABELS.filter((r) => f[r.key]).map((r) => r.label);
    return [...PLAN_BASE_FEATURES, ...flagged];
}
