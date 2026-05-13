import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { App as AntApp, Button, Card, Drawer, Empty, Form, Input, InputNumber, Modal, Progress, Radio, Space, Steps, Table, Tabs, Tag, Typography } from 'antd';
import { ArrowLeftOutlined, CheckCircleOutlined, CloseCircleOutlined, InboxOutlined, PlusOutlined, ReloadOutlined, SearchOutlined, ShoppingCartOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { SkuLine, SkuPickerField } from '@/components/SkuPicker';
import { MoneyText, DateText } from '@/components/MoneyText';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { useSuppliers } from '@/lib/procurement';
import { useWarehouses } from '@/lib/inventory';
import {
    type PurchaseOrder, useCancelPurchaseOrder, useConfirmPurchaseOrder, useCreatePurchaseOrder,
    usePurchaseOrder, usePurchaseOrders, useReceivePurchaseOrder,
} from '@/lib/procurement';

const STATUS_CHIP: Record<PurchaseOrder['status'], { color: string; icon?: React.ReactNode }> = {
    draft: { color: 'default' }, confirmed: { color: 'blue' }, partially_received: { color: 'gold' },
    received: { color: 'green', icon: <CheckCircleOutlined /> }, cancelled: { color: 'red', icon: <CloseCircleOutlined /> },
};

/** /procurement/purchase-orders — danh sách + tạo + nhận hàng theo PO (Phase 6.1 / SPEC 0014). */
export function PurchaseOrdersPage() {
    const canManage = useCan('procurement.manage');
    const canReceive = useCan('procurement.receive');
    const [q, setQ] = useState('');
    const [status, setStatus] = useState<string>('');
    const [page, setPage] = useState(1);
    const { data, isFetching, refetch } = usePurchaseOrders({ q: q || undefined, status: status || undefined, page, per_page: 20 });
    const [detailId, setDetailId] = useState<number | null>(null);
    const [creating, setCreating] = useState(false);

    const columns: ColumnsType<PurchaseOrder> = [
        { title: 'Mã PO', dataIndex: 'code', key: 'code', width: 170, render: (v, r) => <a onClick={() => setDetailId(r.id)}><Typography.Text strong>{v}</Typography.Text></a> },
        { title: 'NCC', key: 'supplier', render: (_, r) => r.supplier?.name ?? `#${r.supplier_id}` },
        { title: 'Kho nhập', key: 'wh', width: 160, render: (_, r) => r.warehouse?.name ?? `#${r.warehouse_id}` },
        { title: 'Trạng thái', dataIndex: 'status', key: 'status', width: 150, render: (s, r) => <Tag color={STATUS_CHIP[s as PurchaseOrder['status']]?.color ?? 'default'} icon={STATUS_CHIP[s as PurchaseOrder['status']]?.icon}>{r.status_label}</Tag> },
        { title: 'Dự kiến', dataIndex: 'expected_at', key: 'expected', width: 120 },
        { title: 'Số lượng', dataIndex: 'total_qty', key: 'qty', width: 110, align: 'right' },
        { title: 'Tổng tiền', dataIndex: 'total_cost', key: 'total_cost', width: 140, align: 'right', render: (v) => <MoneyText value={v} strong /> },
        { title: 'Tạo lúc', dataIndex: 'created_at', key: 'created_at', width: 150, render: (v) => <DateText value={v} /> },
    ];

    return (
        <div>
            <PageHeader title="Đơn mua hàng (PO)" subtitle="Đặt hàng từ NCC → nhận hàng nhiều đợt → tự cộng dồn vào kho + cập nhật giá vốn (FIFO)."
                extra={(
                    <Space>
                        <Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>Làm mới</Button>
                        {canManage && <Button type="primary" icon={<PlusOutlined />} onClick={() => setCreating(true)}>Tạo PO mới</Button>}
                    </Space>
                )}
            />
            <Card>
                <Space style={{ marginBottom: 12 }} wrap>
                    <Input.Search allowClear placeholder="Tìm mã PO" prefix={<SearchOutlined />} style={{ width: 240 }} onSearch={(v) => { setQ(v); setPage(1); }} />
                    <Radio.Group value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }} optionType="button" buttonStyle="solid"
                        options={[
                            { value: '', label: 'Tất cả' }, { value: 'draft', label: 'Nháp' }, { value: 'confirmed', label: 'Đã chốt' },
                            { value: 'partially_received', label: 'Nhận một phần' }, { value: 'received', label: 'Đã nhận đủ' }, { value: 'cancelled', label: 'Đã huỷ' },
                        ]} />
                </Space>
                <Table<PurchaseOrder> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns}
                    locale={{ emptyText: <Empty image={<ShoppingCartOutlined style={{ fontSize: 32, color: '#bfbfbf' }} />} description="Chưa có đơn mua nào." /> }}
                    onRow={(r) => ({ onClick: () => setDetailId(r.id), style: { cursor: 'pointer' } })}
                    pagination={{ current: data?.meta.pagination.page ?? page, pageSize: 20, total: data?.meta.pagination.total ?? 0, onChange: setPage, showTotal: (t) => `${t} PO` }} />
            </Card>

            {creating && <CreatePoDrawer open={creating} onClose={(id) => { setCreating(false); if (id) setDetailId(id); }} />}
            <PoDetailDrawer id={detailId} canReceive={canReceive} canManage={canManage} onClose={() => setDetailId(null)} />
        </div>
    );
}

