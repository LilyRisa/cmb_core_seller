import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

/**
 * SPEC 0037 — popup announcement toàn hệ thống (do super-admin tạo). Toàn hệ thống nên
 * KHÔNG cần tenant header; chỉ cần đăng nhập. FE nhớ-đã-xem theo TAB qua sessionStorage.
 */
export interface ActiveAnnouncement {
    id: number;
    title: string;
    body_html: string;
    dismiss_label: string;
}

export function useActiveAnnouncements() {
    return useQuery({
        queryKey: ['announcements', 'active'],
        queryFn: async () => (await api.get<{ data: ActiveAnnouncement[] }>('/announcements/active')).data.data,
        staleTime: 5 * 60_000,
    });
}
