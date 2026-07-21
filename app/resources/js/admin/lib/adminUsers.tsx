// Spec 2026-05-17 — hooks cho admin_users CRUD ở `/api/v1/admin/admin-users/*`.

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export type AdminRow = {
    id: number;
    username: string;
    email: string | null;
    name: string;
    is_active: boolean;
    last_login_at: string | null;
    created_at: string | null;
};

export type AdminUsersResponse = {
    data: AdminRow[];
    meta: { pagination: { page: number; per_page: number; total: number; total_pages: number } };
};

export function useAdminUsersList(params: {
    q?: string;
    is_active?: boolean;
    page?: number;
    per_page?: number;
}) {
    return useQuery({
        queryKey: ['admin-users', params],
        queryFn: async () => (await api.get<AdminUsersResponse>('/admin/admin-users', { params })).data,
    });
}

export function useCreateAdminUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { username: string; email?: string; name: string; password: string }) =>
            (await api.post<{ data: AdminRow }>('/admin/admin-users', vars)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
    });
}

export function useUpdateAdminUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, ...vars }: { id: number; name?: string; email?: string | null }) =>
            (await api.patch<{ data: AdminRow }>(`/admin/admin-users/${id}`, vars)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
    });
}

export function useSuspendAdminUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, reason }: { id: number; reason: string }) =>
            (await api.post<{ data: AdminRow }>(`/admin/admin-users/${id}/suspend`, { reason })).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
    });
}

export function useReactivateAdminUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, reason }: { id: number; reason: string }) =>
            (await api.post<{ data: AdminRow }>(`/admin/admin-users/${id}/reactivate`, { reason })).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
    });
}

export function useResetAdminPassword() {
    return useMutation({
        mutationFn: async ({ id, password }: { id: number; password: string }) =>
            (await api.post<{ data: { ok: boolean } }>(`/admin/admin-users/${id}/reset-password`, { password })).data.data,
    });
}
