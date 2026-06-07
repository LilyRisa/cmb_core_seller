import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';
import type { Paginated } from './orders';

/**
 * Hooks cấu hình Messaging (SPEC-0024 §6.2): templates, auto-reply rules,
 * knowledge docs, tenant settings. Gọi `/api/v1/messaging/*` qua `tenantApi`.
 */

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

// --- Templates --------------------------------------------------------------

export interface MessageTemplate {
    id: number;
    code: string;
    name: string;
    body: string;
    vars: string[];
    enabled: boolean;
    shortcut_key: string | null;
}

export function useTemplates() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', 'templates', tenantId],
        enabled: api != null,
        queryFn: async () => (await api!.get<Paginated<MessageTemplate>>('/messaging/templates')).data,
    });
}

export function useSaveTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (t: Partial<MessageTemplate> & { id?: number }) => {
            if (t.id) return (await api!.patch(`/messaging/templates/${t.id}`, t)).data;
            return (await api!.post('/messaging/templates', t)).data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'templates'] }),
    });
}

export function useDeleteTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/messaging/templates/${id}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'templates'] }),
    });
}

// --- Utility templates (Messenger Utility Messages — SPEC-0032) -------------

export type UtilityTemplateStatus = 'draft' | 'pending' | 'approved' | 'rejected';

export interface UtilityTemplate {
    id: number;
    channel_account_id: number;
    code: string;
    name: string;
    language: string;
    body: string;
    buttons: Array<Record<string, unknown>>;
    variables: string[];
    external_template_id: string | null;
    status: UtilityTemplateStatus;
    reject_reason: string | null;
    enabled: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export function useUtilityTemplates(channelAccountId?: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', 'utility-templates', tenantId, channelAccountId ?? null],
        enabled: api != null,
        // Poll khi có template đang chờ Meta duyệt để trạng thái tự cập nhật.
        refetchInterval: (q) => (q.state.data?.data?.some((t) => t.status === 'pending') ? 15_000 : false),
        queryFn: async () => {
            const qs = channelAccountId ? `?channel_account_id=${channelAccountId}` : '';
            return (await api!.get<Paginated<UtilityTemplate>>(`/messaging/utility-templates${qs}`)).data;
        },
    });
}

export function useSaveUtilityTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (t: Partial<UtilityTemplate> & { id?: number }) => {
            if (t.id) return (await api!.patch(`/messaging/utility-templates/${t.id}`, t)).data;
            return (await api!.post('/messaging/utility-templates', t)).data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'utility-templates'] }),
    });
}

export function useDeleteUtilityTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/messaging/utility-templates/${id}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'utility-templates'] }),
    });
}

/** Gửi template lên Meta để duyệt (draft → pending). */
export function useSubmitUtilityTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => (await api!.post(`/messaging/utility-templates/${id}/submit`)).data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'utility-templates'] }),
    });
}

/** Đồng bộ trạng thái duyệt từ Meta thủ công. */
export function useSyncUtilityTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => (await api!.post(`/messaging/utility-templates/${id}/sync`)).data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'utility-templates'] }),
    });
}

// --- Auto-reply rules -------------------------------------------------------

export type RuleTrigger = 'schedule' | 'order_status' | 'away_no_response' | 'first_message' | 'keyword' | 'comment_any';

export type ThreadType = 'message' | 'comment';

export interface RuleFilter {
    providers?: string[];
    keywords?: string[];
    /** Loại hội thoại áp dụng. Vắng = cả tin nhắn lẫn bình luận. */
    thread_types?: ThreadType[];
    [k: string]: unknown;
}

export interface RuleAction {
    kind: 'template' | 'raw' | 'ai_reply';
    template_id?: number;
    raw_text?: string;
    /** Đích gửi khi áp cho bình luận: trả lời công khai và/hoặc nhắn riêng. */
    comment_target?: { public?: boolean; private?: boolean };
}

export interface AutoReplyRule {
    id: number;
    name: string;
    trigger: RuleTrigger;
    trigger_config: Record<string, unknown>;
    filter: RuleFilter;
    action: RuleAction;
    cooldown_seconds: number;
    enabled: boolean;
    /** SPEC 0035 — true: áp mọi trang; false: chỉ channel_account_ids. */
    applies_all_pages: boolean;
    channel_account_ids: number[];
    priority: number;
}

