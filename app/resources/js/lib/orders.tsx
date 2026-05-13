import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';
import type { CustomerCard } from './customers';

export interface OrderItem {
    id: number;
    external_item_id: string;
    seller_sku: string | null;
    sku_id: number | null;
    is_mapped: boolean;
    name: string;
    variation: string | null;
    quantity: number;
    unit_price: number;
    discount: number;
    subtotal: number;
    image: string | null;
}

export interface OrderStatusHistory {
    id: number;
    from_status: string | null;
    to_status: string;
    to_status_label: string;
    raw_status: string | null;
    source: string;
    changed_at: string | null;
}

export interface OrderProfit {
    cogs: number;
    platform_fee: number;
    shipping_fee: number;
    estimated_profit: number;
    platform_fee_pct: number;
    cost_complete: boolean;
}

export interface Order {
    id: number;
    source: string;
    channel_account_id: number | null;
    channel_account?: { id: number; name: string; provider: string } | null;
    carrier?: string | null;
    thumbnail?: string | null;          // first order-item image (list view)
    external_order_id: string | null;
    order_number: string | null;
    status: string;
    status_label: string;
    raw_status: string | null;
    payment_status: string | null;
    buyer_name: string | null;
    buyer_phone_masked: string | null;
    shipping_address: Record<string, string> | null;
    currency: string;
    item_total: number;
    shipping_fee: number;
    platform_discount: number;
    seller_discount: number;
    tax: number;
    cod_amount: number;
    grand_total: number;
    /** lợi nhuận ước tính sau phí sàn (SPEC 0012) — null nếu chưa cấu hình phí sàn. cost_complete=false ⇒ giá vốn chưa đủ. */
    profit?: OrderProfit | null;
    /** đơn có ≥1 SKU âm tồn (đã đặt vượt tồn) ⇒ chặn "Chuẩn bị hàng / lấy phiếu giao hàng" (SPEC 0013) */
    out_of_stock?: boolean;
    is_cod: boolean;
    fulfillment_type: string | null;
    items_count: number | null;
    has_issue: boolean;
    issue_reason: string | null;
    tags: string[];
    note: string | null;
    packages: Array<{ trackingNo?: string | null; carrier?: string | null; status?: string | null }>;
    placed_at: string | null;
    paid_at: string | null;
    shipped_at: string | null;
    delivered_at: string | null;
    completed_at: string | null;
    cancelled_at: string | null;
    cancel_reason: string | null;
    created_at: string | null;
    customer?: CustomerCard | null;
    shipment?: { id: number; carrier: string; tracking_no: string | null; status: string; label_url: string | null; has_label: boolean; print_count: number; last_printed_at: string | null; packed_at: string | null } | null;
    items?: OrderItem[];
    status_history?: OrderStatusHistory[];
}

export interface OrderFilters {
    status?: string;
    source?: string;
    channel_account_id?: number;
    carrier?: string;
    sku?: string;
    product?: string;
    q?: string;
    placed_from?: string;
    placed_to?: string;
    has_issue?: boolean;
    out_of_stock?: boolean;
    stage?: 'prepare' | 'pack' | 'handover';   // bước xử lý dựa trên vận đơn (SPEC 0013)
    slip?: 'printable' | 'loading' | 'failed';  // tình trạng phiếu giao hàng của đơn đã "Chuẩn bị hàng" (SPEC 0013)
    sort?: string;
    page?: number;
    per_page?: number;
}

export interface CarrierCount { carrier: string; count: number }
export interface SourceCount { source: string; count: number }
export interface ShopCount { channel_account_id: number; count: number }

export interface UnmappedSkuGroup {
    channel_account_id: number;
    channel_account_name: string;
    external_sku_id: string | null;
    seller_sku: string | null;
    sample_name: string;
    order_count: number;
    item_count: number;
    existing_listing_id: number | null;
    suggested_sku_id: number | null;
}

