import type { ReportLevel } from '@/lib/marketing';

/**
 * Formatter & metadata báo cáo quảng cáo — TRUNG TÍNH provider (Facebook/TikTok).
 * Dùng chung cho MarketingDashboardPage (Facebook) và TikTokAdsDashboardPage.
 * KHÔNG chứa logic if-provider hay thao tác ghi — chỉ trình bày thuần.
 */

export function money(v: number | null | undefined, currency: string | null): string {
    if (v == null) return '—';
    return v.toLocaleString('vi-VN') + (currency ? ' ' + currency : '');
}
export const num = (v: number | null | undefined) => (v == null ? '—' : v.toLocaleString('vi-VN'));
export const pct = (v: number | null | undefined) => (v == null ? '—' : v.toFixed(2) + '%');
export const dec = (v: number | null | undefined) => (v == null ? '—' : v.toFixed(2));

export const LABELS: Record<ReportLevel, string> = { campaign: 'Chiến dịch', adset: 'Nhóm quảng cáo', ad: 'Quảng cáo' };

// Tất cả cột có thể bật/tắt (cột tên luôn hiển thị).
export const ALL_COLUMNS = [
    'external_id', 'status', 'objective', 'result', 'cpr', 'daily_budget', 'lifetime_budget',
    'spend', 'impressions', 'reach', 'clicks', 'ctr', 'cpc', 'cpm', 'frequency',
    'purchase_roas', 'messaging_conversations', 'leads', 'purchases',
] as const;
export const DEFAULT_COLUMNS = ['status', 'objective', 'result', 'cpr', 'daily_budget', 'spend', 'impressions', 'clicks', 'ctr', 'cpc', 'purchase_roas'];
export const COL_TITLE: Record<string, string> = {
    external_id: 'ID', status: 'Trạng thái', objective: 'Mục tiêu', result: 'Kết quả', cpr: 'CP/Kết quả', daily_budget: 'NS/ngày', lifetime_budget: 'NS trọn đời',
    spend: 'Chi tiêu', impressions: 'Hiển thị', reach: 'Tiếp cận', clicks: 'Click', ctr: 'CTR', cpc: 'CPC', cpm: 'CPM',
    frequency: 'Tần suất', purchase_roas: 'ROAS', messaging_conversations: 'Hội thoại', leads: 'Leads', purchases: 'Chuyển đổi',
};

// Giải thích chỉ số (tooltip khi di chuột vào icon "?").
export const COL_HELP: Record<string, string> = {
    objective: 'Mục tiêu tối ưu của chiến dịch — nền tảng phân phối theo mục tiêu này.',
    status: 'Trạng thái phân phối hiện tại của quảng cáo.',
    daily_budget: 'Số tiền tối đa chi cho mỗi ngày.',
    lifetime_budget: 'Số tiền tối đa cho toàn bộ thời gian chạy.',
    spend: 'Tổng số tiền đã chi cho quảng cáo.',
    impressions: 'Số lần quảng cáo được hiển thị (tính cả lặp lại).',
    reach: 'Số người dùng (không trùng) đã nhìn thấy quảng cáo.',
    clicks: 'Số lượt nhấp vào quảng cáo.',
    ctr: 'Tỷ lệ nhấp = Click ÷ Hiển thị (%). Cao nghĩa là nội dung hấp dẫn.',
    cpc: 'Chi phí trung bình mỗi lượt nhấp = Chi tiêu ÷ Click.',
    cpm: 'Chi phí cho mỗi 1.000 lần hiển thị.',
    frequency: 'Số lần trung bình một người nhìn thấy quảng cáo.',
    purchase_roas: 'Lợi nhuận trên chi tiêu quảng cáo = Doanh thu ÷ Chi tiêu.',
    messaging_conversations: 'Số cuộc hội thoại bắt đầu từ quảng cáo.',
    leads: 'Số khách hàng tiềm năng (lead) thu được.',
    purchases: 'Số lượt mua hàng/chuyển đổi ghi nhận.',
    result: 'Kết quả chính theo mục tiêu: tin nhắn, chuyển đổi, hoặc khách tiềm năng.',
    cpr: 'Chi phí trên mỗi kết quả = Chi tiêu ÷ Kết quả.',
};