export function useAutoRules() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', 'rules', tenantId],
        enabled: api != null,
        queryFn: async () => (await api!.get<Paginated<AutoReplyRule>>('/messaging/auto-reply-rules')).data,
    });
}

export function useSaveRule() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (r: Partial<AutoReplyRule> & { id?: number }) => {
            if (r.id) return (await api!.patch(`/messaging/auto-reply-rules/${r.id}`, r)).data;
            return (await api!.post('/messaging/auto-reply-rules', r)).data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'rules'] }),
    });
}

export function useDeleteRule() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/messaging/auto-reply-rules/${id}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'rules'] }),
    });
}

// --- Knowledge docs ---------------------------------------------------------

export interface KnowledgeDoc {
    id: number;
    title: string;
    source: 'inline' | 'url' | 'upload';
    status: 'pending' | 'ready' | 'failed';
    chunk_count: number;
    /** SPEC 0035 — true: dùng cho mọi trang; false: chỉ channel_account_ids. */
    applies_all_pages: boolean;
    channel_account_ids: number[];
    indexed_at: string | null;
    error: string | null;
    created_at: string | null;
}

export function useKnowledgeDocs() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', 'knowledge', tenantId],
        enabled: api != null,
        refetchInterval: (q) => (q.state.data?.data?.some((d) => d.status === 'pending') ? 5_000 : false),
        queryFn: async () => (await api!.get<Paginated<KnowledgeDoc>>('/messaging/knowledge-docs')).data,
    });
}

export interface CreateKnowledgePayload {
    title: string;
    source: 'inline' | 'url' | 'upload';
    inline_text?: string;
    url?: string;
    file?: File;
    applies_all_pages?: boolean;
    channel_account_ids?: number[];
}

export function useCreateKnowledge() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (payload: CreateKnowledgePayload) => {
            // Upload file ⇒ multipart/form-data (axios tự set boundary từ FormData).
            if (payload.source === 'upload' && payload.file) {
                const fd = new FormData();
                fd.append('title', payload.title);
                fd.append('source', 'upload');
                fd.append('file', payload.file);
                fd.append('applies_all_pages', payload.applies_all_pages ? '1' : '0');
                (payload.channel_account_ids ?? []).forEach((id) => fd.append('channel_account_ids[]', String(id)));
                return (await api!.post('/messaging/knowledge-docs', fd)).data;
            }
            return (await api!.post('/messaging/knowledge-docs', payload)).data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'knowledge'] }),
    });
}

export function useDeleteKnowledge() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/messaging/knowledge-docs/${id}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'knowledge'] }),
    });
}

/** Tải lại (re-index) 1 tài liệu — fetch lại nguồn url/Sheet/file khi có dữ liệu mới. */
export function useReindexKnowledge() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => (await api!.post(`/messaging/knowledge-docs/${id}/reindex`)).data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'knowledge'] }),
    });
}

export interface KnowledgeChunk {
    index: number;
    text: string;
    token_count: number;
}

export interface KnowledgeChunksResult {
    id: number;
    title: string;
    source: 'inline' | 'url' | 'upload';
    url: string | null;
    status: 'pending' | 'ready' | 'failed';
    error: string | null;
    chunk_count: number;
    indexed_at: string | null;
    chunks: KnowledgeChunk[];
}

/** Xem nội dung đã trích (chunk) của 1 tài liệu — kiểm tra dữ liệu AI thực sự lấy được. */
export function useKnowledgeChunks(id: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', 'knowledge', 'chunks', id, tenantId],
        enabled: api != null && id != null,
        queryFn: async () => (await api!.get<{ data: KnowledgeChunksResult }>(`/messaging/knowledge-docs/${id}/chunks`)).data.data,
    });
}

// --- Tenant settings --------------------------------------------------------

export interface MessagingSettings {
    ai_provider_code: string | null;
    ai_enabled: boolean;
    /** AI tự gửi tất cả — tách theo nhóm kênh (ADR-0022). */
    auto_mode_marketplace: boolean;
    auto_mode_facebook: boolean;
    away_hours: Record<string, unknown> | null;
    fallback_template_id: number | null;
    available_providers: Array<{ code: string; name: string }>;
}

