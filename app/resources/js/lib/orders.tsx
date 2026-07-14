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

export interface OrderFeeLine {
    type: string;
    label: string;
    amount: number;
}

export interface OrderProfit {
    cogs: number;
    platform_fee: number;
    shipping_fee: number;
    /** Voucher SÀN cấp (được sàn hoàn cho shop) — cộng lại vào doanh thu khi tính lãi. */
    platform_subsidy?: number;
    estimated_profit: number;
    platform_fee_pct: number;
    cost_complete: boolean;
    /** Nguồn phí: 'estimate' (ước tính theo biểu phí) | 'settlement' (đối soát thật) | 'carrier'. */
    fee_source?: string;
    /** Chi tiết các khoản phí sàn (hoa hồng / giao dịch / dịch vụ / cố định …). */
    fee_breakdown?: OrderFeeLine[];
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
    /** Order ở trạng thái kết thúc (delivered / completed / returned-refunded / cancelled). Read-only ⇒ chặn edit. */
    is_terminal?: boolean;
    /** Order chưa đẩy vận đơn (Pending / Processing). */
    is_pre_shipment?: boolean;
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
    /** Số tiền thu khi giao thất bại (VD hàng cồng kềnh khách bùng) — GHN cod_failed_amount / VTP EXTRA_MONEY; 0/null = tắt. */
    failed_collect_amount?: number | null;
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
    /** đơn còn ≥1 dòng chưa ghép SKU sàn ↔ master SKU — KHÔNG phải lỗi, chỉ không trừ tồn cho dòng đó */
    has_unmapped_sku?: boolean;
    prepare_block_reason?: string | null;
    /** SPEC 0038 v2 — báo cáo "bom hàng": đơn thủ công đã hoàn/thất bại mới cho báo; `bad_reported` = đã báo. */
    can_bad_report?: boolean;
    bad_reported?: boolean;
    tags: string[];
    note: string | null;
    /** Meta tự do của đơn — manual order hay dùng `preferred_carrier_account_id` (hint ĐVVC user đã chọn). */
    meta?: Record<string, unknown> | null;
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
    shipment?: {
        id: number; carrier: string; tracking_no: string | null; status: string; status_label?: string; label_url: string | null; has_label: boolean;
        slip_state?: 'printable' | 'loading' | 'failed' | null; label_fetch_next_retry_at?: string | null; label_unavailable?: boolean; pending_reason?: string | null;
        print_count: number; last_printed_at: string | null; packed_at: string | null;
        /** Kết quả thực tế sau giao (SPEC giao-thất-bại-thu-tiền) — null = chưa có sự kiện/carrier không hỗ trợ. */
        cod_collected?: number | null; failed_collect_collected?: number | null; return_fee?: number | null;
    } | null;
    /** SPEC 2026-05-17 — đơn đã đẩy lên ĐVVC: shipment carrier ≠ 'manual' và status không phải pending/cancelled. UI cảnh báo "thay đổi local, không can thiệp vận đơn". */
    is_pushed_to_carrier?: boolean;
    /** Carrier code của shipment đã đẩy (vd 'ghn', 'manual_ghn') — null nếu chưa đẩy. */
    pushed_carrier?: string | null;
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
    has_return?: boolean;   // đơn có yêu cầu trả/hoàn (order_returns) HOẶC status returning/returned_refunded
    out_of_stock?: boolean;
    unmapped?: boolean;   // đơn còn dòng chưa ghép SKU (has_unmapped_sku) — cột riêng, không phải has_issue
    slip?: 'printable' | 'loading' | 'failed';  // tình trạng phiếu giao hàng của đơn đã "Chuẩn bị hàng" (SPEC 0013)
    printed?: boolean;   // đã in phiếu (≥1 vận đơn open có print_count>0) — chỉ áp ở "Đang xử lý"
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
    sample_image: string | null;
    order_count: number;
    item_count: number;
    existing_listing_id: number | null;
    suggested_sku_id: number | null;
}

