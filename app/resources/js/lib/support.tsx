import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

/**
 * Data layer cho widget Trợ giúp (module Support): tab "Hỏi AI" (RAG hỏi-đáp cách
 * dùng hệ thống) + tab "Hỏi CSKH" (hội thoại nhiều tin + đính kèm, SPEC-0028).
 */

export interface HelpSource {
    title: string;
    module: string | null;
    screen: string | null;
    score: number;
}

export interface HelpAnswer {
    answer: string;
    sources: HelpSource[];
    /** rag | keyword | *_no_llm | no_docs | empty — để FE biết nguồn câu trả lời. */
    mode: string;
}

export interface ChatTurn {
    role: 'user' | 'assistant';
    content: string;
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

/** Hỏi trợ lý AI (RAG). Gửi kèm vài lượt hội thoại gần nhất làm ngữ cảnh. */
export function useAskAssistant() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (input: { question: string; history?: ChatTurn[] }) => {
            const { data } = await api!.post<{ data: HelpAnswer }>('/support/assistant/ask', {
                question: input.question,
                history: input.history ?? [],
            });
            return data.data;
        },
    });
}

// --- Tab "Hỏi CSKH" — hội thoại nhiều tin + đính kèm (SPEC-0028) ---------------

export type SupportAttachmentKind = 'image' | 'video' | 'file';

export interface SupportAttachment {
    id: number;
    kind: SupportAttachmentKind;
    mime: string;
    size_bytes: number | null;
    filename: string | null;
    status: string;
    download_url: string | null;
}

export type SupportSender = 'user' | 'cskh';
export type SupportMessageType = 'text' | 'system';

export interface SupportMessage {
    id: number;
    sender: SupportSender;
    type: SupportMessageType;
    body: string | null;
    attachments_count: number;
    attachments?: SupportAttachment[];
    created_at: string | null;
}

export type SupportConversationStatus = 'open' | 'closed';

export interface SupportConversation {
    id: number;
    status: SupportConversationStatus;
    last_sender: SupportSender | null;
    user_unread_count: number;
    last_message_at: string | null;
    closed_at: string | null;
    created_at: string | null;
    messages: SupportMessage[];
}

/**
 * Hội thoại CSKH của tenant (đầy đủ tin + đính kèm signed URL). Poll 8s KHI tab mở.
 * Cùng `queryKey` với widget badge ⇒ React Query gộp observer. Lỗi (vd 402) ⇒ ngừng poll.
 */
export function useSupportConversations(enabled: boolean, intervalMs = 8_000) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['support', 'conversations', tenantId],
        enabled: enabled && api != null,
        retry: false,
        refetchInterval: (query) => (!enabled || query.state.status === 'error' ? false : intervalMs),
        queryFn: async () => (await api!.get<{ data: SupportConversation[] }>('/support/conversations')).data.data,
    });
}

/**
 * Nguồn NHẸ cho badge widget (tổng tin CSKH chưa đọc của tenant). Poll TOÀN CỤC 20s
 * (mount ở HelpChatWidget) — không cần tải cả thread.
 */
export function useSupportUnread(enabled: boolean, intervalMs = 20_000) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['support', 'unread', tenantId],
        enabled: enabled && api != null,
        retry: false,
        refetchInterval: (query) => (!enabled || query.state.status === 'error' ? false : intervalMs),
        queryFn: async () => (await api!.get<{ data: { unread: number } }>('/support/unread')).data.data.unread,
    });
}

/** Gửi tin CSKH (multipart body + files[]). Tự mở cuộc mới nếu cuộc gần nhất đã đóng. */
export function useSendSupportMessage() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (input: { body?: string; files?: File[] }) => {
            const fd = new FormData();
            if (input.body && input.body.trim() !== '') fd.append('body', input.body);
            (input.files ?? []).forEach((f) => fd.append('files[]', f));
            const { data } = await api!.post<{ data: SupportConversation }>('/support/messages', fd);
            return data.data;
        },
        onSuccess: () => {
            void qc.invalidateQueries({ queryKey: ['support', 'conversations', tenantId] });
            void qc.invalidateQueries({ queryKey: ['support', 'unread', tenantId] });
        },
    });
}

/** Đánh dấu đã đọc 1 cuộc ⇒ xoá unread (badge về 0). */
export function useMarkSupportRead() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (conversationId: number) =>
            (await api!.post<{ data: { unread: number } }>(`/support/conversations/${conversationId}/read`)).data.data.unread,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['support', 'unread', tenantId] }),
    });
}
