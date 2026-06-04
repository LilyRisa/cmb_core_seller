import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from '@/lib/api';
import { useCurrentTenantId } from '@/lib/tenant';
import type { GeoItem } from '@/lib/adWizard';

/** Scoped axios for /api/v1/marketing/* — mirrors lib/adWizard.tsx. */
function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export interface ExclusionTemplate {
    id: number;
    name: string;
    payload: GeoItem[];
    created_at: string | null;
}

const KEY = ['marketing', 'exclusion-templates'];

export function useExclusionTemplates() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: [...KEY, tenantId],
        enabled: api != null,
        queryFn: async () =>
            (await api!.get<{ data: ExclusionTemplate[] }>('/marketing/exclusion-templates')).data.data,
    });
}

export function useCreateExclusionTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (body: { name: string; payload: GeoItem[] }) =>
            (await api!.post<{ data: ExclusionTemplate }>('/marketing/exclusion-templates', body)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
    });
}

export function useDeleteExclusionTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            await api!.delete(`/marketing/exclusion-templates/${id}`);
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
    });
}
