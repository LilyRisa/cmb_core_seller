import { ShoppingOutlined } from '@ant-design/icons';
import { CHANNEL_META } from '@/lib/format';

/**
 * Bảng icon nhận diện sàn (file đặt ở /public/images/).
 * Khi thêm sàn mới ⇒ thêm 1 entry ở đây.
 */
export const CHANNEL_ICON: Record<string, string> = {
    tiktok: '/images/tiktok-icon.svg',
    shopee: '/images/shopee-icon.svg',
    lazada: '/images/laz.png',
};

/**
 * Logo gian hàng (chỉ ảnh, không tag/text) — dùng đồng nhất cho mọi nơi đề cập đến gian hàng:
 * bộ lọc, dropdown, bảng, modal. Khi không có icon ⇒ fallback ShoppingOutlined.
 */
export function ChannelLogo({ provider, size = 16, rounded = true }: { provider: string; size?: number; rounded?: boolean }) {
    const icon = CHANNEL_ICON[provider];
    const meta = CHANNEL_META[provider] ?? { name: provider, color: '#8c8c8c' };
    if (!icon) {
        return (
            <span style={{
                width: size, height: size, borderRadius: rounded ? size / 4 : 0, background: meta.color, color: '#fff',
                display: 'inline-flex', alignItems: 'center', justifyContent: 'center', fontSize: Math.max(8, size * 0.55), flexShrink: 0,
            }} aria-label={meta.name}>
                <ShoppingOutlined />
            </span>
        );
    }
    const isTikTokDark = provider === 'tiktok';
    return (
        <span style={{
            width: size, height: size, borderRadius: rounded ? size / 4 : 0,
            background: isTikTokDark ? '#000' : '#fff',
            display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
            padding: size <= 18 ? 1 : 2, flexShrink: 0,
            boxShadow: 'inset 0 0 0 1px rgba(11,20,55,0.06)',
            overflow: 'hidden',
        }} aria-label={meta.name}>
            <img src={icon} alt={meta.name} style={{ width: '100%', height: '100%', objectFit: 'contain', display: 'block' }} />
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