export interface OrderStats {
    total: number;
    has_issue: number;
    has_return: number;   // số đơn có trả/hoàn — dùng cho tab "Trả/hoàn" (SPEC 0025)
    unmapped: number;
    out_of_stock: number;
    by_status: Record<string, number>;
    by_stage: Record<string, number>;   // { prepare, pack, handover }
    by_slip: { printable: number; loading: number; failed: number };   // tình trạng phiếu giao hàng — SPEC 0013
    by_printed: { yes: number; no: number };   // đã in / chưa in phiếu giao hàng — SPEC 0013
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
            Object.entries(filters).forEach(([k, v]) => { if (v === undefined || v === null || v === '') return; params[k] = (v === true ? 1 : v === false ? 0 : v) as never; });
            const { data } = await api!.get<Paginated<Order>>('/orders', { params });
            return data;
        },
    });
}

/**
 * Lấy TOÀN BỘ đơn khớp bộ lọc hiện tại (mọi trang) để hỗ trợ "chọn tất cả trang" — server phân trang nên
 * checkbox header chỉ chọn được trang hiện tại. Lặp gọi `/orders` (per_page=100) tới khi đủ `max` hoặc hết
 * trang. Trả cả `total` để UI biết có bị cắt theo `max` không. Bỏ qua page/per_page trong filter (tự set).
 */
export function useFetchAllOrders() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async ({ filters, max = 500 }: { filters: OrderFilters; max?: number }): Promise<{ orders: Order[]; total: number }> => {
            const base: Record<string, string | number | boolean> = {};
            Object.entries(filters).forEach(([k, v]) => {
                if (k === 'page' || k === 'per_page' || v === undefined || v === null || v === '') return;
                base[k] = (v === true ? 1 : v === false ? 0 : v) as never;
            });
            const orders: Order[] = [];
            let total = 0;
            for (let page = 1; orders.length < max; page++) {
                const { data } = await api!.get<Paginated<Order>>('/orders', { params: { ...base, page, per_page: 100 } });
                total = data.meta.pagination.total;
                orders.push(...data.data);
                if (page >= data.meta.pagination.total_pages) break;
            }
            return { orders: orders.slice(0, max), total };
        },
    });
}

export function useOrderStats(filters: Omit<OrderFilters, 'page' | 'per_page'> = {}) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['orders', tenantId, 'stats', filters],
        enabled: api != null,
        queryFn: async () => {
            const params: Record<string, string | number | boolean> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v === undefined || v === null || v === '') return; params[k] = (v === true ? 1 : v === false ? 0 : v) as never; });
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

/** Huỷ hàng loạt (local "ngừng theo dõi") — không đẩy lên sàn/ĐVVC. */
export function useBulkCancelOrders() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (vars: { ids: number[]; reason?: string }) => {
            const { data } = await api!.post<{ data: { cancelled: number; skipped: number } }>('/orders/bulk-cancel', vars);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['orders', tenantId] }),
    });
}

/** Xoá mềm hàng loạt — chỉ đơn đã huỷ. */
export function useBulkDeleteOrders() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (vars: { ids: number[] }) => {
            const { data } = await api!.post<{ data: { deleted: number; skipped: number } }>('/orders/bulk-delete', vars);
            return data.data;
        },
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

export interface OrderDuplicateSummary { id: number; number: string; status_code: string; status: string; total: number; date: string | null; items: string | null }
export interface OrderDuplicateLookup { latest_order: OrderDuplicateSummary | null; latest_returned_order: OrderDuplicateSummary | null }

/**
 * SĐT đã có đơn THỦ CÔNG cũ (mọi trạng thái) — cảnh báo ở form tạo đơn thủ công (SPEC 2026-07-13 v2).
 * Khớp trực tiếp theo SĐT chuẩn hoá (KHÔNG qua customer_id — nhiều đơn thủ công chưa từng gắn Customer
 * vì chỉ điền "Nhận hàng"). Chỉ tìm trong đơn thủ công, không liên quan đơn sàn.
 */
export function useOrderLookupByPhone(phone: string | undefined | null, excludeOrderId?: number) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const normalized = (phone ?? '').replace(/[^\d+]/g, '');
    const enough = normalized.length >= 9;
    return useQuery({
        queryKey: ['order-lookup-duplicate-by-phone', tenantId, normalized, excludeOrderId],
        enabled: api != null && enough,
        queryFn: async () => {
            const { data } = await api!.get<{ data: OrderDuplicateLookup }>('/orders/lookup-duplicate-by-phone', {
                params: { phone: normalized, exclude_order_id: excludeOrderId },
            });
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
