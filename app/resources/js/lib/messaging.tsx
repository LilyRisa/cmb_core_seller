import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';
import type { Paginated } from './orders';

/**
 * Data layer cho Hộp thư hợp nhất (SPEC-0024). Gọi `/api/v1/messaging/*` qua
 * `tenantApi` (X-Tenant-Id). Realtime (Reverb) là follow-up — MVP dùng refetch
 * + polling nhẹ (refetchInterval) làm fallback (SPEC §6.3).
 */

export type ConversationStatus = 'open' | 'snoozed' | 'resolved' | 'spam';
export type MessageDirection = 'inbound' | 'outbound';
export type DeliveryStatus = 'pending' | 'sent' | 'delivered' | 'read' | 'failed';

export type ChannelGroup = 'marketplace' | 'facebook' | 'internal';

export interface ConversationComment {
    post_message: string | null;
    post_permalink: string | null;
    hidden: boolean;
    private_replied: boolean;
    /** Tên người tham gia comment (commenter + người reply) — hiển thị "A, B +N người". */
    participants: string[];
}

export interface Conversation {
    id: number;
    channel_account_id: number;
    provider: string;
    channel_group: ChannelGroup;
    channel_account_name: string | null;
    channel_account_avatar_url: string | null;
    external_conversation_id: string;
    buyer_external_id: string;
    buyer_name: string | null;
    buyer_avatar_url: string | null;
    customer_id: number | null;
    order_id: number | null;
    status: ConversationStatus;
    blocked_at: string | null;
    unread_count: number;
    message_count: number;
    last_message_at: string | null;
    last_message_preview: string | null;
    last_inbound_at: string | null;
    last_outbound_at: string | null;
    assigned_user_id: number | null;
    has_phone: boolean;
    detected_phone: string | null;
    tags: number[];
    created_at: string | null;
    thread_type: 'message' | 'comment';
    comment: ConversationComment | null;
}

export interface MessageAttachment {
    id: number;
    kind: string;
    mime: string;
    size_bytes: number | null;
    filename: string | null;
    status: string;
    download_url: string | null;
}

export interface Message {
    id: number;
    conversation_id: number;
    external_message_id: string | null;
    direction: MessageDirection;
    kind: string;
    body: string | null;
    attachments_count: number;
    sent_by_user_id: number | null;
    sent_by_ai: boolean;
    delivery_status: DeliveryStatus;
    failure_code: string | null;
    sent_at: string | null;
    read_at: string | null;
    created_at: string | null;
    reaction?: string | null;
    attachments?: MessageAttachment[];
    /** Nút bấm (template/quick-reply của trả lời tự động Facebook) — chỉ hiển thị. */
    buttons?: MessageButton[];
}

export interface MessageButton {
    title: string;
    url?: string;
}

export interface ConversationFilters {
    provider?: string;
    status?: string;
    unread?: boolean;
    blocked?: boolean;
    assigned?: string;
    q?: string;
    page?: number;
    per_page?: number;
    read?: boolean;
    has_phone?: boolean;
    tags?: string; // CSV of tag ids, e.g. "1,2"
    channel_account_id?: number | string; // 1 id hoặc CSV nhiều page "5,6,7"
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export function useConversations(filters: ConversationFilters) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useInfiniteQuery({
        queryKey: ['messaging', 'conversations', tenantId, filters],
        enabled: api != null,
        placeholderData: (prev) => prev,
        refetchInterval: 15_000, // polling fallback tới khi Reverb bật
        initialPageParam: 1,
        queryFn: async ({ pageParam }) => {
            const params: Record<string, string | number | boolean> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '' && v !== false) params[k] = v as never; });
            params.page = pageParam as number; // ghi đè mọi `page` rác trong filters
            const { data } = await api!.get<Paginated<Conversation>>('/messaging/conversations', { params });
            return data;
        },
        // Còn trang sau? page hiện < tổng số trang ⇒ trả page kế cho infinite scroll.
        getNextPageParam: (lastPage: Paginated<Conversation>) => {
            const p = lastPage.meta.pagination;
            return p.page < p.total_pages ? p.page + 1 : undefined;
        },
    });
}

export interface ThreadResult { conversation: Conversation; messages: Message[] }

export function useConversationThread(id: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', 'thread', tenantId, id],
        enabled: api != null && id != null,
        refetchInterval: 10_000,
        queryFn: async () => {
            const { data } = await api!.get<{ data: ThreadResult }>(`/messaging/conversations/${id}`);
            return data.data;
        },
    });
}

