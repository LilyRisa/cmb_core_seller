import { useMemo, useState } from 'react';
import { Alert, App, Button, Card, DatePicker, Empty, Form, Input, InputNumber, Modal, Popconfirm, Radio, Space, Statistic, Switch, Table, Tabs, Tag, Tooltip, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { BankOutlined, CheckCircleOutlined, DollarOutlined, FileTextOutlined, PrinterOutlined, ReloadOutlined, ShopOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { formatAmount, usePartiesByIds } from '@/lib/accounting';
import { ApAgingRow, useApAging, useConfirmPayment, useCreateBill, useCreatePayment, useRecordBill, useVendorBills, useVendorPayments, VendorBill, VendorPayment } from '@/lib/accountingAp';
import { PartyPicker } from '@/components/accounting/PartyPicker';
import { AccountingSetupBanner } from './AccountingSetupBanner';
import { useCan } from '@/lib/tenant';
import { useTenantName } from '@/lib/accountingPrint';
import { printVoucher } from '@/lib/printVoucher';
import { errorMessage } from '@/lib/api';

const PAY_METHOD_LABEL: Record<string, string> = { cash: 'Tiền mặt', bank: 'Chuyển khoản', ewallet: 'Ví điện tử' };

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
        { title: '61-90 ngày', dataIndex: 'b61_90', width: 130, align: 'right', render: (v: number) => v > 0 ? <Typography.Text style={{ color: '#F59E0B' }}>{formatAmount(v)}</Typography.Text> : <Typography.Text type="secondary">0</Typography.Text> },
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
                <Statistic title="61-90 ngày" value={data?.meta.total_b61_90 ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#F59E0B' }} />
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

    const supIds = useMemo(() => Array.from(new Set((data?.data ?? []).map((r) => r.supplier_id).filter((x): x is number => x != null))), [data]);
    const { data: parties = [] } = usePartiesByIds('supplier', supIds);
    const nameMap = useMemo(() => new Map(parties.map((p) => [p.id, p.label])), [parties]);

    const tenantName = useTenantName();
    const onPrint = (r: VendorBill) => printVoucher({
        docTitle: 'CHỨNG TỪ HOÁ ĐƠN NCC',
        tenantName,
        code: r.code,
        dateText: dayjs(r.bill_date).format('DD/MM/YYYY'),
        partyLabel: 'Nhà cung cấp',
        partyName: r.supplier_id ? (nameMap.get(r.supplier_id) ?? `NCC #${r.supplier_id}`) : '—',
        reason: r.memo ?? 'Mua hàng hoá/dịch vụ',
        amount: r.total,
        fields: [
            { label: 'Số HĐ NCC', value: r.bill_no ?? '—' },
            { label: 'Tiền hàng', value: `${formatAmount(r.subtotal)} ₫` },
            { label: 'Thuế VAT', value: `${formatAmount(r.tax)} ₫` },
            { label: 'Hạn thanh toán', value: r.due_date ? dayjs(r.due_date).format('DD/MM/YYYY') : '—' },
        ],
        signers: ['Người lập', 'Kế toán', 'Thủ trưởng đơn vị'],
    });

    const columns: ColumnsType<VendorBill> = [
        { title: 'Mã HĐ', dataIndex: 'code', width: 150, render: (c: string) => <Typography.Text strong style={{ fontFamily: 'ui-monospace, monospace' }}>{c}</Typography.Text> },
        { title: 'Số HĐ NCC', dataIndex: 'bill_no', width: 140, render: (n: string | null) => n ?? '—' },
        { title: 'NCC', dataIndex: 'supplier_id', width: 170, render: (id: number | null) => id ? <Typography.Text>{nameMap.get(id) ?? `NCC #${id}`}</Typography.Text> : <Typography.Text type="secondary">—</Typography.Text> },
        { title: 'Ngày HĐ', dataIndex: 'bill_date', width: 110, render: (d: string) => dayjs(d).format('DD/MM/YYYY') },
        { title: 'Hạn TT', dataIndex: 'due_date', width: 110, render: (d: string | null) => d ? dayjs(d).format('DD/MM/YYYY') : '—' },
        { title: 'Tiền hàng', dataIndex: 'subtotal', width: 130, align: 'right', render: (v: number) => formatAmount(v) },
        { title: 'VAT', dataIndex: 'tax', width: 110, align: 'right', render: (v: number) => v > 0 ? formatAmount(v) : <Typography.Text type="secondary">0</Typography.Text> },
        { title: 'Tổng', dataIndex: 'total', width: 130, align: 'right', render: (v: number) => <Typography.Text strong>{formatAmount(v)}</Typography.Text> },
        { title: 'Trạng thái', dataIndex: 'status', width: 120, render: (s: string, r) => <Tag color={s === 'recorded' ? 'green' : s === 'paid' ? 'blue' : s === 'void' ? 'default' : 'gold'}>{r.status_label}</Tag> },
        {
            title: 'Thao tác',
            width: 170,
            align: 'right',
            render: (_, r) => (
                <Space size={4}>
                    <Tooltip title="In chứng từ"><Button size="small" type="text" icon={<PrinterOutlined />} onClick={() => onPrint(r)} /></Tooltip>
                    {canPost && r.status === 'draft' && (
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
                    )}
                </Space>
            ),
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

    const supIds = useMemo(() => Array.from(new Set((data?.data ?? []).map((r) => r.supplier_id).filter((x): x is number => x != null))), [data]);
    const { data: parties = [] } = usePartiesByIds('supplier', supIds);
    const nameMap = useMemo(() => new Map(parties.map((p) => [p.id, p.label])), [parties]);

    const tenantName = useTenantName();
    const onPrint = (r: VendorPayment) => printVoucher({
        docTitle: 'PHIẾU CHI',
        tenantName,
        code: r.code,
        dateText: dayjs(r.paid_at).format('DD/MM/YYYY'),
        partyLabel: 'Người nhận tiền',
        partyName: r.supplier_id ? (nameMap.get(r.supplier_id) ?? `NCC #${r.supplier_id}`) : '—',
        reason: r.memo ?? 'Chi trả nhà cung cấp',
        amount: r.amount,
        fields: [{ label: 'Hình thức', value: PAY_METHOD_LABEL[r.payment_method] ?? r.payment_method }],
        signers: ['Người lập phiếu', 'Người nhận tiền', 'Thủ quỹ', 'Kế toán trưởng'],
    });

    const columns: ColumnsType<VendorPayment> = [
        { title: 'Mã phiếu', dataIndex: 'code', width: 140, render: (c: string) => <Typography.Text strong style={{ fontFamily: 'ui-monospace, monospace' }}>{c}</Typography.Text> },
        { title: 'Ngày chi', dataIndex: 'paid_at', width: 130, render: (d: string) => dayjs(d).format('DD/MM/YYYY') },
        { title: 'NCC', dataIndex: 'supplier_id', width: 170, render: (id: number | null) => id ? <Typography.Text>{nameMap.get(id) ?? `NCC #${id}`}</Typography.Text> : <Typography.Text type="secondary">—</Typography.Text> },
        { title: 'Số tiền', dataIndex: 'amount', width: 150, align: 'right', render: (v: number) => <Typography.Text strong>{formatAmount(v)} ₫</Typography.Text> },
        { title: 'Phương thức', dataIndex: 'payment_method', width: 130, render: (m: string) => <Tag>{m === 'cash' ? 'Tiền mặt' : m === 'bank' ? 'Chuyển khoản' : 'Ví điện tử'}</Tag> },
        { title: 'Diễn giải', dataIndex: 'memo', ellipsis: true, render: (n: string | null) => n ?? '—' },
        { title: 'Trạng thái', dataIndex: 'status', width: 120, render: (s: string, r) => <Tag color={s === 'confirmed' ? 'green' : s === 'cancelled' ? 'default' : 'gold'}>{r.status_label}</Tag> },
        {
            title: 'Thao tác',
            width: 180,
            align: 'right',
            render: (_, r) => (
                <Space size={4}>
                    <Tooltip title="In phiếu chi"><Button size="small" type="text" icon={<PrinterOutlined />} onClick={() => onPrint(r)} /></Tooltip>
                    {canPost && r.status === 'draft' && (
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
                    )}
                </Space>
            ),
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

    const subtotal = (Form.useWatch('subtotal', form) as number | undefined) ?? 0;
    const tax = (Form.useWatch('tax', form) as number | undefined) ?? 0;
    const total = subtotal + tax;
    const busy = create.isPending || record.isPending;

    const applyVatRate = (rate: number) => form.setFieldValue('tax', Math.round(subtotal * rate / 100));

    const reset = () => form.resetFields();

    const submit = async (recordNow: boolean) => {
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
            if (recordNow) {
                await record.mutateAsync(created.id);
                message.success(`Đã ghi sổ hoá đơn ${created.code} (Dr 1561 +1331 / Cr 331).`);
            } else {
                message.success(`Đã lưu nháp hoá đơn ${created.code}.`);
            }
            reset();
            onClose();
        } catch (e) {
            if ((e as { errorFields?: unknown }).errorFields) return;
            message.error(errorMessage(e));
        }
    };

    return (
        <Modal
            open={open}
            onCancel={() => { reset(); onClose(); }}
            title="Nhập hoá đơn NCC"
            width={640}
            destroyOnClose
            footer={[
                <Button key="cancel" onClick={() => { reset(); onClose(); }}>Huỷ</Button>,
                <Button key="draft" loading={busy} onClick={() => submit(false)}>Lưu nháp</Button>,
                <Button key="record" type="primary" loading={busy} onClick={() => submit(true)}>Lưu & ghi sổ</Button>,
            ]}
        >
            <Form form={form} layout="vertical" initialValues={{ bill_date: dayjs(), tax: 0, supplier_id: presetSupplierId }} preserve={false} key={String(presetSupplierId ?? 'new')}>
                <Form.Item label="Nhà cung cấp" name="supplier_id" rules={[{ required: true, message: 'Chọn nhà cung cấp' }]}>
                    <PartyPicker type="supplier" />
                </Form.Item>
                <Space size={12} style={{ width: '100%' }} align="start">
                    <Form.Item label="Số hoá đơn NCC" name="bill_no" style={{ flex: 1 }}><Input maxLength={64} placeholder="Số HĐ trên hoá đơn giấy/điện tử" /></Form.Item>
                    <Form.Item label="Ngày HĐ" name="bill_date" rules={[{ required: true }]} style={{ width: 160 }}>
                        <DatePicker format="DD/MM/YYYY" style={{ width: '100%' }} />
                    </Form.Item>
                    <Form.Item label="Hạn TT" name="due_date" style={{ width: 160 }}>
                        <DatePicker format="DD/MM/YYYY" style={{ width: '100%' }} />
                    </Form.Item>
                </Space>
                <Form.Item label="Tiền hàng (chưa VAT)" name="subtotal" rules={[{ required: true, type: 'number', min: 0 }]}>
                    <InputNumber<number> min={0} step={1000} style={{ width: '100%' }}
                        formatter={(v) => (v ? formatAmount(Number(v)) : '') as `${number}`}
                        parser={(v) => Number((v ?? '').replace(/\D/g, '')) as 0}
                    />
                </Form.Item>
                <Form.Item label={<Space size={8}><span>Thuế VAT</span><Space size={4}>{[0, 5, 8, 10].map((r) => (
                    <Button key={r} size="small" onClick={() => applyVatRate(r)}>{r}%</Button>
                ))}</Space></Space>} name="tax">
                    <InputNumber<number> min={0} step={1000} style={{ width: '100%' }}
                        formatter={(v) => (v ? formatAmount(Number(v)) : '') as `${number}`}
                        parser={(v) => Number((v ?? '').replace(/\D/g, '')) as 0}
                    />
                </Form.Item>
                <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginBottom: 12 }}>
                    <Typography.Text type="secondary">Tổng thanh toán:</Typography.Text>
                    <Typography.Text strong style={{ fontSize: 16, color: '#fa541c' }}>{formatAmount(total)} ₫</Typography.Text>
                </div>
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
    const [allocate, setAllocate] = useState(false);
    const [alloc, setAlloc] = useState<Record<number, number>>({});

    const supplierId = Form.useWatch('supplier_id', form) as number | undefined;
    const amount = (Form.useWatch('amount', form) as number | undefined) ?? 0;
    const allocTotal = Object.values(alloc).reduce((s, v) => s + (Number(v) || 0), 0);
    const busy = create.isPending || confirmM.isPending;

    const reset = () => { form.resetFields(); setAlloc({}); setAllocate(false); };

    const submit = async (confirm: boolean) => {
        try {
            const v = await form.validateFields();
            const appliedBills = allocate
                ? Object.entries(alloc).filter(([, amt]) => Number(amt) > 0).map(([bid, amt]) => ({ vendor_bill_id: Number(bid), applied_amount: Number(amt) }))
                : undefined;
            const created = await create.mutateAsync({
                supplier_id: v.supplier_id || undefined,
                paid_at: v.paid_at.format('YYYY-MM-DDTHH:mm:ss'),
                amount: v.amount,
                payment_method: v.payment_method,
                applied_bills: appliedBills,
                memo: v.memo,
            });
            if (confirm) {
                await confirmM.mutateAsync(created.id);
                message.success(`Đã xác nhận phiếu chi ${created.code} (Dr 331 / Cr 111|112).`);
            } else {
                message.success(`Đã lưu nháp phiếu chi ${created.code}.`);
            }
            reset();
            onClose();
        } catch (e) {
            if ((e as { errorFields?: unknown }).errorFields) return;
            message.error(errorMessage(e));
        }
    };

    return (
        <Modal
            open={open}
            onCancel={() => { reset(); onClose(); }}
            title="Tạo phiếu chi"
            width={720}
            destroyOnClose
            footer={[
                <Button key="cancel" onClick={() => { reset(); onClose(); }}>Huỷ</Button>,
                <Button key="draft" loading={busy} onClick={() => submit(false)}>Lưu nháp</Button>,
                <Button key="confirm" type="primary" loading={busy} onClick={() => submit(true)}>Tạo & xác nhận (ghi sổ)</Button>,
            ]}
        >
            <Form form={form} layout="vertical" initialValues={{ paid_at: dayjs(), payment_method: 'bank', supplier_id: presetSupplierId }} preserve={false} key={String(presetSupplierId ?? 'new')}>
                <Form.Item label="Nhà cung cấp" name="supplier_id" rules={[{ required: true, message: 'Chọn nhà cung cấp' }]}>
                    <PartyPicker type="supplier" />
                </Form.Item>
                <Space size={12} style={{ width: '100%' }} align="start">
                    <Form.Item label="Ngày chi" name="paid_at" rules={[{ required: true }]} style={{ flex: 1, minWidth: 200 }}>
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

                <Form.Item label={<Space><span>Cấn trừ theo hoá đơn NCC</span><Switch size="small" checked={allocate} onChange={setAllocate} disabled={!supplierId} /></Space>}
                    tooltip="Phân bổ tiền chi vào từng hoá đơn đã ghi sổ để theo dõi công nợ chi tiết.">
                    {allocate && supplierId && (
                        <AllocateBills supplierId={supplierId} value={alloc} onChange={setAlloc} paymentAmount={amount} allocated={allocTotal} />
                    )}
                    {allocate && !supplierId && <Typography.Text type="secondary">Chọn NCC trước để cấn trừ theo hoá đơn.</Typography.Text>}
                </Form.Item>

                <Form.Item label="Diễn giải" name="memo"><Input.TextArea rows={2} maxLength={500} /></Form.Item>
            </Form>
        </Modal>
    );
}

/** Bảng phân bổ tiền chi vào từng hoá đơn NCC đã ghi sổ (applied_bills). */
function AllocateBills({ supplierId, value, onChange, paymentAmount, allocated }: {
    supplierId: number;
    value: Record<number, number>;
    onChange: (v: Record<number, number>) => void;
    paymentAmount: number;
    allocated: number;
}) {
    const { data, isFetching } = useVendorBills({ supplier_id: supplierId, status: 'recorded', per_page: 50 });
    const bills = data?.data ?? [];
    const remaining = paymentAmount - allocated;

    if (isFetching) return <Typography.Text type="secondary">Đang tải hoá đơn…</Typography.Text>;
    if (bills.length === 0) return <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="NCC chưa có hoá đơn đã ghi sổ" />;

    return (
        <div>
            <Space style={{ marginBottom: 8 }} size={16} wrap>
                <Typography.Text>Đã phân bổ: <Typography.Text strong>{formatAmount(allocated)} ₫</Typography.Text></Typography.Text>
                <Typography.Text type={remaining < 0 ? 'danger' : 'secondary'}>Còn lại: {formatAmount(remaining)} ₫</Typography.Text>
            </Space>
            {remaining < 0 && <Alert type="warning" showIcon style={{ marginBottom: 8 }} message="Tổng phân bổ vượt quá số tiền chi." />}
            <Table<VendorBill>
                rowKey="id"
                dataSource={bills}
                pagination={false}
                size="small"
                scroll={{ y: 220 }}
                columns={[
                    { title: 'Hoá đơn', dataIndex: 'code', render: (c: string, b) => <Space size={4}><Typography.Text style={{ fontFamily: 'ui-monospace, monospace' }}>{c}</Typography.Text>{b.bill_no && <Typography.Text type="secondary">· {b.bill_no}</Typography.Text>}</Space> },
                    { title: 'Tổng', dataIndex: 'total', width: 120, align: 'right', render: (v: number) => formatAmount(v) },
                    {
                        title: 'Cấn trừ (₫)',
                        width: 170,
                        render: (_, b) => (
                            <Space size={4}>
                                <InputNumber<number>
                                    min={0}
                                    step={1000}
                                    value={value[b.id] ?? 0}
                                    style={{ width: 120 }}
                                    formatter={(v) => (v ? formatAmount(Number(v)) : '') as `${number}`}
                                    parser={(v) => Number((v ?? '').replace(/\D/g, '')) as 0}
                                    onChange={(amt) => onChange({ ...value, [b.id]: Number(amt) || 0 })}
                                />
                                <Tooltip title="Cấn trừ toàn bộ hoá đơn">
                                    <Button size="small" type="link" onClick={() => onChange({ ...value, [b.id]: Number(b.total) || 0 })}>Hết</Button>
                                </Tooltip>
                            </Space>
                        ),
                    },
                ]}
            />
        </div>
    );
}
