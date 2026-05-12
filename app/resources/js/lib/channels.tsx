import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

export interface ChannelAccount {
    id: number;
    provider: string;
    external_shop_id: string;
    shop_name: string | null;
    display_name: string | null;
    name: string;            // display_name || shop_name || external_shop_id
    shop_region: string | null;
    seller_type: string | null;
    status: string;
    token_expires_at: string | null;
    last_synced_at: string | null;
    last_webhook_at: string | null;
    created_at: string | null;
    has_shop_cipher: boolean;
}

export interface ConnectableProvider {
    code: string;
    name: string;
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export function useChannelAccounts() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['channel-accounts', tenantId],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: ChannelAccount[]; meta: { connectable_providers: ConnectableProvider[] } }>('/channel-accounts');
            return data;
        },
    });
}

/** Begin OAuth: get the marketplace authorization URL, then redirect the browser there. */
export function useConnectChannel() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (provider: string) => {
            const { data } = await api!.post<{ data: { auth_url: string; provider: string } }>(`/channel-accounts/${provider}/connect`);
            return data.data;
        },
        onSuccess: ({ auth_url }) => { window.location.href = auth_url; },
    });
}

/**
 * Xóa kết nối gian hàng — xóa TẤT CẢ đơn của gian hàng + hủy mọi liên kết SKU của nó. Cần `confirm` =
 * tên gian hàng (server kiểm). Trả `{ deleted_orders, unlinked_skus }`. (Đây là DELETE, không phải "tạm ngắt".)
 */
export function useDeleteChannelAccount() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (vars: { id: number; confirm: string }) => {
            const { data } = await api!.delete<{ data: { deleted_orders: number; unlinked_skus: number } }>(`/channel-accounts/${vars.id}`, { data: { confirm: vars.confirm } });
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['channel-accounts', tenantId] });
            qc.invalidateQueries({ queryKey: ['orders', tenantId] });
            qc.invalidateQueries({ queryKey: ['skus', tenantId] });
            qc.invalidateQueries({ queryKey: ['channel-listings', tenantId] });
            qc.invalidateQueries({ queryKey: ['inventory-levels', tenantId] });
        },
    });
}

export function useResyncChannel() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (id: number) => { await api!.post(`/channel-accounts/${id}/resync`); },
    });
}

export function useRenameChannel() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (vars: { id: number; display_name: string | null }) => { await api!.patch(`/channel-accounts/${vars.id}`, { display_name: vars.display_name }); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['channel-accounts', tenantId] }),
    });
}
