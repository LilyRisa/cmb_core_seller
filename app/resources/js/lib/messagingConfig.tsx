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

// --- Auto-reply rules -------------------------------------------------------

export type RuleTrigger = 'schedule' | 'order_status' | 'away_no_response' | 'first_message';

export interface AutoReplyRule {
    id: number;
    name: string;
    trigger: RuleTrigger;
    trigger_config: Record<string, unknown>;
    filter: Record<string, unknown>;
    action: { kind: 'template' | 'raw' | 'ai_reply'; template_id?: number; raw_text?: string };
    cooldown_seconds: number;
    enabled: boolean;
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

export function useCreateKnowledge() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (payload: { title: string; source: 'inline' | 'url'; inline_text?: string; url?: string }) =>
            (await api!.post('/messaging/knowledge-docs', payload)).data,
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

// --- Tenant settings --------------------------------------------------------

export interface MessagingSettings {
    ai_provider_code: string | null;
    ai_enabled: boolean;
    auto_mode: boolean;
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

export function useSaveMessagingSettings() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (s: Partial<Pick<MessagingSettings, 'ai_provider_code' | 'ai_enabled' | 'auto_mode' | 'fallback_template_id'>>) =>
            (await api!.patch('/messaging/settings', s)).data,
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

export function useDisconnectFacebookPage() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/messaging/channels/${id}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'channels'] }),
    });
}
