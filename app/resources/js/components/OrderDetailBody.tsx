import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Alert, App as AntApp, Avatar, Button, Card, Col, Descriptions, Divider, Empty, Input, Row, Space, Table, Tag, Timeline, Typography } from 'antd';
import { LinkOutlined, PrinterOutlined, WarningOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { MoneyText, DateText } from '@/components/MoneyText';
import { CustomerSummaryCard } from '@/components/CustomerSummaryCard';
import { LinkSkusModal } from '@/components/LinkSkusModal';
import { PrintJobBar } from '@/components/OrderProcessing';
import { Order, OrderItem, useOrderNote, useOrderTags } from '@/lib/orders';
import { useCan } from '@/lib/tenant';
import { useCreatePrintJob } from '@/lib/fulfillment';

function AmountRow({ label, value, currency, strong, negative }: { label: string; value: number; currency: string; strong?: boolean; negative?: boolean }) {
    if (!value && !strong) return null;
    return (
        <div style={{ display: 'flex', justifyContent: 'space-between', padding: '4px 0', fontSize: strong ? 16 : 14, fontWeight: strong ? 700 : 400 }}>
            <span style={{ color: strong ? undefined : '#8c8c8c' }}>{label}</span>
            <span style={negative ? { color: '#cf1322' } : undefined}>{negative ? '−' : ''}<MoneyText value={value} currency={currency} /></span>
        </div>
    );
}

/**
 * The body of an order detail (products + status timeline + customer + recipient +
 * tags/note). Shared by the full-page route (`OrderDetailPage`) and the quick-view
 * modal opened from the orders list. Receives a loaded `Order`.
 */
export function OrderDetailBody({ order }: { order: Order }) {
    const { message } = AntApp.useApp();
    const canUpdate = useCan('orders.update');
    const canMap = useCan('inventory.map');
    const canPrint = useCan('fulfillment.print');
    const tags = useOrderTags(order.id);
    const note = useOrderNote(order.id);
    const createPrintJob = useCreatePrintJob();
    const [newTag, setNewTag] = useState('');
    const [noteDraft, setNoteDraft] = useState<string | undefined>(undefined);
    const [linkOpen, setLinkOpen] = useState(false);
    const [printJobId, setPrintJobId] = useState<number | null>(null);

    const addr: Record<string, string | undefined> = order.shipping_address ?? {};
    const itemColumns: ColumnsType<OrderItem> = [
        { title: 'Sản phẩm', key: 'name', render: (_, it) => (
            <Space>
                <Avatar shape="square" size={48} src={it.image ?? undefined} style={{ background: '#f0f0f0' }}>SP</Avatar>
                <Space direction="vertical" size={0}>
                    <span style={{ fontWeight: 500 }}>{it.name}</span>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>{it.variation ?? ''} {it.seller_sku ? `· SKU: ${it.seller_sku}` : ''}</Typography.Text>
                    {!it.is_mapped && <Tag color="warning" style={{ marginTop: 2 }}>Chưa ghép SKU</Tag>}
                </Space>
            </Space>
        ) },
        { title: 'Đơn giá', dataIndex: 'unit_price', key: 'unit_price', width: 120, align: 'right', render: (v) => <MoneyText value={v} currency={order.currency} /> },
        { title: 'SL', dataIndex: 'quantity', key: 'quantity', width: 60, align: 'center' },
        { title: 'Thành tiền', dataIndex: 'subtotal', key: 'subtotal', width: 130, align: 'right', render: (v) => <MoneyText value={v} currency={order.currency} strong /> },
    ];

    return (
        <>
            {order.issue_reason === 'SKU chưa ghép'
                ? <Alert type="warning" showIcon icon={<LinkOutlined />} style={{ marginBottom: 16 }} message="Đơn này chưa liên kết SKU" description="Vẫn in phiếu & bàn giao bình thường — KHÔNG trừ tồn cho các dòng chưa ghép. Liên kết SKU sàn ↔ master SKU để theo dõi tồn kho chính xác."
                    action={canMap ? <Button icon={<LinkOutlined />} onClick={() => setLinkOpen(true)}>Liên kết SKU</Button> : undefined} />
                : order.has_issue && <Alert type="warning" showIcon icon={<WarningOutlined />} style={{ marginBottom: 16 }} message="Đơn hàng có vấn đề" description={order.issue_reason ?? 'Vui lòng kiểm tra.'} />}
            <LinkSkusModal open={linkOpen} orderIds={[order.id]} onClose={() => setLinkOpen(false)} />
            {printJobId != null && <PrintJobBar jobId={printJobId} onClose={() => setPrintJobId(null)} />}

            <Row gutter={16}>
                <Col xs={24} lg={16}>
                    <Card title="Sản phẩm" style={{ marginBottom: 16 }}
                        extra={canPrint && <Button icon={<PrinterOutlined />} loading={createPrintJob.isPending}
                            onClick={() => createPrintJob.mutate({ type: 'invoice', order_ids: [order.id] }, {
                                onSuccess: (j) => setPrintJobId(j.id), onError: () => message.error('Không tạo được phiếu in'),
                            })}>In hoá đơn</Button>}>
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
                            {order.profit && (
                                <>
                                    <Divider style={{ margin: '8px 0' }} />
                                    <AmountRow label={`Phí sàn (${order.profit.platform_fee_pct}%)`} value={order.profit.platform_fee} currency={order.currency} negative />
                                    <AmountRow label="Giá vốn hàng" value={order.profit.cogs} currency={order.currency} negative />
                                    <div style={{ display: 'flex', justifyContent: 'space-between', padding: '4px 0', fontSize: 15, fontWeight: 700, color: order.profit.estimated_profit >= 0 ? '#389e0d' : '#cf1322' }}>
                                        <span>Lợi nhuận ước tính{!order.profit.cost_complete && <WarningOutlined style={{ color: '#faad14', marginLeft: 6 }} />}</span>
                                        <MoneyText value={order.profit.estimated_profit} currency={order.currency} />
                                    </div>
                                    {!order.profit.cost_complete && <Typography.Text type="secondary" style={{ fontSize: 12 }}>* Một số mặt hàng chưa có giá vốn SKU — lợi nhuận chỉ là ước tính.</Typography.Text>}
                                </>
                            )}
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

                    <Card title="Vận đơn" size="small" style={{ marginBottom: 16 }}>
                        {order.shipment ? (
                            <Space direction="vertical" size={2}>
                                <span><b>{order.shipment.carrier.toUpperCase()}</b> · <Tag>{order.shipment.status}</Tag></span>
                                <Typography.Text copyable={!!order.shipment.tracking_no}>{order.shipment.tracking_no ?? '(chưa có mã vận đơn)'}</Typography.Text>
                                {order.shipment.label_url && <a href={order.shipment.label_url} target="_blank" rel="noreferrer">In tem vận đơn</a>}
                            </Space>
                        ) : (
                            <Typography.Text type="secondary">Chưa có vận đơn. Tạo & in tem ở <Link to="/fulfillment">Giao hàng &amp; in</Link>.</Typography.Text>
                        )}
                    </Card>

                    <Card title="Người nhận" size="small" style={{ marginBottom: 16 }}>
                        <Descriptions column={1} size="small" colon={false}>
                            <Descriptions.Item label="Tên">{addr.fullName ?? addr.name ?? order.buyer_name ?? '—'}</Descriptions.Item>
                            <Descriptions.Item label="SĐT">{addr.phone ?? order.buyer_phone_masked ?? '—'}</Descriptions.Item>
                            <Descriptions.Item label="Địa chỉ">{[addr.line1, addr.address, addr.ward, addr.district, addr.province, addr.country].filter(Boolean).join(', ') || '—'}</Descriptions.Item>
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
        </>
    );
}
