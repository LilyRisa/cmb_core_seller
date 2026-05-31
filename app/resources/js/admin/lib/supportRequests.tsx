// Hooks cho /api/v1/admin/support-conversations/* — admin xem & nhắn nhiều tin +
// đóng hội thoại CSKH (tab "Hỏi CSKH" phía user) xuyên tenant (SPEC-0028).

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import type { SupportMessage } from '@/lib/support';

export type { SupportMessage } from '@/lib/support';
export type SupportConversationStatus = 'open' | 'closed';

/** Tóm tắt 1 hội thoại trong danh sách admin. */
export interface AdminSupportConversation {
    id: number;
    tenant_id: number;
    tenant: { id: number; name: string } | null;
    user: { id: number; name: string; email: string } | null;
    status: SupportConversationStatus;
    last_sender: 'user' | 'cskh' | null;
    awaiting: boolean;
    message_count: number;
    last_preview: string | null;
    user_unread_count: number;
    last_message_at: string | null;
    closed_at: string | null;
    created_at: string | null;
}

/** Thread đầy đủ (summary + danh sách tin). */
export interface AdminSupportThread extends AdminSupportConversation {
    messages: SupportMessage[];
}

interface Paginated {
    data: AdminSupportConversation[];
    meta: { pagination: { page: number; per_page: number; total: number; total_pages: number } };
}

export function useAdminSupportConversations(params: { status?: string; awaiting?: boolean; q?: string; page: number }) {
    return useQuery({
        queryKey: ['admin-support-conversations', params],
        queryFn: async () =>
            (await api.get<Paginated>('/admin/support-conversations', {
                params: {
                    status: params.status || undefined,
                    awaiting: params.awaiting ? 1 : undefined,
                    q: params.q || undefined,
                    page: params.page, per_page: 50,
                },
            })).data,
    });
}

export function useAdminSupportThread(id: number | null) {
    return useQuery({
        queryKey: ['admin-support-thread', id],
        enabled: id != null,
        refetchInterval: id != null ? 10_000 : false,
        queryFn: async () => (await api.get<{ data: AdminSupportThread }>(`/admin/support-conversations/${id}`)).data.data,
    });
}

export function useSendAdminSupportMessage() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, body, files }: { id: number; body?: string; files?: File[] }) => {
            const fd = new FormData();
            if (body && body.trim() !== '') fd.append('body', body);
            (files ?? []).forEach((f) => fd.append('files[]', f));
            return (await api.post<{ data: AdminSupportThread }>(`/admin/support-conversations/${id}/messages`, fd)).data.data;
        },
        onSuccess: (_d, vars) => {
            void qc.invalidateQueries({ queryKey: ['admin-support-thread', vars.id] });
            void qc.invalidateQueries({ queryKey: ['admin-support-conversations'] });
        },
    });
}

export function useCloseAdminSupportConversation() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api.post<{ data: AdminSupportThread }>(`/admin/support-conversations/${id}/close`)).data.data,
        onSuccess: (_d, id) => {
            void qc.invalidateQueries({ queryKey: ['admin-support-thread', id] });
            void qc.invalidateQueries({ queryKey: ['admin-support-conversations'] });
        },
    });
}
