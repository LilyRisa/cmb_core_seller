import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect, useMemo } from 'react';
import { tenantApi } from './api';
import { getEcho, realtimeEnabled } from './echo';
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

export type ChannelGroup = 'marketplace' | 'facebook' | 'internal' | 'zalo';

export interface ConversationComment {
    post_message: string | null;
    post_permalink: string | null;
    /** Ảnh bài viết (CDN Facebook hết hạn — chỉ để preview trong post card). */
    post_picture: string | null;
    /** Bài viết là video → phủ icon ▶ lên ảnh preview (full_picture = thumbnail). */
    post_is_video: boolean;
    /** Thời gian đăng bài (ISO-8601) — hiển thị "x giờ trước". */
    post_created_time: string | null;
    hidden: boolean;
    private_replied: boolean;
    /** Tên người tham gia comment (commenter + người reply) — hiển thị "A, B +N người". */
    participants: string[];
    /** Avatar người tham gia (tối đa 2) — chồng 2 avatar như app nhắn tin. */
    participant_avatars: string[];
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
    /** Đoạn trích tin nhắn khớp từ khoá — chỉ có khi đang tìm và khớp trong nội dung tin. */
    match_snippet?: string | null;
}

export interface MessageAttachment {
    id: number;
    kind: string;
    mime: string;
    size_bytes: number | null;
    filename: string | null;
    status: string;
    download_url: string | null;
    transcript?: string | null;
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
    thread_type?: string; // 'message' (DM) | 'comment' — lọc hội thoại Facebook
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

/**
 * Realtime inbox (ADR-0021 — Reverb). Subscribe private channel
 * `tenant.{id}.messaging`; khi có tin/hội thoại mới → invalidate cache để refetch
 * NGAY (không đợi polling 10–20s). No-op khi Reverb tắt (getEcho()=null) ⇒ polling
 * fallback vẫn chạy. Gọi 1 lần ở trang inbox.
 */
export function useMessagingRealtime(): void {
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();

    useEffect(() => {
        const echo = getEcho();
        if (echo == null || tenantId == null) return;

        const channelName = `tenant.${tenantId}.messaging`;
        const channel = echo.private(channelName);
        const refresh = () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'unread-feed'] });
        };
        channel.listen('.message.received', refresh);
        channel.listen('.message.sent', refresh);
        channel.listen('.conversation.created', refresh);

        return () => { echo.leave(channelName); };
    }, [tenantId, qc]);
}

export function useConversations(filters: ConversationFilters) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useInfiniteQuery({
        queryKey: ['messaging', 'conversations', tenantId, filters],
        enabled: api != null,
        placeholderData: (prev) => prev,
        // Realtime bật (Reverb có key) ⇒ poll thưa 60s làm lưới an toàn (ws lo realtime); tắt ⇒ poll 15s.
        refetchInterval: realtimeEnabled() ? 60_000 : 15_000,
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

/**
 * Nguồn poll NHẸ, KHÔNG lọc — dành riêng cho thông báo tin nhắn mới TOÀN CỤC (mount ở AppLayout).
 * Trả 50 hội thoại chưa đọc mới nhất + tổng số chưa đọc (`meta.total`). Tách hẳn khỏi `useConversations`
 * (infinite + theo bộ lọc/tab của trang Hộp thư) để đổi tab/nguồn/scroll KHÔNG làm sai việc đếm tin mới.
 * Lỗi (vd tenant không có gói messaging_inbox → 402) ⇒ ngừng poll, không spam request.
 */
export function useUnreadConversations(enabled: boolean) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', 'unread-feed', tenantId],
        enabled: enabled && api != null,
        retry: false,
        refetchInterval: (query) => (query.state.status === 'error' ? false : (realtimeEnabled() ? 60_000 : 20_000)),
        queryFn: async () => {
            const { data } = await api!.get<Paginated<Conversation>>('/messaging/conversations', { params: { unread: true, per_page: 50 } });
            return { items: data.data, total: data.meta.pagination.total };
        },
    });
}

export interface ThreadResult { conversation: Conversation; messages: Message[] }

/** Số tin mỗi trang — khớp `limit(50)` của backend (ConversationController::show). */
export const THREAD_PAGE_SIZE = 50;

/**
 * Luồng tin của 1 hội thoại — chỉ tải 50 tin MỚI nhất, kéo lên thì lazy load
 * dần tin cũ hơn (infinite query theo cursor `before_message_id`). Trang đầu (không
 * cursor) refetch theo polling/realtime nên tin mới vẫn xuất hiện ở đáy. Mỗi trang
 * trả tin theo thứ tự cũ→mới; gộp ở UI (xem `MessagingPage`).
 */
