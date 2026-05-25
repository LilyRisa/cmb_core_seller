import { useEffect, useRef } from 'react';
import { App } from 'antd';
import { useUnreadConversations } from './messaging';
import { useCurrentTenantId } from './tenant';
import type { Conversation } from './messaging';

/**
 * Thông báo TRONG APP khi có tin nhắn mới — mount MỘT lần ở AppLayout (toàn cục, mọi trang).
 *
 * Yêu cầu:
 *  - Lần đầu mở app: báo MỘT lần TỔNG số tin nhắn mới (chưa đọc / mới kể từ lần xem trước).
 *  - Sau đó: chỉ báo khi có tin nhắn inbound MỚI thật, ở bất kỳ trang nào.
 *  - KHÔNG bắn lại khi điều hướng / đổi tab / cuộn danh sách (nguồn poll cố định, không lọc;
 *    mốc "đã xem" lưu module-level + localStorage nên sống qua remount & reload).
 *  - Tab ẩn ⇒ không bắn toast (Web Push digest lo).
 */
function displayName(c: Conversation): string {
    if (c.thread_type === 'comment') {
        const p = (c.comment?.participants ?? []).filter((n) => n && n.trim() !== '');
        if (p.length === 1) return p[0];
        if (p.length === 2) return `${p[0]}, ${p[1]}`;
        if (p.length >= 3) return `${p[0]}, ${p[1]} +${p.length - 2} người`;
    }
    return c.buyer_name ?? c.buyer_external_id ?? 'Khách';
}

// Mốc "đã xem" (max last_inbound_at) theo tenant — sống qua điều hướng (module-level) + reload (localStorage).
const sessionInit: Record<number, boolean> = {};
const hwmKey = (tid: number) => `msgNotifyHwm:${tid}`;
function loadHwm(tid: number): string {
    try { return localStorage.getItem(hwmKey(tid)) ?? ''; } catch { return ''; }
}
function saveHwm(tid: number, v: string): void {
    try { localStorage.setItem(hwmKey(tid), v); } catch { /* ignore (private mode / quota) */ }
}

export function useGlobalMessageNotifications(enabled: boolean): void {
    const { notification } = App.useApp();
    const tenantId = useCurrentTenantId();
    const feed = useUnreadConversations(enabled);
    const audioRef = useRef<HTMLAudioElement | null>(null);
    const hwmRef = useRef<string>('');

    useEffect(() => {
        if (audioRef.current === null && typeof Audio !== 'undefined') {
            const a = new Audio('/noti.wav');
            a.volume = 0.6;
            audioRef.current = a;
        }
    }, []);

    const items = feed.data?.items;
    const total = feed.data?.total ?? 0;

    useEffect(() => {
        if (tenantId == null || items === undefined) return;
        const visible = typeof document !== 'undefined' && document.visibilityState === 'visible';
        const maxInbound = items.reduce((m, c) => (c.last_inbound_at && c.last_inbound_at > m ? c.last_inbound_at : m), '');
        const ping = () => audioRef.current?.play().catch(() => { /* autoplay bị chặn tới khi user tương tác */ });

        // --- Lần đầu trong phiên: báo MỘT lần tổng số tin mới ---
        if (!sessionInit[tenantId]) {
            // Tab đang ẩn lúc mở app ⇒ chờ tới khi hiện mới báo tổng (chưa init, chưa tiến mốc) để không bỏ lỡ.
            if (!visible) return;
            const stored = loadHwm(tenantId);
            // Chưa có mốc (lần đầu tiên) ⇒ tổng = số hội thoại chưa đọc. Có mốc ⇒ chỉ đếm tin mới kể từ lần trước.
            const count = stored === '' ? total : items.filter((c) => c.last_inbound_at && c.last_inbound_at > stored).length;
            if (visible && count > 0) {
                notification.open({
                    key: 'msg-total',
                    message: 'Tin nhắn mới',
                    description: `Bạn có ${count} tin nhắn mới`,
                    placement: 'topRight',
                    duration: 5,
                });
                ping();
            }
            const newHwm = maxInbound > stored ? maxInbound : stored;
            hwmRef.current = newHwm;
            saveHwm(tenantId, newHwm);
            sessionInit[tenantId] = true;
            return;
        }

        // --- Các lần sau: chỉ báo hội thoại có inbound mới hơn mốc ---
        const hwm = hwmRef.current || loadHwm(tenantId);
        const fresh = items.filter((c) => c.last_inbound_at && c.last_inbound_at > hwm);
        const newHwm = maxInbound > hwm ? maxInbound : hwm;
        hwmRef.current = newHwm;
        saveHwm(tenantId, newHwm); // luôn tiến mốc (kể cả tab ẩn) để không bắn dồn khi quay lại

        if (fresh.length === 0 || !visible) return;
        notification.open({
            key: 'new-message',
            message: 'Có tin nhắn mới',
            description: fresh.length === 1
                ? `Tin nhắn mới từ ${displayName(fresh[0])}`
                : `${fresh.length} hội thoại có tin nhắn mới`,
            placement: 'topRight',
            duration: 4,
        });
        ping();
    }, [items, total, tenantId, notification]);
}
