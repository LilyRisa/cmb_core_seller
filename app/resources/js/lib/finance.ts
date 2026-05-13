import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import type { Paginated } from './orders';
import { useCurrentTenantId } from './tenant';

export interface SettlementLine {
    id: number;
    order_id: number | null;
    external_order_id: string | null;
    external_line_id: string | null;
    fee_type: 'revenue' | 'commission' | 'payment_fee' | 'shipping_fee' | 'shipping_subsidy' | 'voucher_seller' | 'voucher_platform' | 'adjustment' | 'refund' | 'other';
    amount: number;
    occurred_at: string | null;
    description: string | null;
    order: { id: number; order_number: string | null; external_order_id: string | null } | null;
}

export interface Settlement {
    id: number;
    channel_account_id: number;
    channel_account: { id: number; name: string; provider: string } | null;
    external_id: string | null;
    period_start: string | null;
    period_end: string | null;
    currency: string;
    total_payout: number;
    total_revenue: number;
    total_fee: number;
    total_shipping_fee: number;
    status: 'pending' | 'reconciled' | 'error';
    status_label: string;
    fetched_at: string | null;
    reconciled_at: string | null;
    paid_at: string | null;
    lines_count?: number;
    lines?: SettlementLine[];
    created_at: string | null;
}

export const FEE_TYPE_LABEL: Record<SettlementLine['fee_type'], string> = {
    revenue: 'Doanh thu', commission: 'Hoa hồng sàn', payment_fee: 'Phí thanh toán',
    shipping_fee: 'Phí vận chuyển', shipping_subsidy: 'Trợ giá ship',
    voucher_seller: 'Voucher người bán', voucher_platform: 'Voucher sàn',
    adjustment: 'Điều chỉnh', refund: 'Hoàn tiền', other: 'Khác',
};

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export function useSettlements(filters: { channel_account_id?: number; status?: string; from?: string; to?: string; page?: number; per_page?: number } = {}) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['settlements', tenantId, filters],
        enabled: api != null,
        placeholderData: (p) => p,
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '') params[k] = v as never; });
            const { data } = await api!.get<Paginated<Settlement>>('/settlements', { params });
            return data;
        },
    });
}

export function useSettlement(id: number | null | undefined) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['settlement', tenantId, id],
        enabled: api != null && id != null,
        queryFn: async () => { const { data } = await api!.get<{ data: Settlement }>(`/settlements/${id}`); return data.data; },
    });
}

export function useReconcileSettlement() {
    const qc = useQueryClient();
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (id: number) => { const { data } = await api!.post<{ data: { matched: number; settlement: Settlement } }>(`/settlements/${id}/reconcile`); return data.data; },
        onSuccess: (_, id) => { qc.invalidateQueries({ queryKey: ['settlements', tenantId] }); qc.invalidateQueries({ queryKey: ['settlement', tenantId, id] }); },
    });
}

export function useFetchSettlementsForShop() {
    const qc = useQueryClient();
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async ({ channelAccountId, from, to, sync }: { channelAccountId: number; from?: string; to?: string; sync?: boolean }) => {
            const { data } = await api!.post<{ data: { queued?: boolean; fetched?: number; lines?: number } }>(`/channel-accounts/${channelAccountId}/fetch-settlements`, { from, to, sync });
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['settlements', tenantId] }),
    });
}
