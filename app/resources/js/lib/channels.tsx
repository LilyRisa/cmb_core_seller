import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

export interface ChannelAccount {
    id: number;
    provider: string;
    external_shop_id: string;
    shop_name: string | null;
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

export function useDisconnectChannel() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/channel-accounts/${id}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['channel-accounts', tenantId] }),
    });
}

export function useResyncChannel() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (id: number) => { await api!.post(`/channel-accounts/${id}/resync`); },
    });
}
