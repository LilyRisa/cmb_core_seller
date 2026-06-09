import { useEffect, useState } from 'react';
import {
    App as AntApp, Alert, Button, Card, Checkbox, Collapse, Divider, Drawer, Empty, InputNumber,
    Popconfirm, Progress, Segmented, Space, Spin, Switch, Tag, Typography,
} from 'antd';
import { BulbOutlined, CheckCircleOutlined, DeleteOutlined, RobotOutlined } from '@ant-design/icons';
import { useQueryClient } from '@tanstack/react-query';
import { errorMessage } from '@/lib/api';
import { formatDate } from '@/lib/format';
import {
    CAMPAIGN_AI_METRICS, type CampaignAiInsight, type CampaignAiMetric,
    useCampaignAiInsight, useCampaignAiInsightHistory, useDeleteCampaignInsight, useGenerateCampaignAiInsight,
} from '@/lib/marketing';

const { Text, Paragraph } = Typography;

const METRIC_LABEL: Record<CampaignAiMetric, string> = {
    spend: 'Chi tiêu', impressions: 'Hiển thị', clicks: 'Click', reach: 'Tiếp cận',
    ctr: 'CTR', cpc: 'CPC', cpm: 'CPM', frequency: 'Tần suất',
    purchase_roas: 'ROAS', messaging_conversations: 'Hội thoại', leads: 'Leads',
};

const DEFAULT_METRICS: CampaignAiMetric[] = ['spend', 'impressions', 'clicks', 'ctr', 'cpc', 'purchase_roas'];
const DAY_PRESETS = [7, 14, 30];

/** Effectiveness score (0–100) → colour + qualitative label. */
function scoreColor(score: number): string {
    if (score >= 80) return '#52c41a';
    if (score >= 65) return '#73d13d';
    if (score >= 50) return '#faad14';
    if (score >= 35) return '#fa8c16';
    return '#ff4d4f';
}
function scoreLabel(score: number): string {
    if (score >= 80) return 'Xuất sắc';
    if (score >= 65) return 'Tốt';
    if (score >= 50) return 'Khá';
    if (score >= 35) return 'Trung bình';
    return 'Cần cải thiện';
}

type Payload = CampaignAiInsight['payload'];

function normRecs(payload: Payload): { action: string; rationale: string }[] {
    return (payload.recommendations ?? []).map((r) =>
        typeof r === 'string' ? { action: r, rationale: '' } : { action: r.action ?? '', rationale: r.rationale ?? '' });
}

/** The full body of one report (shown when its panel is expanded). */
function ReportBody({ payload }: { payload: Payload }) {
    const score = typeof payload.score === 'number' ? Math.round(payload.score) : null;
    const recs = normRecs(payload);
    const reviews = payload.creative_review ?? [];

    return (
        <Space direction="vertical" size={14} style={{ display: 'flex' }}>
            {score != null && (
                <Card size="small" style={{ background: '#fafafa', borderColor: '#f0f0f0' }} styles={{ body: { padding: 16 } }}>
                    <Space size={16} align="center">
                        <Progress
                            type="dashboard"
                            size={86}
                            percent={score}
                            strokeColor={scoreColor(score)}
                            format={() => <span style={{ fontSize: 20, fontWeight: 600, color: scoreColor(score) }}>{score}</span>}
                        />
                        <div>
                            <div style={{ fontSize: 17, fontWeight: 600, color: scoreColor(score) }}>{scoreLabel(score)}</div>
                            <Text type="secondary">Điểm hiệu quả tổng thể (0–100)</Text>
                        </div>
                    </Space>
                </Card>
            )}

            {payload.summary && <Paragraph style={{ marginBottom: 0 }}>{payload.summary}</Paragraph>}
            {payload.assessment && <Alert type="info" showIcon message="Đánh giá" description={payload.assessment} />}

            {recs.length > 0 && (
                <div>
                    <Text strong>Khuyến nghị</Text>
                    <Space direction="vertical" size={8} style={{ display: 'flex', marginTop: 8 }}>
                        {recs.map((r, i) => (
                            <div key={i} style={{ display: 'flex', gap: 8, alignItems: 'flex-start' }}>
                                <CheckCircleOutlined style={{ color: '#52c41a', marginTop: 4 }} />
                                <div>
                                    {r.action && <Text strong>{r.action}</Text>}
                                    {r.rationale && <div><Text type="secondary">{r.rationale}</Text></div>}
                                </div>
                            </div>
                        ))}
                    </Space>
                </div>
            )}

            {reviews.length > 0 && (
                <div>
                    <Text strong>Đánh giá nội dung quảng cáo</Text>
                    <Space direction="vertical" size={10} style={{ display: 'flex', marginTop: 8 }}>
                        {reviews.map((cr, i) => (
                            <Card key={i} size="small">
                                <Space style={{ marginBottom: cr.suggestions.length ? 6 : 0 }}>
                                    <Tag color={cr.verdict === 'tốt' ? 'green' : 'orange'}>{cr.verdict}</Tag>
                                    <Text strong>{cr.name ?? cr.ref}</Text>
                                </Space>
                                {cr.suggestions.map((s, j) => (
                                    <div key={j} style={{ display: 'flex', gap: 6, color: '#888', fontSize: 12 }}>
                                        <BulbOutlined style={{ marginTop: 3 }} /><span>{s}</span>
                                    </div>
                                ))}
                            </Card>
                        ))}
                    </Space>
                </div>
            )}
        </Space>
    );
}

