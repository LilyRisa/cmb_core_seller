import { useQuery } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

/** Range cố định cho dashboard — match BE `DashboardController::ALLOWED_RANGES`. */
export type DashboardRange = 7 | 30 | 90;

export interface KpiPair { current: number; previous: number }

export interface DashboardSeriesPoint {
    date: string;                // YYYY-MM-DD
    orders: number;
    revenue: number;
    estimated_profit: number;    // sau phí sàn ước tính (SPEC 0012)
    gross_profit: number;        // FIFO COGS, đơn đã ship (SPEC 0014)
}

export interface DashboardSummary {
    range: DashboardRange;
    period: { from: string; to: string };
    previous_period: { from: string; to: string };
    channel_accounts: { total: number; active: number; needs_reconnect: number };
    orders: { today: number; to_process: number; ready_to_ship: number; shipped: number; has_issue: number; unmapped: number; total: number };
    revenue_today: number;
    kpis: {
        revenue: KpiPair;
        orders: KpiPair;
        avg_order_value: KpiPair;
        estimated_profit: KpiPair;
        gross_profit: KpiPair;
        margin_pct: KpiPair;
    };
    series: DashboardSeriesPoint[];
    by_source: Array<{ source: string; orders: number; revenue: number }>;
    top_skus: Array<{ sku_id: number; sku_code: string; name: string; image_url: string | null; qty: number; revenue: number }>;
}

export function useDashboardSummary(range: DashboardRange = 7) {
    const tenantId = useCurrentTenantId();
    const api = useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
    return useQuery({
        queryKey: ['dashboard', tenantId, range],
        enabled: api != null,
        // 60s là cân bằng giữa "tươi" và chi phí query — dashboard không cần realtime tuyệt đối.
        refetchInterval: 60_000,
        placeholderData: (p) => p,
        queryFn: async () => {
            const { data } = await api!.get<{ data: DashboardSummary }>('/dashboard/summary', { params: { range } });
            return data.data;
        },
    });
}

/** Tính delta % so kỳ trước; trả null khi không có baseline (kỳ trước = 0). */
export function deltaPct(pair: KpiPair): number | null {
    if (pair.previous === 0) return pair.current === 0 ? 0 : null;
    return Math.round(((pair.current - pair.previous) / pair.previous) * 1000) / 10;
}
