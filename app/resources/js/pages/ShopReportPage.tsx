import { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { Alert, Card, Col, Empty, Row, Segmented, Space, Spin, Table, Tag, Typography } from 'antd';
import { InfoCircleOutlined, SafetyCertificateOutlined, WarningOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import {
    formatMetric,
    PROVIDER_LABEL,
    RATING_COLOR,
    useShopReport,
    type PenaltyPoint,
    type Punishment,
    type ShopMetric,
    type ShopReportEntry,
} from '@/lib/shopReport';
import { errorCode, errorMessage } from '@/lib/api';

const { Title, Text, Paragraph } = Typography;

const GROUP_LABEL: Record<string, string> = {
    fulfillment: 'Vận hành',
    listing: 'Sản phẩm',
    customer_service: 'CSKH',
    rating: 'Đánh giá',
    sales: 'Doanh số',
    other: 'Khác',
};

function metricColumns(): ColumnsType<ShopMetric> {
    return [
        { title: 'Chỉ số', dataIndex: 'name', key: 'name', render: (v: string, r) => (
            <Space size={4}><Text>{v}</Text><Tag bordered={false}>{GROUP_LABEL[r.group] ?? r.group}</Tag></Space>
        ) },
        { title: 'Giá trị', dataIndex: 'value', key: 'value', align: 'right', width: 130,
            render: (v: number | null, r) => <Text strong>{formatMetric(v, r.unit)}</Text> },
        { title: 'Mục tiêu', key: 'target', align: 'right', width: 120,
            render: (_: unknown, r) => (r.target == null ? <Text type="secondary">—</Text>
                : <Text type="secondary">{r.comparator ?? ''} {formatMetric(r.target, r.unit)}</Text>) },
        { title: 'Trạng thái', dataIndex: 'passed', key: 'passed', align: 'center', width: 110,
            render: (p: boolean | null) => p == null ? <Tag>—</Tag>
                : p ? <Tag color="green">Đạt</Tag> : <Tag color="red">Chưa đạt</Tag> },
    ];
}

function PenaltyBlock({ penalties, punishments }: { penalties: PenaltyPoint[]; punishments: Punishment[] }) {
    const total = penalties.reduce((s, p) => s + (p.points || 0), 0);
    if (penalties.length === 0 && punishments.length === 0) {
        return <Alert type="success" showIcon message="Không có điểm phạt / hình phạt nào trong quý này." />;
    }

    return (
        <Space direction="vertical" size={8} style={{ width: '100%' }}>
            {penalties.length > 0 && (
                <div>
                    <Space size={6}>
                        <WarningOutlined style={{ color: '#fa8c16' }} />
                        <Text strong>Điểm phạt (quý hiện tại): {total}</Text>
                    </Space>
                    <div style={{ marginTop: 6 }}>
                        {penalties.map((p, i) => (
                            <Tag key={i} color="orange" style={{ marginBottom: 4 }}>
                                {p.violation_label ?? `Vi phạm #${p.violation_type ?? '?'}`} · {p.points} điểm
                            </Tag>
                        ))}
                    </div>
                </div>
            )}
            {punishments.length > 0 && (
                <div>
                    <Space size={6}><WarningOutlined style={{ color: '#cf1322' }} /><Text strong>Hình phạt đang áp dụng</Text></Space>
                    <div style={{ marginTop: 6 }}>
                        {punishments.map((p, i) => (
                            <Tag key={i} color="red" style={{ marginBottom: 4 }}>
                                {p.type_label ?? `Hình phạt #${p.type ?? '?'}`}{p.tier ? ` · Bậc ${p.tier}` : ''}
                            </Tag>
                        ))}
                    </div>
                </div>
            )}
        </Space>
    );
}

function ShopCard({ entry }: { entry: ShopReportEntry }) {
    const providerLabel = PROVIDER_LABEL[entry.provider] ?? entry.provider;
    const title = (
        <Space>
            <SafetyCertificateOutlined />
            <Text strong>{entry.shop_name}</Text>
            <Tag>{providerLabel}</Tag>
            {entry.overall_rating != null && (
                <Tag color={RATING_COLOR[entry.overall_rating] ?? 'default'}>{entry.overall_label ?? `Hạng ${entry.overall_rating}`}</Tag>
            )}
            {entry.kind === 'performance' && <Tag color="blue">Hiệu suất (7 ngày)</Tag>}
        </Space>
    );

    let body: React.ReactNode;
    if (!entry.available) {
        body = <Alert type="warning" showIcon
            message={entry.error ?? entry.note ?? 'Không lấy được dữ liệu từ sàn.'} />;
    } else {
        body = (
            <Space direction="vertical" size={12} style={{ width: '100%' }}>
                {entry.kind === 'health' && entry.total_metrics != null && (
                    <Text type="secondary">Đạt {entry.passed_count ?? 0}/{entry.total_metrics} chỉ số mục tiêu.</Text>
                )}
                <Table<ShopMetric>
                    size="small"
                    rowKey="key"
                    columns={metricColumns()}
                    dataSource={entry.metrics}
                    pagination={false}
                    locale={{ emptyText: 'Không có chỉ số.' }}
                />
                {entry.supports_penalty
                    ? <PenaltyBlock penalties={entry.penalties} punishments={entry.punishments} />
                    : <Text type="secondary"><InfoCircleOutlined /> Sàn này không cung cấp điểm phạt qua API — xem trong Trung tâm người bán.</Text>}
                {entry.penalty_error && <Alert type="warning" showIcon message={entry.penalty_error} />}
            </Space>
        );
    }

    return <Card title={title} style={{ marginBottom: 16 }}>{body}</Card>;
}

export function ShopReportPage() {
    const { data, isLoading, error } = useShopReport();
    const [provider, setProvider] = useState<string>('all');

    const providers = useMemo(() => {
        const set = Array.from(new Set((data ?? []).map((e) => e.provider)));
        return ['all', ...set];
    }, [data]);

    const filtered = useMemo(
        () => (data ?? []).filter((e) => provider === 'all' || e.provider === provider),
        [data, provider],
    );

    return (
        <div>
            <Title level={3}><SafetyCertificateOutlined /> Báo cáo sàn</Title>
            <Paragraph type="secondary">
                Sức khỏe, điểm chỉ số và điểm phạt ("sao quả tạ") của các gian hàng đã kết nối. Mỗi sàn hiển thị
                đúng dữ liệu API cung cấp: Lazada/Shopee có điểm hiệu suất; chỉ Shopee có điểm phạt qua API; TikTok
                chỉ có hiệu suất doanh thu.
            </Paragraph>

            {isLoading && <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>}

            {error && (errorCode(error) === 'PLAN_FEATURE_LOCKED'
                ? <Alert type="warning" showIcon message={<span>Tính năng Báo cáo sàn có ở gói cao hơn. <Link to="/plans">Nâng cấp gói</Link> để xem.</span>} />
                : <Alert type="error" showIcon message={errorMessage(error, 'Không tải được báo cáo sàn. Vui lòng thử lại.')} />)}

            {!isLoading && !error && (data?.length ?? 0) === 0 && (
                <Empty description={<span>Chưa có gian hàng Lazada/Shopee/TikTok nào kết nối. <Link to="/channels">Kết nối gian hàng</Link></span>} />
            )}

            {!isLoading && (data?.length ?? 0) > 0 && (
                <>
                    {providers.length > 2 && (
                        <Segmented
                            style={{ marginBottom: 16 }}
                            value={provider}
                            onChange={(v) => setProvider(v as string)}
                            options={providers.map((p) => ({ label: p === 'all' ? 'Tất cả' : (PROVIDER_LABEL[p] ?? p), value: p }))}
                        />
                    )}
                    <Row>
                        <Col span={24}>
                            {filtered.map((entry) => <ShopCard key={entry.channel_account_id} entry={entry} />)}
                        </Col>
                    </Row>
                </>
            )}

            <Alert
                style={{ marginTop: 8 }}
                type="info"
                showIcon
                icon={<InfoCircleOutlined />}
                message="Điểm phạt/vi phạm của Lazada và TikTok chưa được mở qua API — vui lòng xem trực tiếp trong Trung tâm người bán của sàn."
            />
        </div>
    );
}
