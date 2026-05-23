import { useEffect, useRef } from 'react';
import { App } from 'antd';
import type { Conversation } from './messaging';

/**
 * Thông báo TRONG APP khi có tin nhắn mới (dựa trên polling danh sách hội thoại).
 *
 * - Lập baseline ở lần poll đầu (KHÔNG bắn cho dữ liệu cũ đang có sẵn).
 * - Mỗi lần `conversations` đổi: phát hiện hội thoại có `last_inbound_at` MỚI hơn.
 * - Khi tab đang mở (visible): hiện toast "Có tin nhắn mới" + phát `/noti.wav`.
 * - Khi tab ẩn/đóng: để Web Push (digest 30') lo — hook này không bắn.
 *
 * Tên hiển thị: comment dùng người tham gia, còn lại dùng buyer_name.
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

export function useMessageNotifications(conversations: Conversation[]): void {
    const { notification } = App.useApp();
    const baseline = useRef<Map<number, string> | null>(null);
    const audioRef = useRef<HTMLAudioElement | null>(null);

    useEffect(() => {
        if (audioRef.current === null && typeof Audio !== 'undefined') {
            const a = new Audio('/noti.wav');
            a.volume = 0.6;
            audioRef.current = a;
        }
    }, []);

    useEffect(() => {
        // Lần đầu: ghi baseline, không thông báo.
        if (baseline.current === null) {
            baseline.current = new Map(conversations.map((c) => [c.id, c.last_inbound_at ?? '']));
            return;
        }

        const prev = baseline.current;
        const next = new Map<number, string>();
        const fresh: Conversation[] = [];

        for (const c of conversations) {
            const lastInbound = c.last_inbound_at ?? '';
            next.set(c.id, lastInbound);
            if (lastInbound === '') continue;
            const before = prev.get(c.id);
            // ISO8601 so sánh chuỗi = so sánh thời gian. Mới hơn baseline ⇒ tin inbound mới.
            if (before === undefined || lastInbound > before) {
                fresh.push(c);
            }
        }

        baseline.current = next;
        if (fresh.length === 0) return;

        const visible = typeof document !== 'undefined' && document.visibilityState === 'visible';
        if (!visible) return; // tab ẩn/đóng → Web Push digest lo

        notification.open({
            key: 'new-message',
            message: 'Có tin nhắn mới',
            description: fresh.length === 1
                ? `Tin nhắn mới từ ${displayName(fresh[0])}`
                : `${fresh.length} hội thoại có tin nhắn mới`,
            placement: 'topRight',
            duration: 4,
        });
        audioRef.current?.play().catch(() => { /* autoplay bị chặn tới khi user tương tác — bỏ qua */ });
    }, [conversations, notification]);
}
