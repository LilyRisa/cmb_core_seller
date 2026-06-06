import { useMutation, useQuery } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

export type MetricGroup = 'fulfillment' | 'listing' | 'customer_service' | 'rating' | 'sales' | 'other';
export type MetricUnit = 'percent' | 'number' | 'second' | 'day' | 'hour' | 'minute' | 'money';

export interface ShopMetric {
    key: string;
    name: string;
    group: MetricGroup | string;
    value: number | null;
    unit: MetricUnit | string;
    target: number | null;
    comparator: string | null;
    passed: boolean | null;
}

export interface PenaltyPoint {
    points: number;
    violation_type: number | null;
    violation_label: string | null;
    issued_at: string | null;
    reference_id: string | null;
}

export interface Punishment {
    type: number | null;
    type_label: string | null;
    tier: number | null;
    start_at: string | null;
    end_at: string | null;
    ongoing: boolean;
}

/** Sự kiện điểm phạt/vi phạm real-time nhận qua webhook sàn. */
export interface PenaltyEvent {
    kind: 'penalty_issued' | 'penalty_removed' | 'tier_update' | 'listing_violation' | string;
    points: number;
    violation_label: string | null;
    tier: number | null;
    item_name: string | null;
    occurred_at: string | null;
}

export interface ShopReportEntry {
    channel_account_id: number;
    provider: 'lazada' | 'shopee' | 'tiktok' | string;
    shop_name: string;
    available: boolean;
    kind: 'health' | 'performance' | null;
    overall_rating: number | null;   // Shopee 1..4
    overall_label: string | null;
    metrics: ShopMetric[];
    penalties: PenaltyPoint[];
    punishments: Punishment[];
    supports_penalty: boolean;
    recent_penalty_events: PenaltyEvent[];
    note: string | null;
    error: string | null;
    passed_count?: number;
    failed_count?: number;
    total_metrics?: number;
    penalty_error?: string;
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

/** Báo cáo sàn (sức khỏe/hiệu suất/điểm phạt) cho mọi gian hàng đã kết nối. */
export function useShopReport() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['channel-shop-report', tenantId],
        enabled: api != null,
        staleTime: 60_000,
        queryFn: async () => {
            const { data } = await api!.get<{ data: ShopReportEntry[] }>('/channel-shop-report');
            return data.data;
        },
    });
}

export interface ShopAiInsight {
    score: number;
    label: string;
    assessment: string;
    recommendations: Array<{ action: string; rationale: string }>;
    ai_narrative: string | null;
    source: 'rule' | 'ai';
}

/** Phân tích AI sức khỏe cho 1 gian hàng (chấm điểm + khuyến nghị). */
export function useShopAiInsight() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (channelAccountId: number) => {
            const { data } = await api!.post<{ data: ShopAiInsight }>(`/channel-shop-report/${channelAccountId}/ai-insight`);
            return data.data;
        },
    });
}

export const PROVIDER_LABEL: Record<string, string> = {
    lazada: 'Lazada',
    shopee: 'Shopee',
    tiktok: 'TikTok Shop',
};

export const RATING_COLOR: Record<number, string> = { 1: 'red', 2: 'orange', 3: 'green', 4: 'green' };

/** Định dạng giá trị chỉ số theo đơn vị. */
export function formatMetric(value: number | null, unit: string): string {
    if (value == null) return '—';
    switch (unit) {
        case 'percent':
            return `${value.toLocaleString('vi-VN', { maximumFractionDigits: 2 })}%`;
        case 'money':
            return value.toLocaleString('vi-VN');
        case 'second':
            return `${value.toLocaleString('vi-VN', { maximumFractionDigits: 1 })} giây`;
        case 'minute':
            return `${value.toLocaleString('vi-VN', { maximumFractionDigits: 1 })} phút`;
        case 'hour':
            return `${value.toLocaleString('vi-VN', { maximumFractionDigits: 1 })} giờ`;
        case 'day':
            return `${value.toLocaleString('vi-VN', { maximumFractionDigits: 1 })} ngày`;
        default:
            return value.toLocaleString('vi-VN', { maximumFractionDigits: 2 });
    }
}
