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
    external_account_id: string;
    name: string | null;
    currency: string | null;
    status: string;
    last_synced_at: string | null;
    insights_synced_at: string | null;
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

export function useRefreshAdInsights() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api!.post<{ data: { queued: boolean } }>(`/marketing/ad-accounts/${id}/refresh`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['marketing', 'insights'] }),
    });
}
