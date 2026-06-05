import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

/**
 * Facebook Ads (Marketing) — near-real-time insights dashboard (SPEC 2026-06-04).
 * All calls go through /api/v1/marketing/* via the tenant-scoped axios client.
 */
function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export interface AdAccount {
    id: number;
    provider: string;
    business_id: string | null;
    business_name: string | null;
    business_picture_url: string | null;
    external_account_id: string;
    name: string | null;
    currency: string | null;
    status: string;
    fb_account_status: number | null;
    disable_reason: number | null;
    health: { label: string; severity: 'ok' | 'warning' | 'error'; ok: boolean } | null;
    health_checked_at: string | null;
    last_synced_at: string | null;
    insights_synced_at: string | null;
}

export type ReportLevel = 'campaign' | 'adset' | 'ad';

export interface ReportMetrics {
    spend: number;
    impressions: number;
    clicks: number;
    reach: number;
    ctr: number | null;
    cpc: number | null;
    cpm: number | null;
    frequency: number | null;
    purchase_roas: number | null;
    messaging_conversations: number;
    leads: number;
    purchases: number;
    results: number;
}

export interface ReportRow {
    id: number;
    external_id: string;
    parent_id: string | null;
    name: string | null;
    status: string | null;
    effective_status: string | null;
    objective: string | null;
    daily_budget: number | null;
    lifetime_budget: number | null;
    insights: ReportMetrics | null;
}

export interface ReportFilters {
    campaign_ids?: string[];
    adset_ids?: string[];
    q?: string;
    objective?: string;
    ad_id?: string;
}

export function useAdReport(accountId: number | null, level: ReportLevel, since: string, until: string, filters: ReportFilters = {}) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['marketing', 'report', accountId, level, since, until, filters, tenantId],
        enabled: api != null && accountId != null,
        queryFn: async () => {
            const p = new URLSearchParams({ level, since, until });
            (filters.campaign_ids ?? []).forEach((v) => p.append('campaign_ids[]', v));
            (filters.adset_ids ?? []).forEach((v) => p.append('adset_ids[]', v));
            if (filters.q) p.set('q', filters.q);
            if (filters.objective) p.set('objective', filters.objective);
            if (filters.ad_id) p.set('ad_id', filters.ad_id);
            return (await api!.get<{ data: { level: ReportLevel; currency: string | null; rows: ReportRow[] } }>(`/marketing/ad-accounts/${accountId}/report?${p.toString()}`)).data.data;
        },
    });
}

export interface AdInsight {
    window: string;
    date_start: string;
    date_stop: string;
    is_finalizing: boolean;
    spend: number;
    impressions: number;
    clicks: number;
    reach: number;
    ctr: number | null;
    cpc: number | null;
    cpm: number | null;
    frequency: number | null;
    purchase_roas: number | null;
    fetched_at: string | null;
}

export interface AdEntityRow {
    id: number;
    level: 'campaign' | 'adset' | 'ad';
    external_id: string;
    parent_id: number | null;
    name: string | null;
    status: string | null;
    effective_status: string | null;
    daily_budget: number | null;
    lifetime_budget: number | null;
    insights: AdInsight | null;
}

export interface AdInsightsResponse {
    account: {
        id: number;
        name: string | null;
        currency: string | null;
        status: string;
        insights_synced_at: string | null;
        insights: AdInsight | null;
    };
    entities: AdEntityRow[];
}

export function useAdAccounts() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['marketing', 'ad-accounts', tenantId],
        enabled: api != null,
        queryFn: async () => (await api!.get<{ data: AdAccount[] }>('/marketing/ad-accounts')).data.data,
    });
}

export function useConnectFacebookAds() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async () =>
            (await api!.post<{ data: { authorize_url: string } }>('/marketing/ads/connect')).data.data,
    });
}

export function useDisconnectAdAccount() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/marketing/ad-accounts/${id}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['marketing', 'ad-accounts'] }),
    });
}

/** Disconnect many accounts at once — by ids and/or a whole BM (business_id). */
export function useBulkDisconnectAccounts() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (body: { ids?: number[]; business_id?: string }) =>
            (await api!.post<{ data: { deleted: number } }>('/marketing/ad-accounts/disconnect-bulk', body)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['marketing', 'ad-accounts'] }),
    });
}

