import type { ReportMetrics, ReportRow } from '@/lib/marketing';

/** The "Kết quả" Facebook shows depends on the objective: messages / conversions / leads. */
export interface ResultValue {
    label: string;   // e.g. "Tin nhắn", "Chuyển đổi", "Khách tiềm năng"
    value: number;
    color: string;
}

const MESSAGE_OBJECTIVES = new Set(['MESSAGES', 'OUTCOME_ENGAGEMENT']);
const SALES_OBJECTIVES = new Set(['OUTCOME_SALES', 'CONVERSIONS', 'PRODUCT_CATALOG_SALES']);
const LEAD_OBJECTIVES = new Set(['OUTCOME_LEADS', 'LEAD_GENERATION']);

const MSG: ResultValue = { label: 'Tin nhắn', value: 0, color: '#52c41a' };
const CONV: ResultValue = { label: 'Chuyển đổi', value: 0, color: '#722ed1' };
const LEAD: ResultValue = { label: 'Khách tiềm năng', value: 0, color: '#fa8c16' };

/** Màu nhãn "Kết quả" theo mã sự kiện (result_type) backend trả. */
const RESULT_COLOR: Record<string, string> = {
    messaging: '#52c41a',
    complete_registration: '#13c2c2',
    purchase: '#722ed1',
    lead: '#fa8c16',
    add_to_cart: '#722ed1',
    initiate_checkout: '#722ed1',
    view_content: '#1677ff',
    search: '#1677ff',
    add_to_wishlist: '#722ed1',
    link_click: '#1677ff',
    landing_page_view: '#1677ff',
    post_engagement: '#52c41a',
};

/**
 * Pick the result count + label for a row, mirroring Ads Manager's "Kết quả" column.
 * Ưu tiên giá trị backend đã tính theo đúng sự kiện tối ưu (result_label/result_type);
 * chỉ suy theo objective khi thiếu (dữ liệu cache cũ / rollup cha không có nhãn).
 */
export function resultOf(objective: string | null, insights: ReportMetrics | null): ResultValue | null {
    if (insights == null) return null;

    // Nguồn chuẩn: backend (FacebookResultMap) đã chọn đúng sự kiện + nhãn.
    if (insights.result_label != null) {
        return {
            label: insights.result_label,
            value: insights.results ?? 0,
            color: (insights.result_type && RESULT_COLOR[insights.result_type]) || CONV.color,
        };
    }

    // Fallback (không có nhãn từ backend): suy theo objective như trước.
    const conv = insights.messaging_conversations ?? 0;
    const leads = insights.leads ?? 0;
    const results = insights.results ?? 0;

    if (objective != null) {
        if (MESSAGE_OBJECTIVES.has(objective)) return { ...MSG, value: conv };
        if (SALES_OBJECTIVES.has(objective)) return { ...CONV, value: results };
        if (LEAD_OBJECTIVES.has(objective)) return { ...LEAD, value: leads };
    }
    if (results <= 0) return null;
    if (conv === results) return { ...MSG, value: conv };
    if (leads === results) return { ...LEAD, value: leads };
    return { ...CONV, value: results };
}

/** Cost per result = spend / results (null when no results). */
export function cprOf(objective: string | null, insights: ReportMetrics | null): number | null {
    const res = resultOf(objective, insights);
    if (insights == null || res == null || res.value <= 0) return null;
    return Math.round(insights.spend / res.value);
}

/** Aggregate child rows' summable metrics into a parent total (for tree rollups). */
export function sumInsights(rows: ReportRow[]): ReportMetrics | null {
    const withData = rows.filter((r) => r.insights != null);
    if (withData.length === 0) return null;
    const acc: ReportMetrics = {
        spend: 0, impressions: 0, clicks: 0, reach: 0, ctr: null, cpc: null, cpm: null,
        frequency: null, purchase_roas: null, messaging_conversations: 0, leads: 0, purchases: 0, results: 0,
        result_type: null, result_label: null,
    };
    // Nhãn "Kết quả" của cha = của con NẾU các con đồng nhất sự kiện; lệch nhau ⇒ null (FE suy theo objective).
    const types = new Set(withData.map((r) => r.insights!.result_type ?? null));
    if (types.size === 1) {
        acc.result_type = withData[0].insights!.result_type ?? null;
        acc.result_label = withData[0].insights!.result_label ?? null;
    }
    for (const r of withData) {
        const i = r.insights!;
        acc.spend += i.spend; acc.impressions += i.impressions; acc.clicks += i.clicks; acc.reach += i.reach;
        acc.messaging_conversations += i.messaging_conversations; acc.leads += i.leads; acc.purchases += i.purchases;
        acc.results += i.results;
    }
    // Derive ratios from the summed totals.
    acc.ctr = acc.impressions > 0 ? (acc.clicks / acc.impressions) * 100 : null;
    acc.cpc = acc.clicks > 0 ? Math.round(acc.spend / acc.clicks) : null;
    acc.cpm = acc.impressions > 0 ? Math.round((acc.spend / acc.impressions) * 1000) : null;
    return acc;
}
