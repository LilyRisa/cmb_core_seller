import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';
import type { SampleProfile, Template } from './shippingLabelTypes';

export type TemplateInput = Omit<Template, 'id' | 'is_default' | 'schema_version' | 'created_at' | 'updated_at'> & { schema_version?: number };

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

function invalidate(qc: ReturnType<typeof useQueryClient>) {
    qc.invalidateQueries({ queryKey: ['shipping-label-templates'] });
}

export function useShippingLabelTemplates() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['shipping-label-templates', tenantId],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: Template[] }>('/shipping-label-templates');
            return data.data;
        },
    });
}

export function useShippingLabelTemplate(id: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['shipping-label-template', tenantId, id],
        enabled: api != null && id != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: Template }>(`/shipping-label-templates/${id}`);
            return data.data;
        },
    });
}

export function useCreateShippingLabelTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: TemplateInput) => {
            const { data } = await api!.post<{ data: Template }>('/shipping-label-templates', input);
            return data.data;
        },
        onSuccess: () => invalidate(qc),
    });
}

export function useUpdateShippingLabelTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, input }: { id: number; input: TemplateInput }) => {
            const { data } = await api!.put<{ data: Template }>(`/shipping-label-templates/${id}`, input);
            return data.data;
        },
        onSuccess: () => invalidate(qc),
    });
}

export function useDeleteShippingLabelTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/shipping-label-templates/${id}`); },
        onSuccess: () => invalidate(qc),
    });
}

export function useSetDefaultShippingLabelTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            const { data } = await api!.post<{ data: Template }>(`/shipping-label-templates/${id}/set-default`);
            return data.data;
        },
        onSuccess: () => invalidate(qc),
    });
}

export function useDuplicateShippingLabelTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            const { data } = await api!.post<{ data: Template }>(`/shipping-label-templates/${id}/duplicate`);
            return data.data;
        },
        onSuccess: () => invalidate(qc),
    });
}

export function usePreviewShippingLabelTemplate() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async ({ id, sample_profile }: { id: number; sample_profile: SampleProfile }) => {
            const { data } = await api!.post<{ data: { url: string } }>(`/shipping-label-templates/${id}/preview`, { sample_profile });
            return data.data;
        },
    });
}

export function usePreviewInlineShippingLabelTemplate() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (vars: TemplateInput & { sample_profile: SampleProfile }) => {
            const { data } = await api!.post<{ data: { url: string } }>('/shipping-label-templates/preview', vars);
            return data.data;
        },
    });
}