// --- Saved reports (snapshots per filter run) ---
export interface SavedReportSummary {
    id: number; name: string; level: ReportLevel;
    since: string; until: string; filters: ReportFilters; row_count: number; created_at: string | null;
}
export interface SavedReportFull extends Omit<SavedReportSummary, 'row_count'> {
    currency: string | null; rows: ReportRow[];
}

export function useSavedReports(accountId: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['marketing', 'saved-reports', accountId, tenantId],
        enabled: api != null && accountId != null,
        queryFn: async () => (await api!.get<{ data: SavedReportSummary[] }>(`/marketing/ad-accounts/${accountId}/saved-reports`)).data.data,
    });
}

export function useSavedReport(reportId: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['marketing', 'saved-report', reportId, tenantId],
        enabled: api != null && reportId != null,
        queryFn: async () => (await api!.get<{ data: SavedReportFull }>(`/marketing/saved-reports/${reportId}`)).data.data,
    });
}

export function useSaveReport() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { accountId: number; name?: string; level: ReportLevel; since: string; until: string; filters: ReportFilters }) =>
            (await api!.post<{ data: { id: number; name: string; row_count: number } }>(
                `/marketing/ad-accounts/${vars.accountId}/saved-reports`,
                { name: vars.name, level: vars.level, since: vars.since, until: vars.until, filters: vars.filters },
            )).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['marketing', 'saved-reports'] }),
    });
}

export function useDeleteSavedReport() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (reportId: number) => { await api!.delete(`/marketing/saved-reports/${reportId}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['marketing', 'saved-reports'] }),
    });
}

// --- Auto-monitor rules ---
export interface AdMonitor {
    id: number;
    target_level: 'campaign' | 'adset';
    target_external_id: string;
    enabled: boolean;
    increase_enabled: boolean;
    increase_below: number | null;
    increase_step_pct: number;
    max_daily_budget: number | null;
    pause_enabled: boolean;
    pause_above: number | null;
    min_results: number;
    last_action: string | null;
    last_action_at: string | null;
    last_evaluated_at: string | null;
}
export interface UpsertMonitorVars {
    accountId: number;
    target_level: 'campaign' | 'adset';
    target_external_id: string;
    enabled?: boolean;
    increase_enabled?: boolean;
    increase_below?: number | null;
    increase_step_pct?: number;
    max_daily_budget?: number | null;
    pause_enabled?: boolean;
    pause_above?: number | null;
    min_results?: number;
}

export function useAdMonitors(accountId: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['marketing', 'monitors', accountId, tenantId],
        enabled: api != null && accountId != null,
        queryFn: async () => (await api!.get<{ data: AdMonitor[] }>(`/marketing/ad-accounts/${accountId}/monitors`)).data.data,
    });
}

export function useUpsertMonitor() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ accountId, ...body }: UpsertMonitorVars) =>
            (await api!.put<{ data: AdMonitor }>(`/marketing/ad-accounts/${accountId}/monitors`, body)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['marketing', 'monitors'] }),
    });
}

export function useDeleteMonitor() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (monitorId: number) => { await api!.delete(`/marketing/monitors/${monitorId}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['marketing', 'monitors'] }),
    });
}

export type AdEntityLevel = 'campaign' | 'adset' | 'ad';
export interface UpdateAdEntityVars {
    accountId: number;
    externalId: string;
    level: AdEntityLevel;
    name?: string;
    daily_budget_major?: number;
    status?: 'ACTIVE' | 'PAUSED';
}

/** Live-edit one entity: rename / daily budget / pause-resume. */
export function useUpdateAdEntity() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ accountId, externalId, ...body }: UpdateAdEntityVars) =>
            (await api!.patch<{ data: { updated: boolean } }>(
                `/marketing/ad-accounts/${accountId}/entities/${externalId}`, body,
            )).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['marketing', 'report'] }),
    });
}

/** Near-real-time: auto-refetch every 15' (FB refreshes ~15') + manual refresh. */
export function useAdInsights(accountId: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['marketing', 'insights', accountId, tenantId],
        enabled: api != null && accountId != null,
        refetchInterval: 15 * 60 * 1000,
        queryFn: async () => (await api!.get<{ data: AdInsightsResponse }>(`/marketing/ad-accounts/${accountId}/insights`)).data.data,
    });
}

