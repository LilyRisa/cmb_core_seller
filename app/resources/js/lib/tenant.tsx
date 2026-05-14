import { useMemo } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { tenantApi } from './api';
import { getCurrentTenantId, useAuth } from './auth';

/** Roles understood by the backend (CMBcoreSeller\Modules\Tenancy\Enums\Role). */
export const ROLES = [
    { value: 'owner', label: 'Chủ sở hữu' },
    { value: 'admin', label: 'Quản trị' },
    { value: 'staff_order', label: 'NV xử lý đơn' },
    { value: 'staff_warehouse', label: 'NV kho' },
    { value: 'accountant', label: 'Kế toán' },
    { value: 'viewer', label: 'Chỉ xem' },
] as const;

export type RoleValue = (typeof ROLES)[number]['value'];

export const roleLabel = (role: string): string =>
    ROLES.find((r) => r.value === role)?.label ?? role;

export interface TenantDetail {
    id: number;
    name: string;
    slug: string;
    status: string;
    settings: Record<string, unknown> | null;
    current_role: RoleValue | null;
}

export interface TenantMember {
    id: number;
    name: string;
    email: string;
    role: RoleValue;
}

/** The tenant id the UI is currently scoped to (chosen workspace, or the first one). */
export function useCurrentTenantId(): number | null {
    const { data: user } = useAuth();
    return getCurrentTenantId() ?? user?.tenants[0]?.id ?? null;
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export function useTenant() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['tenant', tenantId],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: TenantDetail }>('/tenant');
            return data.data;
        },
    });
}

/** Update workspace info (name / slug / settings). owner/admin only. */
export function useUpdateTenant() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { name?: string; slug?: string; settings?: Record<string, unknown> }) => {
            const { data } = await api!.patch<{ data: TenantDetail }>('/tenant', vars);
            return data.data;
        },
        onSuccess: () => { qc.invalidateQueries({ queryKey: ['tenant', tenantId] }); qc.invalidateQueries({ queryKey: ['me'] }); },
    });
}

export function useTenantMembers() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['tenant', tenantId, 'members'],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: TenantMember[] }>('/tenant/members');
            return data.data;
        },
    });
}

export function useAddMember() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { email: string; role: RoleValue }) => {
            const { data } = await api!.post<{ data: TenantMember }>('/tenant/members', vars);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['tenant', tenantId, 'members'] }),
    });
}

const PERMS: Record<RoleValue, string[]> = {
    // Owner: toàn quyền + `billing.manage` (SPEC 0018).
    owner: ['*'],
    // Admin: `*` nhưng phủ định `billing.manage` (chỉ owner đổi gói / thanh toán).
    admin: ['*', '!billing.manage'],
    staff_order: ['orders.view', 'orders.update', 'orders.create', 'orders.status', 'fulfillment.view', 'fulfillment.print', 'fulfillment.ship', 'products.view', 'inventory.view', 'channels.view', 'dashboard.view'],
    staff_warehouse: ['inventory.view', 'inventory.adjust', 'inventory.transfer', 'inventory.stocktake', 'fulfillment.view', 'fulfillment.scan', 'fulfillment.print', 'orders.view', 'products.view', 'dashboard.view'],
    accountant: ['finance.view', 'finance.reconcile', 'reports.view', 'reports.export', 'orders.view', 'inventory.view', 'dashboard.view', 'billing.view'],
    viewer: ['orders.view', 'inventory.view', 'products.view', 'channels.view', 'dashboard.view'],
};

/**
 * Hide / disable UI by the current tenant role. Mirrors the backend permission
 * sets — but the backend Policy/Gate is the source of truth, never the client.
 */
export function useCan(permission: string): boolean {
    const { data: tenant } = useTenant();
    const role = tenant?.current_role;
    if (!role) return false;
    const perms = PERMS[role] ?? [];
    // Hỗ trợ phủ định `!permission` — khớp Role::can() backend (SPEC 0018: admin có `*` nhưng `!billing.manage`).
    if (perms.includes('!' + permission)) return false;
    return perms.includes('*') || perms.includes(permission);
}
