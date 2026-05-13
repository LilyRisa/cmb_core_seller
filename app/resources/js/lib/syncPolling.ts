import { useEffect, useRef, useState } from 'react';

/**
 * "Kéo xong là render lại — không cần reload trang." Helper cho mutation kích hoạt
 * job đồng bộ chạy nền (FetchChannelListings, SyncOrdersForShop, push-stock…): sau
 * khi mutation thành công, gọi `start()` để poll `tick` mỗi `intervalMs` trong
 * `durationMs`, rồi tự dừng. Dùng `isPolling` để hiện spinner / disable nút.
 *
 * Mặc định: poll 2s/lần trong 30s. Tăng `durationMs` cho job nặng (vd resync orders).
 */
export function useSyncPolling(tick: () => void, opts?: { intervalMs?: number; durationMs?: number }) {
    const interval = opts?.intervalMs ?? 2000;
    const duration = opts?.durationMs ?? 30000;
    const tickRef = useRef(tick);
    tickRef.current = tick;
    const [until, setUntil] = useState(0);
    const isPolling = until > Date.now();

    useEffect(() => {
        if (until <= Date.now()) return;
        const i = setInterval(() => tickRef.current(), interval);
        const t = setTimeout(() => setUntil(0), Math.max(0, until - Date.now()));
        return () => { clearInterval(i); clearTimeout(t); };
    }, [until, interval]);

    return { isPolling, start: (overrideMs?: number) => setUntil(Date.now() + (overrideMs ?? duration)) };
}
