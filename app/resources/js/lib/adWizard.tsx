import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

/** Facebook ad-creation wizard data layer — all calls via /api/v1/marketing/*. */
function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export type AdObjective = 'messages' | 'engagement' | 'traffic';
export type DraftStatus = 'draft' | 'publishing' | 'published' | 'failed';

export interface AdDraftPayload {
    campaign?: { budget_mode?: 'campaign' | 'adset'; daily_budget_major?: number };
    budget?: { type?: 'daily'; daily_major?: number };
    schedule?: { start_time?: string | null };
    targeting?: Record<string, unknown>;
    placements?: 'automatic' | 'manual';
    creative?: {
        mode?: 'page_post' | 'new';
        page_id?: string;
        page_post_id?: string;
        image_hash?: string;
        primary_text?: string;
        headline?: string;
        link_url?: string;
        cta?: string;
    };
    adsets?: AdSetNode[];
    [k: string]: unknown;
}

export interface AdNode {
    key: string;
    name: string;
    external_id?: string | null;
    creative: NonNullable<AdDraftPayload['creative']>;
}

export interface PlacementConfig {
    automatic: boolean;
    device_platforms?: string[];
    publisher_platforms?: string[];
    positions?: Record<string, string[]>;
}

export interface GeoItem { key: string; name: string; type: 'country' | 'region' | 'city'; country_code?: string }

export interface AdSetNode {
    key: string;
    name: string;
    geo?: { include: GeoItem[]; exclude: GeoItem[] };
    budget?: { daily_major?: number };
    targeting?: Record<string, unknown>;
    placements?: 'automatic' | 'manual';
    placement_platforms?: string[];
    placement_config?: PlacementConfig;
    schedule?: { start_time?: string | null };
    external_id?: string | null;
    ads: AdNode[];
}

export interface AdDraft {
    id: number;
    ad_account_id: number;
    name: string | null;
    status: DraftStatus;
    objective: AdObjective | null;
    payload: AdDraftPayload;
    campaign_external_id: string | null;
    adset_external_id: string | null;
    ad_external_id: string | null;
    last_error: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface AdPage { id: string; name: string }
export interface AdPagePost {
    id: string; message: string | null; created_time: string;
    media_type: string; image_url: string | null;
    likes: number; comments: number; shares: number;
}
export interface TargetingOption { id: string; name: string; type: string; audience_size: number | null }
export interface AudienceSize { lower_bound: number | null; upper_bound: number | null }
export interface AdPreview { format: string; body: string }

const KEY = 'marketing-adwizard';

export function useAdDrafts() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: [KEY, 'drafts', tenantId],
        enabled: api != null,
        queryFn: async () => (await api!.get<{ data: AdDraft[] }>('/marketing/ad-drafts')).data.data,
    });
}

export function useAdDraft(id: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: [KEY, 'draft', id, tenantId],
        enabled: api != null && id != null,
        queryFn: async () => (await api!.get<{ data: AdDraft }>(`/marketing/ad-drafts/${id}`)).data.data,
    });
}

export function useCreateDraft() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { ad_account_id: number; name?: string; objective?: AdObjective; payload?: AdDraftPayload }) =>
            (await api!.post<{ data: AdDraft }>('/marketing/ad-drafts', input)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: [KEY, 'drafts'] }),
    });
}

export function useUpdateDraft() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async ({ id, patch }: { id: number; patch: { name?: string; objective?: AdObjective; payload?: AdDraftPayload } }) =>
            (await api!.patch<{ data: AdDraft }>(`/marketing/ad-drafts/${id}`, patch)).data.data,
    });
}

export function usePublishDraft() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api!.post<{ data: { queued: boolean; status: DraftStatus } }>(`/marketing/ad-drafts/${id}/publish`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: [KEY, 'drafts'] }),
    });
}

export function useDeleteDraft() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/marketing/ad-drafts/${id}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: [KEY, 'drafts'] }),
    });
}

export function useAdPages(accountId: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: [KEY, 'pages', accountId, tenantId],
        enabled: api != null && accountId != null,
        queryFn: async () => (await api!.get<{ data: AdPage[] }>(`/marketing/ad-accounts/${accountId}/pages`)).data.data,
    });
}

export function usePagePosts(accountId: number | null, pageId: string | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: [KEY, 'page-posts', accountId, pageId, tenantId],
        enabled: api != null && accountId != null && pageId != null,
        queryFn: async () => (await api!.get<{ data: AdPagePost[] }>(`/marketing/ad-accounts/${accountId}/pages/${pageId}/posts`)).data.data,
    });
}

export function useTargetingSearch() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async ({ accountId, q, type }: { accountId: number; q: string; type?: string }) =>
            (await api!.get<{ data: TargetingOption[] }>(`/marketing/ad-accounts/${accountId}/targeting-search`, { params: { q, type } })).data.data,
    });
}

export function useAudienceEstimate() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async ({ accountId, targeting, optimization_goal }: { accountId: number; targeting: Record<string, unknown>; optimization_goal?: string }) =>
            (await api!.post<{ data: AudienceSize }>(`/marketing/ad-accounts/${accountId}/audience-estimate`, { targeting, optimization_goal })).data.data,
    });
}

export function useAdPreviews() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async ({ accountId, creative, formats }: { accountId: number; creative: Record<string, unknown>; formats?: string[] }) =>
            (await api!.post<{ data: AdPreview[] }>(`/marketing/ad-accounts/${accountId}/ad-previews`, { creative, formats })).data.data,
    });
}
