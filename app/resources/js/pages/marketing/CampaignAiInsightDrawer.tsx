import { useEffect, useMemo, useState } from 'react';
import {
    App as AntApp, Alert, Button, Checkbox, Collapse, Divider, Drawer, Empty, InputNumber, List,
    Popconfirm, Segmented, Space, Spin, Switch, Tag, Typography,
} from 'antd';
import { BulbOutlined, DeleteOutlined, RobotOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import {
    CAMPAIGN_AI_METRICS, type CampaignAiMetric,
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

interface Props {
    open: boolean;
    accountId: number | null;
    campaign: { id: string; name: string | null } | null;
    onClose: () => void;
}

/** Cấu hình + chạy phân tích AI cho riêng một chiến dịch (số ngày, chỉ số, kèm tương tác bài). */
export function CampaignAiInsightDrawer({ open, accountId, campaign, onClose }: Props) {
    const { message } = AntApp.useApp();
    const [days, setDays] = useState<number>(14);
    const [metrics, setMetrics] = useState<CampaignAiMetric[]>(DEFAULT_METRICS);
    const [includeEngagement, setIncludeEngagement] = useState(true);
    const [includeLanding, setIncludeLanding] = useState(true);
    const [polling, setPolling] = useState(false);
    const [startedAt, setStartedAt] = useState<number | null>(null);

    const campaignId = campaign?.id ?? null;
    const { data: insight, isLoading } = useCampaignAiInsight(accountId, campaignId, { enabled: open, poll: polling });
    const { data: history } = useCampaignAiInsightHistory(accountId, campaignId, open);
    const deleteInsight = useDeleteCampaignInsight();
    const generate = useGenerateCampaignAiInsight();

    // Reset transient state whenever a different campaign drawer opens.
    useEffect(() => {
        if (open) {
            setPolling(false);
            setStartedAt(null);
        }
    }, [open, campaignId]);

    // Stop polling once a fresh result (generated after we triggered) has arrived.
    useEffect(() => {
        if (!polling || startedAt == null) return;
        const gen = insight?.generated_at ? Date.parse(insight.generated_at) : 0;
        if (gen >= startedAt) {
            setPolling(false);
            message.success('Đã có phân tích AI cho chiến dịch.');
        }
    }, [insight, polling, startedAt, message]);

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
                        message.success('Đã có phân tích (dùng lại kết quả gần đây).');
                    }
                },
                onError: (e) => message.error(errorMessage(e, 'Không phân tích được (cooldown / chưa cấu hình provider AI marketing).')),
            },
        );
    }

    const recommendations = useMemo(() => {
        const recs = insight?.payload.recommendations ?? [];
        return recs.map((r) => (typeof r === 'string' ? { action: r, rationale: '' } : { action: r.action ?? '', rationale: r.rationale ?? '' }));
    }, [insight]);

    return (
        <Drawer
            open={open}
            onClose={onClose}
            width={520}
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

                <Divider style={{ margin: '4px 0' }} />

                {isLoading ? (
                    <div style={{ textAlign: 'center', padding: 24 }}><Spin /></div>
                ) : insight == null ? (
                    <Empty description="Chưa có phân tích — chọn chỉ số rồi bấm Phân tích." />
                ) : (
                    <Space direction="vertical" size={12} style={{ display: 'flex' }}>
                        <Text type="secondary" style={{ fontSize: 12 }}>
                            Cập nhật: {insight.generated_at ? new Date(insight.generated_at).toLocaleString('vi-VN') : '—'}
                        </Text>
                        {insight.payload.summary && <Paragraph>{insight.payload.summary}</Paragraph>}
                        {insight.payload.assessment && <Alert type="info" message="Đánh giá" description={insight.payload.assessment} />}
                        {recommendations.length > 0 && (
                            <div>
                                <Text strong>Khuyến nghị</Text>
                                {recommendations.map((r, i) => (
                                    <div key={i} style={{ marginTop: 6 }}>
                                        {r.action && <Tag color="blue">{r.action}</Tag>}
                                        <Text>{r.rationale}</Text>
                                    </div>
                                ))}
                            </div>
                        )}
                        {(insight.payload.creative_review?.length ?? 0) > 0 && (
                            <div>
                                <Text strong>Đánh giá nội dung quảng cáo</Text>
                                {insight.payload.creative_review!.map((cr, i) => (
                                    <div key={i} style={{ marginTop: 6 }}>
                                        <Tag color={cr.verdict === 'tốt' ? 'green' : 'orange'}>{cr.verdict}</Tag>
                                        <Text>{cr.name ?? cr.ref}</Text>
                                        {cr.suggestions.map((s, j) => (
                                            <div key={j} style={{ marginLeft: 12, color: '#888', fontSize: 12 }}><BulbOutlined /> {s}</div>
                                        ))}
                                    </div>
                                ))}
                            </div>
                        )}
                    </Space>
                )}

                {(history?.length ?? 0) > 1 && (
                    <Collapse
                        size="small"
                        items={[{
                            key: 'history',
                            label: `Lịch sử phân tích (${history!.length})`,
                            children: (
                                <List
                                    size="small"
                                    dataSource={history ?? []}
                                    renderItem={(h) => (
                                        <List.Item
                                            actions={[
                                                h.id != null ? (
                                                    <Popconfirm
                                                        key="del"
                                                        title="Xoá bản phân tích này?"
                                                        okText="Xoá" cancelText="Huỷ"
                                                        onConfirm={() => deleteInsight.mutate(h.id!, { onSuccess: () => message.success('Đã xoá.') })}
                                                    >
                                                        <Button type="text" size="small" danger icon={<DeleteOutlined />} />
                                                    </Popconfirm>
                                                ) : <span key="x" />,
                                            ]}
                                        >
                                            <Space direction="vertical" size={0}>
                                                <Text style={{ fontSize: 12 }}>
                                                    {h.generated_at ? new Date(h.generated_at).toLocaleString('vi-VN') : '—'}
                                                    {h.params?.days ? ` · ${h.params.days} ngày` : ''}
                                                </Text>
                                                <Text type="secondary" style={{ fontSize: 12 }} ellipsis>{h.payload.summary ?? ''}</Text>
                                            </Space>
                                        </List.Item>
                                    )}
                                />
                            ),
                        }]}
                    />
                )}
            </Space>
        </Drawer>
    );
}