// Chuẩn hoá mục tiêu (raw → tiếng Việt). Gồm mục tiêu Facebook (ODAX + cũ) và TikTok.
export const OBJECTIVE_VI: Record<string, string> = {
    OUTCOME_SALES: 'Bán hàng', OUTCOME_LEADS: 'Khách hàng tiềm năng', OUTCOME_ENGAGEMENT: 'Tương tác',
    OUTCOME_AWARENESS: 'Nhận diện thương hiệu', OUTCOME_TRAFFIC: 'Truy cập web', OUTCOME_APP_PROMOTION: 'Quảng bá ứng dụng',
    LINK_CLICKS: 'Lượt truy cập', CONVERSIONS: 'Chuyển đổi', POST_ENGAGEMENT: 'Tương tác bài viết',
    PAGE_LIKES: 'Thích Trang', MESSAGES: 'Tin nhắn', LEAD_GENERATION: 'Thu thập KH tiềm năng',
    REACH: 'Tiếp cận', BRAND_AWARENESS: 'Nhận diện thương hiệu', VIDEO_VIEWS: 'Lượt xem video',
    PRODUCT_CATALOG_SALES: 'Bán theo danh mục', STORE_VISITS: 'Ghé cửa hàng', APP_INSTALLS: 'Cài đặt ứng dụng',
    // TikTok objective_type
    TRAFFIC: 'Truy cập', WEB_CONVERSIONS: 'Chuyển đổi web', PRODUCT_SALES: 'Bán hàng', ENGAGEMENT: 'Tương tác',
    RF_REACH: 'Tiếp cận (R&F)', APP_PROMOTION: 'Quảng bá ứng dụng', LEAD_GENERATION_CLICK: 'Thu thập KH tiềm năng',
};
export const objectiveVi = (v: string | null) => (v ? OBJECTIVE_VI[v] ?? v : '—');

// Chuẩn hoá trạng thái (raw → tiếng Việt + màu Tag). Gồm Facebook và TikTok (ENABLE/DISABLE).
export const STATUS_VI: Record<string, { label: string; color: string }> = {
    ACTIVE: { label: 'Đang chạy', color: 'green' },
    PAUSED: { label: 'Tạm dừng', color: 'default' },
    CAMPAIGN_PAUSED: { label: 'Chiến dịch tạm dừng', color: 'default' },
    ADSET_PAUSED: { label: 'Nhóm tạm dừng', color: 'default' },
    DELETED: { label: 'Đã xoá', color: 'red' },
    ARCHIVED: { label: 'Đã lưu trữ', color: 'default' },
    PENDING_REVIEW: { label: 'Chờ duyệt', color: 'gold' },
    IN_PROCESS: { label: 'Đang xử lý', color: 'blue' },
    PREAPPROVED: { label: 'Đã duyệt sơ bộ', color: 'blue' },
    DISAPPROVED: { label: 'Bị từ chối', color: 'red' },
    WITH_ISSUES: { label: 'Có vấn đề', color: 'orange' },
    PENDING_BILLING_INFO: { label: 'Chờ thông tin thanh toán', color: 'gold' },
    // TikTok operation_status
    ENABLE: { label: 'Đang chạy', color: 'green' },
    DISABLE: { label: 'Tạm dừng', color: 'default' },
};
// TikTok secondary_status: dạng <CAMPAIGN|ADGROUP|AD>_STATUS_<TOKEN>. Map theo TOKEN cuối
// (vd ADGROUP_STATUS_DISABLE → "Tạm dừng") để không hiển thị enum thô.
const TIKTOK_SECONDARY: Record<string, { label: string; color: string }> = {
    DELIVERY_OK: { label: 'Đang chạy', color: 'green' },
    LIVE_OK: { label: 'Đang chạy', color: 'green' },
    ENABLE: { label: 'Đang chạy', color: 'green' },
    DISABLE: { label: 'Tạm dừng', color: 'default' },
    CAMPAIGN_DISABLE: { label: 'Chiến dịch tạm dừng', color: 'default' },
    ADGROUP_DISABLE: { label: 'Nhóm tạm dừng', color: 'default' },
    DELETE: { label: 'Đã xoá', color: 'red' },
    NOT_START: { label: 'Chưa chạy', color: 'default' },
    TIME_DONE: { label: 'Đã kết thúc', color: 'default' },
    NO_DELIVERY: { label: 'Không phân phối', color: 'orange' },
    AUDIT: { label: 'Chờ duyệt', color: 'gold' },
    REAUDIT: { label: 'Đang duyệt lại', color: 'gold' },
    AUDIT_DENY: { label: 'Bị từ chối', color: 'red' },
    REJECT: { label: 'Bị từ chối', color: 'red' },
    BUDGET_EXCEED: { label: 'Hết ngân sách', color: 'orange' },
    BALANCE_EXCEED: { label: 'Hết số dư', color: 'orange' },
    FROZEN: { label: 'Bị đóng băng', color: 'red' },
};

export const statusVi = (v: string | null): { label: string; color: string } => {
    if (!v) return { label: '—', color: 'default' };
    if (STATUS_VI[v]) return STATUS_VI[v];
    const m = v.match(/^(?:CAMPAIGN|ADGROUP|AD)_STATUS_(.+)$/);
    if (m && TIKTOK_SECONDARY[m[1]]) return TIKTOK_SECONDARY[m[1]];
    return { label: v, color: 'default' };
};
