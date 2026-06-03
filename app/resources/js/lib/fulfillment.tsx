import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';
import type { Paginated } from './orders';

export interface ShipmentEvent { id: number; code: string; description: string | null; status: string | null; source: string; occurred_at: string | null }

export interface Shipment {
    id: number;
    order_id: number;
    carrier: string;
    carrier_account_id: number | null;
    tracking_no: string | null;
    package_no: string | null;
    status: string;             // pending|created|picked_up|in_transit|delivered|failed|returned|cancelled
    service: string | null;
    weight_grams: number | null;
    cod_amount: number;
    fee: number;
    label_url: string | null;
    has_label: boolean;
    print_count: number;
    last_printed_at: string | null;
    packed_at: string | null;
    picked_up_at: string | null;
    delivered_at: string | null;
    created_at: string | null;
    order?: { id: number; order_number: string | null; external_order_id: string | null; status: string; source: string; buyer_name: string | null; grand_total: number } | null;
    events?: ShipmentEvent[];
}

export interface CarrierAccount {
    id: number;
    carrier: string;
    name: string;
    default_service: string | null;
    is_default: boolean;
    is_active: boolean;
    meta: Record<string, unknown>;
    credential_keys: string[];
    created_at: string | null;
}

export interface Carrier { code: string; name: string; capabilities: string[]; needs_credentials: boolean }

export interface PrintJob {
    id: number;
    type: 'label' | 'picking' | 'packing' | 'invoice' | 'delivery';
    scope: { order_ids?: number[]; shipment_ids?: number[] };
    status: 'pending' | 'processing' | 'done' | 'error';
    file_url: string | null;
    file_size: number | null;
    error: string | null;
    meta: Record<string, unknown>;
    created_at: string | null;
}

export interface BulkActionResult { id: number; status: 'ok' | 'skipped' | 'error'; reason?: string; technical?: string }

export const SHIPMENT_STATUS_LABEL: Record<string, string> = {
    pending: 'Chờ tạo', created: 'Đã tạo vận đơn', packed: 'Đã đóng gói',
    // SPEC 0021 — `awaiting_pickup` = đã tạo đơn với ĐVVC, đang chờ shipper tới lấy hàng.
    awaiting_pickup: 'Chờ lấy hàng',
    picked_up: 'Đã bàn giao ĐVVC', in_transit: 'Đang vận chuyển',
    delivered: 'Đã giao', failed: 'Giao thất bại', returned: 'Hoàn về', cancelled: 'Đã huỷ',
};

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

function useInvalidate(keys: unknown[][]) {
    const qc = useQueryClient();
    return () => keys.forEach((k) => qc.invalidateQueries({ queryKey: k }));
}

// ---- queries -----------------------------------------------------------------

export function useShipments(filters: { status?: string; carrier?: string; order_id?: number; q?: string; page?: number; per_page?: number }) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['shipments', tenantId, filters],
        enabled: api != null,
        placeholderData: (p) => p,
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '') params[k] = v as never; });
            const { data } = await api!.get<Paginated<Shipment>>('/shipments', { params });
            return data;
        },
    });
}

export function useShipment(id: number | null | undefined) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['shipment', tenantId, id],
        enabled: api != null && id != null,
        queryFn: async () => { const { data } = await api!.get<{ data: Shipment }>(`/shipments/${id}`); return data.data; },
    });
}

export function useCarriers() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['carriers', tenantId],
        enabled: api != null,
        queryFn: async () => { const { data } = await api!.get<{ data: Carrier[] }>('/carriers'); return data.data; },
    });
}

export function useCarrierAccounts() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['carrier-accounts', tenantId],
        enabled: api != null,
        queryFn: async () => { const { data } = await api!.get<{ data: CarrierAccount[] }>('/carrier-accounts'); return data.data; },
    });
}

export function usePrintJob(id: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['print-job', tenantId, id],
        enabled: api != null && id != null,
        // poll while the PDF is being rendered; stop once done/error.
        refetchInterval: (query) => (['done', 'error'].includes((query.state.data as PrintJob | undefined)?.status ?? '') ? false : 1500),
        queryFn: async () => { const { data } = await api!.get<{ data: PrintJob }>(`/print-jobs/${id}`); return data.data; },
    });
}

// ---- mutations ---------------------------------------------------------------

export function useCreateCarrierAccount() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['carrier-accounts', tenantId]]);
    return useMutation({
        mutationFn: async (vars: { carrier: string; name: string; credentials?: Record<string, unknown>; default_service?: string | null; is_default?: boolean; meta?: Record<string, unknown> }) => {
            const { data } = await api!.post<{ data: CarrierAccount }>('/carrier-accounts', vars); return data.data;
        },
        onSuccess: invalidate,
    });
}

