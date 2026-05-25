import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

/** Đơn Hoàn & Hủy (after-sales) — SPEC 0025. */
export interface ReturnRecord {
    id: number;
    source: string;
    kind: 'cancel' | 'return' | 'refund';
    status: AfterSalesStatus;
    status_label: string;
    raw_status: string | null;
    external_return_id: string;
    external_order_id: string | null;
    order_id: number | null;
    order_number: string | null;
    reason: string | null;
    refund_amount: number;
    currency: string;
    items: Array<{ seller_sku?: string; name?: string; quantity?: number }>;
    requested_at: string | null;
    decided_at: string | null;
    created_at: string | null;
}

export type AfterSalesStatus = 'requested' | 'approved' | 'rejected' | 'processing' | 'completed' | 'cancelled';

export const AFTER_SALES_STATUS_LABEL: Record<AfterSalesStatus, string> = {
    requested: 'Chờ xử lý',
    approved: 'Đã duyệt',
    rejected: 'Đã từ chối',
    processing: 'Đang xử lý',
    completed: 'Hoàn tất',
    cancelled: 'Đã huỷ yêu cầu',
};

export const KIND_LABEL: Record<ReturnRecord['kind'], string> = {
    cancel: 'Hủy đơn',
    return: 'Trả hàng',
    refund: 'Hoàn tiền',
};

export interface ReturnsFilters {
    status?: string;
    kind?: string;
    source?: string;
    open_only?: boolean;
    q?: string;
    page?: number;
    per_page?: number;
}

interface Paginated<T> {
    data: T[];
    meta: { pagination: { page: number; per_page: number; total: number; total_pages: number } };
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export function useReturns(filters: ReturnsFilters) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['returns', tenantId, filters],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<Paginated<ReturnRecord>>('/returns', { params: filters });
            return data;
        },
    });
}

export interface ReturnStats {
    by_status: Record<string, number>;
    open: number;
    requested: number;
}

export function useReturnStats() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['returns', 'stats', tenantId],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: ReturnStats }>('/returns/stats');
            return data.data;
        },
    });
}

export function useDecideReturn() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (vars: { id: number; action: 'approve' | 'reject'; comment?: string }) => {
            const { data } = await api!.post<{ data: ReturnRecord }>(`/returns/${vars.id}/${vars.action}`, { comment: vars.comment });
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['returns', tenantId] });
            qc.invalidateQueries({ queryKey: ['returns', 'stats', tenantId] });
        },
    });
}
