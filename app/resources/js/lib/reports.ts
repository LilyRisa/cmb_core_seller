import { useQuery } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

export type Granularity = 'day' | 'week' | 'month';

export interface ReportFilters {
    from?: string;
    to?: string;
    granularity?: Granularity;
    source?: string;
    channel_account_id?: number;
}

export interface RevenueReport {
    from: string;
    to: string;
    granularity: Granularity;
    totals: { orders: number; revenue: number; item_total: number; shipping_fee: number; avg_order_value: number };
    series: Array<{ date: string; orders: number; revenue: number; shipping_fee: number }>;
    by_source: Array<{ source: string; orders: number; revenue: number }>;
}

export interface ProfitReport {
    from: string;
    to: string;
    granularity: Granularity;
    totals: { orders: number; revenue: number; cogs: number; gross_profit: number; margin_pct: number };
    series: Array<{ date: string; revenue: number; cogs: number; gross_profit: number; margin_pct: number }>;
}

export interface TopProductsReport {
    from: string;
    to: string;
    sort_by: 'revenue' | 'profit' | 'qty';
    limit: number;
    items: Array<{
        sku_id: number;
        sku: { id: number; sku_code: string; name: string; image_url: string | null } | null;
        qty: number;
        revenue: number;
        cogs: number;
        gross_profit: number;
        margin_pct: number;
    }>;
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

function buildParams(filters: ReportFilters | (ReportFilters & Record<string, unknown>)): Record<string, string | number> {
    const out: Record<string, string | number> = {};
    Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '' && v !== null) out[k] = v as never; });

    return out;
}

export function useRevenueReport(filters: ReportFilters) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['reports/revenue', tenantId, filters],
        enabled: api != null,
        placeholderData: (p) => p,
        queryFn: async () => { const { data } = await api!.get<{ data: RevenueReport }>('/reports/revenue', { params: buildParams(filters) }); return data.data; },
    });
}

export function useProfitReport(filters: ReportFilters) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['reports/profit', tenantId, filters],
        enabled: api != null,
        placeholderData: (p) => p,
        queryFn: async () => { const { data } = await api!.get<{ data: ProfitReport }>('/reports/profit', { params: buildParams(filters) }); return data.data; },
    });
}

export function useTopProductsReport(filters: ReportFilters & { limit?: number; sort_by?: 'revenue' | 'profit' | 'qty' }) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['reports/top-products', tenantId, filters],
        enabled: api != null,
        placeholderData: (p) => p,
        queryFn: async () => { const { data } = await api!.get<{ data: TopProductsReport }>('/reports/top-products', { params: buildParams(filters) }); return data.data; },
    });
}

/** URL for direct CSV download (server streams) — open in new tab. */
export function exportReportUrl(tenantId: number, type: 'revenue' | 'profit' | 'top-products', filters: ReportFilters & { limit?: number; sort_by?: string }): string {
    const params = new URLSearchParams({ type });
    Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '' && v !== null) params.set(k, String(v)); });

    return `/api/v1/reports/export?${params.toString()}&X-Tenant-Id=${tenantId}`;
}
