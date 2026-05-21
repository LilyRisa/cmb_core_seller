import React from 'react';
import { FacebookFilled, ShoppingOutlined } from '@ant-design/icons';
import { CHANNEL_META } from '@/lib/format';

/**
 * Bảng icon nhận diện sàn dạng ảnh (file đặt ở /public/images/).
 * Khi thêm sàn mới có file ảnh ⇒ thêm 1 entry ở đây.
 */
export const CHANNEL_ICON: Record<string, string> = {
    tiktok: '/images/tiktok-icon.svg',
    shopee: '/images/shopee-icon.svg',
    lazada: '/images/laz.png',
};

/**
 * Bảng icon nhận diện sàn dạng React element (dùng cho provider không có file ảnh riêng).
 * Ưu tiên sau CHANNEL_ICON trong ChannelLogo — không dùng trực tiếp ở các consumer khác.
 */
const CHANNEL_ICON_ELEMENT: Record<string, React.ReactElement> = {
    facebook_page: <FacebookFilled style={{ color: '#1877F2' }} />,
    facebook: <FacebookFilled style={{ color: '#1877F2' }} />,
};

/**
 * Logo gian hàng (chỉ ảnh, không tag/text) — dùng đồng nhất cho mọi nơi đề cập đến gian hàng:
 * bộ lọc, dropdown, bảng, modal. Khi không có icon ⇒ fallback ShoppingOutlined.
 */
export function ChannelLogo({ provider, size = 16, rounded = true }: { provider: string; size?: number; rounded?: boolean }) {
    const imgSrc = CHANNEL_ICON[provider];
    const iconEl = CHANNEL_ICON_ELEMENT[provider];
    const meta = CHANNEL_META[provider] ?? { name: provider, color: '#8c8c8c' };
    if (!imgSrc && !iconEl) {
        return (
            <span style={{
                width: size, height: size, borderRadius: rounded ? size / 4 : 0, background: meta.color, color: '#fff',
                display: 'inline-flex', alignItems: 'center', justifyContent: 'center', fontSize: Math.max(8, size * 0.55), flexShrink: 0,
            }} aria-label={meta.name}>
                <ShoppingOutlined />
            </span>
        );
    }
    return (
        <span style={{
            width: size, height: size, borderRadius: rounded ? size / 4 : 0,
            background: '#fff',
            display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
            padding: size <= 18 ? 1 : 2, flexShrink: 0,
            boxShadow: 'inset 0 0 0 1px rgba(11,20,55,0.06)',
            overflow: 'hidden',
            fontSize: Math.max(8, size * 0.65),
        }} aria-label={meta.name}>
            {imgSrc
                ? <img src={imgSrc} alt={meta.name} style={{ width: '100%', height: '100%', objectFit: 'contain', display: 'block' }} />
                : iconEl}
        </span>
    );
}

/**
 * Combo: logo gian hàng + tên hiển thị. Dùng cho mọi vị trí "gian hàng" trong UI để tạo
 * nhận diện thị giác nhất quán (giống Báo cáo & Liên kết gian hàng).
 */
export function ChannelLabel({ provider, name, logoSize = 16, strong = false }: { provider: string; name?: string | null; logoSize?: number; strong?: boolean }) {
    const meta = CHANNEL_META[provider] ?? { name: provider, color: '#8c8c8c' };
    return (
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, minWidth: 0, lineHeight: 1.2 }}>
            <ChannelLogo provider={provider} size={logoSize} />
            <span style={{ fontWeight: strong ? 600 : 500, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{name ?? meta.name}</span>
        </span>
    );
}
