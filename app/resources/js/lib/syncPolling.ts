import { useEffect, useRef, useState } from 'react';

/**
 * "Kéo xong là render lại — không cần reload trang." Helper cho mutation kích hoạt job
 * đồng bộ chạy nền (FetchChannelListings, SyncOrdersForShop, push-stock…): sau khi
 * mutation thành công, gọi `start()` để poll `tick` VÀI nhịp với khoảng cách TĂNG DẦN
 * (backoff) rồi DỪNG HẲN. Dùng `isPolling` để hiện spinner / disable nút.
 *
 * Vì sao backoff thay vì `setInterval` 2s: poll cố định 2s suốt 60–90s nã ~30–45 request
 * vào /channel-listings cho mỗi lần bấm (spinner nhấp nháy → người dùng tưởng "gọi vô hạn").
 * Backoff chỉ bắn ~6 request rồi tự dừng; bấm lại sẽ HUỶ chuỗi cũ (không chồng nhiều timer).
 *
 * Mặc định trải 6 nhịp [2,3,5,8,13,21] (Fibonacci) chuẩn hoá theo `durationMs` (mặc định 30s).
 */
export function useSyncPolling(tick: () => void, opts?: { durationMs?: number }) {
    const duration = opts?.durationMs ?? 30000;
    const tickRef = useRef(tick);
    tickRef.current = tick;
    const timers = useRef<ReturnType<typeof setTimeout>[]>([]);
    const [polling, setPolling] = useState(false);

    const stop = () => {
        timers.current.forEach(clearTimeout);
        timers.current = [];
        setPolling(false);
    };

    // Dọn timer khi unmount để không refetch trên component đã gỡ.
    useEffect(() => () => { timers.current.forEach(clearTimeout); timers.current = []; }, []);

    const start = (overrideMs?: number) => {
        stop(); // re-entrancy: bấm lại huỷ chuỗi đang chạy, tránh chồng nhiều chuỗi poll.
        setPolling(true);
        const total = overrideMs ?? duration;
        const steps = [2, 3, 5, 8, 13, 21];
        const units = steps.reduce((a, b) => a + b, 0);
        let elapsed = 0;
        steps.forEach((u, idx) => {
            elapsed += (u / units) * total;
            const last = idx === steps.length - 1;
            timers.current.push(setTimeout(() => {
                tickRef.current();
                if (last) setPolling(false);
            }, elapsed));
        });
    };

    return { isPolling: polling, start };
}
