// Spec 2026-05-17 — HTTP client cho admin SPA.
//
// `withCredentials: true` để Sanctum SPA cookie flow (session admin_web).
// CSRF auto-attach: axios đọc cookie `XSRF-TOKEN` (set bởi `/sanctum/csrf-cookie`)
// và gửi qua header `X-XSRF-TOKEN` cho mọi non-GET. Bundle admin KHÔNG share
// instance này với user SPA — tránh leak header / interceptor giữa hai bundle.

import axios from 'axios';

export const adminClient = axios.create({
    baseURL: '/api/v1/admin',
    withCredentials: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
});

/**
 * Gọi trước login để Sanctum set cookie `XSRF-TOKEN` + Laravel session cookie.
 * Idempotent — gọi nhiều lần an toàn.
 */
export async function ensureAdminCsrf(): Promise<void> {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}
