import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from './api';

/**
 * SPEC 0020 — hooks gọi /api/v1/admin/* cho trang super-admin.
 *
 * KHÔNG dùng `tenantApi(...)` (admin global, không cần `X-Tenant-Id`).
 * Mọi mutation invalidate `['admin', 'tenants']` để list/detail tự refetch.
 */

export interface AdminOwner { id: number; name: string; email: string }

export interface AdminSubscription {
    id: number;
    plan_code: string | null;
    plan_name?: string | null;
    status: string;
    billing_cycle: string;
    current_period_start: string | null;
    current_period_end: string | null;
    over_quota_warned_at: string | null;
    over_quota_locked: boolean;
}

export interface AdminTenantSummary {
    id: number;
    name: string;
    slug: string;
    status: string;
    created_at: string | null;
    owner: AdminOwner | null;
    subscription: AdminSubscription | null;
    usage: { channel_accounts: { used: number; limit: number; over: boolean } };
}

export interface AdminChannelAccount {
    id: number;
    provider: string;
    name: string;
    shop_name: string | null;
    display_name: string | null;
    external_shop_id: string;
    status: string;
    last_synced_at: string | null;
    created_at: string | null;
}

export interface AdminMember {
    user_id: number;
    role: string;
    name: string | null;
    email: string | null;
    is_super_admin: boolean;
}

export interface AdminAuditEntry {
    id: number;
    action: string;
    user_id: number | null;
    changes: Record<string, unknown> | null;
    ip: string | null;
    created_at: string | null;
}

export interface AdminTenantDetail extends AdminTenantSummary {
    channel_accounts: AdminChannelAccount[];
    members: AdminMember[];
    recent_admin_actions: AdminAuditEntry[];
}

export interface AdminUserRow {
    id: number;
    name: string;
    email: string;
    is_super_admin: boolean;
    tenants: Array<{ id: number; name: string; slug: string; role: string }>;
    created_at: string | null;
}

interface Paginated<T> { data: T[]; meta: { pagination: { page: number; per_page: number; total: number; total_pages: number } } }

export interface AdminTenantsFilters { q?: string; over_quota?: boolean; suspended?: boolean; page?: number; per_page?: number }

export function useAdminTenants(filters: AdminTenantsFilters = {}) {
    return useQuery({
        queryKey: ['admin', 'tenants', filters],
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            if (filters.q) params.q = filters.q;
            if (filters.over_quota) params.over_quota = 1;
            if (filters.suspended) params.suspended = 1;
            if (filters.page) params.page = filters.page;
            if (filters.per_page) params.per_page = filters.per_page;
            const { data } = await api.get<Paginated<AdminTenantSummary>>('/admin/tenants', { params });
            return data;
        },
        placeholderData: (p) => p,
        staleTime: 30_000,
    });
}

export function useAdminTenant(id: number | null) {
    return useQuery({
        queryKey: ['admin', 'tenants', 'detail', id],
        enabled: id != null,
        queryFn: async () => {
            const { data } = await api.get<{ data: AdminTenantDetail }>(`/admin/tenants/${id}`);
            return data.data;
        },
    });
}

export function useAdminUsers(filters: { q?: string; is_super_admin?: boolean; page?: number; per_page?: number } = {}) {
    return useQuery({
        queryKey: ['admin', 'users', filters],
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            if (filters.q) params.q = filters.q;
            if (filters.is_super_admin) params.is_super_admin = 1;
            if (filters.page) params.page = filters.page;
            if (filters.per_page) params.per_page = filters.per_page;
            const { data } = await api.get<Paginated<AdminUserRow>>('/admin/users', { params });
            return data;
        },
        placeholderData: (p) => p,
        staleTime: 60_000,
    });
}

export function useAdminDeleteChannel() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { tenantId: number; channelAccountId: number; reason: string }) => {
            const { data } = await api.delete<{ data: { deleted_orders: number; unlinked_skus: number } }>(
                `/admin/tenants/${vars.tenantId}/channel-accounts/${vars.channelAccountId}`,
                { data: { reason: vars.reason } },
            );
            return data.data;
        },
        onSuccess: (_d, vars) => {
            qc.invalidateQueries({ queryKey: ['admin', 'tenants', 'detail', vars.tenantId] });
            qc.invalidateQueries({ queryKey: ['admin', 'tenants'] });
        },
    });
}

export function useAdminChangePlan() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { tenantId: number; plan_code: string; cycle: 'monthly' | 'yearly' | 'trial'; reason: string }) => {
            const { data } = await api.post<{ data: AdminSubscription }>(
                `/admin/tenants/${vars.tenantId}/subscription`,
                { plan_code: vars.plan_code, cycle: vars.cycle, reason: vars.reason },
            );
            return data.data;
        },
        onSuccess: (_d, vars) => {
            qc.invalidateQueries({ queryKey: ['admin', 'tenants', 'detail', vars.tenantId] });
            qc.invalidateQueries({ queryKey: ['admin', 'tenants'] });
        },
    });
}

export function useAdminSuspendTenant() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { tenantId: number; reason: string }) => {
            const { data } = await api.post<{ data: AdminTenantSummary }>(
                `/admin/tenants/${vars.tenantId}/suspend`,
                { reason: vars.reason },
            );
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin'] }),
    });
}

export function useAdminReactivateTenant() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { tenantId: number }) => {
            const { data } = await api.post<{ data: AdminTenantSummary }>(`/admin/tenants/${vars.tenantId}/reactivate`);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin'] }),
    });
}