export function useSendText(conversationId: number | null) {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { body: string; message_tag?: string }) => {
            const { data } = await api!.post<{ data: Message }>(
                `/messaging/conversations/${conversationId}/messages`,
                { body: input.body, message_tag: input.message_tag },
            );
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
        },
    });
}

export function useMarkRead() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (conversationId: number) => {
            await api!.post(`/messaging/conversations/${conversationId}/read`);
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] }),
    });
}

export function useSendMedia(conversationId: number | null) {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { file: File; kind: 'image' | 'video' | 'file'; caption?: string }) => {
            const form = new FormData();
            form.append('file', input.file);
            form.append('kind', input.kind);
            if (input.caption) form.append('caption', input.caption);
            const { data } = await api!.post<{ data: Message }>(
                `/messaging/conversations/${conversationId}/messages/media`, form,
            );
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
        },
    });
}

export function useMarkUnread() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (conversationId: number) => {
            await api!.post(`/messaging/conversations/${conversationId}/unread`);
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] }),
    });
}

export function useBlockConversation() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (conversationId: number) => {
            await api!.post(`/messaging/conversations/${conversationId}/block`);
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
        },
    });
}

export function useUnblockConversation() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (conversationId: number) => {
            await api!.delete(`/messaging/conversations/${conversationId}/block`);
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
        },
    });
}

export function useAiSuggestion(conversationId: number | null) {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async () => {
            const { data } = await api!.post<{ data: { draft_id: number; draft_text: string } }>(
                `/messaging/conversations/${conversationId}/ai-suggestion`,
            );
            return data.data;
        },
    });
}

const PROVIDER_LABELS: Record<string, string> = {
    facebook_page: 'Facebook',
    tiktok_chat: 'TikTok',
    shopee_chat: 'Shopee',
    lazada_chat: 'Lazada',
    manual: 'Nội bộ',
};

export function providerLabel(code: string): string {
    return PROVIDER_LABELS[code] ?? code;
}

/**
 * Tách inbox theo nguồn: "Tin nhắn sàn" (marketplace) vs "Tin nhắn Facebook".
 * Map sang param `provider` (CSV) của API list. 'all' = không lọc.
 */
export type InboxGroup = 'all' | 'marketplace' | 'facebook';

export const INBOX_GROUP_PROVIDERS: Record<InboxGroup, string | undefined> = {
    all: undefined,
    marketplace: 'tiktok_chat,shopee_chat,lazada_chat',
    facebook: 'facebook_page',
};

// ---------------------------------------------------------------------------
// Messaging tags
// ---------------------------------------------------------------------------

export interface MessagingTag {
    id: number;
    name: string;
    color: string;
}

export function useMessagingTags() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', 'tags', tenantId],
        enabled: api != null,
        queryFn: async () => (await api!.get<{ data: MessagingTag[] }>('/messaging/tags')).data.data,
    });
}

export function useSaveTag() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (t: { id?: number; name: string; color: string }) => {
            if (t.id) return (await api!.patch<{ data: MessagingTag }>(`/messaging/tags/${t.id}`, { name: t.name, color: t.color })).data.data;
            return (await api!.post<{ data: MessagingTag }>('/messaging/tags', { name: t.name, color: t.color })).data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'tags'] }),
    });
}

export function useDeleteTag() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/messaging/tags/${id}`); },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'tags'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
        },
    });
}

export function useSetConversationTags() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { conversationId: number; tags: number[] }) => {
            await api!.patch(`/messaging/conversations/${input.conversationId}`, { tags: input.tags });
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
        },
    });
}

// ---------------------------------------------------------------------------
// Facebook comment actions
// ---------------------------------------------------------------------------

export function useHideComment() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { conversationId: number; hidden: boolean }) => {
            await api!.post(`/messaging/conversations/${input.conversationId}/comment/hide`, { hidden: input.hidden });
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
        },
    });
}

export function useDeleteComment() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (conversationId: number) => {
            await api!.delete(`/messaging/conversations/${conversationId}/comment`);
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
        },
    });
}

export function useReplyComment(conversationId: number | null) {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (body: string) => {
            const { data } = await api!.post<{ data: Message }>(
                `/messaging/conversations/${conversationId}/comment/reply`,
                { body },
            );
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
        },
    });
}

export function usePrivateReplyComment(conversationId: number | null) {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (body: string) => {
            const { data } = await api!.post<{ data: Message }>(
                `/messaging/conversations/${conversationId}/comment/private-reply`,
                { body },
            );
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
        },
    });
}
