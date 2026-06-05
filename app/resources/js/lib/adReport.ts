import type { ReportMetrics, ReportRow } from '@/lib/marketing';

/** The "Kết quả" Facebook shows depends on the objective: messages / conversions / leads. */
export interface ResultValue {
    label: string;   // e.g. "Tin nhắn", "Chuyển đổi", "Khách tiềm năng"
    value: number;
    color: string;
}

const MESSAGE_OBJECTIVES = new Set(['MESSAGES']);
const SALES_OBJECTIVES = new Set(['OUTCOME_SALES', 'CONVERSIONS', 'PRODUCT_CATALOG_SALES']);
const LEAD_OBJECTIVES = new Set(['OUTCOME_LEADS', 'LEAD_GENERATION']);

const MSG: ResultValue = { label: 'Tin nhắn', value: 0, color: '#52c41a' };
const CONV: ResultValue = { label: 'Chuyển đổi', value: 0, color: '#722ed1' };
const LEAD: ResultValue = { label: 'Khách tiềm năng', value: 0, color: '#fa8c16' };

/**
 * Pick the result count + label for a row, mirroring Ads Manager's "Kết quả" column.
 * Objective decides the type when known (campaign rows); otherwise the first
 * non-zero conversion metric wins (so adset/ad rows still show something useful).
 */
export function resultOf(objective: string | null, insights: ReportMetrics | null): ResultValue | null {
    if (insights == null) return null;
    const conv = insights.messaging_conversations ?? 0;
    const leads = insights.leads ?? 0;
    // `results` is the connector's primary conversion (purchase → lead → registration
    // → add-to-cart → checkout → messaging), so conversion campaigns aren't shown as 0.
    const results = insights.results ?? 0;

    if (objective != null) {
        if (MESSAGE_OBJECTIVES.has(objective)) return { ...MSG, value: conv };
        if (SALES_OBJECTIVES.has(objective)) return { ...CONV, value: results };
        if (LEAD_OBJECTIVES.has(objective)) return { ...LEAD, value: leads };
    }
    // Unknown objective (adset/ad rows or OUTCOME_ENGAGEMENT) → label by the metric
    // that matches the primary result.
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
    };
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
