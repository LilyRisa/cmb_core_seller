// Plan C (2026-07-23) — hooks quản lý "trang thông báo chung" ở /api/v1/admin/general-notification-pages/*.
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { adminClient } from './adminClient';

export interface AdminGeneralNotificationPage {
    id: number;
    title: string;
    slug: string;
    body_html: string;
    cover_image_url: string | null;
    cta_label: string | null;
    cta_url: string | null;
    audience_type: 'all' | 'tenant_ids';
    audience_tenant_ids: number[] | null;
    status: 'draft' | 'scheduled' | 'sent';
    scheduled_at: string | null;
    expires_at: string | null;
    sent_at: string | null;
    created_at: string | null;
}

export interface GeneralNotificationPageInput {
    title: string;
    body_html: string;
    cover_image_url?: string | null;
    cta_label?: string | null;
    cta_url?: string | null;
    audience_type: 'all' | 'tenant_ids';
    audience_tenant_ids?: number[];
    scheduled_at?: string | null;
    expires_at?: string | null;
}

interface ListResponse {
    data: AdminGeneralNotificationPage[];
    meta: { pagination: { total: number } };
}

export function useAdminGeneralNotificationPages() {
    return useQuery({
        queryKey: ['admin', 'general-notification-pages'],
        queryFn: async () => (await adminClient.get<ListResponse>('/general-notification-pages')).data,
    });
}

export function useCreateGeneralNotificationPage() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: GeneralNotificationPageInput) =>
            (await adminClient.post<{ data: AdminGeneralNotificationPage }>('/general-notification-pages', input)).data.data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'general-notification-pages'] }),
    });
}

export function useUpdateGeneralNotificationPage() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, ...input }: { id: number } & Partial<GeneralNotificationPageInput>) =>
            (await adminClient.patch<{ data: AdminGeneralNotificationPage }>(`/general-notification-pages/${id}`, input)).data.data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'general-notification-pages'] }),
    });
}

export function useDeleteGeneralNotificationPage() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => (await adminClient.delete(`/general-notification-pages/${id}`)).data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'general-notification-pages'] }),
    });
}

export function useSendGeneralNotificationPage() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await adminClient.post<{ data: { dispatched: boolean } }>(`/general-notification-pages/${id}/send`)).data.data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'general-notification-pages'] }),
    });
}

/** Upload ảnh bìa trong form → R2; trả URL công khai. */
export async function uploadGeneralNotificationPageMedia(file: File): Promise<string> {
    const fd = new FormData();
    fd.append('file', file);
    const { data } = await adminClient.post<{ data: { url: string } }>('/general-notification-pages/media', fd);
    return data.data.url;
}
