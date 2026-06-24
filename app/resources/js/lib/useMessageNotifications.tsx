import { useEffect, useRef } from 'react';
import { App, Avatar } from 'antd';
import { MessageFilled } from '@ant-design/icons';
import { useUnreadConversations } from './messaging';
import { useCurrentTenantId } from './tenant';
import type { Conversation } from './messaging';

/**
 * Thông báo TRONG APP khi có tin nhắn mới — mount MỘT lần ở vỏ (AppLayout / DesktopShell).
 * Toast kiểu macOS: avatar người gửi + tên + preview, bấm để mở đúng hội thoại.
 *
 * Yêu cầu:
 *  - Lần đầu mở app: báo MỘT lần TỔNG số tin nhắn mới (chưa đọc / mới kể từ lần xem trước).
 *  - Sau đó: chỉ báo khi có tin nhắn inbound MỚI thật, ở bất kỳ trang nào.
 *  - KHÔNG bắn lại khi điều hướng / đổi tab / cuộn (nguồn poll cố định; mốc "đã xem" sống
 *    qua remount & reload).
 *  - Tab ẩn ⇒ không bắn toast (Web Push digest lo).
 *
 * `onOpen(conversationId?)`: vỏ truyền cách mở hội thoại (v1 navigate, v2 openApp tab).
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

export function useGlobalMessageNotifications(enabled: boolean, onOpen?: (conversationId?: number) => void): void {
    const { notification } = App.useApp();
    const tenantId = useCurrentTenantId();
    const feed = useUnreadConversations(enabled);
    const audioRef = useRef<HTMLAudioElement | null>(null);
    const hwmRef = useRef<string>('');
    const onOpenRef = useRef(onOpen);
    onOpenRef.current = onOpen;

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

        // Toast 1 hội thoại — avatar + tên + preview, bấm để mở.
        const showOne = (c: Conversation) => {
            const key = `msg-conv-${c.id}`;
            notification.open({
                key,
                className: 'macos-noti',
                message: displayName(c),
                description: c.last_message_preview ?? 'Tin nhắn mới',
                icon: <Avatar size={40} src={c.buyer_avatar_url ?? undefined} style={{ background: '#2563EB' }}>{displayName(c).charAt(0).toUpperCase()}</Avatar>,
                placement: 'topRight',
                duration: 5,
                onClick: () => { onOpenRef.current?.(c.id); notification.destroy(key); },
            });
        };
        // Toast tổng hợp — bấm để mở hộp thư.
        const showSummary = (key: string, desc: string) => {
            notification.open({
                key,
                className: 'macos-noti',
                message: 'Tin nhắn mới',
                description: desc,
                icon: <Avatar size={40} style={{ background: '#2563EB' }} icon={<MessageFilled />} />,
                placement: 'topRight',
                duration: 5,
                onClick: () => { onOpenRef.current?.(); notification.destroy(key); },
            });
        };

        // --- Lần đầu trong phiên: báo MỘT lần tổng số tin mới ---
        if (!sessionInit[tenantId]) {
            if (!visible) return; // tab ẩn lúc mở app ⇒ chờ hiện mới báo (chưa init, chưa tiến mốc)
            const stored = loadHwm(tenantId);
            const count = stored === '' ? total : items.filter((c) => c.last_inbound_at && c.last_inbound_at > stored).length;
            if (visible && count > 0) {
                showSummary('msg-total', `Bạn có ${count} tin nhắn mới`);
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
        if (fresh.length === 1) showOne(fresh[0]);
        else showSummary('msg-fresh', `${fresh.length} hội thoại có tin nhắn mới`);
        ping();
    }, [items, total, tenantId, notification]);
}
