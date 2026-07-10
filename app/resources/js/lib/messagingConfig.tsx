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

/** Ảnh/tệp đính kèm của 1 mẫu tin — `url` là signed URL ngắn hạn để hiển thị. */
export interface TemplateAttachment {
    storage_path: string;
    kind: 'image' | 'video' | 'file';
    mime: string | null;
    url: string | null;
}

export interface MessageTemplate {
    id: number;
    code: string;
    name: string;
    body: string;
    vars: string[];
    enabled: boolean;
    shortcut_key: string | null;
    attachments: TemplateAttachment[];
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

/** Upload 1 ảnh đính kèm cho mẫu tin → trả về TemplateAttachment (có storage_path + url). */
export function useUploadTemplateAttachment() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (file: File): Promise<TemplateAttachment> => {
            const form = new FormData();
            form.append('file', file);
            form.append('kind', 'image');
            return (await api!.post<{ data: TemplateAttachment }>('/messaging/template-attachments', form)).data.data;
        },
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

export function useAutoRules(provider?: string) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', 'rules', tenantId, provider ?? null],
        enabled: api != null,
        queryFn: async () => (await api!.get<Paginated<AutoReplyRule>>('/messaging/auto-reply-rules', {
            params: provider ? { provider } : {},
        })).data,
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

// --- Tenant settings --------------------------------------------------------

export interface MessagingSettings {
    ai_provider_code: string | null;
    ai_enabled: boolean;
    /** AI tự gửi tất cả — tách theo nhóm kênh (ADR-0022). */
    auto_mode_marketplace: boolean;
    auto_mode_facebook: boolean;
    away_hours: Record<string, unknown> | null;
    fallback_template_id: number | null;
    /** Phong cách chốt sale AI dùng khi trả lời (mặc định 'default'). */
    sales_closing_style?: string;
    /** Ghi chú thêm cho phong cách chốt sale (tuỳ chọn). */
    sales_closing_note?: string;
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
            s: Partial<Pick<MessagingSettings, 'ai_provider_code' | 'ai_enabled' | 'auto_mode_marketplace' | 'auto_mode_facebook' | 'fallback_template_id' | 'sales_closing_style' | 'sales_closing_note'>>,
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

// Zalo OA OAuth — GET /messaging/zalo/connect → redirect URL (SPEC 0039 Phase 1).
// Trả { authorize_url } để caller dùng openOAuthPopup (giống Facebook).
export function useStartZaloConnect() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async () => {
            const { data } = await api!.get<{ data: { authorize_url: string } }>('/messaging/zalo/connect');
            return data.data;
        },
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

/** Thông tin cửa hàng theo page — dùng để AI trả lời có ngữ cảnh (SĐT, địa chỉ, chính sách bảo hành...). */
export interface BusinessInfo {
    shop_name?: string;
    phone?: string;
    address?: string;
    email?: string;
    warranty_policy?: string;
    working_hours?: string;
    website?: string;
    extra_note?: string;
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
    business_info: BusinessInfo | null;
    token_expired: boolean;
    connected_at: string | null;
    avatar_url: string | null;
    message_count: number;
    sync: ChannelSync;
    comment_sync: CommentSync;
    /** SPEC-0039 — Zalo OA chưa đủ gói để gửi tin nhắn. */
    zalo_send_blocked?: boolean;
    zalo_send_blocked_reason?: string | null;
}

export function useMessagingChannels(provider?: string) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', 'channels', tenantId, provider ?? null],
        enabled: api != null,
        refetchInterval: (q) =>
            q.state.data?.some((c) => c.sync.status === 'queued' || c.sync.status === 'running') ? 4_000 : false,
        queryFn: async () => {
            const params: Record<string, string> = {};
            if (provider) params.provider = provider;
            return (await api!.get<{ data: MessagingChannel[] }>('/messaging/channels', { params })).data.data;
        },
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

/** Lưu thông tin cửa hàng (business_info) cho 1 page. */
export function useSetChannelBusinessInfo() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { id: number; business_info: BusinessInfo }) => {
            await api!.patch(`/messaging/channels/${input.id}/business-info`, { business_info: input.business_info });
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'channels'] }),
    });
}

/** Áp dụng cùng thông tin cửa hàng cho nhiều page đã chọn. */
export function useBulkSetChannelBusinessInfo() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { ids: number[]; business_info: BusinessInfo }) => {
            await api!.patch('/messaging/channels/business-info', input);
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
