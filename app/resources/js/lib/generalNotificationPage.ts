import { useQuery } from '@tanstack/react-query';
import { tenantApi } from '@/lib/api';
import { useCurrentTenantId } from '@/lib/tenant';

export interface GeneralNotificationPageContent {
    title: string;
    body_html: string;
    cover_image_url: string | null;
    cta_label: string | null;
    cta_url: string | null;
    sent_at: string | null;
}

/** Plan C (2026-07-23) — nội dung trang "Chung" theo slug (chỉ tenant nằm trong audience đã gửi mới xem được). */
export function useGeneralNotificationPage(slug: string | undefined) {
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['notifications', 'general', tenantId, slug],
        queryFn: async () =>
            (await tenantApi(tenantId!).get<{ data: GeneralNotificationPageContent }>(`/notifications/general/${slug}`)).data.data,
        enabled: tenantId != null && !!slug,
        retry: false,
    });
}