interface Props {
    open: boolean;
    accountId: number | null;
    campaign: { id: string; name: string | null } | null;
    onClose: () => void;
}

/** Cấu hình + chạy phân tích AI cho riêng một chiến dịch (số ngày, chỉ số, kèm tương tác bài). */
export function CampaignAiInsightDrawer({ open, accountId, campaign, onClose }: Props) {
    const { message } = AntApp.useApp();
    const qc = useQueryClient();
    const [days, setDays] = useState<number>(14);
    const [metrics, setMetrics] = useState<CampaignAiMetric[]>(DEFAULT_METRICS);
    const [includeEngagement, setIncludeEngagement] = useState(true);
    const [includeLanding, setIncludeLanding] = useState(true);
    const [polling, setPolling] = useState(false);
    const [startedAt, setStartedAt] = useState<number | null>(null);

    const campaignId = campaign?.id ?? null;
    const { data: insight } = useCampaignAiInsight(accountId, campaignId, { enabled: open, poll: polling });
    const { data: history, isLoading } = useCampaignAiInsightHistory(accountId, campaignId, open);
    const deleteInsight = useDeleteCampaignInsight();
    const generate = useGenerateCampaignAiInsight();

    const reports = history ?? [];

    // Reset transient state whenever a different campaign drawer opens.
    useEffect(() => {
        if (open) {
            setPolling(false);
            setStartedAt(null);
        }
    }, [open, campaignId]);

    // Stop polling once a fresh result (generated after we triggered) has arrived, then refresh the list.
    useEffect(() => {
        if (!polling || startedAt == null) return;
        const gen = insight?.generated_at ? Date.parse(insight.generated_at) : 0;
        if (gen >= startedAt) {
            setPolling(false);
            qc.invalidateQueries({ queryKey: ['marketing', 'campaign-insight-history'] });
            message.success('Đã có báo cáo phân tích AI cho chiến dịch.');
        }
    }, [insight, polling, startedAt, message, qc]);

    const isPgPresetDay = DAY_PRESETS.includes(days);

    function handleGenerate() {
        if (accountId == null || campaignId == null) return;
        const triggeredAt = Date.now();
        generate.mutate(
            { accountId, campaignId, params: { days, metrics, include_engagement: includeEngagement, include_landing: includeLanding } },
            {
                onSuccess: (res) => {
                    if (res.queued) {
                        setStartedAt(triggeredAt);
                        setPolling(true);
                        message.info('Đang phân tích — sẽ hiển thị khi xong và gửi email cho Quản trị.');
                    } else {
                        message.success('Đã có báo cáo (dùng lại kết quả gần đây).');
                    }
                },
                onError: (e) => message.error(errorMessage(e, 'Không phân tích được (cooldown / chưa cấu hình provider AI marketing).')),
            },
        );
    }

    return (
        <Drawer
            open={open}
            onClose={onClose}
            width={600}
            title={<Space><RobotOutlined />Phân tích AI: {campaign?.name ?? campaignId}</Space>}
            destroyOnClose
        >
            <Space direction="vertical" size={16} style={{ display: 'flex' }}>
                <div>
                    <Text strong>Khoảng thời gian</Text>
                    <div style={{ marginTop: 8 }}>
                        <Space wrap>
                            <Segmented
                                value={isPgPresetDay ? days : 'custom'}
                                onChange={(v) => { if (v !== 'custom') setDays(Number(v)); }}
                                options={[...DAY_PRESETS.map((d) => ({ label: `${d} ngày`, value: d })), { label: 'Tuỳ chỉnh', value: 'custom' }]}
                            />
                            {!isPgPresetDay && (
                                <InputNumber min={1} max={90} value={days} onChange={(v) => setDays(Math.max(1, Math.min(90, Number(v ?? 1))))} addonAfter="ngày" />
                            )}
                        </Space>
                    </div>
                </div>

                <div>
                    <Text strong>Chỉ số đưa vào phân tích</Text>
                    <div style={{ marginTop: 8 }}>
                        <Checkbox.Group value={metrics} onChange={(v) => setMetrics(v as CampaignAiMetric[])}>
                            <Space wrap>
                                {CAMPAIGN_AI_METRICS.map((m) => <Checkbox key={m} value={m}>{METRIC_LABEL[m]}</Checkbox>)}
                            </Space>
                        </Checkbox.Group>
                    </div>
                </div>

                <Space>
                    <Switch checked={includeEngagement} onChange={setIncludeEngagement} />
                    <Text>Kèm nội dung bài viết + lượt like/comment</Text>
                </Space>

                <Space direction="vertical" size={0}>
                    <Space>
                        <Switch checked={includeLanding} onChange={setIncludeLanding} />
                        <Text>Phân tích trang đích (website)</Text>
                    </Space>
                    <Text type="secondary" style={{ fontSize: 11 }}>
                        Tự tải nội dung trang đích (tiêu đề, CTA, form, pixel) cho chiến dịch chuyển đổi website để AI phân tích sâu hơn.
                    </Text>
                </Space>

                <Button type="primary" icon={<BulbOutlined />} loading={generate.isPending || polling} onClick={handleGenerate} disabled={accountId == null}>
                    {polling ? 'Đang phân tích…' : 'Phân tích'}
                </Button>

                <Divider style={{ margin: '4px 0' }} orientation="left">
                    <Text strong>Báo cáo phân tích{reports.length > 0 ? ` (${reports.length})` : ''}</Text>
                </Divider>

                {polling && (
                    <Alert
                        type="info"
                        showIcon
                        icon={<Spin size="small" />}
                        message="Đang phân tích — báo cáo mới sẽ xuất hiện ở đầu danh sách."
                    />
                )}

                {isLoading && reports.length === 0 ? (
                    <div style={{ textAlign: 'center', padding: 24 }}><Spin /></div>
                ) : reports.length === 0 ? (
                    <Empty description="Chưa có báo cáo — chọn chỉ số rồi bấm Phân tích." />
                ) : (
                    <Collapse
                        // Remount when the newest report changes so it opens by default.
                        key={reports[0]?.id ?? 'none'}
                        defaultActiveKey={reports[0]?.id != null ? [String(reports[0].id)] : []}
                        items={reports.map((h, idx) => {
                            const score = typeof h.payload.score === 'number' ? Math.round(h.payload.score) : null;
                            return {
                                key: String(h.id ?? idx),
                                label: (
                                    <Space size={8} align="center" wrap>
                                        {score != null
                                            ? <Tag color={scoreColor(score)} style={{ margin: 0, fontWeight: 600 }}>{score}</Tag>
                                            : <Tag style={{ margin: 0 }}>—</Tag>}
                                        <Text style={{ fontSize: 13 }}>
                                            {formatDate(h.generated_at)}
                                        </Text>
                                        {idx === 0 && <Tag color="blue" style={{ margin: 0 }}>Mới nhất</Tag>}
                                        {h.params?.days ? <Text type="secondary" style={{ fontSize: 12 }}>{h.params.days} ngày</Text> : null}
                                    </Space>
                                ),
                                extra: h.id != null ? (
                                    <span onClick={(e) => e.stopPropagation()}>
                                        <Popconfirm
                                            title="Xoá báo cáo này?"
                                            okText="Xoá" cancelText="Huỷ"
                                            okButtonProps={{ danger: true }}
                                            onConfirm={() => deleteInsight.mutate(h.id!, { onSuccess: () => message.success('Đã xoá báo cáo.') })}
                                        >
                                            <Button type="text" size="small" danger icon={<DeleteOutlined />} />
                                        </Popconfirm>
                                    </span>
                                ) : undefined,
                                children: <ReportBody payload={h.payload} />,
                            };
                        })}
                    />
                )}
            </Space>
        </Drawer>
    );
}