function CreatePoDrawer({ open, onClose }: { open: boolean; onClose: (createdId?: number) => void }) {
    const { message } = AntApp.useApp();
    const [form] = Form.useForm();
    const { data: suppliers } = useSuppliers({ is_active: true, per_page: 100 });
    const { data: warehouses } = useWarehouses();
    const create = useCreatePurchaseOrder();

    const submit = () => form.validateFields().then((v) => {
        const items = (v.items ?? []).filter((it: { sku_id?: number }) => it?.sku_id);
        if (items.length === 0) { message.warning('Thêm ít nhất một dòng hàng.'); return; }
        create.mutate({ ...v, items }, {
            onSuccess: (po) => { message.success(`Đã tạo PO ${po.code}`); form.resetFields(); onClose(po.id); },
            onError: (e) => message.error(errorMessage(e)),
        });
    });

    return (
        <Drawer open={open} onClose={() => onClose()} title="Tạo đơn mua hàng" width={720}
            extra={<Button type="primary" loading={create.isPending} onClick={submit}>Tạo nháp</Button>}>
            <Form form={form} layout="vertical">
                <Space.Compact block>
                    <Form.Item name="supplier_id" label="Nhà cung cấp" rules={[{ required: true, message: 'Chọn NCC' }]} style={{ width: '50%' }}>
                        <SupplierPicker suppliers={suppliers?.data ?? []} />
                    </Form.Item>
                    <Form.Item name="warehouse_id" label="Kho nhập" rules={[{ required: true, message: 'Chọn kho' }]} style={{ width: '50%' }}>
                        <Radio.Group optionType="button" options={(warehouses ?? []).map((w) => ({ value: w.id, label: w.name }))} />
                    </Form.Item>
                </Space.Compact>
                <Form.Item name="expected_at" label="Ngày dự kiến nhận"><Input type="date" /></Form.Item>
                <Form.Item name="note" label="Ghi chú"><Input.TextArea rows={2} maxLength={500} /></Form.Item>

                <Typography.Text type="secondary" style={{ display: 'block', marginBottom: 8 }}>Dòng hàng</Typography.Text>
                <Form.List name="items" initialValue={[{}]}>
                    {(fields, { add, remove }) => (
                        <>
                            {fields.map((f) => (
                                <Space key={f.key} align="baseline" wrap style={{ display: 'flex', marginBottom: 8 }}>
                                    <Form.Item {...f} name={[f.name, 'sku_id']} rules={[{ required: true }]} style={{ width: 360, marginBottom: 0 }}>
                                        <SkuPickerField placeholder="Chọn SKU…" />
                                    </Form.Item>
                                    <Form.Item {...f} name={[f.name, 'qty_ordered']} rules={[{ required: true }]} style={{ marginBottom: 0 }}>
                                        <InputNumber min={1} placeholder="SL" style={{ width: 100 }} />
                                    </Form.Item>
                                    <Form.Item {...f} name={[f.name, 'unit_cost']} style={{ marginBottom: 0 }}
                                        tooltip="Để trống ⇒ lấy giá mặc định từ NCC × SKU khi confirm PO.">
                                        <InputNumber min={0} step={1000} placeholder="Giá nhập" style={{ width: 140 }} addonAfter="₫" />
                                    </Form.Item>
                                    {fields.length > 1 && <Button type="text" danger icon={<CloseCircleOutlined />} onClick={() => remove(f.name)} />}
                                </Space>
                            ))}
                            <Button type="dashed" icon={<PlusOutlined />} onClick={() => add({})} block>Thêm dòng</Button>
                        </>
                    )}
                </Form.List>
            </Form>
        </Drawer>
    );
}

function SupplierPicker({ value, onChange, suppliers }: { value?: number; onChange?: (v?: number) => void; suppliers: Array<{ id: number; code: string; name: string }> }) {
    const opts = useMemo(() => suppliers.map((s) => ({ value: s.id, label: `${s.code} — ${s.name}` })), [suppliers]);
    return <Radio.Group value={value} onChange={(e) => onChange?.(e.target.value)} optionType="button" buttonStyle="solid" options={opts.length ? opts : [{ value: 0, label: 'Chưa có NCC nào — tạo ở trang Nhà cung cấp' }]} />;
}

