import { useMemo } from 'react';
import { Tag, Tree, Typography } from 'antd';
import { BulbOutlined } from '@ant-design/icons';
import type { DataNode } from 'antd/es/tree';
import type { AdForecast } from '@/lib/marketing';

const { Text } = Typography;

function money(v: number | null | undefined, currency: string | null): string {
    if (v == null) return '—';
    return v.toLocaleString('vi-VN') + (currency ? ' ' + currency : '');
}

interface Props {
    forecast: AdForecast;
    currency: string | null;
}

/** Tổ chức báo cáo Ai (dự báo + chiến lược + đánh giá nội dung) theo cây phân cấp. */
export function ForecastTree({ forecast, currency }: Props) {
    const treeData = useMemo<DataNode[]>(() => {
        const p = forecast.payload;
        const f = p.forecast?.next_7d ?? {};
        const strategy = p.strategy ?? [];
        const review = p.creative_review ?? [];

        // Group strategy by campaign (null → "Chung").
        const byCampaign = new Map<string, typeof strategy>();
        for (const s of strategy) {
            const k = s.campaign ?? 'Chung';
            if (!byCampaign.has(k)) byCampaign.set(k, []);
            byCampaign.get(k)!.push(s);
        }

        const nodes: DataNode[] = [
            {
                title: <Text strong>Dự báo 7 ngày tới</Text>,
                key: 'forecast',
                children: [
                    { title: `Đơn dự kiến: ${f.orders ?? '—'}`, key: 'f-orders' },
                    { title: `Chi tiêu dự kiến: ${money(f.spend ?? null, currency)}`, key: 'f-spend' },
                    { title: `Hội thoại dự kiến: ${f.conversations ?? '—'}`, key: 'f-conv' },
                    { title: `Cost/đơn dự báo: ${money(f.projected_cost_per_order ?? null, currency)}`, key: 'f-cpo' },
                ],
            },
        ];

        if (strategy.length > 0) {
            nodes.push({
                title: <Text strong>Chiến lược đề xuất ({strategy.length})</Text>,
                key: 'strategy',
                children: [...byCampaign.entries()].map(([camp, items], ci) => ({
                    title: <span><Tag color="blue">{camp}</Tag></span>,
                    key: `s-${ci}`,
                    children: items.map((s, si) => ({
                        title: <span><Tag>{s.action}</Tag>{s.rationale}</span>,
                        key: `s-${ci}-${si}`,
                    })),
                })),
            });
        }

        if (review.length > 0) {
            nodes.push({
                title: <Text strong>Đánh giá nội dung quảng cáo ({review.length})</Text>,
                key: 'creative',
                children: review.map((cr, i) => ({
                    title: <span><Tag color={cr.verdict === 'tốt' ? 'green' : 'orange'}>{cr.verdict}</Tag>{cr.name ?? cr.ref}</span>,
                    key: `c-${i}`,
                    children: [
                        ...cr.issues.map((iss, j) => ({ title: <Text type="danger">⚠ {iss}</Text>, key: `c-${i}-i-${j}` })),
                        ...cr.suggestions.map((s, j) => ({ title: <span><BulbOutlined style={{ color: '#faad14' }} /> {s}</span>, key: `c-${i}-s-${j}` })),
                    ],
                })),
            });
        }

        return nodes;
    }, [forecast, currency]);

    return (
        <Tree
            treeData={treeData}
            defaultExpandedKeys={['forecast', 'strategy', 'creative']}
            selectable={false}
            showLine
        />
    );
}
