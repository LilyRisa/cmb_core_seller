import { useState } from 'react';
import { App, Button, Card, DatePicker, Form, Input, InputNumber, Modal, Popconfirm, Radio, Space, Statistic, Table, Tabs, Tag, Tooltip, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { BankOutlined, CheckCircleOutlined, DollarOutlined, FileTextOutlined, ReloadOutlined, ShopOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { formatAmount } from '@/lib/accounting';
import { ApAgingRow, useApAging, useConfirmPayment, useCreateBill, useCreatePayment, useRecordBill, useVendorBills, useVendorPayments, VendorBill, VendorPayment } from '@/lib/accountingAp';
import { AccountingSetupBanner } from './AccountingSetupBanner';
import { useCan } from '@/lib/tenant';
import { errorMessage } from '@/lib/api';

export function ApPage() {
    const [tab, setTab] = useState<'aging' | 'bills' | 'payments'>('aging');
    const [createBillOpen, setCreateBillOpen] = useState(false);
    const [createPaymentOpen, setCreatePaymentOpen] = useState(false);
    const [presetSupplierId, setPresetSupplierId] = useState<number | undefined>();
    const canPost = useCan('accounting.post');

    return (
        <div style={{ padding: '8px 0' }}>
            <AccountingSetupBanner />
            <Card
                title={<Typography.Title level={5} style={{ margin: 0 }}>Công nợ phải trả NCC (TK 331)</Typography.Title>}
                extra={canPost && (
                    <Space>
                        <Button icon={<FileTextOutlined />} onClick={() => { setPresetSupplierId(undefined); setCreateBillOpen(true); }}>Nhập hoá đơn NCC</Button>
                        <Button type="primary" icon={<DollarOutlined />} onClick={() => { setPresetSupplierId(undefined); setCreatePaymentOpen(true); }}>Tạo phiếu chi</Button>
                    </Space>
                )}
                styles={{ body: { padding: 0 } }}
            >
                <Tabs
                    activeKey={tab}
                    onChange={(k) => setTab(k as typeof tab)}
                    style={{ padding: '0 16px' }}
                    items={[
                        { key: 'aging', label: <span><ShopOutlined /> Aging theo NCC</span>, children: <ApAgingTab onCreatePayment={(id) => { setPresetSupplierId(id); setCreatePaymentOpen(true); }} /> },
                        { key: 'bills', label: <span><FileTextOutlined /> Hoá đơn NCC</span>, children: <BillsTab /> },
                        { key: 'payments', label: <span><BankOutlined /> Phiếu chi</span>, children: <PaymentsTab /> },
                    ]}
                />
            </Card>

            <CreateBillModal open={createBillOpen} onClose={() => setCreateBillOpen(false)} presetSupplierId={presetSupplierId} />
            <CreatePaymentModal open={createPaymentOpen} onClose={() => setCreatePaymentOpen(false)} presetSupplierId={presetSupplierId} />
        </div>
    );
}

function ApAgingTab({ onCreatePayment }: { onCreatePayment: (id: number) => void }) {
    const { data, isFetching, refetch } = useApAging();
    const canPost = useCan('accounting.post');

    const columns: ColumnsType<ApAgingRow> = [
        {
            title: 'Nhà cung cấp',
            render: (_, r) => (
                <Space size={6}>
                    {r.supplier_code && <Tag style={{ marginInlineEnd: 0, fontFamily: 'ui-monospace, monospace' }}>{r.supplier_code}</Tag>}
                    <Typography.Text strong>{r.supplier_name ?? `NCC #${r.supplier_id}`}</Typography.Text>
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
                <Button size="small" type="link" icon={<DollarOutlined />} onClick={() => onCreatePayment(r.supplier_id)}>Tạo phiếu chi</Button>
            ) : null,
        },
    ];

    return (
        <div>
            <div style={{ display: 'flex', gap: 12, marginBottom: 16, alignItems: 'center', justifyContent: 'flex-end' }}>
                <Tooltip title="Tải lại"><Button icon={<ReloadOutlined />} onClick={() => refetch()} /></Tooltip>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 16, marginBottom: 16 }}>
                <Statistic title="Tổng phải trả" value={data?.meta.total_balance ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#fa541c' }} />
                <Statistic title="0-30 ngày" value={data?.meta.total_b0_30 ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} />
                <Statistic title="31-60 ngày" value={data?.meta.total_b31_60 ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#faad14' }} />
                <Statistic title="61-90 ngày" value={data?.meta.total_b61_90 ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#fa8c16' }} />
                <Statistic title="> 90 ngày" value={data?.meta.total_b90p ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#cf1322' }} />
            </div>
            <Table<ApAgingRow>
                rowKey="supplier_id"
                dataSource={data?.data ?? []}
                columns={columns}
                loading={isFetching}
                pagination={{ pageSize: 25, showSizeChanger: true, pageSizeOptions: [10, 25, 50, 100] }}
                size="middle"
                scroll={{ x: 1100 }}
                locale={{ emptyText: 'Không có công nợ NCC nào. Khi ghi sổ hoá đơn NCC ⇒ TK 331 tự cập nhật.' }}
            />
        </div>
    );
}

function BillsTab() {
    const [page, setPage] = useState(1);
    const { data, isFetching } = useVendorBills({ page, per_page: 20 });
    const record = useRecordBill();
    const { message } = App.useApp();
    const canPost = useCan('accounting.post');

    const columns: ColumnsType<VendorBill> = [
        { title: 'Mã HĐ', dataIndex: 'code', width: 150, render: (c: string) => <Typography.Text strong style={{ fontFamily: 'ui-monospace, monospace' }}>{c}</Typography.Text> },
        { title: 'Số HĐ NCC', dataIndex: 'bill_no', width: 140, render: (n: string | null) => n ?? '—' },
        { title: 'NCC', dataIndex: 'supplier_id', width: 100, render: (id: number | null) => id ? <Tag color="blue">#{id}</Tag> : '—' },
        { title: 'Ngày HĐ', dataIndex: 'bill_date', width: 110, render: (d: string) => dayjs(d).format('DD/MM/YYYY') },
        { title: 'Hạn TT', dataIndex: 'due_date', width: 110, render: (d: string | null) => d ? dayjs(d).format('DD/MM/YYYY') : '—' },
        { title: 'Tiền hàng', dataIndex: 'subtotal', width: 130, align: 'right', render: (v: number) => formatAmount(v) },
        { title: 'VAT', dataIndex: 'tax', width: 110, align: 'right', render: (v: number) => v > 0 ? formatAmount(v) : <Typography.Text type="secondary">0</Typography.Text> },
        { title: 'Tổng', dataIndex: 'total', width: 130, align: 'right', render: (v: number) => <Typography.Text strong>{formatAmount(v)}</Typography.Text> },
        { title: 'Trạng thái', dataIndex: 'status', width: 120, render: (s: string, r) => <Tag color={s === 'recorded' ? 'green' : s === 'paid' ? 'blue' : s === 'void' ? 'default' : 'gold'}>{r.status_label}</Tag> },
        {
            title: 'Thao tác',
            width: 130,
            align: 'right',
            render: (_, r) => canPost && r.status === 'draft' ? (
                <Popconfirm
                    title="Ghi sổ hoá đơn?"
                    description="Hệ thống ghi Dr 1561 (+1331) / Cr 331."
                    onConfirm={async () => {
                        try { await record.mutateAsync(r.id); message.success(`Đã ghi sổ ${r.code}.`); }
                        catch (e) { message.error(errorMessage(e)); }
                    }}
                >
                    <Button size="small" type="primary" ghost icon={<CheckCircleOutlined />}>Ghi sổ</Button>
                </Popconfirm>
            ) : null,
        },
    ];

    return (
        <Table<VendorBill>
            rowKey="id"
            dataSource={data?.data ?? []}
            columns={columns}
            loading={isFetching}
            pagination={{ current: page, pageSize: 20, total: data?.meta.total ?? 0, onChange: setPage, showSizeChanger: false }}
            size="middle"
            scroll={{ x: 1300 }}
            locale={{ emptyText: 'Chưa có hoá đơn NCC.' }}
        />
    );
}

function PaymentsTab() {
    const [page, setPage] = useState(1);
    const { data, isFetching } = useVendorPayments({ page, per_page: 20 });
    const confirmM = useConfirmPayment();
    const { message } = App.useApp();
    const canPost = useCan('accounting.post');

    const columns: ColumnsType<VendorPayment> = [
        { title: 'Mã phiếu', dataIndex: 'code', width: 140, render: (c: string) => <Typography.Text strong style={{ fontFamily: 'ui-monospace, monospace' }}>{c}</Typography.Text> },
        { title: 'Ngày chi', dataIndex: 'paid_at', width: 130, render: (d: string) => dayjs(d).format('DD/MM/YYYY') },
        { title: 'NCC', dataIndex: 'supplier_id', width: 90, render: (id: number | null) => id ? <Tag color="blue">#{id}</Tag> : '—' },
        { title: 'Số tiền', dataIndex: 'amount', width: 150, align: 'right', render: (v: number) => <Typography.Text strong>{formatAmount(v)} ₫</Typography.Text> },
        { title: 'Phương thức', dataIndex: 'payment_method', width: 130, render: (m: string) => <Tag>{m === 'cash' ? 'Tiền mặt' : m === 'bank' ? 'Chuyển khoản' : 'Ví điện tử'}</Tag> },
        { title: 'Diễn giải', dataIndex: 'memo', ellipsis: true, render: (n: string | null) => n ?? '—' },
        { title: 'Trạng thái', dataIndex: 'status', width: 120, render: (s: string, r) => <Tag color={s === 'confirmed' ? 'green' : s === 'cancelled' ? 'default' : 'gold'}>{r.status_label}</Tag> },
        {
            title: 'Thao tác',
            width: 130,
            align: 'right',
            render: (_, r) => canPost && r.status === 'draft' ? (
                <Popconfirm
                    title="Xác nhận phiếu chi?"
                    description="Hệ thống ghi Dr 331 / Cr 1111|1121."
                    onConfirm={async () => {
                        try { await confirmM.mutateAsync(r.id); message.success(`Đã chi ${r.code}.`); }
                        catch (e) { message.error(errorMessage(e)); }
                    }}
                >
                    <Button size="small" type="primary" ghost icon={<CheckCircleOutlined />}>Xác nhận</Button>
                </Popconfirm>
            ) : null,
        },
    ];

    return (
        <Table<VendorPayment>
            rowKey="id"
            dataSource={data?.data ?? []}
            columns={columns}
            loading={isFetching}
            pagination={{ current: page, pageSize: 20, total: data?.meta.total ?? 0, onChange: setPage, showSizeChanger: false }}
            size="middle"
            scroll={{ x: 1100 }}
            locale={{ emptyText: 'Chưa có phiếu chi nào.' }}
        />
    );
}

function CreateBillModal({ open, onClose, presetSupplierId }: { open: boolean; onClose: () => void; presetSupplierId?: number }) {
    const [form] = Form.useForm();
    const create = useCreateBill();
    const record = useRecordBill();
    const { message } = App.useApp();

    return (
        <Modal
            open={open}
            onCancel={onClose}
            title="Nhập hoá đơn NCC"
            okText="Lưu & ghi sổ"
            cancelText="Huỷ"
            destroyOnClose
            confirmLoading={create.isPending || record.isPending}
            onOk={async () => {
                try {
                    const v = await form.validateFields();
                    const created = await create.mutateAsync({
                        supplier_id: v.supplier_id || undefined,
                        bill_no: v.bill_no || undefined,
                        bill_date: v.bill_date.format('YYYY-MM-DDTHH:mm:ss'),
                        due_date: v.due_date ? v.due_date.format('YYYY-MM-DD') : undefined,
                        subtotal: v.subtotal,
                        tax: v.tax || 0,
                        memo: v.memo,
                    });
                    await record.mutateAsync(created.id);
                    message.success(`Đã ghi sổ ${created.code}.`);
                    form.resetFields();
                    onClose();
                } catch (e) {
                    if ((e as { errorFields?: unknown }).errorFields) return;
                    message.error(errorMessage(e));
                }
            }}
        >
            <Form form={form} layout="vertical" initialValues={{ bill_date: dayjs(), tax: 0, supplier_id: presetSupplierId }} preserve={false} key={String(presetSupplierId ?? 'new')}>
                <Form.Item label="NCC (ID)" name="supplier_id" rules={[{ required: true }]}>
                    <InputNumber min={1} style={{ width: '100%' }} placeholder="ID nhà cung cấp" />
                </Form.Item>
                <Form.Item label="Số hoá đơn NCC" name="bill_no"><Input maxLength={64} /></Form.Item>
                <Space size={12} style={{ width: '100%' }}>
                    <Form.Item label="Ngày HĐ" name="bill_date" rules={[{ required: true }]} style={{ flex: 1 }}>
                        <DatePicker format="DD/MM/YYYY" style={{ width: '100%' }} />
                    </Form.Item>
                    <Form.Item label="Hạn TT" name="due_date" style={{ flex: 1 }}>
                        <DatePicker format="DD/MM/YYYY" style={{ width: '100%' }} />
                    </Form.Item>
                </Space>
                <Form.Item label="Tiền hàng (chưa VAT)" name="subtotal" rules={[{ required: true, type: 'number', min: 0 }]}>
                    <InputNumber<number> min={0} step={1000} style={{ width: '100%' }}
                        formatter={(v) => (v ? formatAmount(Number(v)) : '') as `${number}`}
                        parser={(v) => Number((v ?? '').replace(/\D/g, '')) as 0}
                    />
                </Form.Item>
                <Form.Item label="VAT" name="tax">
                    <InputNumber<number> min={0} step={1000} style={{ width: '100%' }}
                        formatter={(v) => (v ? formatAmount(Number(v)) : '') as `${number}`}
                        parser={(v) => Number((v ?? '').replace(/\D/g, '')) as 0}
                    />
                </Form.Item>
                <Form.Item label="Diễn giải" name="memo"><Input.TextArea rows={2} maxLength={500} /></Form.Item>
            </Form>
        </Modal>
    );
}

function CreatePaymentModal({ open, onClose, presetSupplierId }: { open: boolean; onClose: () => void; presetSupplierId?: number }) {
    const [form] = Form.useForm();
    const create = useCreatePayment();
    const confirmM = useConfirmPayment();
    const { message } = App.useApp();

    return (
        <Modal
            open={open}
            onCancel={onClose}
            title="Tạo phiếu chi"
            okText="Tạo & xác nhận"
            cancelText="Huỷ"
            destroyOnClose
            confirmLoading={create.isPending || confirmM.isPending}
            onOk={async () => {
                try {
                    const v = await form.validateFields();
                    const created = await create.mutateAsync({
                        supplier_id: v.supplier_id || undefined,
                        paid_at: v.paid_at.format('YYYY-MM-DDTHH:mm:ss'),
                        amount: v.amount,
                        payment_method: v.payment_method,
                        memo: v.memo,
                    });
                    await confirmM.mutateAsync(created.id);
                    message.success(`Đã xác nhận ${created.code}.`);
                    form.resetFields();
                    onClose();
                } catch (e) {
                    if ((e as { errorFields?: unknown }).errorFields) return;
                    message.error(errorMessage(e));
                }
            }}
        >
            <Form form={form} layout="vertical" initialValues={{ paid_at: dayjs(), payment_method: 'bank', supplier_id: presetSupplierId }} preserve={false} key={String(presetSupplierId ?? 'new')}>
                <Form.Item label="NCC (ID)" name="supplier_id" rules={[{ required: true }]}>
                    <InputNumber min={1} style={{ width: '100%' }} placeholder="ID nhà cung cấp" />
                </Form.Item>
                <Form.Item label="Ngày chi" name="paid_at" rules={[{ required: true }]}>
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
                <Form.Item label="Diễn giải" name="memo"><Input.TextArea rows={2} maxLength={500} /></Form.Item>
            </Form>
        </Modal>
    );
}
