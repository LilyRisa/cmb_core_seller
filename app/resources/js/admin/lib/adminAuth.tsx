// Spec 2026-05-17 — admin auth hooks (TanStack Query).
//
// useAdminMe: poll `/auth/me`; 401 ⇒ null (chưa login).
// useAdminLogin: gọi `/sanctum/csrf-cookie` trước, sau đó `POST /auth/login`.
// useAdminLogout: clear session phía server, set query cache về null.

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { adminClient, ensureAdminCsrf } from './adminClient';

export type AdminMe = {
    id: number;
    username: string;
    email: string | null;
    name: string;
    is_active: boolean;
    last_login_at: string | null;
};

export function useAdminMe() {
    return useQuery<AdminMe | null>({
        queryKey: ['admin-me'],
        queryFn: async () => {
            try {
                const r = await adminClient.get('/auth/me');
                return r.data.data as AdminMe;
            } catch (e: any) {
                if (e?.response?.status === 401) return null;
                throw e;
            }
        },
        staleTime: 30_000,
    });
}

export function useAdminLogin() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { username: string; password: string }) => {
            await ensureAdminCsrf();
            const r = await adminClient.post('/auth/login', vars);
            return r.data.data as AdminMe;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-me'] }),
    });
}

export function useAdminLogout() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async () => {
            await adminClient.post('/auth/logout');
        },
        onSuccess: () => qc.setQueryData(['admin-me'], null),
    });
}

export function useAdminChangePassword() {
    return useMutation({
        mutationFn: async (vars: { current_password: string; password: string }) => {
            const r = await adminClient.post('/auth/change-password', vars);
            return r.data.data;
        },
    });
}
