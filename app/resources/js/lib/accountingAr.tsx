import { useMemo } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { tenantApi } from './api';
import { getCurrentTenantId, useAuth } from './auth';

function useCurrentTenantId(): number | null {
    const { data: user } = useAuth();
    return getCurrentTenantId() ?? user?.tenants[0]?.id ?? null;
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export interface AgingRow {
    customer_id: number;
    customer_name: string | null;
    customer_phone: string | null;
    reputation_label: string | null;
    total: number;
    b0_30: number;
    b31_60: number;
    b61_90: number;
    b90p: number;
}

export interface AgingResponse {
    data: AgingRow[];
    meta: {
        total_balance: number;
        total_b0_30: number;
        total_b31_60: number;
        total_b61_90: number;
        total_b90p: number;
    };
}

export function useArAging() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'ar-aging'],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<AgingResponse>('/accounting/ar/aging');
            return data;
        },
    });
}

export interface CustomerReceipt {
    id: number;
    code: string;
    customer_id: number | null;
    received_at: string;
    amount: number;
    payment_method: 'cash' | 'bank' | 'ewallet';
    cash_account_id: number | null;
    applied_orders: Array<{ order_id: number; applied_amount: number }> | null;
    memo: string | null;
    journal_entry_id: number | null;
    status: 'draft' | 'confirmed' | 'cancelled';
    status_label: string;
    created_at: string | null;
    confirmed_at: string | null;
}

export interface ReceiptListResponse {
    data: CustomerReceipt[];
    meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export function useReceipts(filters: { status?: string; customer_id?: number; page?: number; per_page?: number } = {}) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'receipts', filters],
        enabled: api != null,
        placeholderData: (prev) => prev,
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            (Object.keys(filters) as Array<keyof typeof filters>).forEach((k) => {
                const v = filters[k];
                if (v !== undefined) params[k] = v as string | number;
            });
            const { data } = await api!.get<ReceiptListResponse>('/accounting/customer-receipts', { params });
            return data;
        },
    });
}

export function useCreateReceipt() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: {
            customer_id?: number;
            received_at: string;
            amount: number;
            payment_method: 'cash' | 'bank' | 'ewallet';
            applied_orders?: Array<{ order_id: number; applied_amount: number }>;
            memo?: string;
        }) => {
            const { data } = await api!.post<{ data: CustomerReceipt }>('/accounting/customer-receipts', vars);
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['accounting', tenantId, 'receipts'] });
            qc.invalidateQueries({ queryKey: ['accounting', tenantId, 'ar-aging'] });
        },
    });
}

export function useConfirmReceipt() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            const { data } = await api!.post<{ data: CustomerReceipt }>(`/accounting/customer-receipts/${id}/confirm`);
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['accounting', tenantId] });
        },
    });
}

export function useCancelReceipt() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            const { data } = await api!.post<{ data: CustomerReceipt }>(`/accounting/customer-receipts/${id}/cancel`);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', tenantId, 'receipts'] }),
    });
}
