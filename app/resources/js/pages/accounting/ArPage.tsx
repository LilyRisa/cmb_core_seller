import { useState } from 'react';
import { App, Button, Card, DatePicker, Form, Input, InputNumber, Modal, Popconfirm, Radio, Space, Statistic, Table, Tabs, Tag, Tooltip, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { CheckCircleOutlined, CloseCircleOutlined, DollarOutlined, PlusOutlined, ReloadOutlined, TeamOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { formatAmount } from '@/lib/accounting';
import { AgingRow, CustomerReceipt, useArAging, useCancelReceipt, useConfirmReceipt, useCreateReceipt, useReceipts } from '@/lib/accountingAr';
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
        { title: '61-90 ngày', dataIndex: 'b61_90', width: 130, align: 'right', render: (v: number) => v > 0 ? <Typography.Text style={{ color: '#fa8c16' }}>{formatAmount(v)}</Typography.Text> : <Typography.Text type="secondary">0</Typography.Text> },
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
                <Statistic title="Tổng phải thu" value={data?.meta.total_balance ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#1668dc' }} />
                <Statistic title="0-30 ngày" value={data?.meta.total_b0_30 ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} />
                <Statistic title="31-60 ngày" value={data?.meta.total_b31_60 ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#faad14' }} />
                <Statistic title="61-90 ngày" value={data?.meta.total_b61_90 ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#fa8c16' }} />
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

    const columns: ColumnsType<CustomerReceipt> = [
        { title: 'Mã phiếu', dataIndex: 'code', width: 140, render: (c: string) => <Typography.Text strong style={{ fontFamily: 'ui-monospace, monospace' }}>{c}</Typography.Text> },
        { title: 'Ngày thu', dataIndex: 'received_at', width: 130, render: (d: string) => dayjs(d).format('DD/MM/YYYY') },
        { title: 'Khách', dataIndex: 'customer_id', width: 100, render: (id: number | null) => id ? <Tag color="blue">#{id}</Tag> : '—' },
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

    return (
        <Modal
            open={open}
            onCancel={onClose}
            title="Tạo phiếu thu"
            okText="Tạo & xác nhận"
            cancelText="Huỷ"
            destroyOnClose
            confirmLoading={create.isPending || confirmM.isPending}
            onOk={async () => {
                try {
                    const v = await form.validateFields();
                    const created = await create.mutateAsync({
                        customer_id: v.customer_id || undefined,
                        received_at: v.received_at.format('YYYY-MM-DDTHH:mm:ss'),
                        amount: v.amount,
                        payment_method: v.payment_method,
                        memo: v.memo,
                    });
                    // Auto-confirm theo UX phổ biến — kế toán có thể tạo draft riêng nếu cần.
                    await confirmM.mutateAsync(created.id);
                    message.success(`Đã tạo & xác nhận ${created.code}.`);
                    form.resetFields();
                    onClose();
                } catch (e) {
                    if ((e as { errorFields?: unknown }).errorFields) return;
                    message.error(errorMessage(e));
                }
            }}
        >
            <Form form={form} layout="vertical" initialValues={{
                received_at: dayjs(),
                payment_method: 'cash',
                customer_id: presetCustomerId,
            }} preserve={false} key={String(presetCustomerId ?? 'new')}>
                <Form.Item label="Khách hàng (ID)" name="customer_id" tooltip="Bỏ trống nếu thu chung không gắn khách">
                    <InputNumber min={1} style={{ width: '100%' }} placeholder="ID khách" />
                </Form.Item>
                <Form.Item label="Ngày thu" name="received_at" rules={[{ required: true }]}>
                    <DatePicker showTime={{ format: 'HH:mm' }} format="DD/MM/YYYY HH:mm" style={{ width: '100%' }} />
                </Form.Item>
                <Form.Item label="Số tiền (VND)" name="amount" rules={[{ required: true, type: 'number', min: 1 }]}>
                    <InputNumber<number> min={1} step={1000} style={{ width: '100%' }}
                        formatter={(v) => (v ? formatAmount(Number(v)) : '') as `${number}`}
                        parser={(v) => Number((v ?? '').replace(/\D/g, '')) as 0}
                    />
                </Form.Item>
                <Form.Item label="Phương thức" name="payment_method" rules={[{ required: true }]}>
                    <Radio.Group optionType="button" buttonStyle="solid">
                        <Radio.Button value="cash">Tiền mặt (1111)</Radio.Button>
                        <Radio.Button value="bank">Chuyển khoản (1121)</Radio.Button>
                        <Radio.Button value="ewallet">Ví điện tử (1121)</Radio.Button>
                    </Radio.Group>
                </Form.Item>
                <Form.Item label="Diễn giải" name="memo">
                    <Input.TextArea rows={2} maxLength={500} placeholder="vd: Thu nợ đơn ORD-001 và ORD-002" />
                </Form.Item>
            </Form>
        </Modal>
    );
}
