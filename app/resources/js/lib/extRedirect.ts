/**
 * Đăng nhập Chrome Extension qua `/extension/connect` (EXTENSION_OAUTH_LOGIN_CONTRACT).
 *
 * Backend đưa user CHƯA đăng nhập / chưa verify email về `/login?redirect=/extension/connect?...`.
 * Ta lưu lại `redirect` (chỉ chấp nhận path nội bộ `/extension/connect`) rồi CHỈ chuyển hướng
 * về đó khi user đã đăng nhập **và đã verify email** (tiêu thụ ở RequireAuth) — giữ đúng luật
 * "đăng ký xong phải verify mail rồi mới chuyển hướng".
 */
const KEY = 'ext_login_redirect';

/** Chỉ cho phép path nội bộ trỏ về luồng extension — chống open-redirect. */
export function isSafeExtRedirect(v: string | null | undefined): v is string {
    return typeof v === 'string' && v.startsWith('/extension/connect');
}

/** Đọc `?redirect=` từ URL hiện tại; nếu hợp lệ thì lưu lại để dùng sau khi verify. */
export function captureExtRedirect(search: string): void {
    const r = new URLSearchParams(search).get('redirect');
    if (isSafeExtRedirect(r)) {
        localStorage.setItem(KEY, r);
    }
}

/** Lấy & xoá redirect đã lưu (null nếu không có / không hợp lệ). */
export function takeExtRedirect(): string | null {
    const v = localStorage.getItem(KEY);
    if (v != null) {
        localStorage.removeItem(KEY);
    }
    return isSafeExtRedirect(v) ? v : null;
}
