import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';
import type { Order, Paginated } from './orders';

export type ReputationLabel = 'ok' | 'watch' | 'risk' | 'blocked';

export interface LifetimeStats {
    orders_total: number;
    orders_completed: number;
    orders_cancelled: number;
    orders_returned: number;
    orders_delivery_failed: number;
    orders_in_progress?: number;
    revenue_completed?: number;
    last_order_id?: number;
    last_order_status?: string;
}

export interface CustomerNote {
    id: number;
    customer_id: number;
    author_user_id: number | null;
    is_auto: boolean;
    kind: string;
    severity: 'info' | 'warning' | 'danger';
    note: string;
    order_id: number | null;
    created_at: string | null;
}

export interface WarningNote {
    kind: string;
    note: string;
    severity: 'info' | 'warning' | 'danger';
    created_at: string | null;
}

/** Compact form used inside an Order (`order.customer`). */
export interface CustomerCard {
    id: number;
    name: string | null;
    phone_masked: string | null;
    reputation: { score: number; label: ReputationLabel };
    is_blocked: boolean;
    is_anonymized?: boolean;
    tags: string[];
    lifetime_stats: LifetimeStats;
    manual_note: string | null;
    latest_warning_note: WarningNote | null;
}

export interface Customer extends CustomerCard {
    phone: string | null;            // only when caller has customers.view_phone
    block_reason: string | null;
    blocked_at: string | null;
    addresses_meta: Array<Record<string, unknown>>;
    first_seen_at: string;
    last_seen_at: string;
    merged_into_customer_id: number | null;
    notes?: CustomerNote[];
}

export interface CustomerFilters {
    q?: string;
    reputation?: string;
    tag?: string;
    min_orders?: number;
    has_note?: boolean;
    blocked?: boolean;
    sort?: string;
    page?: number;
    per_page?: number;
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

/** SPEC 0021 — tra cứu nhanh khách theo SĐT lúc tạo đơn thủ công (taodon.png). Trả khách + địa chỉ cũ + đơn đang xử lý + đơn đang/đã hoàn (FE hiện cảnh báo). */
export interface CustomerAddress { name?: string | null; phone?: string | null; address?: string | null; detail?: string | null; ward?: string | null; ward_code?: string | null; district?: string | null; district_id?: number | null; province?: string | null; city?: string | null; province_id?: number | null }
export interface CustomerLookupOrder { id: number; order_number: string | null; status: string; placed_at: string | null; grand_total: number; source: string }
export interface CustomerLookupResult { customer: Customer | null; addresses: CustomerAddress[]; open_orders: CustomerLookupOrder[]; returning_orders: CustomerLookupOrder[] }

export function useCustomerLookup(phone: string | undefined | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const normalized = (phone ?? '').replace(/[^\d+]/g, '');
    const enough = normalized.length >= 9;
    return useQuery({
        queryKey: ['customer-lookup', tenantId, normalized],
        enabled: api != null && enough,
        staleTime: 30_000,
        queryFn: async () => {
            const { data } = await api!.get<{ data: CustomerLookupResult }>('/customers/lookup', { params: { phone: normalized } });
            return data.data;
        },
    });
}

export function useCustomers(filters: CustomerFilters) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['customers', tenantId, filters],
        enabled: api != null,
        placeholderData: (prev) => prev,
        queryFn: async () => {
            const params: Record<string, string | number | boolean> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '' && v !== false) params[k] = v as never; });
            const { data } = await api!.get<Paginated<Customer>>('/customers', { params });
            return data;
        },
    });
}

export function useCustomer(id: number | string | undefined) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['customer', tenantId, id],
        enabled: api != null && id != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: Customer }>(`/customers/${id}`);
            return data.data;
        },
    });
}

export function useCustomerOrders(id: number | string | undefined, filters: { source?: string; status?: string; page?: number; per_page?: number } = {}) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['customer-orders', tenantId, id, filters],
        enabled: api != null && id != null,
        placeholderData: (prev) => prev,
        queryFn: async () => {
            const { data } = await api!.get<Paginated<Order>>(`/customers/${id}/orders`, { params: filters });
            return data;
        },
    });
}

function useInvalidateCustomer(id: number) {
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return () => {
        qc.invalidateQueries({ queryKey: ['customer', tenantId, id] });
        qc.invalidateQueries({ queryKey: ['customers', tenantId] });
    };
}

export function useAddCustomerNote(id: number) {
    const api = useScopedApi();
    const invalidate = useInvalidateCustomer(id);
    return useMutation({
        mutationFn: async (vars: { note: string; severity?: 'info' | 'warning' | 'danger'; order_id?: number }) => {
            const { data } = await api!.post<{ data: CustomerNote }>(`/customers/${id}/notes`, vars);
            return data.data;
        },
        onSuccess: invalidate,
    });
}

export function useDeleteCustomerNote(id: number) {
    const api = useScopedApi();
    const invalidate = useInvalidateCustomer(id);
    return useMutation({
        mutationFn: async (noteId: number) => { await api!.delete(`/customers/${id}/notes/${noteId}`); },
        onSuccess: invalidate,
    });
}

export function useBlockCustomer(id: number) {
    const api = useScopedApi();
    const invalidate = useInvalidateCustomer(id);
    return useMutation({
        mutationFn: async (vars: { block: boolean; reason?: string }) => {
            const { data } = await api!.post<{ data: Customer }>(`/customers/${id}/${vars.block ? 'block' : 'unblock'}`, vars.block ? { reason: vars.reason } : {});
            return data.data;
        },
        onSuccess: invalidate,
    });
}

export function useCustomerTags(id: number) {
    const api = useScopedApi();
    const invalidate = useInvalidateCustomer(id);
    return useMutation({
        mutationFn: async (vars: { add?: string[]; remove?: string[] }) => {
            const { data } = await api!.post<{ data: Customer }>(`/customers/${id}/tags`, vars);
            return data.data;
        },
        onSuccess: invalidate,
    });
}

export function useMergeCustomers() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (vars: { keep_id: number; remove_id: number }) => {
            const { data } = await api!.post<{ data: Customer }>('/customers/merge', vars);
            return data.data;
        },
        onSuccess: () => { qc.invalidateQueries({ queryKey: ['customers', tenantId] }); qc.invalidateQueries({ queryKey: ['customer', tenantId] }); },
    });
}

// --- display helpers --------------------------------------------------------

export const REPUTATION_META: Record<ReputationLabel, { label: string; color: string }> = {
    ok: { label: 'OK', color: 'green' },
    watch: { label: 'Cần kiểm tra', color: 'gold' },
    risk: { label: 'Rủi ro cao', color: 'red' },
    blocked: { label: 'Đã chặn', color: 'default' },
};

export const REPUTATION_TABS: Array<{ key: string; label: string }> = [
    { key: '', label: 'Tất cả' },
    { key: 'watch', label: 'Cần kiểm tra' },
    { key: 'risk', label: 'Rủi ro cao' },
    { key: 'blocked', label: 'Đã chặn' },
];

export const NOTE_SEVERITY_COLOR: Record<string, string> = { info: 'default', warning: 'gold', danger: 'red' };
