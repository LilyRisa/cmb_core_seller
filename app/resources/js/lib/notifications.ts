import { useEffect, useMemo } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { tenantApi } from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { useCurrentTenantId } from '@/lib/tenant';
import { getEcho, realtimeEnabled } from '@/lib/echo';

/**
 * Thông báo in-app (SPEC 0036) — chuông + danh sách. Nguồn `GET /notifications`
 * (meta.unread_count cho badge). Realtime qua private channel RIÊNG user
 * `tenant.{id}.notifications.{userId}`; Reverb tắt ⇒ polling fallback (refetchInterval).
 */
export type NotificationLevel = 'info' | 'warning' | 'critical';

export type NotificationCategory = 'order' | 'system' | 'general';

export interface AppNotification {
    id: number;
    type: string;
    category: NotificationCategory;
    level: NotificationLevel;
    title: string;
    body: string | null;
    action_url: string | null;
    data: Record<string, unknown> | null;
    is_read: boolean;
    read_at: string | null;
    created_at: string | null;
}

interface NotificationsResponse {
    data: AppNotification[];
    meta: { unread_count: number; unread_count_by_category: Record<NotificationCategory, number> };
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

/** Danh sách + số chưa đọc cho chuông. `category` lọc theo tab panel FE. Poll fallback khi Reverb tắt. */
export function useNotifications(category?: NotificationCategory) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['notifications', 'list', tenantId, category ?? 'all'],
        queryFn: async () =>
            (await api!.get<NotificationsResponse>('/notifications', { params: { limit: 30, category } })).data,
        enabled: api != null,
        // Reverb đẩy realtime ⇒ poll thưa; tắt Reverb ⇒ poll dày hơn để chuông không "đứng".
        refetchInterval: (query) => (query.state.status === 'error' ? false : (realtimeEnabled() ? 60_000 : 30_000)),
    });
}

/** Đánh dấu đã đọc 1 thông báo. */
export function useMarkNotificationRead() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api!.post<{ data: { unread_count: number } }>(`/notifications/${id}/read`)).data.data.unread_count,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['notifications', 'list', tenantId] }),
    });
}

/** Đánh dấu đã đọc tất cả. */
export function useMarkAllNotificationsRead() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async () =>
            (await api!.post<{ data: { unread_count: number } }>('/notifications/read-all')).data.data.unread_count,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['notifications', 'list', tenantId] }),
    });
}

/**
 * Realtime chuông — subscribe channel RIÊNG của user; có `.notification.created` →
 * invalidate để cập nhật NGAY. No-op khi Reverb tắt ⇒ polling fallback vẫn chạy.
 * Gọi 1 lần ở AppLayout.
 */
export function useNotificationsRealtime(): void {
    const tenantId = useCurrentTenantId();
    const { data: user } = useAuth();
    const qc = useQueryClient();
    const userId = user?.id ?? null;

    useEffect(() => {
        const echo = getEcho();
        if (echo == null || tenantId == null || userId == null) return;

        const channelName = `tenant.${tenantId}.notifications.${userId}`;
        echo.private(channelName).listen('.notification.created', () => {
            void qc.invalidateQueries({ queryKey: ['notifications', 'list', tenantId] });
        });

        return () => { echo.leave(channelName); };
    }, [tenantId, userId, qc]);
}
