// SPEC 0037 — hooks quản lý popup announcement ở /api/v1/admin/announcements/*.
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { adminClient } from './adminClient';

export interface AdminAnnouncement {
    id: number;
    title: string;
    body_html: string;
    is_active: boolean;
    starts_at: string | null;
    ends_at: string | null;
    dismiss_label: string;
    created_at: string | null;
}

export interface AnnouncementInput {
    title: string;
    body_html: string;
    is_active?: boolean;
    starts_at?: string | null;
    ends_at?: string | null;
    dismiss_label?: string;
}

interface ListResponse {
    data: AdminAnnouncement[];
    meta: { pagination: { total: number } };
}

export function useAdminAnnouncements() {
    return useQuery({
        queryKey: ['admin', 'announcements'],
        queryFn: async () => (await adminClient.get<ListResponse>('/announcements')).data,
    });
}

export function useCreateAnnouncement() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: AnnouncementInput) =>
            (await adminClient.post<{ data: AdminAnnouncement }>('/announcements', input)).data.data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'announcements'] }),
    });
}

export function useUpdateAnnouncement() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, ...input }: { id: number } & Partial<AnnouncementInput>) =>
            (await adminClient.patch<{ data: AdminAnnouncement }>(`/announcements/${id}`, input)).data.data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'announcements'] }),
    });
}

export function useDeleteAnnouncement() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => (await adminClient.delete(`/announcements/${id}`)).data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'announcements'] }),
    });
}

/** Upload ảnh/video trong editor → R2; trả URL công khai để chèn vào nội dung. */
export async function uploadAnnouncementMedia(file: File): Promise<string> {
    const fd = new FormData();
    fd.append('file', file);
    const { data } = await adminClient.post<{ data: { url: string } }>('/announcements/media', fd);
    return data.data.url;
}
