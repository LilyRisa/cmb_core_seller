import { useQuery } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

export interface DashboardSummary {
    channel_accounts: { total: number; active: number; needs_reconnect: number };
    orders: { today: number; to_process: number; ready_to_ship: number; shipped: number; has_issue: number; total: number };
    revenue_today: number;
}

export function useDashboardSummary() {
    const tenantId = useCurrentTenantId();
    const api = useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
    return useQuery({
        queryKey: ['dashboard', tenantId],
        enabled: api != null,
        refetchInterval: 60_000,
        queryFn: async () => {
            const { data } = await api!.get<{ data: DashboardSummary }>('/dashboard/summary');
            return data.data;
        },
    });
}
