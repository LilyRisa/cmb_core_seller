import { Segmented } from 'antd';
import { useLocation, useNavigate, useSearchParams } from 'react-router-dom';

/** Thanh điều hướng giữa các trang quản lý Messaging (SPEC-0024 §6.2). */
export function MessagingNav() {
    const nav = useNavigate();
    const { pathname } = useLocation();
    // Giữ ngữ cảnh nền tảng (?platform=zalo_oa) khi chuyển tab — nếu không sẽ rơi về Facebook.
    const [params] = useSearchParams();
    const platform = params.get('platform');
    const qs = platform ? `?platform=${platform}` : '';
    const isZalo = platform === 'zalo_oa';
    const options = [
        { label: 'Hộp thư', value: '/messaging' },
        { label: 'Kết nối kênh', value: '/messaging/channels' },
        // Mẫu tin & Tin tiện ích (ZNS) chỉ cho Facebook/sàn — Zalo OA chưa dùng (ZNS ở phase sau).
        ...(isZalo ? [] : [
            { label: 'Mẫu tin', value: '/messaging/templates' },
            { label: 'Tin tiện ích', value: '/messaging/utility-templates' },
        ]),
        { label: 'Tự động trả lời', value: '/messaging/auto-rules' },
        { label: 'Kịch bản tự động', value: '/messaging/flows' },
        { label: 'AI training', value: '/messaging/knowledge' },
        { label: 'Cài đặt AI', value: '/settings/messaging' },
    ];
    const value = options.find((o) => o.value === pathname)?.value ?? '/messaging';

    return (
        <Segmented<string>
            value={value}
            // Cài đặt AI là tenant-global (không có platform) → không gắn query.
            onChange={(v) => nav(v === '/settings/messaging' ? v : v + qs)}
            options={options}
            style={{ marginBottom: 16 }}
        />
    );
}