export function useUpdateCarrierAccount() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['carrier-accounts', tenantId]]);
    return useMutation({
        mutationFn: async ({ id, ...vars }: { id: number } & Record<string, unknown>) => { const { data } = await api!.patch<{ data: CarrierAccount }>(`/carrier-accounts/${id}`, vars); return data.data; },
        onSuccess: invalidate,
    });
}

export function useDeleteCarrierAccount() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['carrier-accounts', tenantId]]);
    return useMutation({ mutationFn: async (id: number) => { await api!.delete(`/carrier-accounts/${id}`); }, onSuccess: invalidate });
}

/** A2 (SPEC 0021) — kiểm tra credentials còn hợp lệ. Trả ok/lỗi + cập nhật `meta.last_verified_at`. */
export interface CarrierVerifyResult {
    ok: boolean;
    message: string;
    error_code: string | null;
    expires_at: string | null;
    verified_at: string;
    account: CarrierAccount;
}
export function useVerifyCarrierAccount() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const invalidate = useInvalidate([['carrier-accounts', tenantId]]);
    return useMutation({
        mutationFn: async (id: number) => { const { data } = await api!.post<{ data: CarrierVerifyResult }>(`/carrier-accounts/${id}/verify`); return data.data; },
        onSuccess: invalidate,
    });
}

/** GHN master-data record — đúng schema từ /shiip/public-api/master-data/*. */
export interface GhnProvince { ProvinceID: number; ProvinceName: string; Code?: string }
export interface GhnDistrict { DistrictID: number; ProvinceID: number; DistrictName: string; Code?: string; SupportType?: number }
export interface GhnWard { WardCode: string; DistrictID: number; WardName: string }

/** GHN shop (gian hàng) — 1 token có thể có nhiều shop, mỗi shop 1 ShopId. */
export interface GhnShop {
    id: number;
    name: string;
    phone: string;
    address: string;
    district_id: number | null;
    ward_code: string | null;
    version: number | null;
    status: number | null;
}

/**
 * Load shop list theo token GHN — dùng trong form thêm tài khoản để user chọn shop thay vì gõ ShopId.
 * BE cache 10 phút theo hash token để giảm hit GHN.
 */
export function useGhnShops() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (vars: { token: string }) => {
            const { data } = await api!.post<{ data: GhnShop[] }>('/carrier-accounts/ghn/shops', vars);
            return data.data;
        },
    });
}

/**
 * Proxy gọi GHN master-data bằng token user đang gõ trong form thêm tài khoản. Trả về danh sách
 * tỉnh/quận/phường để FE dựng cascading Select — không cần user gõ tay mã quận. Backend cache 1 giờ
 * theo hash token để giảm hit GHN khi user thử lại nhiều lần.
 */
export function useGhnMasterData() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (vars:
            | { token: string; level: 'provinces' }
            | { token: string; level: 'districts'; province_id: number }
            | { token: string; level: 'wards'; district_id: number }
        ) => {
            const { data } = await api!.post<{ data: GhnProvince[] | GhnDistrict[] | GhnWard[] }>('/carrier-accounts/ghn/master-data', vars);
            return data.data;
        },
    });
}

function useFulfillmentInvalidate() {
    const tenantId = useCurrentTenantId();
    return useInvalidate([['fulfillment-ready', tenantId], ['shipments', tenantId], ['orders', tenantId], ['inventory-levels', tenantId]]);
}

export function useShipOrder() {
    const api = useScopedApi();
    const invalidate = useFulfillmentInvalidate();
    return useMutation({
        mutationFn: async ({ orderId, ...vars }: { orderId: number; carrier_account_id?: number | null; service?: string | null; tracking_no?: string | null; cod_amount?: number; weight_grams?: number }) => {
            const { data } = await api!.post<{ data: Shipment }>(`/orders/${orderId}/ship`, vars); return data.data;
        },
        onSuccess: invalidate,
    });
}

export interface ShippingQuote { carrier: string; carrier_name: string; fee: number; insurance_fee: number }

/**
 * Gợi ý phí ship (carrier-agnostic). Trả null nếu ĐVVC không hỗ trợ tính phí / lỗi / chưa cấu hình —
 * caller (màn tạo đơn) tự ẩn gợi ý, KHÔNG chặn tạo đơn. Hiện chỉ GHTK trả phí.
 */
