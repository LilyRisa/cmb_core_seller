import { useEffect, useRef } from 'react';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

/**
 * CAPTCHA Cloudflare Turnstile (SPEC 2026-06-10) — register/login/forgot. site_key lấy
 * từ /auth/captcha-config; widget render khi enabled. Khi tắt: ẩn, không gửi token.
 */
export interface CaptchaConfig {
    enabled: boolean;
    provider: string;
    site_key: string;
}

export function useCaptchaConfig() {
    return useQuery({
        queryKey: ['auth', 'captcha-config'],
        staleTime: Infinity,
        queryFn: async () => (await api.get<{ data: CaptchaConfig }>('/auth/captcha-config')).data.data,
    });
}

type TurnstileRenderOpts = {
    sitekey: string;
    callback: (token: string) => void;
    'expired-callback'?: () => void;
    'error-callback'?: () => void;
};
interface TurnstileApi {
    render: (el: HTMLElement, opts: TurnstileRenderOpts) => string;
    remove: (id: string) => void;
}
const turnstile = (): TurnstileApi | undefined => (window as unknown as { turnstile?: TurnstileApi }).turnstile;

let scriptPromise: Promise<void> | null = null;
function loadTurnstile(): Promise<void> {
    if (scriptPromise) return scriptPromise;
    scriptPromise = new Promise<void>((resolve) => {
        if (turnstile()) return resolve();
        const s = document.createElement('script');
        s.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        s.async = true;
        s.defer = true;
        s.onload = () => resolve();
        document.head.appendChild(s);
    });
    return scriptPromise;
}

/** Widget Turnstile. Gọi onVerify(token) khi giải xong; onVerify('') khi hết hạn/lỗi. */
export function Captcha({ siteKey, onVerify }: { siteKey: string; onVerify: (token: string) => void }) {
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        let widgetId: string | undefined;
        let cancelled = false;
        void loadTurnstile().then(() => {
            const t = turnstile();
            if (! t || cancelled || ref.current === null) return;
            widgetId = t.render(ref.current, {
                sitekey: siteKey,
                callback: (token) => onVerify(token),
                'expired-callback': () => onVerify(''),
                'error-callback': () => onVerify(''),
            });
        });

        return () => {
            cancelled = true;
            const t = turnstile();
            if (t && widgetId !== undefined) {
                try { t.remove(widgetId); } catch { /* noop */ }
            }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [siteKey]);

    return <div ref={ref} style={{ marginBottom: 16 }} />;
}
