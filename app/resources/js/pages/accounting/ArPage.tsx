import { useMemo, useState } from 'react';
import { Alert, App, Button, Card, DatePicker, Empty, Form, Input, InputNumber, Modal, Popconfirm, Radio, Space, Statistic, Switch, Table, Tabs, Tag, Tooltip, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { CheckCircleOutlined, CloseCircleOutlined, DollarOutlined, PlusOutlined, ReloadOutlined, TeamOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { formatAmount, usePartiesByIds } from '@/lib/accounting';
import { AgingRow, CustomerReceipt, useArAging, useCancelReceipt, useConfirmReceipt, useCreateReceipt, useReceipts } from '@/lib/accountingAr';
import { useCustomerOrders } from '@/lib/customers';
import type { Order } from '@/lib/orders';
import { PartyPicker } from '@/components/accounting/PartyPicker';
import { AccountingSetupBanner } from './AccountingSetupBanner';
import { useCan } from '@/lib/tenant';
import { errorMessage } from '@/lib/api';

export function ArPage() {
    const [tab, setTab] = useState<'aging' | 'receipts'>('aging');
    const [createOpen, setCreateOpen] = useState(false);
    const [presetCustomerId, setPresetCustomerId] = useState<number | undefined>();
    const canPost = useCan('accounting.post');

    return (
        <div style={{ padding: '8px 0' }}>
            <AccountingSetupBanner />
            <Card
                title={<Typography.Title level={5} style={{ margin: 0 }}>Công nợ phải thu khách hàng (TK 131)</Typography.Title>}
                extra={canPost && (
                    <Button type="primary" icon={<PlusOutlined />} onClick={() => { setPresetCustomerId(undefined); setCreateOpen(true); }}>
                        Tạo phiếu thu
                    </Button>
                )}
                styles={{ body: { padding: 0 } }}
            >
                <Tabs
                    activeKey={tab}
                    onChange={(k) => setTab(k as 'aging' | 'receipts')}
                    style={{ padding: '0 16px' }}
                    items={[
                        { key: 'aging', label: <span><TeamOutlined /> Aging theo khách</span>, children: <AgingTab onCreateForCustomer={(id) => { setPresetCustomerId(id); setCreateOpen(true); }} /> },
                        { key: 'receipts', label: <span><DollarOutlined /> Phiếu thu</span>, children: <ReceiptsTab /> },
                    ]}
                />
            </Card>

            <CreateReceiptModal open={createOpen} onClose={() => setCreateOpen(false)} presetCustomerId={presetCustomerId} />
        </div>
    );
}

function AgingTab({ onCreateForCustomer }: { onCreateForCustomer: (id: number) => void }) {
    const { data, isFetching, refetch } = useArAging();
    const canPost = useCan('accounting.post');

    const columns: ColumnsType<AgingRow> = [
        {
            title: 'Khách hàng',
            dataIndex: 'customer_name',
            render: (n: string | null, r) => (
                <Space size={6}>
                    <Typography.Text strong>{n ?? `Khách #${r.customer_id}`}</Typography.Text>
                    {r.reputation_label && r.reputation_label !== 'ok' && (
                        <Tag color={r.reputation_label === 'risk' ? 'red' : r.reputation_label === 'watch' ? 'orange' : 'volcano'} style={{ marginInlineEnd: 0, fontSize: 11 }}>
                            {r.reputation_label}
                        </Tag>
                    )}
                </Space>
            ),
        },
        { title: '0-30 ngày', dataIndex: 'b0_30', width: 130, align: 'right', render: (v: number) => v > 0 ? <Typography.Text>{formatAmount(v)}</Typography.Text> : <Typography.Text type="secondary">0</Typography.Text> },
        { title: '31-60 ngày', dataIndex: 'b31_60', width: 130, align: 'right', render: (v: number) => v > 0 ? <Typography.Text style={{ color: '#faad14' }}>{formatAmount(v)}</Typography.Text> : <Typography.Text type="secondary">0</Typography.Text> },
        { title: '61-90 ngày', dataIndex: 'b61_90', width: 130, align: 'right', render: (v: number) => v > 0 ? <Typography.Text style={{ color: '#F59E0B' }}>{formatAmount(v)}</Typography.Text> : <Typography.Text type="secondary">0</Typography.Text> },
        { title: '> 90 ngày', dataIndex: 'b90p', width: 130, align: 'right', render: (v: number) => v > 0 ? <Typography.Text strong style={{ color: '#cf1322' }}>{formatAmount(v)}</Typography.Text> : <Typography.Text type="secondary">0</Typography.Text> },
        { title: 'Tổng nợ', dataIndex: 'total', width: 150, align: 'right', render: (v: number) => <Typography.Text strong>{formatAmount(v)} ₫</Typography.Text> },
        {
            title: 'Thao tác',
            width: 130,
            align: 'right',
            render: (_, r) => canPost ? (
                <Button size="small" type="link" icon={<DollarOutlined />} onClick={() => onCreateForCustomer(r.customer_id)}>
                    Tạo phiếu thu
                </Button>
            ) : null,
        },
    ];

    return (
        <div>
            <div style={{ display: 'flex', gap: 12, marginBottom: 16, alignItems: 'center', justifyContent: 'flex-end' }}>
                <Tooltip title="Tải lại"><Button icon={<ReloadOutlined />} onClick={() => refetch()} /></Tooltip>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 16, marginBottom: 16 }}>
                <Statistic title="Tổng phải thu" value={data?.meta.total_balance ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#2563EB' }} />
                <Statistic title="0-30 ngày" value={data?.meta.total_b0_30 ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} />
                <Statistic title="31-60 ngày" value={data?.meta.total_b31_60 ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#faad14' }} />
                <Statistic title="61-90 ngày" value={data?.meta.total_b61_90 ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#F59E0B' }} />
                <Statistic title="> 90 ngày" value={data?.meta.total_b90p ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#cf1322' }} />
            </div>
            <Table<AgingRow>
                rowKey="customer_id"
                dataSource={data?.data ?? []}
                columns={columns}
                loading={isFetching}
                pagination={{ pageSize: 25, showSizeChanger: true, pageSizeOptions: [10, 25, 50, 100] }}
                size="middle"
                scroll={{ x: 1100 }}
                locale={{ emptyText: 'Chưa có khách hàng nào có công nợ. Khi đơn shipped, hệ thống tự ghi sổ TK 131.' }}
            />
        </div>
    );
}

function ReceiptsTab() {
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(20);
    const { data, isFetching } = useReceipts({ page, per_page: perPage });
    const canPost = useCan('accounting.post');
    const confirmM = useConfirmReceipt();
    const cancelM = useCancelReceipt();
    const { message } = App.useApp();

    const custIds = useMemo(() => Array.from(new Set((data?.data ?? []).map((r) => r.customer_id).filter((x): x is number => x != null))), [data]);
    const { data: parties = [] } = usePartiesByIds('customer', custIds);
    const nameMap = useMemo(() => new Map(parties.map((p) => [p.id, p.label])), [parties]);

    const columns: ColumnsType<CustomerReceipt> = [
        { title: 'Mã phiếu', dataIndex: 'code', width: 140, render: (c: string) => <Typography.Text strong style={{ fontFamily: 'ui-monospace, monospace' }}>{c}</Typography.Text> },
        { title: 'Ngày thu', dataIndex: 'received_at', width: 130, render: (d: string) => dayjs(d).format('DD/MM/YYYY') },
        { title: 'Khách', dataIndex: 'customer_id', width: 180, render: (id: number | null) => id ? <Typography.Text>{nameMap.get(id) ?? `Khách #${id}`}</Typography.Text> : <Typography.Text type="secondary">Thu chung</Typography.Text> },
        { title: 'Số tiền', dataIndex: 'amount', width: 150, align: 'right', render: (v: number) => <Typography.Text strong>{formatAmount(v)} ₫</Typography.Text> },
        { title: 'Phương thức', dataIndex: 'payment_method', width: 130, render: (m: string) => <Tag>{m === 'cash' ? 'Tiền mặt' : m === 'bank' ? 'Chuyển khoản' : 'Ví điện tử'}</Tag> },
        { title: 'Diễn giải', dataIndex: 'memo', ellipsis: true, render: (n: string | null) => n ?? '—' },
        { title: 'Trạng thái', dataIndex: 'status', width: 120, render: (s: string, r) => <Tag color={s === 'confirmed' ? 'green' : s === 'cancelled' ? 'default' : 'gold'}>{r.status_label}</Tag> },
        {
            title: 'Thao tác',
            width: 200,
            align: 'right',
            render: (_, r) => canPost && r.status === 'draft' ? (
                <Space size={4}>
                    <Popconfirm
                        title="Xác nhận phiếu thu?"
                        description="Hệ thống ghi sổ Dr 1111/1121 / Cr 131 — cấn trừ công nợ khách."
                        okText="Xác nhận"
                        cancelText="Huỷ"
                        onConfirm={async () => {
                            try { await confirmM.mutateAsync(r.id); message.success(`Đã xác nhận ${r.code}.`); }
                            catch (e) { message.error(errorMessage(e)); }
                        }}
                    >
                        <Button size="small" type="primary" ghost icon={<CheckCircleOutlined />}>Xác nhận</Button>
                    </Popconfirm>
                    <Popconfirm
                        title="Huỷ phiếu thu?"
                        okText="Huỷ"
                        cancelText="Đóng"
                        okButtonProps={{ danger: true }}
                        onConfirm={async () => {
                            try { await cancelM.mutateAsync(r.id); message.success('Đã huỷ.'); }
                            catch (e) { message.error(errorMessage(e)); }
                        }}
                    >
                        <Button size="small" type="text" icon={<CloseCircleOutlined />} danger />
                    </Popconfirm>
                </Space>
            ) : null,
        },
    ];

    return (
        <Table<CustomerReceipt>
            rowKey="id"
            dataSource={data?.data ?? []}
            columns={columns}
            loading={isFetching}
            pagination={{
                current: page,
                pageSize: perPage,
                total: data?.meta.total ?? 0,
                showSizeChanger: true,
                pageSizeOptions: [10, 20, 50, 100],
                onChange: (p, ps) => { setPage(p); setPerPage(ps); },
            }}
            size="middle"
            scroll={{ x: 1000 }}
            locale={{ emptyText: 'Chưa có phiếu thu nào.' }}
        />
    );
}

function CreateReceiptModal({ open, onClose, presetCustomerId }: { open: boolean; onClose: () => void; presetCustomerId?: number }) {
    const [form] = Form.useForm();
    const create = useCreateReceipt();
    const confirmM = useConfirmReceipt();
    const { message } = App.useApp();
    const [allocate, setAllocate] = useState(false);
    const [alloc, setAlloc] = useState<Record<number, number>>({});

    const customerId = Form.useWatch('customer_id', form) as number | undefined;
    const amount = (Form.useWatch('amount', form) as number | undefined) ?? 0;
    const allocTotal = Object.values(alloc).reduce((s, v) => s + (Number(v) || 0), 0);

    const reset = () => { form.resetFields(); setAlloc({}); setAllocate(false); };

    const submit = async (confirm: boolean) => {
        try {
            const v = await form.validateFields();
            const appliedOrders = allocate
                ? Object.entries(alloc).filter(([, amt]) => Number(amt) > 0).map(([oid, amt]) => ({ order_id: Number(oid), applied_amount: Number(amt) }))
                : undefined;
            const created = await create.mutateAsync({
                customer_id: v.customer_id || undefined,
                received_at: v.received_at.format('YYYY-MM-DDTHH:mm:ss'),
                amount: v.amount,
                payment_method: v.payment_method,
                applied_orders: appliedOrders,
                memo: v.memo,
            });
            if (confirm) {
                await confirmM.mutateAsync(created.id);
                message.success(`Đã tạo & xác nhận phiếu thu ${created.code}.`);
            } else {
                message.success(`Đã lưu nháp phiếu thu ${created.code}.`);
            }
            reset();
            onClose();
        } catch (e) {
            if ((e as { errorFields?: unknown }).errorFields) return;
            message.error(errorMessage(e));
        }
    };

    const busy = create.isPending || confirmM.isPending;

    return (
        <Modal
            open={open}
            onCancel={() => { reset(); onClose(); }}
            title="Tạo phiếu thu"
            width={720}
            destroyOnClose
            footer={[
                <Button key="cancel" onClick={() => { reset(); onClose(); }}>Huỷ</Button>,
                <Button key="draft" loading={busy} onClick={() => submit(false)}>Lưu nháp</Button>,
                <Button key="confirm" type="primary" loading={busy} onClick={() => submit(true)}>Tạo & xác nhận (ghi sổ)</Button>,
            ]}
        >
            <Form form={form} layout="vertical" initialValues={{
                received_at: dayjs(),
                payment_method: 'cash',
                customer_id: presetCustomerId,
            }} preserve={false} key={String(presetCustomerId ?? 'new')}>
                <Form.Item label="Khách hàng" name="customer_id" tooltip="Bỏ trống nếu thu chung, không gắn công nợ khách cụ thể.">
                    <PartyPicker type="customer" placeholder="Tìm khách theo tên / SĐT… (bỏ trống = thu chung)" />
                </Form.Item>
                <Space size={12} style={{ width: '100%' }} align="start">
                    <Form.Item label="Ngày thu" name="received_at" rules={[{ required: true }]} style={{ flex: 1, minWidth: 200 }}>
                        <DatePicker showTime={{ format: 'HH:mm' }} format="DD/MM/YYYY HH:mm" style={{ width: '100%' }} />
                    </Form.Item>
                    <Form.Item label="Số tiền (VND)" name="amount" rules={[{ required: true, type: 'number', min: 1 }]} style={{ flex: 1, minWidth: 200 }}>
                        <InputNumber<number> min={1} step={1000} style={{ width: '100%' }}
                            formatter={(v) => (v ? formatAmount(Number(v)) : '') as `${number}`}
                            parser={(v) => Number((v ?? '').replace(/\D/g, '')) as 0}
                        />
                    </Form.Item>
                </Space>
                <Form.Item label="Phương thức" name="payment_method" rules={[{ required: true }]}>
                    <Radio.Group optionType="button" buttonStyle="solid">
                        <Radio.Button value="cash">Tiền mặt (1111)</Radio.Button>
                        <Radio.Button value="bank">Chuyển khoản (1121)</Radio.Button>
                        <Radio.Button value="ewallet">Ví điện tử (1121)</Radio.Button>
                    </Radio.Group>
                </Form.Item>

                <Form.Item label={<Space><span>Cấn trừ theo đơn hàng</span><Switch size="small" checked={allocate} onChange={setAllocate} disabled={!customerId} /></Space>}
                    tooltip="Phân bổ số tiền thu vào từng đơn hàng để theo dõi công nợ chi tiết.">
                    {allocate && customerId && (
                        <AllocateOrders customerId={customerId} value={alloc} onChange={setAlloc} receiptAmount={amount} allocated={allocTotal} />
                    )}
                    {allocate && !customerId && <Typography.Text type="secondary">Chọn khách hàng trước để cấn trừ theo đơn.</Typography.Text>}
                </Form.Item>

                <Form.Item label="Diễn giải" name="memo">
                    <Input.TextArea rows={2} maxLength={500} placeholder="vd: Thu nợ đơn ORD-001 và ORD-002" />
                </Form.Item>
            </Form>
        </Modal>
    );
}

/** Bảng phân bổ số tiền thu vào từng đơn hàng của khách (applied_orders). */
function AllocateOrders({ customerId, value, onChange, receiptAmount, allocated }: {
    customerId: number;
    value: Record<number, number>;
    onChange: (v: Record<number, number>) => void;
    receiptAmount: number;
    allocated: number;
}) {
    const { data, isFetching } = useCustomerOrders(customerId, { per_page: 50 });
    const orders = data?.data ?? [];
    const remaining = receiptAmount - allocated;

    if (isFetching) return <Typography.Text type="secondary">Đang tải đơn hàng…</Typography.Text>;
    if (orders.length === 0) return <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Khách chưa có đơn hàng" />;

    return (
        <div>
            <Space style={{ marginBottom: 8 }} size={16} wrap>
                <Typography.Text>Đã phân bổ: <Typography.Text strong>{formatAmount(allocated)} ₫</Typography.Text></Typography.Text>
                <Typography.Text type={remaining < 0 ? 'danger' : 'secondary'}>Còn lại: {formatAmount(remaining)} ₫</Typography.Text>
            </Space>
            {remaining < 0 && <Alert type="warning" showIcon style={{ marginBottom: 8 }} message="Tổng phân bổ vượt quá số tiền thu." />}
            <Table<Order>
                rowKey="id"
                dataSource={orders}
                pagination={false}
                size="small"
                scroll={{ y: 220 }}
                columns={[
                    { title: 'Đơn', dataIndex: 'order_number', render: (n: string | null, o) => n ?? `#${o.id}` },
                    { title: 'Giá trị', dataIndex: 'grand_total', width: 130, align: 'right', render: (v: number) => formatAmount(v ?? 0) },
                    {
                        title: 'Cấn trừ (₫)',
                        width: 170,
                        render: (_, o) => (
                            <Space size={4}>
                                <InputNumber<number>
                                    min={0}
                                    step={1000}
                                    value={value[o.id] ?? 0}
                                    style={{ width: 120 }}
                                    formatter={(v) => (v ? formatAmount(Number(v)) : '') as `${number}`}
                                    parser={(v) => Number((v ?? '').replace(/\D/g, '')) as 0}
                                    onChange={(amt) => onChange({ ...value, [o.id]: Number(amt) || 0 })}
                                />
                                <Tooltip title="Cấn trừ toàn bộ giá trị đơn">
                                    <Button size="small" type="link" onClick={() => onChange({ ...value, [o.id]: Number(o.grand_total) || 0 })}>Hết</Button>
                                </Tooltip>
                            </Space>
                        ),
                    },
                ]}
            />
        </div>
    );
}
