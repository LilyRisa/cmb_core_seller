import { App } from 'antd';
import { useCallback, useEffect, useRef, useState } from 'react';
import { errorMessage, tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

/** base64url (VAPID public key) → Uint8Array cho PushManager.subscribe. */
function urlBase64ToUint8Array(base64: string): Uint8Array {
    const padding = '='.repeat((4 - (base64.length % 4)) % 4);
    const b64 = (base64 + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(b64);
    const arr = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
    return arr;
}

/**
 * Web Push: đăng ký service worker + subscription, gửi heartbeat khi tab visible.
 * `enable()` xin quyền + subscribe (gọi khi user bấm "Bật thông báo"). Khoá VAPID
 * lấy từ /messaging/push/public-key (super-admin cấu hình ở /admin/settings).
 */
export function usePushNotifications() {
    const { message } = App.useApp();
    const tenantId = useCurrentTenantId();
    const supported = typeof window !== 'undefined'
        && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    const [permission, setPermission] = useState<NotificationPermission>(
        supported ? Notification.permission : 'denied',
    );
    const [enabled, setEnabled] = useState(false);
    const endpointRef = useRef<string | null>(null);
    const api = tenantId != null ? tenantApi(tenantId) : null;

    const enable = useCallback(async () => {
        if (!supported || api == null) {
            message.warning('Trình duyệt này không hỗ trợ thông báo đẩy.');
            return;
        }
        try {
            const perm = await Notification.requestPermission();
            setPermission(perm);
            if (perm !== 'granted') {
                // 'denied' = user đã chặn (phải tự mở lại trong cài đặt trình duyệt); 'default' = bỏ qua hộp xin quyền.
                message.warning(perm === 'denied'
                    ? 'Bạn đã chặn quyền thông báo cho trang này. Mở khoá trong cài đặt trình duyệt rồi thử lại.'
                    : 'Chưa cấp quyền thông báo — hãy chọn "Cho phép" khi trình duyệt hỏi.');
                return;
            }

            const reg = await navigator.serviceWorker.register('/sw.js');
            await navigator.serviceWorker.ready;

            const { data } = await api.get<{ data: { public_key: string } }>('/messaging/push/public-key');
            const key = data.data.public_key;
            if (!key) {
                // VAPID chưa cấu hình ở /admin/settings → không thể subscribe.
                message.error('Thông báo đẩy chưa được cấu hình (VAPID). Vui lòng liên hệ quản trị viên.');
                return;
            }

            let sub = await reg.pushManager.getSubscription();
            if (!sub) {
                sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    // cast: Uint8Array là ArrayBufferView hợp lệ cho BufferSource (lib DOM TS hẹp hơn).
                    applicationServerKey: urlBase64ToUint8Array(key) as BufferSource,
                });
            }
            const json = sub.toJSON();
            endpointRef.current = json.endpoint ?? null;
            await api.post('/messaging/push/subscribe', { endpoint: json.endpoint, keys: json.keys });
            setEnabled(true);
            message.success('Đã bật thông báo trình duyệt cho tin nhắn mới.');
        } catch (e) {
            // Trước đây nuốt lỗi ⇒ bấm chuông "không có gì xảy ra", không ai biết tại sao (0 subscription).
            console.error('[push] enable failed', e);
            message.error(errorMessage(e, 'Không bật được thông báo đẩy. Thử lại sau.'));
        }
    }, [supported, api, message]);

    // Heartbeat khi tab visible ⇒ digest 30' bỏ qua user đang hoạt động.
    useEffect(() => {
        if (!enabled || api == null) return;
        const beat = () => {
            if (document.visibilityState === 'visible' && endpointRef.current) {
                api.post('/messaging/push/heartbeat', { endpoint: endpointRef.current }).catch(() => { /* ignore */ });
            }
        };
        beat();
        const iv = window.setInterval(beat, 60_000);
        document.addEventListener('visibilitychange', beat);
        return () => { window.clearInterval(iv); document.removeEventListener('visibilitychange', beat); };
    }, [enabled, api]);

    // Khôi phục trạng thái nếu trình duyệt đã có subscription từ trước.
    useEffect(() => {
        if (!supported) return;
        navigator.serviceWorker.getRegistration().then(async (reg) => {
            if (!reg) return;
            const sub = await reg.pushManager.getSubscription();
            if (sub) { endpointRef.current = sub.endpoint; setEnabled(true); }
        }).catch(() => { /* ignore */ });
    }, [supported]);

    return { supported, permission, enabled, enable };
}
