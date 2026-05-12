import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Alert, Avatar, Button, Card, Col, Descriptions, Divider, Empty, Input, Result, Row, Skeleton, Space, Table, Tag, Timeline, Typography } from 'antd';
import { App as AntApp } from 'antd';
import { ArrowLeftOutlined, WarningOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { StatusTag } from '@/components/StatusTag';
import { ChannelBadge } from '@/components/ChannelBadge';
import { MoneyText, DateText } from '@/components/MoneyText';
import { CustomerSummaryCard } from '@/components/CustomerSummaryCard';
import { errorMessage } from '@/lib/api';
import { OrderItem, useOrder, useOrderNote, useOrderTags } from '@/lib/orders';
import { useCan } from '@/lib/tenant';

function AmountRow({ label, value, currency, strong, negative }: { label: string; value: number; currency: string; strong?: boolean; negative?: boolean }) {
    if (!value && !strong) return null;
    return (
        <div style={{ display: 'flex', justifyContent: 'space-between', padding: '4px 0', fontSize: strong ? 16 : 14, fontWeight: strong ? 700 : 400 }}>
            <span style={{ color: strong ? undefined : '#8c8c8c' }}>{label}</span>
            <span style={negative ? { color: '#cf1322' } : undefined}>{negative ? '−' : ''}<MoneyText value={value} currency={currency} /></span>
        </div>
    );
}

export function OrderDetailPage() {
    const { id } = useParams();
    const { message } = AntApp.useApp();
    const { data: order, isLoading, isError, error } = useOrder(id);
    const canUpdate = useCan('orders.update');
    const tags = useOrderTags(Number(id));
    const note = useOrderNote(Number(id));
    const [newTag, setNewTag] = useState('');
    const [noteDraft, setNoteDraft] = useState<string | undefined>(undefined);

    if (isError) return <Result status="error" title="Không tải được đơn hàng" subTitle={errorMessage(error)} extra={<Link to="/orders"><Button>Về danh sách đơn</Button></Link>} />;
    if (isLoading || !order) return <Card><Skeleton active paragraph={{ rows: 8 }} /></Card>;

    const addr = order.shipping_address ?? {};
    const itemColumns: ColumnsType<OrderItem> = [
        { title: 'Sản phẩm', key: 'name', render: (_, it) => (
            <Space>
                <Avatar shape="square" size={48} src={it.image ?? undefined} style={{ background: '#f0f0f0' }}>SP</Avatar>
                <Space direction="vertical" size={0}>
                    <span style={{ fontWeight: 500 }}>{it.name}</span>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>{it.variation ?? ''} {it.seller_sku ? `· SKU: ${it.seller_sku}` : ''}</Typography.Text>
                    {!it.is_mapped && <Tag color="warning" style={{ marginTop: 2 }}>Chưa ghép SKU (Phase 2)</Tag>}
                </Space>
            </Space>
        ) },
        { title: 'Đơn giá', dataIndex: 'unit_price', key: 'unit_price', width: 120, align: 'right', render: (v) => <MoneyText value={v} currency={order.currency} /> },
        { title: 'SL', dataIndex: 'quantity', key: 'quantity', width: 60, align: 'center' },
        { title: 'Thành tiền', dataIndex: 'subtotal', key: 'subtotal', width: 130, align: 'right', render: (v) => <MoneyText value={v} currency={order.currency} strong /> },
    ];

    return (
        <div>
            <PageHeader
                title={<Space size="middle">
                    <Link to="/orders"><Button type="text" icon={<ArrowLeftOutlined />} /></Link>
                    <span>Đơn {order.order_number ?? order.external_order_id ?? `#${order.id}`}</span>
                    <ChannelBadge provider={order.source} />
                    <StatusTag status={order.status} label={order.status_label} rawStatus={order.raw_status} />
                    {order.is_cod && <Tag color="gold">COD</Tag>}
                </Space>}
                subtitle={<>Đặt lúc <DateText value={order.placed_at} /> · cập nhật <DateText value={order.created_at} /></>}
            />

            {order.has_issue && <Alert type="warning" showIcon icon={<WarningOutlined />} style={{ marginBottom: 16 }} message="Đơn hàng có vấn đề" description={order.issue_reason ?? 'Vui lòng kiểm tra.'} />}

            <Row gutter={16}>
                <Col xs={24} lg={16}>
                    <Card title="Sản phẩm" style={{ marginBottom: 16 }}>
                        <Table<OrderItem> rowKey="id" size="small" pagination={false} dataSource={order.items ?? []} columns={itemColumns} locale={{ emptyText: <Empty /> }} />
                        <Divider />
                        <div style={{ maxWidth: 360, marginLeft: 'auto' }}>
                            <AmountRow label="Tạm tính" value={order.item_total} currency={order.currency} />
                            <AmountRow label="Phí vận chuyển" value={order.shipping_fee} currency={order.currency} />
                            <AmountRow label="Giảm giá người bán" value={order.seller_discount} currency={order.currency} negative />
                            <AmountRow label="Giảm giá sàn" value={order.platform_discount} currency={order.currency} negative />
                            <AmountRow label="Thuế" value={order.tax} currency={order.currency} />
                            <Divider style={{ margin: '8px 0' }} />
                            <AmountRow label="Tổng cộng" value={order.grand_total} currency={order.currency} strong />
                            {order.is_cod && <AmountRow label="Thu hộ (COD)" value={order.cod_amount} currency={order.currency} />}
                        </div>
                    </Card>

                    <Card title="Lịch sử trạng thái">
                        {(order.status_history ?? []).length === 0 ? <Empty /> : (
                            <Timeline
                                items={(order.status_history ?? []).map((h) => ({
                                    children: <Space direction="vertical" size={0}>
                                        <span><b>{h.to_status_label}</b> {h.from_status ? <Typography.Text type="secondary">(từ {h.from_status})</Typography.Text> : null}</span>
                                        <Typography.Text type="secondary" style={{ fontSize: 12 }}><DateText value={h.changed_at} /> · nguồn: {h.source}{h.raw_status ? ` · raw: ${h.raw_status}` : ''}</Typography.Text>
                                    </Space>,
                                }))}
                            />
                        )}
                    </Card>
                </Col>

                <Col xs={24} lg={8}>
                    <CustomerSummaryCard customer={order.customer} />

                    <Card title="Người nhận" size="small" style={{ marginBottom: 16 }}>
                        <Descriptions column={1} size="small" colon={false}>
                            <Descriptions.Item label="Tên">{addr.fullName ?? order.buyer_name ?? '—'}</Descriptions.Item>
                            <Descriptions.Item label="SĐT">{addr.phone ?? order.buyer_phone_masked ?? '—'}</Descriptions.Item>
                            <Descriptions.Item label="Địa chỉ">{[addr.line1, addr.ward, addr.district, addr.province, addr.country].filter(Boolean).join(', ') || '—'}</Descriptions.Item>
                            {addr.note && <Descriptions.Item label="Ghi chú giao hàng">{addr.note}</Descriptions.Item>}
                        </Descriptions>
                    </Card>

                    {(order.packages ?? []).length > 0 && (
                        <Card title="Kiện hàng / Vận đơn" size="small" style={{ marginBottom: 16 }}>
                            {order.packages.map((p, i) => (
                                <div key={i} style={{ padding: '4px 0' }}>
                                    <Typography.Text>{p.trackingNo ?? '(chưa có mã vận đơn)'}</Typography.Text>
                                    {p.carrier && <Tag style={{ marginLeft: 8 }}>{p.carrier}</Tag>}
                                    {p.status && <Tag color="processing" style={{ marginLeft: 4 }}>{p.status}</Tag>}
                                </div>
                            ))}
                        </Card>
                    )}

                    <Card title="Nhãn (tags)" size="small" style={{ marginBottom: 16 }}>
                        <Space wrap style={{ marginBottom: canUpdate ? 8 : 0 }}>
                            {(order.tags ?? []).length === 0 && <Typography.Text type="secondary">Chưa có nhãn</Typography.Text>}
                            {(order.tags ?? []).map((t) => (
                                <Tag key={t} color="blue" closable={canUpdate} onClose={(e) => { e.preventDefault(); tags.mutate({ remove: [t] }); }}>{t}</Tag>
                            ))}
                        </Space>
                        {canUpdate && (
                            <Space.Compact style={{ width: '100%' }}>
                                <Input value={newTag} onChange={(e) => setNewTag(e.target.value)} placeholder="Thêm nhãn…" onPressEnter={() => { if (newTag.trim()) { tags.mutate({ add: [newTag.trim()] }, { onSuccess: () => setNewTag('') }); } }} maxLength={50} />
                                <Button type="primary" loading={tags.isPending} onClick={() => { if (newTag.trim()) { tags.mutate({ add: [newTag.trim()] }, { onSuccess: () => setNewTag('') }); } }}>Thêm</Button>
                            </Space.Compact>
                        )}
                    </Card>

                    <Card title="Ghi chú nội bộ" size="small">
                        <Input.TextArea
                            rows={3} maxLength={2000} disabled={!canUpdate}
                            value={noteDraft ?? order.note ?? ''}
                            onChange={(e) => setNoteDraft(e.target.value)}
                            placeholder="Ghi chú cho đội xử lý đơn…"
                        />
                        {canUpdate && (
                            <Button style={{ marginTop: 8 }} loading={note.isPending} disabled={noteDraft === undefined || noteDraft === (order.note ?? '')}
                                onClick={() => note.mutate(noteDraft?.trim() || null, { onSuccess: () => { message.success('Đã lưu ghi chú'); setNoteDraft(undefined); } })}>
                                Lưu ghi chú
                            </Button>
                        )}
                    </Card>
                </Col>
            </Row>
        </div>
    );
}
