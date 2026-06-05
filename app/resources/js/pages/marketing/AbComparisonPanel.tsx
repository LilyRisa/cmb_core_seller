import { useMemo, useState } from 'react';
import { Card, Empty, Segmented, Table, Tag, Typography } from 'antd';
import { TrophyOutlined } from '@ant-design/icons';
import type { ReportRow } from '@/lib/marketing';

const { Text } = Typography;

// Metrics worth picking a winner on, with their "better" direction.
const COMPARE_METRICS: { key: keyof NonNullable<ReportRow['insights']>; label: string; higherIsBetter: boolean }[] = [
    { key: 'ctr', label: 'CTR', higherIsBetter: true },
    { key: 'cpc', label: 'CPC', higherIsBetter: false },
    { key: 'cpm', label: 'CPM', higherIsBetter: false },
    { key: 'purchase_roas', label: 'ROAS', higherIsBetter: true },
    { key: 'messaging_conversations', label: 'Hội thoại', higherIsBetter: true },
    { key: 'leads', label: 'Leads', higherIsBetter: true },
];

/** Strip a trailing " [A]" / " [B]" variant tag to find the experiment base name. */
function baseName(name: string | null): string | null {
    if (name == null) return null;
    const m = name.match(/^(.*?)\s*\[[A-Z]\]$/);
    return m ? m[1].trim() : null;
}

interface AbGroup {
    base: string;
    variants: ReportRow[];
}

function groupVariants(rows: ReportRow[]): AbGroup[] {
    const map = new Map<string, ReportRow[]>();
    for (const r of rows) {
        const b = baseName(r.name);
        if (b == null) continue;
        (map.get(b) ?? map.set(b, []).get(b)!).push(r);
    }
    return [...map.entries()]
        .filter(([, v]) => v.length >= 2)
        .map(([base, variants]) => ({ base, variants }));
}

const num = (v: number | null | undefined) => (v == null ? '—' : v.toLocaleString('vi-VN'));

function money(v: number | null | undefined, currency: string | null): string {
    if (v == null) return '—';
    return v.toLocaleString('vi-VN') + (currency ? ' ' + currency : '');
}

interface Props {
    rows: ReportRow[];
    currency: string | null;
}

/** Compare published A/B variants ([A]/[B]…) and highlight a winner by a chosen metric. */
export function AbComparisonPanel({ rows, currency }: Props) {
    const [metricKey, setMetricKey] = useState<string>('ctr');
    const groups = useMemo(() => groupVariants(rows), [rows]);
    const metric = COMPARE_METRICS.find((m) => m.key === metricKey) ?? COMPARE_METRICS[0];

    if (groups.length === 0) {
        return (
            <Empty
                description="Chưa có cặp A/B nào. Tạo biến thể A/B ở trình tạo quảng cáo (nút A/B Test) rồi xuất bản."
                style={{ padding: 24 }}
            />
        );
    }

    // Winner per group: best value of the selected metric (ignoring null).
    function winnerKey(g: AbGroup): string | null {
        let best: ReportRow | null = null;
        for (const v of g.variants) {
            const val = v.insights?.[metric.key] ?? null;
            if (val == null) continue;
            const bestVal = best?.insights?.[metric.key] ?? null;
            if (bestVal == null || (metric.higherIsBetter ? val > bestVal : val < bestVal)) best = v;
        }
        return best?.external_id ?? null;
    }

    return (
        <div>
            <div style={{ marginBottom: 12 }}>
                <Text strong style={{ marginRight: 8 }}>Tiêu chí người thắng:</Text>
                <Segmented
                    size="small"
                    value={metricKey}
                    onChange={(v) => setMetricKey(String(v))}
                    options={COMPARE_METRICS.map((m) => ({ label: m.label, value: m.key as string }))}
                />
            </div>

            {groups.map((g) => {
                const win = winnerKey(g);
                return (
                    <Card key={g.base} size="small" title={`A/B: ${g.base}`} style={{ marginBottom: 12 }}>
                        <Table<ReportRow>
                            rowKey="external_id"
                            size="small"
                            pagination={false}
                            dataSource={g.variants}
                            columns={[
                                {
                                    title: 'Biến thể', dataIndex: 'name', key: 'name',
                                    render: (v: string | null, r: ReportRow) => (
                                        <span>
                                            {v ?? r.external_id}
                                            {r.external_id === win && (
                                                <Tag color="gold" style={{ marginLeft: 8 }} icon={<TrophyOutlined />}>Thắng</Tag>
                                            )}
                                        </span>
                                    ),
                                },
                                { title: 'Chi tiêu', key: 'spend', render: (_: unknown, r: ReportRow) => money(r.insights?.spend, currency) },
                                { title: 'CTR', key: 'ctr', render: (_: unknown, r: ReportRow) => (r.insights?.ctr == null ? '—' : r.insights.ctr.toFixed(2) + '%') },
                                { title: 'CPC', key: 'cpc', render: (_: unknown, r: ReportRow) => money(r.insights?.cpc, currency) },
                                { title: 'ROAS', key: 'roas', render: (_: unknown, r: ReportRow) => (r.insights?.purchase_roas == null ? '—' : r.insights.purchase_roas.toFixed(2)) },
                                { title: 'Hội thoại', key: 'conv', render: (_: unknown, r: ReportRow) => num(r.insights?.messaging_conversations) },
                                { title: 'Leads', key: 'leads', render: (_: unknown, r: ReportRow) => num(r.insights?.leads) },
                            ]}
                        />
                    </Card>
                );
            })}
        </div>
    );
}
