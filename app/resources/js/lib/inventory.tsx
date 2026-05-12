import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';
import type { Paginated } from './orders';

export interface InventoryLevel {
    id: number;
    sku_id: number;
    warehouse_id: number;
    on_hand: number;
    reserved: number;
    safety_stock: number;
    available: number;
    cost_price: number;
    is_negative: boolean;
    sku?: { id: number; sku_code: string; name: string };
    warehouse?: { id: number; name: string; is_default: boolean };
}

export interface InventoryMovement {
    id: number;
    sku_id: number;
    warehouse_id: number;
    qty_change: number;
    type: string;
    ref_type: string | null;
    ref_id: number | null;
    balance_after: number;
    note: string | null;
    created_at: string | null;
}

export interface Sku {
    id: number;
    product_id: number | null;
    spu_code: string | null;
    category: string | null;
    sku_code: string;
    barcode: string | null;
    gtins: string[];
    name: string;
    base_unit: string;
    cost_price: number;                 // giá vốn tham khảo (VND)
    ref_sale_price: number | null;      // giá bán tham khảo (VND)
    ref_profit_per_unit: number | null; // ref_sale_price − cost_price
    ref_margin_percent: number | null;  // profit ÷ sale price × 100
    sale_start_date: string | null;     // YYYY-MM-DD
    note: string | null;
    weight_grams: number | null;
    length_cm: string | null;
    width_cm: string | null;
    height_cm: string | null;
    image_url: string | null;           // reserved — image upload is a TODO (SPEC 0005 §7)
    attributes: Record<string, unknown>;
    is_active: boolean;
    on_hand_total?: number;
    reserved_total?: number;
    available_total?: number;
    levels?: InventoryLevel[];
    mappings?: Array<{ id: number; channel_listing_id: number; sku_id: number; quantity: number; type: string; channel_listing?: { id: number; channel_account_id: number; external_sku_id: string; seller_sku: string | null; title: string | null } | null }>;
    movements?: InventoryMovement[];
    created_at: string | null;
}

/** Payload for POST /skus — the "Thêm SKU đơn độc" form. See SPEC 0005. */
export interface CreateSkuPayload {
    sku_code: string;
    name: string;
    product_id?: number | null;
    spu_code?: string | null;
    category?: string | null;
    barcode?: string | null;
    gtins?: string[];
    base_unit?: string;
    cost_price?: number;
    ref_sale_price?: number | null;
    sale_start_date?: string | null;
    note?: string | null;
    weight_grams?: number | null;
    length_cm?: number | null;
    width_cm?: number | null;
    height_cm?: number | null;
    mappings?: Array<{ channel_account_id: number; external_sku_id: string; seller_sku?: string | null; quantity?: number }>;
    levels?: Array<{ warehouse_id: number; on_hand?: number; cost_price?: number }>;
}

/** Partial payload for PATCH /skus/{id} — basic catalogue fields only (mappings/levels are not editable here). */
export type UpdateSkuPayload = Partial<{
    sku_code: string;
    name: string;
    spu_code: string | null;
    category: string | null;
    barcode: string | null;
    gtins: string[];
    base_unit: string;
    cost_price: number;
    ref_sale_price: number | null;
    sale_start_date: string | null;
    note: string | null;
    weight_grams: number | null;
    length_cm: number | null;
    width_cm: number | null;
    height_cm: number | null;
    is_active: boolean;
}>;

export interface Product {
    id: number;
    name: string;
    image: string | null;
    brand: string | null;
    category: string | null;
    skus_count?: number;
    created_at: string | null;
}

export interface ChannelListing {
    id: number;
    channel_account_id: number;
    external_product_id: string | null;
    external_sku_id: string;
    seller_sku: string | null;
    title: string | null;
    variation: string | null;
    price: number | null;
    channel_stock: number | null;
    currency: string;
    is_active: boolean;
    is_stock_locked: boolean;
    sync_status: string;
    sync_error: string | null;
    last_pushed_at: string | null;
    is_mapped: boolean | null;
    mappings?: Array<{ id: number; sku_id: number; quantity: number; type: string; sku: { id: number; sku_code: string; name: string } | null }>;
}

