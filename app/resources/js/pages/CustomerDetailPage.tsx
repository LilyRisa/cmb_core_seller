import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { App as AntApp, Alert, Button, Card, Col, Descriptions, Empty, Input, List, Result, Row, Select, Skeleton, Space, Table, Tag, Typography } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { ReputationBadge } from '@/components/ReputationBadge';
import { StatusTag } from '@/components/StatusTag';
import { ChannelBadge } from '@/components/ChannelBadge';
import { MoneyText, DateText } from '@/components/MoneyText';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { CustomerNote, NOTE_SEVERITY_COLOR, useAddCustomerNote, useBlockCustomer, useCustomer, useCustomerOrders, useDeleteCustomerNote } from '@/lib/customers';
import { Order } from '@/lib/orders';

export function CustomerDetailPage() {
    const { id } = useParams();
    const cid = Number(id);
    const { message, modal } = AntApp.useApp();
    const { data: customer, isLoading, isError, error } = useCustomer(id);
    const { data: ordersPage, isFetching: ordersLoading } = useCustomerOrders(id, { per_page: 50 });
    const canNote = useCan('customers.note');
    const canBlock = useCan('customers.block');
    const addNote = useAddCustomerNote(cid);
    const deleteNote = useDeleteCustomerNote(cid);
    const block = useBlockCustomer(cid);
    const [noteText, setNoteText] = useState('');
    const [noteSeverity, setNoteSeverity] = useState<'info' | 'warning' | 'danger'>('info');

    if (isError) return <Result status="error" title="Không tải được khách hàng" subTitle={errorMessage(error)} extra={<Link to="/customers"><Button>Về danh sách</Button></Link>} />;
    if (isLoading || !customer) return <Card><Skeleton active paragraph={{ rows: 8 }} /></Card>;

    const s = customer.lifetime_stats;

    const orderColumns: ColumnsType<Order> = [
        { title: 'Đơn', key: 'order', render: (_, o) => <Space direction="vertical" size={2}><Link to={`/orders/${o.id}`} style={{ fontWeight: 600 }}>{o.order_number ?? o.external_order_id ?? `#${o.id}`}</Link><ChannelBadge provider={o.source} /></Space> },
        { title: 'Tổng tiền', dataIndex: 'grand_total', key: 'total', width: 130, align: 'right', render: (v, o) => <MoneyText value={v} currency={o.currency} strong /> },
        { title: 'Trạng thái', dataIndex: 'status', key: 'status', width: 150, render: (v, o) => <StatusTag status={v} label={o.status_label} rawStatus={o.raw_status} /> },
        { title: 'Đặt lúc', dataIndex: 'placed_at', key: 'placed_at', width: 150, render: (v) => <DateText value={v} /> },
    ];

    const submitNote = () => {
        if (!noteText.trim()) return;
        addNote.mutate({ note: noteText.trim(), severity: noteSeverity }, { onSuccess: () => { setNoteText(''); setNoteSeverity('info'); message.success('Đã thêm ghi chú'); }, onError: (e) => message.error(errorMessage(e)) });
    };

    const toggleBlock = () => {
        if (customer.is_blocked) {
            block.mutate({ block: false }, { onSuccess: () => message.success('Đã bỏ chặn'), onError: (e) => message.error(errorMessage(e)) });
            return;
        }
        let reason = '';
        modal.confirm({
            title: 'Chặn khách hàng này?',
            content: <Input placeholder="Lý do (tuỳ chọn)" onChange={(e) => { reason = e.target.value; }} />,
            okText: 'Chặn', okType: 'danger', cancelText: 'Huỷ',
            onOk: () => block.mutateAsync({ block: true, reason: reason || undefined }).then(() => message.success('Đã chặn khách')).catch((e) => message.error(errorMessage(e))),
        });
    };

    return (
        <div>
            <PageHeader
                title={<Space size="middle">
                    <Link to="/customers"><Button type="text" icon={<ArrowLeftOutlined />} /></Link>
                    <span>{customer.name ?? 'Khách lẻ'}</span>
                    <ReputationBadge label={customer.is_blocked ? 'blocked' : customer.reputation.label} score={customer.reputation.score} showOk />
                    {customer.tags?.map((t) => <Tag key={t} color={t === 'vip' ? 'purple' : 'blue'}>{t}</Tag>)}
                </Space>}
                subtitle={<>Lần đầu <DateText value={customer.first_seen_at} /> · gần nhất <DateText value={customer.last_seen_at} /></>}
                extra={canBlock && <Button danger={!customer.is_blocked} onClick={toggleBlock} loading={block.isPending}>{customer.is_blocked ? 'Bỏ chặn' : 'Chặn khách'}</Button>}
            />

            {customer.is_blocked && <Alert type="error" showIcon style={{ marginBottom: 16 }} message="Khách hàng đã bị chặn" description={customer.block_reason ?? undefined} />}

            <Row gutter={16}>
                <Col xs={24} lg={8}>
                    <Card title="Thông tin" size="small" style={{ marginBottom: 16 }}>
                        <Descriptions column={1} size="small" colon={false}>
                            <Descriptions.Item label="SĐT">{customer.phone ?? customer.phone_masked ?? '—'}</Descriptions.Item>
                            <Descriptions.Item label="Điểm uy tín">{customer.reputation.score}/100</Descriptions.Item>
                            <Descriptions.Item label="Tổng đơn">{s.orders_total}</Descriptions.Item>
                            <Descriptions.Item label="Hoàn thành">{s.orders_completed}</Descriptions.Item>
                            <Descriptions.Item label="Huỷ">{s.orders_cancelled}</Descriptions.Item>
                            <Descriptions.Item label="Hoàn / giao hỏng">{s.orders_returned ?? 0} / {s.orders_delivery_failed ?? 0}</Descriptions.Item>
                            <Descriptions.Item label="Doanh thu (đã hoàn thành)"><MoneyText value={s.revenue_completed ?? 0} /></Descriptions.Item>
                        </Descriptions>
                        {(customer.addresses_meta ?? []).length > 0 && (
                            <>
                                <Typography.Text type="secondary" style={{ fontSize: 12 }}>Địa chỉ đã dùng</Typography.Text>
                                <List size="small" dataSource={customer.addresses_meta} renderItem={(a) => <List.Item style={{ paddingInline: 0 }}><Typography.Text style={{ fontSize: 13 }}>{Object.values(a as Record<string, unknown>).filter(Boolean).join(', ')}</Typography.Text></List.Item>} />
                            </>
                        )}
                    </Card>
                </Col>

                <Col xs={24} lg={16}>
                    <Card title="Ghi chú" size="small" style={{ marginBottom: 16 }}>
                        {canNote && (
                            <Space.Compact style={{ width: '100%', marginBottom: 12 }}>
                                <Select value={noteSeverity} style={{ width: 130 }} onChange={(v) => setNoteSeverity(v)} options={[
                                    { value: 'info', label: 'Thông tin' }, { value: 'warning', label: 'Cảnh báo' }, { value: 'danger', label: 'Nguy hiểm' },
                                ]} />
                                <Input value={noteText} onChange={(e) => setNoteText(e.target.value)} onPressEnter={submitNote} placeholder="Thêm ghi chú về khách hàng…" maxLength={2000} />
                                <Button type="primary" loading={addNote.isPending} onClick={submitNote}>Thêm</Button>
                            </Space.Compact>
                        )}
                        {(customer.notes ?? []).length === 0 ? <Empty description="Chưa có ghi chú" /> : (
                            <List
                                dataSource={customer.notes}
                                renderItem={(n: CustomerNote) => (
                                    <List.Item
                                        actions={canNote && !n.is_auto ? [<Button key="del" type="link" size="small" danger onClick={() => deleteNote.mutate(n.id)}>Xoá</Button>] : []}
                                    >
                                        <Space direction="vertical" size={2} style={{ width: '100%' }}>
                                            <Space size={6}>
                                                <Tag color={NOTE_SEVERITY_COLOR[n.severity] ?? 'default'}>{n.severity}</Tag>
                                                {n.is_auto && <Tag>tự động</Tag>}
                                                {n.order_id && <Link to={`/orders/${n.order_id}`}>đơn #{n.order_id}</Link>}
                                            </Space>
                                            <Typography.Text>{n.note}</Typography.Text>
                                            <Typography.Text type="secondary" style={{ fontSize: 12 }}><DateText value={n.created_at} /></Typography.Text>
                                        </Space>
                                    </List.Item>
                                )}
                            />
                        )}
                    </Card>

                    <Card title="Lịch sử đơn" size="small">
                        <Table<Order> rowKey="id" size="small" loading={ordersLoading} pagination={false}
                            dataSource={ordersPage?.data ?? []} columns={orderColumns}
                            locale={{ emptyText: <Empty description="Chưa có đơn" /> }} />
                    </Card>
                </Col>
            </Row>
        </div>
    );
}
