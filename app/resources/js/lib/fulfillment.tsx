import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';
import type { Order, Paginated } from './orders';

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

export type ProcessingStage = 'prepare' | 'pack' | 'handover';

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
    type: 'label' | 'picking' | 'packing' | 'invoice';
    scope: { order_ids?: number[]; shipment_ids?: number[] };
    status: 'pending' | 'processing' | 'done' | 'error';
    file_url: string | null;
    file_size: number | null;
    error: string | null;
    meta: Record<string, unknown>;
    created_at: string | null;
}

export const SHIPMENT_STATUS_LABEL: Record<string, string> = {
    pending: 'Chờ tạo', created: 'Đã tạo vận đơn', packed: 'Đã đóng gói', picked_up: 'Đã bàn giao ĐVVC', in_transit: 'Đang vận chuyển',
    delivered: 'Đã giao', failed: 'Giao thất bại', returned: 'Hoàn về', cancelled: 'Đã huỷ',
};

export const STAGE_LABEL: Record<ProcessingStage, string> = { prepare: 'Cần xử lý', pack: 'Chờ đóng gói', handover: 'Chờ bàn giao' };

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

function useInvalidate(keys: unknown[][]) {
    const qc = useQueryClient();
    return () => keys.forEach((k) => qc.invalidateQueries({ queryKey: k }));
}

// ---- queries -----------------------------------------------------------------

export interface ProcessingFilters { source?: string; carrier?: string; customer?: string; product?: string; channel_account_id?: number; page?: number; per_page?: number }

/** The order-processing board (SPEC 0009) — one stage at a time, with the shared filters. */
export function useProcessingBoard(stage: ProcessingStage, filters: ProcessingFilters) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['fulfillment-board', tenantId, stage, filters],
        enabled: api != null,
        placeholderData: (p) => p,
        queryFn: async () => {
            const params: Record<string, string | number> = { stage };
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '') params[k] = v as never; });
            const { data } = await api!.get<Paginated<Order> & { meta: { stage: string } }>('/fulfillment/processing', { params });
            return data;
        },
    });
}

export function useProcessingCounts(filters: Omit<ProcessingFilters, 'page' | 'per_page'>) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['fulfillment-board-counts', tenantId, filters],
        enabled: api != null,
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '') params[k] = v as never; });
            const { data } = await api!.get<{ data: Record<ProcessingStage, number> }>('/fulfillment/processing/counts', { params });
            return data.data;
        },
    });
}

export function useReadyOrders(filters: { q?: string; channel_account_id?: number; source?: string; page?: number; per_page?: number }) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['fulfillment-ready', tenantId, filters],
        enabled: api != null,
        placeholderData: (p) => p,
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '') params[k] = v as never; });
            const { data } = await api!.get<Paginated<Order>>('/fulfillment/ready', { params });
            return data;
        },
    });
}

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
    return useMutation({ mutationFn: async (shipment_ids: number[]) => { const { data } = await api!.post<{ data: { packed: number } }>('/shipments/pack', { shipment_ids }); return data.data; }, onSuccess: invalidate });
}

export function useHandoverShipments() {
    const api = useScopedApi();
    const invalidate = useFulfillmentInvalidate();
    return useMutation({ mutationFn: async (shipment_ids: number[]) => { const { data } = await api!.post<{ data: { handed_over: number } }>('/shipments/handover', { shipment_ids }); return data.data; }, onSuccess: invalidate });
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
        mutationFn: async (vars: { type: 'label' | 'picking' | 'packing' | 'invoice'; order_ids?: number[]; shipment_ids?: number[] }) => {
            const { data } = await api!.post<{ data: PrintJob }>('/print-jobs', vars); return data.data;
        },
    });
}