function PoDetailDrawer({ id, canReceive, canManage, onClose }: { id: number | null; canReceive: boolean; canManage: boolean; onClose: () => void }) {
    const { message } = AntApp.useApp();
    const navigate = useNavigate();
    const open = id != null;
    const { data: po, isFetching, refetch } = usePurchaseOrder(id);
    const confirm = useConfirmPurchaseOrder();
    const cancel = useCancelPurchaseOrder();
    const receive = useReceivePurchaseOrder();
    const [receiving, setReceiving] = useState(false);
    const [receiveForm] = Form.useForm();

    if (!open) return null;
    const stepIndex = po ? ['draft', 'confirmed', 'partially_received', 'received'].indexOf(po.status) : 0;

    const doConfirm = () => Modal.confirm({
        title: 'Chốt PO?', content: 'Sau khi chốt sẽ không sửa được header / dòng hàng nữa. Giá nhập sẽ được dùng làm giá vốn của các lô hàng khi nhận.',
        okText: 'Chốt PO', onOk: () => confirm.mutate(po!.id, { onSuccess: () => message.success('Đã chốt PO'), onError: (e) => message.error(errorMessage(e)) }),
    });
    const doCancel = () => Modal.confirm({
        title: 'Huỷ PO?', content: 'Chỉ huỷ được khi PO ở nháp. PO đã chốt phải tạo phiếu điều chỉnh kế toán riêng.',
        okText: 'Huỷ PO', okButtonProps: { danger: true },
        onOk: () => cancel.mutate(po!.id, { onSuccess: () => message.success('Đã huỷ PO'), onError: (e) => message.error(errorMessage(e)) }),
    });
    const openReceive = () => {
        const lines = (po?.items ?? []).filter((it) => it.qty_remaining > 0).map((it) => ({ sku_id: it.sku_id, qty: it.qty_remaining }));
        receiveForm.setFieldsValue({ lines });
        setReceiving(true);
    };
    const submitReceive = () => receiveForm.validateFields().then((v) => {
        const lines = (v.lines ?? []).filter((l: { qty?: number }) => (l?.qty ?? 0) > 0);
        if (lines.length === 0) { message.warning('Chưa nhập số lượng nhận.'); return; }
        receive.mutate({ id: po!.id, lines }, {
            onSuccess: (gr) => { message.success(`Đã tạo phiếu nhập ${gr.code} — xác nhận để áp tồn`); setReceiving(false); navigate(gr.redirect); },
            onError: (e) => message.error(errorMessage(e)),
        });
    });

    return (
        <Drawer open={open} onClose={onClose} width={780} title={po ? `Đơn mua ${po.code}` : 'Đơn mua'} loading={isFetching}
            extra={(
                <Space>
                    <Button icon={<ReloadOutlined />} onClick={() => refetch()} />
                    {canManage && po?.status === 'draft' && <Button type="primary" onClick={doConfirm} loading={confirm.isPending}>Chốt PO</Button>}
                    {canManage && po?.status === 'draft' && <Button danger onClick={doCancel} loading={cancel.isPending}>Huỷ</Button>}
                    {(canReceive || canManage) && po && ['confirmed', 'partially_received'].includes(po.status) && <Button type="primary" icon={<InboxOutlined />} onClick={openReceive}>Nhận hàng</Button>}
                </Space>
            )}>
            {po && (
                <>
                    <Steps current={stepIndex} size="small" style={{ marginBottom: 16 }}
                        items={[{ title: 'Nháp' }, { title: 'Đã chốt' }, { title: 'Nhận một phần' }, { title: 'Đã nhận đủ' }]} />
                    <Card size="small" style={{ marginBottom: 12 }}>
                        <Space direction="vertical" size={4} style={{ width: '100%' }}>
                            <Space><Typography.Text strong>NCC:</Typography.Text>{po.supplier?.name ?? `#${po.supplier_id}`}</Space>
                            <Space><Typography.Text strong>Kho nhập:</Typography.Text>{po.warehouse?.name ?? `#${po.warehouse_id}`}</Space>
                            <Space><Typography.Text strong>Dự kiến:</Typography.Text>{po.expected_at ?? '—'}</Space>
                            {po.note && <Space><Typography.Text strong>Ghi chú:</Typography.Text><Typography.Text type="secondary">{po.note}</Typography.Text></Space>}
                            <Space><Typography.Text strong>Tổng tiền:</Typography.Text><MoneyText value={po.total_cost} strong /></Space>
                            <Space><Typography.Text strong>Tiến độ nhận:</Typography.Text><Progress percent={po.progress_percent ?? 0} size="small" style={{ width: 200 }} /></Space>
                        </Space>
                    </Card>

                    <Tabs items={[
                        {
                            key: 'items', label: `Dòng hàng (${po.items?.length ?? 0})`,
                            children: (
                                <Table size="small" rowKey="id" pagination={false} dataSource={po.items ?? []} columns={[
                                    { title: 'SKU', key: 'sku', render: (_, r) => r.sku ? <SkuLine sku={r.sku} avatarSize={28} maxTextWidth={260} /> : `#${r.sku_id}` },
                                    { title: 'Đã đặt', dataIndex: 'qty_ordered', key: 'q', width: 80, align: 'right' },
                                    { title: 'Đã nhận', dataIndex: 'qty_received', key: 'r', width: 90, align: 'right', render: (v, r) => <Typography.Text type={v >= r.qty_ordered ? 'success' : undefined}>{v}</Typography.Text> },
                                    { title: 'Còn lại', dataIndex: 'qty_remaining', key: 'rem', width: 80, align: 'right' },
                                    { title: 'Giá nhập', dataIndex: 'unit_cost', key: 'uc', width: 120, align: 'right', render: (v) => <MoneyText value={v} /> },
                                    { title: 'Thành tiền', dataIndex: 'subtotal', key: 'sub', width: 140, align: 'right', render: (v) => <MoneyText value={v} /> },
                                ]} />
                            ),
                        },
                        {
                            key: 'receipts', label: `Phiếu nhập đã liên kết (${po.goods_receipts?.length ?? 0})`,
                            children: po.goods_receipts && po.goods_receipts.length > 0 ? (
                                <Table size="small" rowKey="id" pagination={false} dataSource={po.goods_receipts} columns={[
                                    { title: 'Mã phiếu', dataIndex: 'code', key: 'code', render: (v) => <Typography.Text strong>{v}</Typography.Text> },
                                    { title: 'Trạng thái', dataIndex: 'status', key: 's', render: (s) => <Tag color={s === 'confirmed' ? 'green' : 'default'}>{s}</Tag> },
                                    { title: 'Tổng', dataIndex: 'total_cost', key: 't', align: 'right', render: (v) => <MoneyText value={v} /> },
                                    { title: 'Xác nhận lúc', dataIndex: 'confirmed_at', key: 'ca', render: (v) => <DateText value={v} /> },
                                    { title: '', key: 'go', width: 80, render: (_, r) => <a onClick={() => navigate(`/inventory?tab=docs&doc=goods-receipts&id=${r.id}`)}>Mở</a> },
                                ]} />
                            ) : <Empty description="Chưa có phiếu nhập kho nào." />,
                        },
                    ]} />

                    {/* Nhận hàng — modal */}
                    <Modal title={`Nhận hàng cho PO ${po.code}`} open={receiving} onCancel={() => setReceiving(false)} onOk={submitReceive} okText="Tạo phiếu nhập" confirmLoading={receive.isPending} width={640}>
                        <Typography.Paragraph type="secondary">Hệ thống tạo phiếu nhập kho ở trạng thái nháp; mở phiếu để xác nhận → mới áp tồn + cập nhật giá vốn FIFO.</Typography.Paragraph>
                        <Form form={receiveForm} layout="vertical">
                            <Form.List name="lines">
                                {(fields) => (
                                    <>
                                        {fields.map((f) => {
                                            const it = po.items?.[f.name];

                                            return (
                                                <Space key={f.key} style={{ display: 'flex', marginBottom: 8 }} align="baseline" wrap>
                                                    {it?.sku ? <SkuLine sku={it.sku} avatarSize={28} maxTextWidth={240} /> : <Typography.Text>#{it?.sku_id}</Typography.Text>}
                                                    <Typography.Text type="secondary">Còn {it?.qty_remaining ?? 0}</Typography.Text>
                                                    <Form.Item {...f} name={[f.name, 'sku_id']} hidden><Input /></Form.Item>
                                                    <Form.Item {...f} name={[f.name, 'qty']} rules={[{ required: true }, { type: 'number', min: 0, max: it?.qty_remaining }]} style={{ marginBottom: 0 }}>
                                                        <InputNumber min={0} max={it?.qty_remaining ?? undefined} placeholder="SL nhận" style={{ width: 140 }} />
                                                    </Form.Item>
                                                </Space>
                                            );
                                        })}
                                    </>
                                )}
                            </Form.List>
                        </Form>
                    </Modal>
                </>
            )}
            {po?.status === 'cancelled' && <Tag color="red" style={{ marginTop: 8 }} icon={<CloseCircleOutlined />}>PO đã huỷ</Tag>}
            <Button type="link" icon={<ArrowLeftOutlined />} onClick={onClose} style={{ display: 'none' }}>Đóng</Button>
        </Drawer>
    );
}