export function useConversationThread(id: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useInfiniteQuery({
        queryKey: ['messaging', 'thread', tenantId, id],
        enabled: api != null && id != null,
        refetchInterval: realtimeEnabled() ? 60_000 : 10_000,
        initialPageParam: null as number | null,
        queryFn: async ({ pageParam }) => {
            const params: Record<string, number> = {};
            if (pageParam != null) params.before_message_id = pageParam;
            const { data } = await api!.get<{ data: ThreadResult }>(`/messaging/conversations/${id}`, { params });
            return data.data;
        },
        // "Trang kế" = tin CŨ hơn: cursor = id tin cũ nhất (messages[0]) của trang vừa tải.
        // Trang trả < PAGE_SIZE ⇒ đã hết tin cũ.
        getNextPageParam: (lastPage: ThreadResult) =>
            lastPage.messages.length < THREAD_PAGE_SIZE ? undefined : (lastPage.messages[0]?.id ?? undefined),
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

/** Gửi lại 1 tin outbound đang lỗi (reset pending + dispatch lại). */
export function useResendMessage(conversationId: number | null) {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (messageId: number) => {
            const { data } = await api!.post<{ data: Message }>(
                `/messaging/conversations/${conversationId}/messages/${messageId}/resend`,
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
    zalo_oa: 'Zalo OA',
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
export type InboxGroup = 'all' | 'marketplace' | 'facebook' | 'zalo';

export const INBOX_GROUP_PROVIDERS: Record<InboxGroup, string | undefined> = {
    all: undefined,
    marketplace: 'tiktok_chat,shopee_chat,lazada_chat',
    facebook: 'facebook_page',
    zalo: 'zalo_oa',
};

/**
 * TẮT TẠM tin nhắn sàn (TikTok/Shopee/Lazada chat) — chưa triển khai xong, mặc định chỉ Facebook.
 * Bật lại bằng cách đổi thành `true` (ẩn/hiện tab "Sàn" ở hộp thư + thẻ kết nối kênh sàn).
 */
export const MARKETPLACE_CHAT_ENABLED = false;

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

/** Gắn đơn vừa tạo (từ khung chat) vào hội thoại ⇒ icon đơn hiện ở danh sách. */
export function useLinkConversationOrder() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { conversationId: number; orderId: number; notifyCustomer?: boolean }) => {
            const { data } = await api!.post<{ data: Conversation }>(
                `/messaging/conversations/${input.conversationId}/link-order`,
                // SPEC 0031 — đơn tạo trong khung chat ⇒ tự gửi tin xác nhận cho khách.
                { order_id: input.orderId, ...(input.notifyCustomer ? { notify_customer: true } : {}) },
            );
            return data.data;
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

/**
 * Xoá bình luận. `commentId` rỗng ⇒ xoá comment gốc (spam cả hội thoại); có
 * `commentId` con ⇒ chỉ xoá đúng comment đó (giữ hội thoại).
 */
export function useDeleteComment() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { conversationId: number; commentId?: string }) => {
            await api!.delete(`/messaging/conversations/${input.conversationId}/comment`, {
                data: input.commentId ? { comment_id: input.commentId } : {},
            });
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
        },
    });
}

/** Thích / bỏ thích 1 comment (Page engagement — chỉ Facebook). */
export function useLikeComment() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (input: { conversationId: number; commentId?: string; like: boolean }) => {
            await api!.post(`/messaging/conversations/${input.conversationId}/comment/like`, {
                comment_id: input.commentId,
                like: input.like,
            });
        },
    });
}

/**
 * Nhắn riêng đầy đủ (modal): text + nhiều đính kèm (ảnh/video/file). Gửi multipart
 * `files[]`. Trả conversation đã cập nhật (meta.private_replied + PSID).
 */
export function useSendCommentPrivateMessage(conversationId: number | null) {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { body: string; commentId?: string; files: File[] }) => {
            const fd = new FormData();
            if (input.body) fd.append('body', input.body);
            if (input.commentId) fd.append('comment_id', input.commentId);
            input.files.forEach((f) => fd.append('files[]', f));
            const { data } = await api!.post<{ data: Conversation; meta?: { delivered: number; total: number; dm_conversation_id?: number | null } }>(
                `/messaging/conversations/${conversationId}/comment/private-message`, fd,
            );
            return {
                conversation: data.data,
                delivered: data.meta?.delivered ?? 0,
                total: data.meta?.total ?? 0,
                dmConversationId: data.meta?.dm_conversation_id ?? null,
            };
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
        },
    });
}

/** Trả lời công khai comment — kèm ảnh tuỳ chọn (multipart khi có ảnh). */
export function useReplyComment(conversationId: number | null) {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { body: string; image?: File }) => {
            let data;
            if (input.image) {
                const fd = new FormData();
                fd.append('body', input.body);
                fd.append('image', input.image);
                ({ data } = await api!.post<{ data: Message }>(`/messaging/conversations/${conversationId}/comment/reply`, fd));
            } else {
                ({ data } = await api!.post<{ data: Message }>(`/messaging/conversations/${conversationId}/comment/reply`, { body: input.body }));
            }
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
        },
    });
}

/** Nhắn riêng cho người comment (Facebook Private Reply) — kèm ảnh tuỳ chọn. */
export function usePrivateReplyComment(conversationId: number | null) {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { body: string; image?: File }) => {
            let data;
            if (input.image) {
                const fd = new FormData();
                fd.append('body', input.body);
                fd.append('image', input.image);
                ({ data } = await api!.post<{ data: Message }>(`/messaging/conversations/${conversationId}/comment/private-reply`, fd));
            } else {
                ({ data } = await api!.post<{ data: Message }>(`/messaging/conversations/${conversationId}/comment/private-reply`, { body: input.body }));
            }
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['messaging', 'thread'] });
            qc.invalidateQueries({ queryKey: ['messaging', 'conversations'] });
        },
    });
}