export interface Warehouse { id: number; name: string; code: string | null; is_default: boolean }

const MOVEMENT_LABEL: Record<string, string> = {
    manual_adjust: 'Điều chỉnh tay', goods_receipt: 'Nhập kho', order_reserve: 'Giữ cho đơn', order_release: 'Nhả tồn',
    order_ship: 'Xuất giao', return_in: 'Hàng trả về', transfer_out: 'Chuyển đi', transfer_in: 'Chuyển đến', stocktake_adjust: 'Kiểm kê',
};
export const movementLabel = (t: string) => MOVEMENT_LABEL[t] ?? t;

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export function useInventoryLevels(filters: { sku_id?: number; warehouse_id?: number; low_stock?: number; negative?: boolean; page?: number; per_page?: number }) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['inventory-levels', tenantId, filters],
        enabled: api != null,
        placeholderData: (p) => p,
        queryFn: async () => {
            const params: Record<string, string | number | boolean> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== false) params[k] = v as never; });
            const { data } = await api!.get<Paginated<InventoryLevel>>('/inventory/levels', { params });
            return data;
        },
    });
}

export function useSkus(filters: { q?: string; product_id?: number; low_stock?: number; page?: number; per_page?: number }) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['skus', tenantId, filters],
        enabled: api != null,
        placeholderData: (p) => p,
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '') params[k] = v as never; });
            const { data } = await api!.get<Paginated<Sku>>('/skus', { params });
            return data;
        },
    });
}

export function useSku(id: number | string | undefined) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['sku', tenantId, id],
        enabled: api != null && id != null,
        queryFn: async () => { const { data } = await api!.get<{ data: Sku }>(`/skus/${id}`); return data.data; },
    });
}

export function useProducts(filters: { q?: string; page?: number; per_page?: number }) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['products', tenantId, filters],
        enabled: api != null,
        placeholderData: (p) => p,
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '') params[k] = v as never; });
            const { data } = await api!.get<Paginated<Product>>('/products', { params });
            return data;
        },
    });
}

export function useChannelListings(filters: { channel_account_id?: number; mapped?: 0 | 1; sync_status?: string; q?: string; page?: number; per_page?: number }) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['channel-listings', tenantId, filters],
        enabled: api != null,
        placeholderData: (p) => p,
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '') params[k] = v as never; });
            const { data } = await api!.get<Paginated<ChannelListing>>('/channel-listings', { params });
            return data;
        },
    });
}

export function useWarehouses() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['warehouses', tenantId],
        enabled: api != null,
        queryFn: async () => { const { data } = await api!.get<{ data: Warehouse[] }>('/warehouses'); return data.data; },
    });
}

function useInvalidate(keys: unknown[][]) {
    const qc = useQueryClient();
    return () => keys.forEach((k) => qc.invalidateQueries({ queryKey: k }));
}

export function useAdjustStock() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['inventory-levels', tenantId], ['skus', tenantId], ['sku', tenantId]]);
    return useMutation({
        mutationFn: async (vars: { sku_id: number; warehouse_id?: number; qty_change: number; note?: string }) => {
            const { data } = await api!.post<{ data: InventoryMovement }>('/inventory/adjust', vars);
            return data.data;
        },
        onSuccess: invalidate,
    });
}

export function useBulkAdjustStock() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['inventory-levels', tenantId], ['skus', tenantId], ['sku', tenantId]]);
    return useMutation({
        mutationFn: async (vars: { kind: 'goods_receipt' | 'manual_adjust'; warehouse_id?: number; note?: string; lines: Array<{ sku_id: number; qty_change: number }> }) => {
            const { data } = await api!.post<{ data: { applied: number } }>('/inventory/bulk-adjust', vars);
            return data.data;
        },
        onSuccess: invalidate,
    });
}

