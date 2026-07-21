/**
 * Bắt UTM/fbclid first-touch lúc khách ghé trang public lần đầu (SPEC
 * 2026-07-22-facebook-pixel-capi-growth-attribution-design.md §3) — cùng pattern với
 * `lib/extRedirect.ts`. Chỉ ghi localStorage nếu CHƯA có sẵn (first-touch: giữ nguồn
 * quảng cáo đầu tiên, không bị ghi đè bởi lượt ghé thăm sau).
 */
const STORAGE_KEY = 'cmb_acquisition_v1';

export interface AcquisitionData {
    utm_source?: string;
    utm_medium?: string;
    utm_campaign?: string;
    utm_content?: string;
    utm_term?: string;
    fbclid?: string;
    landing_page?: string;
    referrer?: string;
}

const UTM_FIELDS: Array<keyof AcquisitionData> = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid'];

export function captureAcquisition(search: string, pathname: string): void {
    if (localStorage.getItem(STORAGE_KEY)) {
        return;
    }
    const params = new URLSearchParams(search);
    const data: AcquisitionData = {};
    for (const field of UTM_FIELDS) {
        const v = params.get(field);
        if (v) data[field] = v;
    }
    if (Object.keys(data).length === 0) {
        return;
    }
    // Tuyệt đối hoá URL — Meta CAPI từ chối `event_source_url` dạng pathname tương đối
    // (SPEC 2026-07-22-facebook-pixel-capi-growth-attribution-design.md §3).
    data.landing_page = window.location.origin + pathname;
    if (document.referrer) data.referrer = document.referrer;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
}

export function readAcquisition(): AcquisitionData {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return {};
    try {
        return JSON.parse(raw) as AcquisitionData;
    } catch {
        return {};
    }
}

export function clearAcquisition(): void {
    localStorage.removeItem(STORAGE_KEY);
}

function readCookie(name: string): string | undefined {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : undefined;
}

/** `_fbp`/`_fbc` do chính script Meta Pixel tự set (base code trong app.blade.php). */
export function readFacebookCookies(): { fbp?: string; fbc?: string } {
    return { fbp: readCookie('_fbp'), fbc: readCookie('_fbc') };
}