export function useMessagingSettings() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', 'settings', tenantId],
        enabled: api != null,
        queryFn: async () => (await api!.get<{ data: MessagingSettings }>('/messaging/settings')).data.data,
    });
}

/** Phản hồi lưu cài đặt — `meta.paused_catch_all_flows`: số luồng "Mọi tin nhắn" bị tạm dừng do bật AI FB. */
export interface SaveMessagingSettingsResult {
    data: MessagingSettings;
    meta?: { paused_catch_all_flows?: number };
}

export function useSaveMessagingSettings() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (
            s: Partial<Pick<MessagingSettings, 'ai_provider_code' | 'ai_enabled' | 'auto_mode_marketplace' | 'auto_mode_facebook' | 'fallback_template_id'>>,
        ): Promise<SaveMessagingSettingsResult> => (await api!.patch('/messaging/settings', s)).data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'settings'] }),
    });
}

// --- Kết nối kênh (Facebook Page OAuth) -------------------------------------

export function useConnectFacebook() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async () =>
            (await api!.post<{ data: { authorize_url: string } }>('/messaging/facebook/connect')).data.data,
    });
}

// Lazada IM Chat dùng app "IM ERP" RIÊNG (tách khỏi gian hàng) — OAuth flow riêng.
export function useConnectLazadaIm() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async () =>
            (await api!.post<{ data: { authorize_url: string } }>('/messaging/lazada-im/connect')).data.data,
    });
}

// --- Quản lý kênh Facebook Page (UI /messaging/channels) -------------------

export interface ChannelSync {
    status: 'idle' | 'queued' | 'running' | 'done' | 'failed';
    total: number | null;
    done: number;
    message_count: number;
    started_at: string | null;
    finished_at: string | null;
    last_synced_at: string | null;
    error: string | null;
}

export interface CommentSync {
    status: string;
    synced_at: string | null;
    error: string | null;
}

export interface MessagingChannel {
    id: number;
    provider: string;
    shop_name: string | null;
    name: string;
    external_shop_id: string;
    status: string;
    messaging_enabled: boolean;
    /** SPEC 0035 — AI tự trả lời bật cho page này. */
    ai_auto_mode: boolean;
    token_expired: boolean;
    connected_at: string | null;
    avatar_url: string | null;
    message_count: number;
    sync: ChannelSync;
    comment_sync: CommentSync;
}

export function useMessagingChannels() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', 'channels', tenantId],
        enabled: api != null,
        refetchInterval: (q) =>
            q.state.data?.some((c) => c.sync.status === 'queued' || c.sync.status === 'running') ? 4_000 : false,
        queryFn: async () => (await api!.get<{ data: MessagingChannel[] }>('/messaging/channels')).data.data,
    });
}

export function useSyncChannel() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.post(`/messaging/channels/${id}/sync`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'channels'] }),
    });
}

/** SPEC 0035 — bật/tắt AI tự trả lời cho 1 page. */
export function useSetChannelAiMode() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { id: number; ai_auto_mode: boolean }) => {
            await api!.patch(`/messaging/channels/${input.id}/ai-mode`, { ai_auto_mode: input.ai_auto_mode });
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'channels'] }),
    });
}

export function useDisconnectFacebookPage() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/messaging/channels/${id}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'channels'] }),
    });
}

/** Đồng bộ hàng loạt các Page đã tick chọn. */
export function useBulkSyncChannels() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (ids: number[]) =>
            (await api!.post<{ data: { ok: boolean; processed: number } }>('/messaging/channels/bulk-sync', { ids })).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'channels'] }),
    });
}

/** Ngắt kết nối hàng loạt các Page đã tick chọn (xoá hẳn + cascade hội thoại). */
export function useBulkDisconnectChannels() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (ids: number[]) =>
            (await api!.post<{ data: { ok: boolean; processed: number; conversations_deleted: number } }>('/messaging/channels/bulk-disconnect', { ids })).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'channels'] }),
    });
}
