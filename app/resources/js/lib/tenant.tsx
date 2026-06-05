import { useMemo } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { tenantApi } from './api';
import { getCurrentTenantId, useAuth } from './auth';

/** Legacy preset keys (kept for labels; roles are now custom — SPEC 0031). */
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
    code: string | null;
    status: string;
    settings: Record<string, unknown> | null;
    current_role: string | null;
    current_role_id: number | null;
    can_manage_team: boolean;
}

export interface TenantMember {
    id: number;
    name: string;
    email: string | null;
    username: string | null;
    is_sub_account: boolean;
    role_id: number | null;
    role_name: string | null;
}

export interface PermissionItem { key: string; label: string; type: 'view' | 'action' }
export interface PermissionGroup { key: string; label: string; permissions: PermissionItem[] }

export interface TenantRole {
    id: number;
    name: string;
    permissions: string[];
    is_owner: boolean;
    is_system: boolean;
    members_count: number;
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

/** Update workspace info (name / slug / settings). */
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

// --- Members (SPEC 0031) ---------------------------------------------------

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

function useInvalidateMembers() {
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return () => qc.invalidateQueries({ queryKey: ['tenant', tenantId, 'members'] });
}

/** Add an existing email user as a member. */
export function useAddExistingMember() {
    const api = useScopedApi();
    const invalidate = useInvalidateMembers();
    return useMutation({
        mutationFn: async (vars: { email: string; role_id: number }) =>
            (await api!.post<{ data: TenantMember }>('/tenant/members', { mode: 'email', ...vars })).data.data,
        onSuccess: invalidate,
    });
}

/** Create an email-less sub-account "{name}@{code}". */
export function useCreateSubAccount() {
    const api = useScopedApi();
    const invalidate = useInvalidateMembers();
    return useMutation({
        mutationFn: async (vars: { name: string; password: string; role_id: number }) =>
            (await api!.post<{ data: TenantMember }>('/tenant/members', { mode: 'sub', ...vars })).data.data,
        onSuccess: invalidate,
    });
}

export function useUpdateMemberRole() {
    const api = useScopedApi();
    const invalidate = useInvalidateMembers();
    return useMutation({
        mutationFn: async (vars: { userId: number; role_id: number }) =>
            (await api!.put<{ data: TenantMember }>(`/tenant/members/${vars.userId}`, { role_id: vars.role_id })).data.data,
        onSuccess: invalidate,
    });
}

export function useRemoveMember() {
    const api = useScopedApi();
    const invalidate = useInvalidateMembers();
    return useMutation({
        mutationFn: async (userId: number) => { await api!.delete(`/tenant/members/${userId}`); },
        onSuccess: invalidate,
    });
}

export function useResetMemberPassword() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (vars: { userId: number; password: string }) => {
            await api!.post(`/tenant/members/${vars.userId}/reset-password`, { password: vars.password });
        },
    });
}

// --- Roles & permission catalog (SPEC 0031) --------------------------------

export function usePermissionCatalog() {
    const api = useScopedApi();
    return useQuery({
        queryKey: ['tenant', 'permission-catalog'],
        enabled: api != null,
        staleTime: 5 * 60_000,
        queryFn: async () => (await api!.get<{ data: PermissionGroup[] }>('/tenant/permissions')).data.data,
    });
}

export function useRoles() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['tenant', tenantId, 'roles'],
        enabled: api != null,
        queryFn: async () => (await api!.get<{ data: TenantRole[] }>('/tenant/roles')).data.data,
    });
}

function useInvalidateRoles() {
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return () => qc.invalidateQueries({ queryKey: ['tenant', tenantId, 'roles'] });
}

export function useCreateRole() {
    const api = useScopedApi();
    const invalidate = useInvalidateRoles();
    return useMutation({
        mutationFn: async (vars: { name: string; permissions: string[] }) =>
            (await api!.post<{ data: TenantRole }>('/tenant/roles', vars)).data.data,
        onSuccess: invalidate,
    });
}

export function useUpdateRole() {
    const api = useScopedApi();
    const invalidate = useInvalidateRoles();
    return useMutation({
        mutationFn: async (vars: { id: number; name: string; permissions: string[] }) =>
            (await api!.put<{ data: TenantRole }>(`/tenant/roles/${vars.id}`, { name: vars.name, permissions: vars.permissions })).data.data,
        onSuccess: invalidate,
    });
}

export function useDeleteRole() {
    const api = useScopedApi();
    const invalidate = useInvalidateRoles();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/tenant/roles/${id}`); },
        onSuccess: invalidate,
    });
}

// --- Permission gating -----------------------------------------------------

/** Ability strings granted in the current tenant (from /me — owner ⇒ ['*']). */
export function useTenantPermissions(): string[] {
    const { data: user } = useAuth();
    const tenantId = useCurrentTenantId();
    return user?.tenants.find((t) => t.id === tenantId)?.permissions ?? [];
}

/**
 * Hide / disable UI by the current tenant's permissions. The backend Gate is the
 * source of truth — this is UX only.
 */
export function useCan(permission: string): boolean {
    const perms = useTenantPermissions();
    return perms.includes('*') || perms.includes(permission);
}
