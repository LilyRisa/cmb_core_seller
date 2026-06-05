import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from '@/lib/api';
import { useCurrentTenantId } from '@/lib/tenant';

/** Scoped axios for /api/v1/marketing/* — mirrors lib/adWizard.tsx. */
function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export interface AudienceTemplateItem { id: string; name: string; type: string }
export interface AudienceTemplatePayload {
    include: AudienceTemplateItem[];
    narrow: AudienceTemplateItem[];
    exclude: AudienceTemplateItem[];
}
export interface AudienceTemplate {
    id: number;
    name: string;
    payload: AudienceTemplatePayload;
    created_at: string | null;
}

const KEY = ['marketing', 'audience-templates'];

export function useAudienceTemplates() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: [...KEY, tenantId],
        enabled: api != null,
        queryFn: async () =>
            (await api!.get<{ data: AudienceTemplate[] }>('/marketing/audience-templates')).data.data,
    });
}

export function useCreateAudienceTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (body: { name: string; payload: AudienceTemplatePayload }) =>
            (await api!.post<{ data: AudienceTemplate }>('/marketing/audience-templates', body)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
    });
}

export function useDeleteAudienceTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            await api!.delete(`/marketing/audience-templates/${id}`);
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
    });
}
