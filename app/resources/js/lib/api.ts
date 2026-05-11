import axios, { AxiosError } from 'axios';

/**
 * Axios instance for the JSON API. Cookie-based (Sanctum SPA): credentials are
 * sent, and the XSRF token is read from the cookie automatically by axios.
 * See docs/05-api/conventions.md.
 */
export const api = axios.create({
    baseURL: '/api/v1',
    withCredentials: true,
    withXSRFToken: true,
    headers: { Accept: 'application/json' },
});

let csrfReady = false;

/** Sanctum requires hitting this once before the first state-changing request. */
export async function ensureCsrf(): Promise<void> {
    if (csrfReady) return;
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
    csrfReady = true;
}

/** Pull a tenant-scoped client (adds the X-Tenant-Id header). */
export function tenantApi(tenantId: number | string) {
    return axios.create({
        ...api.defaults,
        headers: { ...api.defaults.headers, 'X-Tenant-Id': String(tenantId) },
    });
}

export interface ApiErrorBody {
    error: { code: string; message: string; details?: unknown; trace_id?: string };
}

export function errorMessage(err: unknown, fallback = 'Đã có lỗi xảy ra.'): string {
    const e = err as AxiosError<ApiErrorBody>;
    return e?.response?.data?.error?.message ?? e?.message ?? fallback;
}

export function isUnauthenticated(err: unknown): boolean {
    return (err as AxiosError)?.response?.status === 401;
}