export interface ReconRow {
    date: string;
    spend: number;
    conversations: number;
    leads: number;
    manual_orders: number;
    manual_revenue: number;
    cost_per_conversation: number | null;
    cost_per_order: number | null;
    conv_to_order_pct: number | null;
}

export function useAdReconciliation(accountId: number | null, days = 14) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['marketing', 'reconciliation', accountId, days, tenantId],
        enabled: api != null && accountId != null,
        refetchInterval: 15 * 60 * 1000,
        queryFn: async () => (await api!.get<{ data: { currency: string | null; rows: ReconRow[] } }>(`/marketing/ad-accounts/${accountId}/reconciliation?days=${days}`)).data.data,
    });
}

export function useRefreshAdInsights() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api!.post<{ data: { queued: boolean } }>(`/marketing/ad-accounts/${id}/refresh`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['marketing', 'insights'] }),
    });
}

export interface ForecastStrategy { action: string; campaign: string | null; rationale: string; confidence: number | null }
export interface CreativeReview { ref: string; name: string | null; verdict: string; issues: string[]; suggestions: string[] }
export interface AdForecast {
    payload: {
        forecast?: { next_7d?: { conversations?: number; orders?: number; spend?: number; projected_cost_per_order?: number | null } };
        strategy?: ForecastStrategy[];
        creative_review?: CreativeReview[];
    };
    provider_code: string | null;
    model: string | null;
    generated_at: string | null;
}

/** Cached forecast only — does NOT auto-call AI (quota saving). */
export function useAdForecast(accountId: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['marketing', 'forecast', accountId, tenantId],
        enabled: api != null && accountId != null,
        queryFn: async () => (await api!.get<{ data: AdForecast | null }>(`/marketing/ad-accounts/${accountId}/forecast`)).data.data,
    });
}

/** On-demand generate (cooldown-guarded server-side). */
export function useGenerateForecast() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api!.post<{ data: AdForecast | null; status?: string; queued?: boolean }>(`/marketing/ad-accounts/${id}/forecast`)).data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['marketing', 'forecast'] }),
    });
}

// --- Per-campaign AI insight (sub-feature G) ---

export const CAMPAIGN_AI_METRICS = [
    'spend', 'impressions', 'clicks', 'reach', 'ctr', 'cpc', 'cpm', 'frequency',
    'purchase_roas', 'messaging_conversations', 'leads',
] as const;
export type CampaignAiMetric = (typeof CAMPAIGN_AI_METRICS)[number];

export interface CampaignAiInsightParams { days: number; metrics: string[]; include_engagement: boolean }
export interface CampaignAiInsight {
    payload: {
        summary?: string;
        assessment?: string;
        recommendations?: Array<{ action?: string; rationale?: string } | string>;
        creative_review?: CreativeReview[];
        [k: string]: unknown;
    };
    params: CampaignAiInsightParams;
    provider_code: string | null;
    model: string | null;
    generated_at: string | null;
}

/** Cached per-campaign insight; pass poll=true to refetch while generating. */
export function useCampaignAiInsight(
    accountId: number | null,
    campaignId: string | null,
    opts: { enabled: boolean; poll: boolean },
) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['marketing', 'campaign-insight', accountId, campaignId, tenantId],
        enabled: opts.enabled && api != null && accountId != null && campaignId != null,
        refetchInterval: opts.poll ? 4000 : false,
        queryFn: async () => (await api!.get<{ data: CampaignAiInsight | null }>(
            `/marketing/ad-accounts/${accountId}/campaigns/${campaignId}/ai-insight`,
        )).data.data,
    });
}

/** On-demand generate for one campaign (cooldown + params-aware server-side). */
export function useGenerateCampaignAiInsight() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { accountId: number; campaignId: string; params: CampaignAiInsightParams }) =>
            (await api!.post<{ data: CampaignAiInsight | null; status?: string; queued?: boolean }>(
                `/marketing/ad-accounts/${vars.accountId}/campaigns/${vars.campaignId}/ai-insight`,
                vars.params,
            )).data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['marketing', 'campaign-insight'] }),
    });
}
