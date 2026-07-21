// Spec 2026-05-17 — hooks cho tenant user CRUD ở `/api/v1/admin/users/*`.

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export type TenantUserRow = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    suspended_at: string | null;
    tenants: { id: number; name: string; role: string }[];
    ai_usage: { this_month: number; all_time: number };
    created_at: string | null;
};

export type TenantUserDetail = TenantUserRow & {
    email_verified_at: string | null;
    suspended_at: string | null;
};

export type TenantUsersResponse = {
    data: TenantUserRow[];
    meta: { pagination: { page: number; per_page: number; total: number; total_pages: number } };
};

export function useTenantUsers(params: { q?: string; page?: number; per_page?: number }) {
    return useQuery({
        queryKey: ['tenant-users', params],
        queryFn: async () => (await api.get<TenantUsersResponse>('/admin/users', { params })).data,
    });
}

export function useTenantUserDetail(id: number | null) {
    return useQuery({
        queryKey: ['tenant-user', id],
        queryFn: async () => (await api.get<{ data: TenantUserDetail }>(`/admin/users/${id}`)).data.data,
        enabled: id !== null,
    });
}

export function useUpdateTenantUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, ...vars }: { id: number; name?: string; email?: string }) =>
            (await api.patch<{ data: { id: number; name: string; email: string } }>(`/admin/users/${id}`, vars)).data.data,
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['tenant-users'] });
            qc.invalidateQueries({ queryKey: ['tenant-user'] });
        },
    });
}

export function useResetTenantUserPassword() {
    return useMutation({
        mutationFn: async ({ id, password }: { id: number; password: string }) =>
            (await api.post<{ data: { ok: boolean } }>(`/admin/users/${id}/reset-password`, { password })).data.data,
    });
}

export function useSuspendTenantUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, reason }: { id: number; reason: string }) =>
            (await api.post<{ data: { id: number; suspended_at: string } }>(`/admin/users/${id}/suspend`, { reason })).data.data,
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['tenant-users'] });
            qc.invalidateQueries({ queryKey: ['tenant-user'] });
        },
    });
}

export function useReactivateTenantUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, reason }: { id: number; reason: string }) =>
            (await api.post<{ data: { id: number; suspended_at: null } }>(`/admin/users/${id}/reactivate`, { reason })).data.data,
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['tenant-users'] });
            qc.invalidateQueries({ queryKey: ['tenant-user'] });
        },
    });
}

export type AiUsageBreakdown = {
    all_time: number;
    by_month: { period_ym: number; count: number }[];
    by_feature: { feature: string; count: number }[];
};

export function useTenantUserAiUsage(id: number | null) {
    return useQuery({
        queryKey: ['tenant-user-ai-usage', id],
        queryFn: async () => (await api.get<{ data: AiUsageBreakdown }>(`/admin/users/${id}/ai-usage`)).data.data,
        enabled: id !== null,
    });
}
