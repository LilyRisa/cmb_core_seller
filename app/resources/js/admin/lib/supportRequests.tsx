// Hooks cho /api/v1/admin/support-requests/* — admin xem & trả lời yêu cầu CSKH
// (tab "Hỏi CSKH" phía user) xuyên tenant.

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export type SupportRequestStatus = 'pending' | 'answered' | 'closed';

export interface AdminSupportRequest {
    id: number;
    tenant_id: number;
    tenant: { id: number; name: string } | null;
    user: { id: number; name: string; email: string } | null;
    question: string;
    status: SupportRequestStatus;
    answer: string | null;
    answered_at: string | null;
    created_at: string | null;
}

interface Paginated {
    data: AdminSupportRequest[];
    meta: { pagination: { page: number; per_page: number; total: number; total_pages: number } };
}

export function useAdminSupportRequests(params: { status?: string; q?: string; page: number }) {
    return useQuery({
        queryKey: ['admin-support-requests', params],
        queryFn: async () =>
            (await api.get<Paginated>('/admin/support-requests', {
                params: { status: params.status || undefined, q: params.q || undefined, page: params.page, per_page: 50 },
            })).data,
    });
}

export function useAnswerSupportRequest() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, answer }: { id: number; answer: string }) =>
            (await api.post(`/admin/support-requests/${id}/answer`, { answer })).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-support-requests'] }),
    });
}

export function useCloseSupportRequest() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api.post(`/admin/support-requests/${id}/close`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-support-requests'] }),
    });
}
