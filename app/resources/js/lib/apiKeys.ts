import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

/** API key bên thứ 3 (owner-only) — SPEC 2026-06-26. */
export interface ApiKey {
    id: number;
    name: string;
    last_four: string | null;
    abilities: string[];
    expires_at: string | null;
    last_used_at: string | null;
    created_at: string | null;
}

export interface CreatedApiKey { id: number; name: string; token: string; expires_at: string | null }

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export function useApiKeys() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['api-keys', tenantId],
        enabled: api != null,
        queryFn: async () => (await api!.get<{ data: ApiKey[] }>('/tenant/api-keys')).data.data,
    });
}

export function useCreateApiKey() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (vars: { name: string; expires_at?: string | null }) =>
            (await api!.post<{ data: CreatedApiKey }>('/tenant/api-keys', vars)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['api-keys', tenantId] }),
    });
}

export function useDeleteApiKey() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/tenant/api-keys/${id}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['api-keys', tenantId] }),
    });
}