export interface OrderStats {
    total: number;
    has_issue: number;
    unmapped: number;
    out_of_stock: number;
    by_status: Record<string, number>;
    by_stage: Record<string, number>;   // { prepare, pack, handover }
    by_slip: { printable: number; loading: number; failed: number };   // tình trạng phiếu giao hàng — SPEC 0013
    by_source: SourceCount[];
    by_shop: ShopCount[];
    by_carrier: CarrierCount[];
}

export interface Paginated<T> {
    data: T[];
    meta: { pagination: { page: number; per_page: number; total: number; total_pages: number } };
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export function useOrders(filters: OrderFilters) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['orders', tenantId, filters],
        enabled: api != null,
        placeholderData: (prev) => prev,
        queryFn: async () => {
            const params: Record<string, string | number | boolean> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '' && v !== false) params[k] = v as never; });
            const { data } = await api!.get<Paginated<Order>>('/orders', { params });
            return data;
        },
    });
}

export function useOrderStats(filters: Omit<OrderFilters, 'status' | 'page' | 'per_page'> = {}) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['orders', tenantId, 'stats', filters],
        enabled: api != null,
        queryFn: async () => {
            const params: Record<string, string | number | boolean> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '' && v !== false) params[k] = v as never; });
            const { data } = await api!.get<{ data: OrderStats }>('/orders/stats', { params });
            return data.data;
        },
    });
}

export function useSyncOrders() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async () => { const { data } = await api!.post<{ data: { queued: number } }>('/orders/sync'); return data.data; },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['orders', tenantId] }),
    });
}

/** Distinct channel SKUs (merged) across orders whose lines are still unmapped. orderIds empty/undefined = all unmapped orders. */
export function useUnmappedSkus(orderIds: number[] | undefined, enabled = true) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const key = (orderIds && orderIds.length ? orderIds.slice().sort((a, b) => a - b).join(',') : 'all');
    return useQuery({
        queryKey: ['orders-unmapped-skus', tenantId, key],
        enabled: api != null && enabled,
        queryFn: async () => {
            const params = orderIds && orderIds.length ? { order_ids: orderIds.join(',') } : {};
            const { data } = await api!.get<{ data: UnmappedSkuGroup[] }>('/orders/unmapped-skus', { params });
            return data.data;
        },
    });
}

export function useLinkOrderSkus() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (links: Array<{ channel_account_id: number; external_sku_id?: string | null; seller_sku?: string | null; sku_id: number }>) => {
            const { data } = await api!.post<{ data: { linked: number; listings_created: number; orders_resolved: number } }>('/orders/link-skus', { links });
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['orders', tenantId] });
            qc.invalidateQueries({ queryKey: ['orders-unmapped-skus', tenantId] });
            qc.invalidateQueries({ queryKey: ['inventory-levels', tenantId] });
            qc.invalidateQueries({ queryKey: ['skus', tenantId] });
            qc.invalidateQueries({ queryKey: ['channel-listings', tenantId] });
        },
    });
}

export function useOrder(id: number | string | undefined) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['order', tenantId, id],
        enabled: api != null && id != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: Order }>(`/orders/${id}`);
            return data.data;
        },
    });
}

export function useOrderTags(id: number) {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (vars: { add?: string[]; remove?: string[] }) => {
            const { data } = await api!.post<{ data: Order }>(`/orders/${id}/tags`, vars);
            return data.data;
        },
        onSuccess: (order) => {
            qc.setQueryData(['order', tenantId, id], order);
            qc.invalidateQueries({ queryKey: ['orders', tenantId] });
        },
    });
}

export function useOrderNote(id: number) {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (note: string | null) => {
            const { data } = await api!.patch<{ data: Order }>(`/orders/${id}/note`, { note });
            return data.data;
        },
        onSuccess: (order) => qc.setQueryData(['order', tenantId, id], order),
    });
}
