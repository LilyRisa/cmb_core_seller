import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import type { Paginated } from './orders';
import { useCurrentTenantId } from './tenant';

export interface Supplier {
    id: number;
    code: string;
    name: string;
    phone: string | null;
    email: string | null;
    tax_code: string | null;
    address: string | null;
    payment_terms_days: number;
    note: string | null;
    is_active: boolean;
    prices_count?: number;
    prices?: Array<{ id: number; sku_id: number; unit_cost: number; moq: number; currency: string; is_default: boolean; valid_from: string | null; valid_to: string | null; sku?: { id: number; sku_code: string; name: string; image_url: string | null } | null }>;
    created_at: string | null;
}

export interface PurchaseOrderItem {
    id: number;
    sku_id: number;
    qty_ordered: number;
    qty_received: number;
    qty_remaining: number;
    unit_cost: number;
    subtotal: number;
    note: string | null;
    sku: { id: number; sku_code: string; name: string; image_url: string | null } | null;
}

export interface PurchaseOrder {
    id: number;
    code: string;
    supplier_id: number;
    supplier?: { id: number; code: string; name: string } | null;
    warehouse_id: number;
    warehouse?: { id: number; name: string } | null;
    status: 'draft' | 'confirmed' | 'partially_received' | 'received' | 'cancelled';
    status_label: string;
    expected_at: string | null;
    note: string | null;
    total_qty: number;
    total_cost: number;
    currency: string;
    received_qty?: number;
    progress_percent?: number;
    items?: PurchaseOrderItem[];
    goods_receipts?: Array<{ id: number; code: string; status: string; total_cost: number; confirmed_at: string | null; created_at: string | null }>;
    confirmed_at: string | null;
    cancelled_at: string | null;
    created_at: string | null;
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

// ---- Suppliers --------------------------------------------------------------

export function useSuppliers(filters: { q?: string; is_active?: boolean; page?: number; per_page?: number } = {}) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['suppliers', tenantId, filters],
        enabled: api != null,
        placeholderData: (p) => p,
        queryFn: async () => {
            const params: Record<string, string | number | boolean> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '') params[k] = v as never; });
            const { data } = await api!.get<Paginated<Supplier>>('/suppliers', { params });
            return data;
        },
    });
}

export function useSupplier(id: number | null | undefined) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['supplier', tenantId, id],
        enabled: api != null && id != null,
        queryFn: async () => { const { data } = await api!.get<{ data: Supplier }>(`/suppliers/${id}`); return data.data; },
    });
}

export function useCreateSupplier() {
    const qc = useQueryClient();
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (vars: Partial<Supplier>) => { const { data } = await api!.post<{ data: Supplier }>('/suppliers', vars); return data.data; },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['suppliers', tenantId] }),
    });
}

export function useUpdateSupplier() {
    const qc = useQueryClient();
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async ({ id, ...vars }: { id: number } & Partial<Supplier>) => { const { data } = await api!.patch<{ data: Supplier }>(`/suppliers/${id}`, vars); return data.data; },
        onSuccess: () => { qc.invalidateQueries({ queryKey: ['suppliers', tenantId] }); qc.invalidateQueries({ queryKey: ['supplier', tenantId] }); },
    });
}

export function useDeleteSupplier() {
    const qc = useQueryClient();
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useMutation({ mutationFn: async (id: number) => { await api!.delete(`/suppliers/${id}`); }, onSuccess: () => qc.invalidateQueries({ queryKey: ['suppliers', tenantId] }) });
}

export function useSetSupplierPrice() {
    const qc = useQueryClient();
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async ({ supplierId, ...vars }: { supplierId: number; sku_id: number; unit_cost: number; moq?: number; valid_from?: string | null; valid_to?: string | null; is_default?: boolean; note?: string | null }) => {
            await api!.post(`/suppliers/${supplierId}/prices`, vars);
        },
        onSuccess: (_, v) => qc.invalidateQueries({ queryKey: ['supplier', tenantId, v.supplierId] }),
    });
}

// ---- Purchase Orders --------------------------------------------------------

export function usePurchaseOrders(filters: { status?: string; supplier_id?: number; warehouse_id?: number; q?: string; page?: number; per_page?: number } = {}) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['purchase-orders', tenantId, filters],
        enabled: api != null,
        placeholderData: (p) => p,
        queryFn: async () => {
            const params: Record<string, string | number | boolean> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '') params[k] = v as never; });
            const { data } = await api!.get<Paginated<PurchaseOrder>>('/purchase-orders', { params });
            return data;
        },
    });
}

export function usePurchaseOrder(id: number | null | undefined) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['purchase-order', tenantId, id],
        enabled: api != null && id != null,
        queryFn: async () => { const { data } = await api!.get<{ data: PurchaseOrder }>(`/purchase-orders/${id}`); return data.data; },
    });
}

interface PoMutationVars { supplier_id: number; warehouse_id: number; expected_at?: string | null; note?: string | null; items: Array<{ sku_id: number; qty_ordered: number; unit_cost?: number | null; note?: string | null }> }

export function useCreatePurchaseOrder() {
    const qc = useQueryClient();
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (vars: PoMutationVars) => { const { data } = await api!.post<{ data: PurchaseOrder }>('/purchase-orders', vars); return data.data; },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['purchase-orders', tenantId] }),
    });
}

export function useConfirmPurchaseOrder() {
    const qc = useQueryClient();
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (id: number) => { const { data } = await api!.post<{ data: PurchaseOrder }>(`/purchase-orders/${id}/confirm`); return data.data; },
        onSuccess: (_, id) => { qc.invalidateQueries({ queryKey: ['purchase-orders', tenantId] }); qc.invalidateQueries({ queryKey: ['purchase-order', tenantId, id] }); },
    });
}

export function useCancelPurchaseOrder() {
    const qc = useQueryClient();
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (id: number) => { await api!.post(`/purchase-orders/${id}/cancel`); },
        onSuccess: (_, id) => { qc.invalidateQueries({ queryKey: ['purchase-orders', tenantId] }); qc.invalidateQueries({ queryKey: ['purchase-order', tenantId, id] }); },
    });
}

export function useReceivePurchaseOrder() {
    const qc = useQueryClient();
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async ({ id, lines }: { id: number; lines: Array<{ sku_id: number; qty: number; unit_cost?: number | null }> }) => {
            const { data } = await api!.post<{ data: { goods_receipt: { id: number; code: string; status: string; redirect: string } } }>(`/purchase-orders/${id}/receive`, { lines });
            return data.data.goods_receipt;
        },
        onSuccess: (_, v) => { qc.invalidateQueries({ queryKey: ['purchase-orders', tenantId] }); qc.invalidateQueries({ queryKey: ['purchase-order', tenantId, v.id] }); },
    });
}
