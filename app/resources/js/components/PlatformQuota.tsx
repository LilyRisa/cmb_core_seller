import { Space, Typography } from 'antd';
import { ChannelLogo } from '@/components/ChannelLogo';

const PLATFORMS = [
    { p: 'shopee', name: 'Shopee' },
    { p: 'tiktok', name: 'TikTok Shop' },
    { p: 'lazada', name: 'Lazada' },
];

/**
 * Số gian hàng kết nối được mỗi nền tảng, kèm logo nhỏ Shopee/TikTok/Lazada.
 * `facebook=true` ⇒ thêm dòng Facebook Page (gói Cơ bản & Chuyên nghiệp). SPEC 2026-06-26.
 */
export function PlatformQuota({ perPlatform, facebook = false, size = 18 }: { perPlatform?: number; facebook?: boolean; size?: number }) {
    const text = perPlatform == null ? '—' : perPlatform < 0 ? 'Không giới hạn' : `${perPlatform} gian hàng`;
    return (
        <Space direction="vertical" size={4} style={{ display: 'flex' }}>
            {PLATFORMS.map(({ p, name }) => (
                <Space key={p} size={8}>
                    <ChannelLogo provider={p} size={size} />
                    <Typography.Text>{name}: <b>{text}</b></Typography.Text>
                </Space>
            ))}
            {facebook && (
                <Space size={8}>
                    <ChannelLogo provider="facebook_page" size={size} />
                    <Typography.Text>Facebook Page: <b>{text}</b></Typography.Text>
                </Space>
            )}
        </Space>
    );
}
