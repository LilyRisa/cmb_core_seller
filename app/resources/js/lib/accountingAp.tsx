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

export interface ApAgingRow {
    supplier_id: number;
    supplier_name: string | null;
    supplier_code: string | null;
    total: number;
    b0_30: number;
    b31_60: number;
    b61_90: number;
    b90p: number;
}

export interface ApAgingResponse {
    data: ApAgingRow[];
    meta: { total_balance: number; total_b0_30: number; total_b31_60: number; total_b61_90: number; total_b90p: number };
}

export function useApAging() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'ap-aging'],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<ApAgingResponse>('/accounting/ap/aging');
            return data;
        },
    });
}

export interface VendorBill {
    id: number;
    code: string;
    supplier_id: number | null;
    purchase_order_id: number | null;
    goods_receipt_id: number | null;
    bill_no: string | null;
    bill_date: string;
    due_date: string | null;
    subtotal: number;
    tax: number;
    total: number;
    status: 'draft' | 'recorded' | 'paid' | 'void';
    status_label: string;
    memo: string | null;
    journal_entry_id: number | null;
    created_at: string | null;
    recorded_at: string | null;
}

export interface VendorPayment {
    id: number;
    code: string;
    supplier_id: number | null;
    paid_at: string;
    amount: number;
    payment_method: 'cash' | 'bank' | 'ewallet';
    applied_bills: Array<{ vendor_bill_id: number; applied_amount: number }> | null;
    memo: string | null;
    status: 'draft' | 'confirmed' | 'cancelled';
    status_label: string;
    created_at: string | null;
    confirmed_at: string | null;
}

interface ListResp<T> { data: T[]; meta: { current_page: number; last_page: number; per_page: number; total: number } }

export function useVendorBills(filters: { status?: string; supplier_id?: number; page?: number; per_page?: number } = {}) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'vendor-bills', filters],
        enabled: api != null,
        placeholderData: (prev) => prev,
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            (Object.keys(filters) as Array<keyof typeof filters>).forEach((k) => {
                const v = filters[k];
                if (v !== undefined) params[k] = v as string | number;
            });
            const { data } = await api!.get<ListResp<VendorBill>>('/accounting/vendor-bills', { params });
            return data;
        },
    });
}

export function useCreateBill() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { supplier_id?: number; bill_no?: string; bill_date: string; due_date?: string; subtotal: number; tax?: number; memo?: string }) => {
            const { data } = await api!.post<{ data: VendorBill }>('/accounting/vendor-bills', vars);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', tenantId, 'vendor-bills'] }),
    });
}

export function useRecordBill() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            const { data } = await api!.post<{ data: VendorBill }>(`/accounting/vendor-bills/${id}/record`);
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['accounting', tenantId] });
        },
    });
}

export function useVendorPayments(filters: { status?: string; supplier_id?: number; page?: number; per_page?: number } = {}) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'vendor-payments', filters],
        enabled: api != null,
        placeholderData: (prev) => prev,
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            (Object.keys(filters) as Array<keyof typeof filters>).forEach((k) => {
                const v = filters[k];
                if (v !== undefined) params[k] = v as string | number;
            });
            const { data } = await api!.get<ListResp<VendorPayment>>('/accounting/vendor-payments', { params });
            return data;
        },
    });
}

export function useCreatePayment() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { supplier_id?: number; paid_at: string; amount: number; payment_method: 'cash' | 'bank' | 'ewallet'; memo?: string }) => {
            const { data } = await api!.post<{ data: VendorPayment }>('/accounting/vendor-payments', vars);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', tenantId, 'vendor-payments'] }),
    });
}

export function useConfirmPayment() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            const { data } = await api!.post<{ data: VendorPayment }>(`/accounting/vendor-payments/${id}/confirm`);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', tenantId] }),
    });
}