export function useBulkPushStock() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['channel-listings', tenantId]]);
    return useMutation({
        mutationFn: async (skuIds: number[]) => { const { data } = await api!.post<{ data: { queued: number } }>('/inventory/push-stock', { sku_ids: skuIds }); return data.data; },
        onSuccess: invalidate,
    });
}

export function useCreateProduct() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['products', tenantId]]);
    return useMutation({
        mutationFn: async (vars: { name: string; brand?: string; category?: string }) => { const { data } = await api!.post<{ data: Product }>('/products', vars); return data.data; },
        onSuccess: invalidate,
    });
}

export function useCreateSku() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['skus', tenantId], ['inventory-levels', tenantId], ['channel-listings', tenantId]]);
    return useMutation({
        mutationFn: async (vars: CreateSkuPayload) => {
            const { data } = await api!.post<{ data: Sku }>('/skus', vars);
            return data.data;
        },
        onSuccess: invalidate,
    });
}

export function useUpdateSku() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['skus', tenantId], ['sku', tenantId], ['inventory-levels', tenantId]]);
    return useMutation({
        mutationFn: async ({ id, patch }: { id: number; patch: UpdateSkuPayload }) => {
            const { data } = await api!.patch<{ data: Sku }>(`/skus/${id}`, patch);
            return data.data;
        },
        onSuccess: invalidate,
    });
}

export function useDeleteSku() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['skus', tenantId], ['sku', tenantId], ['inventory-levels', tenantId], ['channel-listings', tenantId]]);
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/skus/${id}`); },
        onSuccess: invalidate,
    });
}

/** Upload (replace) a SKU image — multipart. Returns the updated SKU (with `image_url`). */
export function useUploadSkuImage() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['skus', tenantId]]);
    return useMutation({
        mutationFn: async ({ skuId, file }: { skuId: number; file: File }) => {
            const fd = new FormData();
            fd.append('image', file);
            const { data } = await api!.post<{ data: Sku }>(`/skus/${skuId}/image`, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
            return data.data;
        },
        onSuccess: invalidate,
    });
}

/** Generic image upload (POST /media/image) — returns `{ path, url }`. Used for ad-hoc order-item images. */
export function useUploadImage() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async ({ file, folder }: { file: File; folder?: string }) => {
            const fd = new FormData();
            fd.append('image', file);
            if (folder) fd.append('folder', folder);
            const { data } = await api!.post<{ data: { path: string; url: string } }>('/media/image', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
            return data.data;
        },
    });
}

export function useDeleteSkuImage() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['skus', tenantId]]);
    return useMutation({ mutationFn: async (skuId: number) => { await api!.delete(`/skus/${skuId}/image`); }, onSuccess: invalidate });
}

export function useSetSkuMapping() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['channel-listings', tenantId]]);
    return useMutation({
        mutationFn: async (vars: { channel_listing_id: number; type: 'single' | 'bundle'; lines: Array<{ sku_id: number; quantity?: number }> }) => {
            await api!.post('/sku-mappings', vars);
        },
        onSuccess: invalidate,
    });
}

export function useRemoveSkuMapping() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['channel-listings', tenantId]]);
    return useMutation({ mutationFn: async (id: number) => { await api!.delete(`/sku-mappings/${id}`); }, onSuccess: invalidate });
}

export function useAutoMatchSkus() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['channel-listings', tenantId]]);
    return useMutation({ mutationFn: async () => { const { data } = await api!.post<{ data: { matched: number } }>('/sku-mappings/auto-match'); return data.data; }, onSuccess: invalidate });
}

export function useSyncChannelListings() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['channel-listings', tenantId]]);
    return useMutation({ mutationFn: async () => { const { data } = await api!.post<{ data: { queued: number } }>('/channel-listings/sync'); return data.data; }, onSuccess: invalidate });
}

export function useCreateManualOrder() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (payload: Record<string, unknown>) => { const { data } = await api!.post<{ data: { id: number } }>('/orders', payload); return data.data; },
        onSuccess: () => { qc.invalidateQueries({ queryKey: ['orders', tenantId] }); qc.invalidateQueries({ queryKey: ['inventory-levels', tenantId] }); },
    });
}
