import { Progress, Space, Typography } from 'antd';
import { CHANNEL_META } from '@/lib/format';
import { ChannelLogo } from '@/components/ChannelLogo';
import { ChannelBadge } from '@/components/ChannelBadge';

/**
 * Hiển thị khách đã mua qua nguồn nào — đơn thủ công vs đơn sàn (TikTok/Shopee/Lazada) + tỉ trọng.
 * Nguồn dữ liệu: `customer.lifetime_stats.orders_by_source` (đếm đơn theo `orders.source`, R5/SPEC 0002).
 * Rỗng ⇒ khách cũ chưa backfill hoặc chưa có đơn gắn nguồn.
 */
function sortedEntries(bySource?: Record<string, number> | null): Array<[string, number]> {
    return Object.entries(bySource ?? {})
        .map(([k, v]) => [k, Number(v)] as [string, number])
        .filter(([, n]) => n > 0)
        .sort((a, b) => b[1] - a[1]);
}

/** Dạng gọn cho bảng danh sách: logo nền tảng + số đơn, theo thứ tự mua nhiều → ít. */
export function CustomerSourceBadges({ ordersBySource }: { ordersBySource?: Record<string, number> | null }) {
    const entries = sortedEntries(ordersBySource);
    if (entries.length === 0) return <Typography.Text type="secondary">—</Typography.Text>;
    return (
        <Space size={8} wrap>
            {entries.map(([src, n]) => (
                <span
                    key={src}
                    title={`${CHANNEL_META[src]?.name ?? src}: ${n} đơn`}
                    style={{ display: 'inline-flex', alignItems: 'center', gap: 4 }}
                >
                    <ChannelLogo provider={src} size={16} />
                    <Typography.Text style={{ fontSize: 12 }}>{n}</Typography.Text>
                </span>
            ))}
        </Space>
    );
}

/** Dạng đầy đủ cho trang chi tiết: badge nền tảng + thanh tỉ trọng + số đơn/phần trăm. */
export function CustomerSourceBreakdown({ ordersBySource }: { ordersBySource?: Record<string, number> | null }) {
    const entries = sortedEntries(ordersBySource);
    const total = entries.reduce((sum, [, n]) => sum + n, 0);
    if (total === 0) {
        return <Typography.Text type="secondary" style={{ fontSize: 12 }}>Chưa có dữ liệu kênh mua hàng.</Typography.Text>;
    }
    return (
        <Space direction="vertical" size={8} style={{ width: '100%' }}>
            {entries.map(([src, n]) => {
                const pct = Math.round((n / total) * 100);
                return (
                    <div key={src} style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                        <span style={{ width: 132, flexShrink: 0 }}><ChannelBadge provider={src} /></span>
                        <Progress
                            percent={pct} size="small" showInfo={false}
                            strokeColor={CHANNEL_META[src]?.color ?? '#8c8c8c'}
                            style={{ flex: 1, marginBottom: 0 }}
                        />
                        <Typography.Text type="secondary" style={{ fontSize: 12, width: 86, textAlign: 'right', flexShrink: 0 }}>
                            {n} đơn · {pct}%
                        </Typography.Text>
                    </div>
                );
            })}
        </Space>
    );
}
