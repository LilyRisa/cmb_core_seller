import { Space, Typography } from 'antd';
import { ChannelLogo } from '@/components/ChannelLogo';

const PLATFORMS = [
    { p: 'shopee', name: 'Shopee' },
    { p: 'tiktok', name: 'TikTok Shop' },
    { p: 'lazada', name: 'Lazada' },
];

/** Số gian hàng kết nối được mỗi nền tảng, kèm logo nhỏ Shopee/TikTok/Lazada. SPEC 2026-06-26. */
export function PlatformQuota({ perPlatform, size = 18 }: { perPlatform?: number; size?: number }) {
    const text = perPlatform == null ? '—' : perPlatform < 0 ? 'Không giới hạn' : `${perPlatform} gian hàng`;
    return (
        <Space direction="vertical" size={4} style={{ display: 'flex' }}>
            {PLATFORMS.map(({ p, name }) => (
                <Space key={p} size={8}>
                    <ChannelLogo provider={p} size={size} />
                    <Typography.Text>{name}: <b>{text}</b></Typography.Text>
                </Space>
            ))}
        </Space>
    );
}