export function useShippingQuote() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (vars: {
            carrier_account_id?: number | null;
            weight_grams: number;
            value?: number;
            recipient: { province: string; district: string; ward?: string; address?: string };
        }): Promise<ShippingQuote | null> => {
            const { data } = await api!.post<{ data: ShippingQuote | null }>('/fulfillment/quote', vars);
            return data.data;
        },
    });
}

export function useBulkCreateShipments() {
    const api = useScopedApi();
    const invalidate = useFulfillmentInvalidate();
    return useMutation({
        mutationFn: async (vars: { order_ids: number[]; carrier_account_id?: number | null; service?: string | null }) => {
            const { data } = await api!.post<{ data: { created: Shipment[]; errors: Array<{ order_id: number; message: string }> } }>('/shipments/bulk-create', vars); return data.data;
        },
        onSuccess: invalidate,
    });
}

export function useTrackShipment() {
    const api = useScopedApi();
    const invalidate = useFulfillmentInvalidate();
    return useMutation({ mutationFn: async (id: number) => { const { data } = await api!.post<{ data: Shipment }>(`/shipments/${id}/track`); return data.data; }, onSuccess: invalidate });
}

export function useCancelShipment() {
    const api = useScopedApi();
    const invalidate = useFulfillmentInvalidate();
    return useMutation({ mutationFn: async (id: number) => { const { data } = await api!.post<{ data: Shipment }>(`/shipments/${id}/cancel`); return data.data; }, onSuccess: invalidate });
}

export function usePackShipments() {
    const api = useScopedApi();
    const invalidate = useFulfillmentInvalidate();
    return useMutation({ mutationFn: async (shipment_ids: number[]) => { const { data } = await api!.post<{ data: { packed: number; results: BulkActionResult[] } }>('/shipments/pack', { shipment_ids }); return data.data; }, onSuccess: invalidate });
}

export function useHandoverShipments() {
    const api = useScopedApi();
    const invalidate = useFulfillmentInvalidate();
    return useMutation({ mutationFn: async (shipment_ids: number[]) => { const { data } = await api!.post<{ data: { handed_over: number; results: BulkActionResult[] } }>('/shipments/handover', { shipment_ids }); return data.data; }, onSuccess: invalidate });
}

/** "Nhận phiếu giao hàng" cho các đơn đã "Chuẩn bị hàng" — kéo tem/AWB thật của sàn về; đơn chưa có phiếu ⇒ render 1 print job `delivery` (trả `print_job_id` để FE hiện tiến trình). SPEC 0013. */
export function useBulkRefetchSlip() {
    const api = useScopedApi();
    const invalidate = useFulfillmentInvalidate();
    return useMutation({
        mutationFn: async (order_ids: number[]) => { const { data } = await api!.post<{ data: { ok: number; errors: Array<{ order_id: number; message: string }>; print_job_id: number | null } }>('/shipments/bulk-refetch-slip', { order_ids }); return data.data; },
        onSuccess: invalidate,
    });
}

/** "Đánh dấu các đơn đã in" sau khi mở file PDF (popup) — cộng print_count cho vận đơn trong print job. SPEC 0013. */
export function useMarkPrinted() {
    const api = useScopedApi();
    const invalidate = useFulfillmentInvalidate();
    return useMutation({
        mutationFn: async ({ jobId, copies }: { jobId: number; copies?: number }) => { const { data } = await api!.post<{ data: { shipment_ids: number[]; copies: number } }>(`/print-jobs/${jobId}/mark-printed`, copies ? { copies } : {}); return data.data; },
        onSuccess: invalidate,
    });
}

interface ScanResult { action: 'pack' | 'handover'; message: string; shipment: Shipment; order: { id: number; order_number: string | null; status: string } | null }

/** Scan a tracking/order code to mark a parcel "đã đóng gói" (`mode='pack'`) or to "bàn giao ĐVVC" (`mode='handover'`). */
export function useScanProcess(mode: 'pack' | 'handover') {
    const api = useScopedApi();
    const invalidate = useFulfillmentInvalidate();
    return useMutation({
        mutationFn: async (code: string) => { const { data } = await api!.post<{ data: ScanResult }>(mode === 'handover' ? '/scan-handover' : '/scan-pack', { code }); return data.data; },
        onSuccess: invalidate,
    });
}

export function useCreatePrintJob() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (vars: { type: 'label' | 'picking' | 'packing' | 'invoice' | 'delivery'; order_ids?: number[]; shipment_ids?: number[]; template_id?: number | null }) => {
            const { data } = await api!.post<{ data: PrintJob }>('/print-jobs', vars); return data.data;
        },
    });
}
