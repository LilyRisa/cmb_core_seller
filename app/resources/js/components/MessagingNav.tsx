import { Segmented } from 'antd';
import { useLocation, useNavigate } from 'react-router-dom';

/** Thanh điều hướng giữa các trang quản lý Messaging (SPEC-0024 §6.2). */
export function MessagingNav() {
    const nav = useNavigate();
    const { pathname } = useLocation();
    const options = [
        { label: 'Hộp thư', value: '/messaging' },
        { label: 'Kết nối kênh', value: '/messaging/channels' },
        { label: 'Mẫu tin', value: '/messaging/templates' },
        { label: 'Tin tiện ích', value: '/messaging/utility-templates' },
        { label: 'Tự động trả lời', value: '/messaging/auto-rules' },
        { label: 'Kịch bản tự động', value: '/messaging/flows' },
        { label: 'AI training', value: '/messaging/knowledge' },
        { label: 'Cài đặt AI', value: '/settings/messaging' },
    ];
    const value = options.find((o) => o.value === pathname)?.value ?? '/messaging';

    return (
        <Segmented<string>
            value={value}
            onChange={(v) => nav(v)}
            options={options}
            style={{ marginBottom: 16 }}
        />
    );
}
