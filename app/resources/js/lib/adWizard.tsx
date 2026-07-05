import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

/** Facebook ad-creation wizard data layer — all calls via /api/v1/marketing/*. */
function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export type AdObjective = 'messages' | 'engagement' | 'traffic' | 'conversions';
export type DraftStatus = 'draft' | 'publishing' | 'published' | 'failed';

export interface AdDraftPayload {
    campaign?: { budget_mode?: 'campaign' | 'adset'; daily_budget_major?: number };
    budget?: { type?: 'daily'; daily_major?: number };
    schedule?: { start_time?: string | null; end_time?: string | null };
    targeting?: Record<string, unknown>;
    placements?: 'automatic' | 'manual';
    creative?: {
        mode?: 'page_post' | 'new';
        page_id?: string;
        page_post_id?: string;
        // CTA sẵn có của bài viết đã chọn (chỉ hiển thị — bài promote qua object_story_id
        // giữ nguyên nút của bài; null = bài chưa có nút ⇒ cho người dùng tự chọn).
        page_post_cta_type?: string | null;
        image_hash?: string;
        primary_text?: string;
        headline?: string;
        link_url?: string;
        cta?: string;
        standard_enhancements?: boolean;
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
    schedule?: { start_time?: string | null; end_time?: string | null };
    experiment?: AbExperiment;
    conversion?: { pixel_id?: string; custom_event_type?: string };
    external_id?: string | null;
    ads: AdNode[];
}

/** A/B test variable an experiment varies between its variants. */
export type AbVariable = 'creative' | 'audience' | 'placement';
/** Marks an ad set as part of an A/B experiment (FE metadata; not sent to Graph). */
export interface AbExperiment { id: string; variable: AbVariable }

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

export interface AdPage { id: string; name: string; picture_url?: string | null }
export interface AdPagePost {
    id: string; message: string | null; created_time: string;
    media_type: string; image_url: string | null;
    likes: number; comments: number; shares: number;
    link_url?: string | null; cta_type?: string | null;
}
export interface TargetingOption { id: string; name: string; type: string; audience_size: number | null }
export interface AudienceSize { lower_bound: number | null; upper_bound: number | null }
export interface AdPreview { format: string; body: string }

const KEY = 'marketing-adwizard';

/**
 * Bản nháp gắn với một tài khoản quảng cáo. Truyền accountId để chỉ lấy nháp của
 * tài khoản đó; queryKey chứa accountId nên đổi tài khoản ⇒ refetch danh sách mới.
 */
export function useAdDrafts(accountId?: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: [KEY, 'drafts', tenantId, accountId ?? null],
        enabled: api != null,
        queryFn: async () => (await api!.get<{ data: AdDraft[] }>('/marketing/ad-drafts', {
            params: accountId != null ? { ad_account_id: accountId } : undefined,
        })).data.data,
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

export function useDuplicateDraft() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api!.post<{ data: AdDraft }>(`/marketing/ad-drafts/${id}/duplicate`)).data.data,
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

export interface AdPixel {
    id: string;
    name: string;
    last_fired_time?: string | null;
    is_unavailable?: boolean | null;
}

export function useAdPixels(accountId: number | null, enabled = true) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: [KEY, 'pixels', accountId, tenantId],
        enabled: enabled && api != null && accountId != null,
        queryFn: async () => (await api!.get<{ data: AdPixel[] }>(`/marketing/ad-accounts/${accountId}/pixels`)).data.data,
    });
}

/** Share a pixel from one ad account to another (same BM). */
export function useSharePixel() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (vars: { accountId: number; pixelId: string; target_account_id: string }) =>
            (await api!.post<{ data: { shared: boolean } }>(
                `/marketing/ad-accounts/${vars.accountId}/pixels/${vars.pixelId}/share`,
                { target_account_id: vars.target_account_id },
            )).data.data,
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

export interface AiCampaignInput {
    accountId: number;
    page_id: string;
    page_post_id: string;
    objective: AdObjective;
    mode: 'test' | 'scale';
    optimization_mode: 'advantage_plus' | 'manual';
    prompt?: string;
    caption?: string | null;
    likes?: number;
    comments?: number;
    shares?: number;
    link_url?: string | null;
    landing_url?: string | null;
    cta_type?: string | null;
    pixel_id?: string | null;
    conversion_event?: string | null;
    start_time?: string | null;
}

export interface AiCampaignResult {
    draft: AdDraft;
    recommendations: string[];
}

/** AI tạo chiến dịch từ một bài viết → trả draft + đề xuất của AI. */
export function useGenerateAiCampaign() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ accountId, ...body }: AiCampaignInput): Promise<AiCampaignResult> => {
            const res = (await api!.post<{ data: AdDraft; meta?: { recommendations?: string[] } }>(
                `/marketing/ad-accounts/${accountId}/ai-campaign`,
                body,
            )).data;
            return { draft: res.data, recommendations: res.meta?.recommendations ?? [] };
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: [KEY, 'drafts'] }),
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
