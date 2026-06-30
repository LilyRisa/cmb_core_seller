import { useMemo } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export interface EInvoiceAccount {
    id: number;
    provider: string;
    name: string;
    is_invoice_with_code: boolean | null;
    default_mode: 'hsm' | 'mtt';
    templates: Record<string, unknown>;
    seller_info: Record<string, unknown>;
    is_default: boolean;
    is_active: boolean;
    meta: Record<string, unknown> & { last_verified_at?: string; last_verify_ok?: boolean; last_verify_error?: string | null };
    credential_keys: string[];
    created_at: string | null;
}

export interface VerifyResult {
    ok: boolean;
    message: string;
    error_code: string | null;
    expires_at: string | null;
    verified_at: string;
    account: EInvoiceAccount;
}

export interface CompanyInfo {
    company_name: string;
    tax_code: string;
    address: string | null;
    is_invoice_with_code: boolean;
    email: string | null;
}

export interface InvoiceTemplate {
    template_id: string;
    template_name: string;
    inv_series: string;
    invoice_type: number;
    is_published: boolean;
    inactive: boolean;
}

export function useEInvoiceAccounts() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['einvoice', tenantId, 'accounts'],
        enabled: api != null,
        retry: (n, err) => {
            const s = (err as { response?: { status?: number } })?.response?.status;
            return s !== 402 && s !== 403 && n < 2;
        },
        queryFn: async () => {
            const { data } = await api!.get<{ data: EInvoiceAccount[] }>('/einvoice/accounts');
            return data.data;
        },
    });
}

export function useCreateEInvoiceAccount() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { provider: string; name: string; default_mode?: 'hsm' | 'mtt'; credentials?: Record<string, string>; is_default?: boolean }) => {
            const { data } = await api!.post<{ data: EInvoiceAccount }>('/einvoice/accounts', vars);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['einvoice', tenantId, 'accounts'] }),
    });
}

export function useUpdateEInvoiceAccount() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, ...patch }: { id: number } & Partial<Pick<EInvoiceAccount, 'name' | 'default_mode' | 'is_default' | 'is_active' | 'templates' | 'seller_info'>> & { credentials?: Record<string, string> }) => {
            const { data } = await api!.patch<{ data: EInvoiceAccount }>(`/einvoice/accounts/${id}`, patch);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['einvoice', tenantId, 'accounts'] }),
    });
}

export function useDeleteEInvoiceAccount() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            await api!.delete(`/einvoice/accounts/${id}`);
            return id;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['einvoice', tenantId, 'accounts'] }),
    });
}

export function useVerifyEInvoiceAccount() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            const { data } = await api!.post<{ data: VerifyResult }>(`/einvoice/accounts/${id}/verify`);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['einvoice', tenantId, 'accounts'] }),
    });
}

export function useEInvoiceCompanyInfo() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (id: number) => {
            const { data } = await api!.get<{ data: CompanyInfo }>(`/einvoice/accounts/${id}/company-info`);
            return data.data;
        },
    });
}

export function useEInvoiceTemplates() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async ({ id, year }: { id: number; year?: number }) => {
            const { data } = await api!.get<{ data: InvoiceTemplate[] }>(`/einvoice/accounts/${id}/templates`, { params: year ? { year } : {} });
            return data.data;
        },
    });
}
