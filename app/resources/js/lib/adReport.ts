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
    const purch = insights.purchases ?? 0;
    const leads = insights.leads ?? 0;

    if (objective != null) {
        if (MESSAGE_OBJECTIVES.has(objective)) return { ...MSG, value: conv };
        if (SALES_OBJECTIVES.has(objective)) return { ...CONV, value: purch };
        if (LEAD_OBJECTIVES.has(objective)) return { ...LEAD, value: leads };
    }
    // Unknown objective (adset/ad rows or OUTCOME_ENGAGEMENT) → first non-zero.
    if (conv > 0) return { ...MSG, value: conv };
    if (purch > 0) return { ...CONV, value: purch };
    if (leads > 0) return { ...LEAD, value: leads };
    return null;
}

/** Aggregate child rows' summable metrics into a parent total (for tree rollups). */
export function sumInsights(rows: ReportRow[]): ReportMetrics | null {
    const withData = rows.filter((r) => r.insights != null);
    if (withData.length === 0) return null;
    const acc: ReportMetrics = {
        spend: 0, impressions: 0, clicks: 0, reach: 0, ctr: null, cpc: null, cpm: null,
        frequency: null, purchase_roas: null, messaging_conversations: 0, leads: 0, purchases: 0,
    };
    for (const r of withData) {
        const i = r.insights!;
        acc.spend += i.spend; acc.impressions += i.impressions; acc.clicks += i.clicks; acc.reach += i.reach;
        acc.messaging_conversations += i.messaging_conversations; acc.leads += i.leads; acc.purchases += i.purchases;
    }
    // Derive ratios from the summed totals.
    acc.ctr = acc.impressions > 0 ? (acc.clicks / acc.impressions) * 100 : null;
    acc.cpc = acc.clicks > 0 ? Math.round(acc.spend / acc.clicks) : null;
    acc.cpm = acc.impressions > 0 ? Math.round((acc.spend / acc.impressions) * 1000) : null;
    return acc;
}
