// SPEC 0039 — hooks quản lý thư viện hình nền Desktop ở /api/v1/admin/desktop-backgrounds/*.
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { adminClient } from './adminClient';

export interface AdminDesktopBackground {
    id: number;
    name: string;
    image_url: string;
    image_path: string;
    is_active: boolean;
    position: number;
}

export interface DesktopBackgroundInput {
    name: string;
    image_url: string;
    image_path: string;
    is_active?: boolean;
    position?: number;
}

export function useAdminDesktopBackgrounds() {
    return useQuery({
        queryKey: ['admin', 'desktop-backgrounds'],
        queryFn: async () => (await adminClient.get<{ data: AdminDesktopBackground[] }>('/desktop-backgrounds')).data.data,
    });
}

export function useCreateDesktopBackground() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: DesktopBackgroundInput) =>
            (await adminClient.post<{ data: AdminDesktopBackground }>('/desktop-backgrounds', input)).data.data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'desktop-backgrounds'] }),
    });
}

export function useUpdateDesktopBackground() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, ...input }: { id: number } & Partial<DesktopBackgroundInput>) =>
            (await adminClient.patch<{ data: AdminDesktopBackground }>(`/desktop-backgrounds/${id}`, input)).data.data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'desktop-backgrounds'] }),
    });
}

export function useDeleteDesktopBackground() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => (await adminClient.delete(`/desktop-backgrounds/${id}`)).data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'desktop-backgrounds'] }),
    });
}

/** Upload ảnh nền → R2; trả {url, path} để lưu kèm preset. */
export async function uploadDesktopBackgroundMedia(file: File): Promise<{ url: string; path: string }> {
    const fd = new FormData();
    fd.append('file', file);
    const { data } = await adminClient.post<{ data: { url: string; path: string } }>('/desktop-backgrounds/media', fd);
    return data.data;
}
