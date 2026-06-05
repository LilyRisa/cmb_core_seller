import { useMemo } from 'react';
import { Empty, Spin, Table, Tag, Typography } from 'antd';
import { useAdReport, type ReportRow } from '@/lib/marketing';
import { resultOf, sumInsights } from '@/lib/adReport';

const { Text } = Typography;

interface TreeRow extends ReportRow {
    _level: 'campaign' | 'adset' | 'ad';
    children?: TreeRow[];
}

const LEVEL_TAG: Record<string, { label: string; color: string }> = {
    campaign: { label: 'Chiến dịch', color: 'blue' },
    adset: { label: 'Nhóm', color: 'cyan' },
    ad: { label: 'QC', color: 'default' },
};

function money(v: number | null | undefined, currency: string | null): string {
    if (v == null) return '—';
    return v.toLocaleString('vi-VN') + (currency ? ' ' + currency : '');
}
const num = (v: number | null | undefined) => (v == null ? '—' : v.toLocaleString('vi-VN'));

interface Props {
    accountId: number | null;
    since: string;
    until: string;
    currency: string | null;
}

/** Cây Chiến dịch ▸ Nhóm QC ▸ Quảng cáo — cha hiện tổng, bấm để mở rộng xem con. */
export function ReportTree({ accountId, since, until, currency }: Props) {
    const campaigns = useAdReport(accountId, 'campaign', since, until, {});
    const adsets = useAdReport(accountId, 'adset', since, until, {});
    const ads = useAdReport(accountId, 'ad', since, until, {});

    const loading = campaigns.isFetching || adsets.isFetching || ads.isFetching;

    const tree = useMemo<TreeRow[]>(() => {
        const campRows = campaigns.data?.rows ?? [];
        const adsetRows = adsets.data?.rows ?? [];
        const adRows = ads.data?.rows ?? [];

        const adsByAdset = new Map<string, ReportRow[]>();
        for (const ad of adRows) {
            const p = ad.parent_id ?? '';
            if (!adsByAdset.has(p)) adsByAdset.set(p, []);
            adsByAdset.get(p)!.push(ad);
        }
        const adsetsByCampaign = new Map<string, TreeRow[]>();
        for (const as of adsetRows) {
            const children = (adsByAdset.get(as.external_id) ?? []).map((ad) => ({ ...ad, _level: 'ad' as const }));
            const node: TreeRow = {
                ...as,
                _level: 'adset',
                insights: as.insights ?? sumInsights(children),
                children: children.length ? children : undefined,
            };
            const p = as.parent_id ?? '';
            if (!adsetsByCampaign.has(p)) adsetsByCampaign.set(p, []);
            adsetsByCampaign.get(p)!.push(node);
        }
        return campRows.map((c) => {
            const children = adsetsByCampaign.get(c.external_id) ?? [];
            return {
                ...c,
                _level: 'campaign' as const,
                insights: c.insights ?? sumInsights(children),
                children: children.length ? children : undefined,
            };
        });
    }, [campaigns.data, adsets.data, ads.data]);

    if (loading && tree.length === 0) {
        return <div style={{ textAlign: 'center', padding: 32 }}><Spin /></div>;
    }

    return (
        <Table<TreeRow>
            rowKey="external_id"
            size="small"
            loading={loading}
            scroll={{ x: 'max-content' }}
            dataSource={tree}
            pagination={{ defaultPageSize: 20, showSizeChanger: true }}
            expandable={{ childrenColumnName: 'children' }}
            locale={{ emptyText: <Empty description="Không có dữ liệu cho khoảng ngày này." /> }}
            columns={[
                {
                    title: 'Tên', key: 'name', fixed: 'left', width: 280,
                    render: (_: unknown, r: TreeRow) => (
                        <span>
                            <Tag color={LEVEL_TAG[r._level].color} style={{ marginInlineEnd: 6 }}>{LEVEL_TAG[r._level].label}</Tag>
                            {r.name ?? r.external_id}
                        </span>
                    ),
                },
                {
                    title: 'Kết quả', key: 'result', width: 150,
                    render: (_: unknown, r: TreeRow) => {
                        const res = resultOf(r.objective, r.insights);
                        return res == null ? '—' : (
                            <span>
                                <Text strong style={{ color: res.color }}>{res.value.toLocaleString('vi-VN')}</Text>
                                <Text type="secondary" style={{ fontSize: 11, marginLeft: 4 }}>{res.label}</Text>
                            </span>
                        );
                    },
                },
                { title: 'Chi tiêu', key: 'spend', render: (_: unknown, r: TreeRow) => money(r.insights?.spend, currency) },
                { title: 'Hiển thị', key: 'impr', render: (_: unknown, r: TreeRow) => num(r.insights?.impressions) },
                { title: 'Click', key: 'clicks', render: (_: unknown, r: TreeRow) => num(r.insights?.clicks) },
                { title: 'CTR', key: 'ctr', render: (_: unknown, r: TreeRow) => (r.insights?.ctr == null ? '—' : r.insights.ctr.toFixed(2) + '%') },
                { title: 'CPC', key: 'cpc', render: (_: unknown, r: TreeRow) => money(r.insights?.cpc, currency) },
            ]}
        />
    );
}
