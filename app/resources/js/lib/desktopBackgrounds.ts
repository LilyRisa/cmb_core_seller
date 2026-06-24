// SPEC 0039 — preset hình nền Desktop đang bật (cho người dùng chọn ở Cài đặt → Giao diện).
import { useQuery } from '@tanstack/react-query';
import { api } from './api';

export interface DesktopBackgroundOption {
    id: number;
    name: string;
    image_url: string;
}

export function useDesktopBackgrounds() {
    return useQuery({
        queryKey: ['desktop-backgrounds'],
        queryFn: async () => (await api.get<{ data: DesktopBackgroundOption[] }>('/desktop-backgrounds')).data.data,
        staleTime: 5 * 60_000,
    });
}
