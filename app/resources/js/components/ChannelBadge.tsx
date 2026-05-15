import { Tag } from 'antd';
import { ShoppingOutlined } from '@ant-design/icons';
import { CHANNEL_META } from '@/lib/format';

/** Bảng icon nhận diện sàn (file user đã đặt vào /public/images/). Khi thêm sàn mới ⇒ thêm 1 entry ở đây. */
const CHANNEL_ICON: Record<string, string> = {
    tiktok: '/images/tiktok-icon.svg',
    shopee: '/images/shopee-icon.svg',
    lazada: '/images/laz.png',
};

export function ChannelBadge({ provider }: { provider: string }) {
    const meta = CHANNEL_META[provider] ?? { name: provider, color: '#8c8c8c' };
    const icon = CHANNEL_ICON[provider];
    return (
        <Tag style={{ color: '#fff', background: meta.color, border: 'none', borderRadius: 4, fontWeight: 500, display: 'inline-flex', alignItems: 'center', gap: 4, paddingInline: 6, lineHeight: '20px' }}>
            {icon ? (
                <img src={icon} alt={meta.name} style={{ width: 14, height: 14, objectFit: 'contain', filter: provider === 'tiktok' ? 'brightness(0) invert(1)' : undefined }} />
            ) : <ShoppingOutlined style={{ fontSize: 12 }} />}
            <span>{meta.name}</span>
        </Tag>
    );
}
