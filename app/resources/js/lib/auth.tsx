import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, ensureCsrf } from './api';

export interface TenantSummary {
    id: number;
    name: string;
    slug: string;
    role: string;
}

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    /** SPEC 0020 — true ⇒ user là super-admin hệ thống, được vào /admin/*. */
    is_super_admin?: boolean;
    tenants: TenantSummary[];
}

async function fetchMe(): Promise<AuthUser | null> {
    try {
        const { data } = await api.get<{ data: AuthUser }>('/auth/me');
        return data.data;
    } catch (err: unknown) {
        const status = (err as { response?: { status?: number } })?.response?.status;
        if (status === 401) return null;
        throw err;
    }
}

/** Current authenticated user (null when logged out). */
export function useAuth() {
    return useQuery({
        queryKey: ['me'],
        queryFn: fetchMe,
        retry: false,
        staleTime: 60_000,
    });
}

export function useLogin() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { email: string; password: string; remember?: boolean }) => {
            await ensureCsrf();
            const { data } = await api.post<{ data: AuthUser }>('/auth/login', vars);
            return data.data;
        },
        onSuccess: (user) => qc.setQueryData(['me'], user),
    });
}

export function useRegister() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: {
            name: string;
            email: string;
            password: string;
            password_confirmation: string;
            tenant_name?: string;
        }) => {
            await ensureCsrf();
            const { data } = await api.post<{ data: AuthUser }>('/auth/register', vars);
            return data.data;
        },
        onSuccess: (user) => qc.setQueryData(['me'], user),
    });
}

/** Update own profile (name / email / password). Requires `current_password` when changing email/password. */
export function useUpdateProfile() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { name?: string; email?: string; current_password?: string; password?: string; password_confirmation?: string }) => {
            await ensureCsrf();
            const { data } = await api.patch<{ data: AuthUser }>('/auth/profile', vars);
            return data.data;
        },
        onSuccess: (user) => qc.setQueryData(['me'], user),
    });
}

export function useLogout() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async () => {
            await ensureCsrf();
            await api.post('/auth/logout');
        },
        onSuccess: () => {
            qc.setQueryData(['me'], null);
            qc.clear();
        },
    });
}

const TENANT_KEY = 'cmb.currentTenantId';

export function getCurrentTenantId(): number | null {
    const v = localStorage.getItem(TENANT_KEY);
    return v ? Number(v) : null;
}

export function setCurrentTenantId(id: number | null): void {
    if (id == null) localStorage.removeItem(TENANT_KEY);
    else localStorage.setItem(TENANT_KEY, String(id));
}
