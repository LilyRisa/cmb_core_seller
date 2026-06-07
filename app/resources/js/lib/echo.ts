import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axios from 'axios';
import { ensureCsrf } from './api';

/**
 * Laravel Echo (Reverb) cho realtime inbox messaging (ADR-0021).
 *
 * Realtime BẬT khi build có `VITE_REVERB_APP_KEY`; không có ⇒ `getEcho()` trả null
 * và FE rơi về polling (refetchInterval) — môi trường dev/chưa deploy Reverb không vỡ.
 *
 * Private channel auth `/broadcasting/auth` chạy ở web group: gửi cookie Sanctum SPA
 * + XSRF (tự `ensureCsrf`). KHÔNG đi qua baseURL `/api/v1` nên dùng axios gốc.
 */
type ReverbEcho = Echo<'reverb'>;

let instance: ReverbEcho | null = null;

/** Realtime khả dụng khi build có cấu hình Reverb (VITE_REVERB_APP_KEY). */
export function realtimeEnabled(): boolean {
    return Boolean(import.meta.env.VITE_REVERB_APP_KEY);
}

/** Echo singleton (lazy). Null khi realtime tắt ⇒ caller dùng polling fallback. */
export function getEcho(): ReverbEcho | null {
    if (!realtimeEnabled()) return null;
    if (instance) return instance;

    (window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher;

    instance = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        authorizer: (channel) => ({
            authorize: (socketId, callback) => {
                ensureCsrf()
                    .then(() => axios.post(
                        '/broadcasting/auth',
                        { socket_id: socketId, channel_name: channel.name },
                        { withCredentials: true, withXSRFToken: true, headers: { Accept: 'application/json' } },
                    ))
                    .then((res) => callback(null, res.data))
                    .catch((err) => callback(err, null));
            },
        }),
    });

    return instance;
}
