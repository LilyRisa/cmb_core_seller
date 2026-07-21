import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface AdminOverview {
    tenants: {
        active_total: number;
        by_plan: Array<{ plan_code: string; plan_name: string; count: number }>;
        new_by_day: Array<{ date: string; count: number }>;
        trial_ending_soon: Array<{ tenant_id: number; tenant_name: string; trial_ends_at: string }>;
    };
    revenue: {
        mrr_estimate: number;
        invoices_this_month: { paid_count: number; paid_total: number; pending_count: number; pending_total: number };
        revenue_by_month: Array<{ period_ym: number; total: number }>;
        active_vouchers: number;
    };
    support: {
        open_count: number;
        avg_resolution_hours: number;
        recent_audit_log: Array<{ action: string; actor: string; at: string }>;
    };
    ai_usage: {
        calls_this_month: number;
        top_tenants: Array<{ tenant_id: number; tenant_name: string; calls_this_month: number }>;
    };
}

export function useAdminOverview() {
    return useQuery({
        queryKey: ['admin', 'dashboard', 'overview'],
        queryFn: async () => {
            const { data } = await api.get<{ data: AdminOverview }>('/admin/dashboard/overview');
            return data.data;
        },
        staleTime: 60_000,
    });
}
