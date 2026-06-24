import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, ensureCsrf } from './api';

export interface TenantSummary {
    id: number;
    name: string;
    slug: string;
    /** SPEC 0031 — 5-char shop code (used in sub-account usernames). */
    code: string | null;
    /** Legacy preset key, kept for compat; prefer role_id/role_name. */
    role: string | null;
    role_id: number | null;
    role_name: string | null;
    /** SPEC 0031 — granted ability strings in this tenant (owner ⇒ ['*']). */
    permissions: string[];
}

export interface AuthUser {
    id: number;
    name: string;
    email: string | null;
    /** SPEC 0031 — set for email-less sub-accounts ("{name}@{code}"). */
    username: string | null;
    /** SPEC 0022 — ISO timestamp khi user verify email; `null` ⇒ FE chặn vào app. */
    email_verified_at: string | null;
    tenants: TenantSummary[];
    /** SPEC 0037 — lựa chọn giao diện + phiên tab (Web Desktop v2). */
    preferences?: {
        ui_shell: 'v1' | 'v2';
        ui_open_tabs: { appKey: string; path: string }[];
        ui_active_tab: string | null;
        /** SPEC 0039 — URL hình nền Desktop đã chọn; null = gradient mặc định. */
        ui_desktop_bg: string | null;
    };
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
        mutationFn: async (vars: { email: string; password: string; remember?: boolean; captcha_token?: string }) => {
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
            captcha_token?: string;
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

/**
 * SPEC 0022 — gửi lại email xác thực (`POST /auth/email/verify/resend`).
 * BE throttle 6/giờ. Trả `{ sent: true }` hoặc `{ sent: false, reason: 'already_verified' }`.
 */
export interface ResendResult { sent: boolean; reason?: string }

export function useResendVerification() {
    const qc = useQueryClient();
    return useMutation<ResendResult, unknown, void>({
        mutationFn: async () => {
            await ensureCsrf();
            const { data } = await api.post<{ data: ResendResult }>('/auth/email/verify/resend');
            return data.data;
        },
        // Nếu BE bảo đã verified ⇒ refresh `me` để gate FE tự nhả.
        onSuccess: (r) => {
            if (r.sent === false && r.reason === 'already_verified') {
                qc.invalidateQueries({ queryKey: ['me'] });
            }
        },
    });
}

/**
 * SPEC 0022 — bước 1: yêu cầu gửi email đặt lại mật khẩu (`POST /auth/password/forgot`).
 * BE luôn trả `{ sent: true }` generic (chống enumerate email). Throttle 5/15p.
 */
export function useForgotPassword() {
    return useMutation<{ sent: boolean }, unknown, { email: string; captcha_token?: string }>({
        mutationFn: async (vars) => {
            await ensureCsrf();
            const { data } = await api.post<{ data: { sent: boolean } }>('/auth/password/forgot', vars);
            return data.data;
        },
    });
}

/**
 * SPEC 0022 — bước 2: đặt mật khẩu mới bằng token trong email (`POST /auth/password/reset`).
 * Policy: ≥8 ký tự, có chữ hoa + chữ thường + ký tự đặc biệt. Throttle 30/giờ.
 * Lỗi: `422 INVALID_RESET_TOKEN` (token sai/hết hạn) | `422 VALIDATION_FAILED`.
 */
export function useResetPassword() {
    return useMutation<
        { reset: boolean },
        unknown,
        { email: string; token: string; password: string; password_confirmation: string }
    >({
        mutationFn: async (vars) => {
            await ensureCsrf();
            const { data } = await api.post<{ data: { reset: boolean } }>('/auth/password/reset', vars);
            return data.data;
        },
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
