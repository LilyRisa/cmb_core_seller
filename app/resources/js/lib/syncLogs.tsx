import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';
import type { Paginated } from './orders';

export interface SyncRun {
    id: number;
    channel_account_id: number;
    shop_name: string | null;
    provider: string | null;
    type: 'poll' | 'backfill' | 'webhook';
    status: 'running' | 'done' | 'failed';
    started_at: string | null;
    finished_at: string | null;
    duration_seconds: number | null;
    cursor: string | null;
    stats: { fetched: number; created: number; updated: number; skipped: number; errors: number };
    error: string | null;
}

export interface WebhookEvent {
    id: number;
    provider: string;
    event_type: string;
    raw_type: string | null;
    external_id: string | null;
    external_shop_id: string | null;
    channel_account_id: number | null;
    shop_name: string | null;
    signature_ok: boolean;
    status: 'pending' | 'processed' | 'ignored' | 'failed';
    attempts: number;
    error: string | null;
    received_at: string | null;
    processed_at: string | null;
}

export interface SyncRunFilters {
    channel_account_id?: number;
    type?: string;
    status?: string;
    page?: number;
    per_page?: number;
}

export interface WebhookEventFilters {
    channel_account_id?: number;
    provider?: string;
    event_type?: string;
    status?: string;
    signature_ok?: boolean;
    page?: number;
    per_page?: number;
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

function toParams<T extends object>(filters: T): Record<string, string | number | boolean> {
    const params: Record<string, string | number | boolean> = {};
    Object.entries(filters).forEach(([k, v]) => { if (v !== undefined && v !== '' && v !== null) params[k] = v as never; });
    return params;
}

export function useSyncRuns(filters: SyncRunFilters) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['sync-runs', tenantId, filters],
        enabled: api != null,
        placeholderData: (prev) => prev,
        refetchInterval: 15_000,
        queryFn: async () => {
            const { data } = await api!.get<Paginated<SyncRun>>('/sync-runs', { params: toParams(filters) });
            return data;
        },
    });
}

export function useWebhookEvents(filters: WebhookEventFilters) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['webhook-events', tenantId, filters],
        enabled: api != null,
        placeholderData: (prev) => prev,
        refetchInterval: 15_000,
        queryFn: async () => {
            const { data } = await api!.get<Paginated<WebhookEvent>>('/webhook-events', { params: toParams(filters) });
            return data;
        },
    });
}

export function useRedriveWebhook() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (id: number) => { await api!.post(`/webhook-events/${id}/redrive`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['webhook-events', tenantId] }),
    });
}

export function useRedriveSyncRun() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (id: number) => { await api!.post(`/sync-runs/${id}/redrive`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['sync-runs', tenantId] }),
    });
}

// --- display helpers --------------------------------------------------------

export const SYNC_RUN_TYPE_LABEL: Record<string, string> = {
    poll: 'Định kỳ', backfill: 'Lấy lại lịch sử', webhook: 'Webhook',
};

export const SYNC_RUN_STATUS: Record<string, { label: string; color: string }> = {
    running: { label: 'Đang chạy', color: 'processing' },
    done: { label: 'Hoàn tất', color: 'success' },
    failed: { label: 'Thất bại', color: 'error' },
};

export const WEBHOOK_STATUS: Record<string, { label: string; color: string }> = {
    pending: { label: 'Chờ xử lý', color: 'gold' },
    processed: { label: 'Đã xử lý', color: 'success' },
    ignored: { label: 'Bỏ qua', color: 'default' },
    failed: { label: 'Thất bại', color: 'error' },
};

export const WEBHOOK_EVENT_TYPE_LABEL: Record<string, string> = {
    order_created: 'Đơn mới',
    order_status_update: 'Cập nhật trạng thái đơn',
    order_cancel: 'Huỷ đơn',
    return_update: 'Cập nhật trả/hoàn',
    settlement_available: 'Đối soát sẵn sàng',
    product_update: 'Cập nhật sản phẩm',
    shop_deauthorized: 'Gian hàng bị huỷ uỷ quyền',
    data_deletion: 'Yêu cầu xoá dữ liệu',
    unknown: 'Không xác định',
};
