import { Tag } from 'antd';
import { ShoppingOutlined } from '@ant-design/icons';
import { CHANNEL_META } from '@/lib/format';
import { CHANNEL_ICON } from '@/components/ChannelLogo';

// Logo bản TRẮNG cho thẻ nền-màu: logo gốc cùng tông với nền sẽ bị chìm (vd Shopee cam trên nền cam ⇒ mất
// logo). Ưu tiên bản trắng nếu có; còn lại dùng filter brightness(0) invert(1) để chuyển logo 1 màu → trắng.
const WHITE_BADGE_ICON: Record<string, string> = { shopee: '/images/shopee-icon-white.svg' };
const INVERT_ON_BADGE = new Set(['tiktok']); // logo đơn sắc (đen) ⇒ filter sang trắng

export function ChannelBadge({ provider }: { provider: string }) {
    const meta = CHANNEL_META[provider] ?? { name: provider, color: '#8c8c8c' };
    const icon = WHITE_BADGE_ICON[provider] ?? CHANNEL_ICON[provider];
    return (
        <Tag style={{ color: '#fff', background: meta.color, border: 'none', borderRadius: 4, fontWeight: 500, display: 'inline-flex', alignItems: 'center', gap: 4, paddingInline: 6, lineHeight: '20px' }}>
            {icon ? (
                <img src={icon} alt={meta.name} style={{ width: 14, height: 14, objectFit: 'contain', filter: INVERT_ON_BADGE.has(provider) ? 'brightness(0) invert(1)' : undefined }} />
            ) : <ShoppingOutlined style={{ fontSize: 12 }} />}
            <span>{meta.name}</span>
        </Tag>
    );
}
