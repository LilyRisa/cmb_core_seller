import { useState } from 'react';
import { App as AntApp, Avatar, Button, Card, Drawer, Empty, Form, Input, InputNumber, Modal, Popconfirm, Space, Switch, Table, Tabs, Tag, Typography } from 'antd';
import { DeleteOutlined, EditOutlined, PlusOutlined, ReloadOutlined, SearchOutlined, ShopOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { SkuLine, SkuPickerField } from '@/components/SkuPicker';
import { MoneyText } from '@/components/MoneyText';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import {
    type Supplier, useCreateSupplier, useDeleteSupplier, useSetSupplierPrice, useSupplier, useSuppliers, useUpdateSupplier,
} from '@/lib/procurement';

/**
 * /procurement/suppliers — Quản lý nhà cung cấp + bảng giá nhập (Phase 6.1 / SPEC 0014).
 * UI: bảng NCC + drawer thêm/sửa; tab "Bảng giá nhập" hiện ảnh + mã + giá nhập per SKU + cờ default.
 */
export function SuppliersPage() {
    const { message } = AntApp.useApp();
    const canManage = useCan('procurement.manage');
    const [q, setQ] = useState('');
    const [page, setPage] = useState(1);
    const { data, isFetching, refetch } = useSuppliers({ q: q || undefined, page, per_page: 20 });
    const [editing, setEditing] = useState<Supplier | null>(null);
    const [creating, setCreating] = useState(false);
    const remove = useDeleteSupplier();

    const columns: ColumnsType<Supplier> = [
        { title: 'Mã NCC', dataIndex: 'code', key: 'code', width: 140, render: (v) => <Typography.Text strong>{v}</Typography.Text> },
        { title: 'Tên NCC', dataIndex: 'name', key: 'name', render: (v, r) => <Space direction="vertical" size={0}><span>{v}</span>{r.tax_code && <Typography.Text type="secondary" style={{ fontSize: 12 }}>MST: {r.tax_code}</Typography.Text>}</Space> },
        { title: 'Liên hệ', key: 'contact', width: 200, render: (_, r) => <Space direction="vertical" size={0} style={{ fontSize: 12 }}>{r.phone && <span>{r.phone}</span>}{r.email && <Typography.Text type="secondary">{r.email}</Typography.Text>}</Space> },
        { title: 'Công nợ', dataIndex: 'payment_terms_days', key: 'pt', width: 100, align: 'center', render: (v) => v > 0 ? <Tag>NET-{v}</Tag> : <Typography.Text type="secondary">Trả ngay</Typography.Text> },
        { title: 'SKU đã ghép giá', dataIndex: 'prices_count', key: 'prices_count', width: 130, align: 'center', render: (v) => <Tag color={v > 0 ? 'blue' : 'default'}>{v ?? 0}</Tag> },
        { title: 'Trạng thái', dataIndex: 'is_active', key: 'is_active', width: 110, render: (v) => v ? <Tag color="green">Đang hoạt động</Tag> : <Tag>Tạm dừng</Tag> },
        ...(canManage ? [{ title: '', key: 'a', width: 90, render: (_: unknown, r: Supplier) => (
            <Space size={2}>
                <Button size="small" type="text" icon={<EditOutlined />} onClick={() => setEditing(r)} title="Sửa" />
                <Popconfirm title="Xoá NCC này?" okButtonProps={{ danger: true }} okText="Xoá" cancelText="Huỷ"
                    onConfirm={() => remove.mutate(r.id, { onSuccess: () => message.success('Đã xoá NCC'), onError: (e) => message.error(errorMessage(e)) })}>
                    <Button size="small" type="text" danger icon={<DeleteOutlined />} title="Xoá" />
                </Popconfirm>
            </Space>
        ) }] : []),
    ];

    return (
        <div>
            <PageHeader title="Nhà cung cấp" subtitle="Sổ NCC + bảng giá nhập theo SKU — phục vụ Đơn mua (PO) & sổ kế toán."
                extra={(
                    <Space>
                        <Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>Làm mới</Button>
                        {canManage && <Button type="primary" icon={<PlusOutlined />} onClick={() => setCreating(true)}>Thêm NCC</Button>}
                    </Space>
                )}
            />
            <Card>
                <Space style={{ marginBottom: 12 }}>
                    <Input.Search allowClear placeholder="Tìm mã / tên / SĐT" prefix={<SearchOutlined />} style={{ width: 280 }} onSearch={(v) => { setQ(v); setPage(1); }} />
                </Space>
                <Table<Supplier> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns}
                    locale={{ emptyText: <Empty image={<ShopOutlined style={{ fontSize: 32, color: '#bfbfbf' }} />} description="Chưa có nhà cung cấp nào." /> }}
                    pagination={{ current: data?.meta.pagination.page ?? page, pageSize: 20, total: data?.meta.pagination.total ?? 0, onChange: setPage, showTotal: (t) => `${t} NCC` }} />
            </Card>

            <SupplierDrawer open={creating} onClose={() => setCreating(false)} />
            <SupplierDrawer open={editing != null} supplier={editing} onClose={() => setEditing(null)} />
        </div>
    );
}

function SupplierDrawer({ open, supplier, onClose }: { open: boolean; supplier?: Supplier | null; onClose: () => void }) {
    const { message } = AntApp.useApp();
    const canManage = useCan('procurement.manage');
    const isEdit = supplier != null;
    const { data: detail } = useSupplier(isEdit ? supplier?.id : null);
    const [form] = Form.useForm();
    const create = useCreateSupplier();
    const update = useUpdateSupplier();

    const submit = () => form.validateFields().then((v) => {
        if (isEdit) {
            update.mutate({ id: supplier!.id, ...v }, { onSuccess: () => { message.success('Đã lưu NCC'); onClose(); }, onError: (e) => message.error(errorMessage(e)) });
        } else {
            create.mutate(v, { onSuccess: () => { message.success('Đã tạo NCC'); onClose(); form.resetFields(); }, onError: (e) => message.error(errorMessage(e)) });
        }
    });

    return (
        <Drawer open={open} onClose={onClose} title={isEdit ? `Sửa NCC — ${supplier?.code}` : 'Thêm nhà cung cấp'} width={640}
            extra={canManage && <Button type="primary" loading={create.isPending || update.isPending} onClick={submit}>Lưu</Button>}>
            {open && (
                <Tabs items={[
                    {
                        key: 'info', label: 'Thông tin chung', children: (
                            <Form form={form} layout="vertical" initialValues={supplier ?? { is_active: true, payment_terms_days: 0 }} disabled={!canManage}>
                                <Form.Item name="name" label="Tên NCC" rules={[{ required: true, message: 'Bắt buộc' }, { max: 255 }]}><Input maxLength={255} placeholder="VD: Công ty TNHH ABC" /></Form.Item>
                                <Space.Compact block>
                                    <Form.Item name="phone" label="SĐT" style={{ width: '50%' }}><Input maxLength={32} /></Form.Item>
                                    <Form.Item name="email" label="Email" style={{ width: '50%' }}><Input maxLength={191} /></Form.Item>
                                </Space.Compact>
                                <Form.Item name="tax_code" label="Mã số thuế (MST)"><Input maxLength={32} /></Form.Item>
                                <Form.Item name="address" label="Địa chỉ"><Input maxLength={255} /></Form.Item>
                                <Form.Item name="payment_terms_days" label="Công nợ (số ngày)" tooltip="Số ngày được phép thanh toán sau khi nhận hàng (NET). Để 0 nếu trả ngay.">
                                    <InputNumber min={0} max={365} addonAfter="ngày" style={{ width: 160 }} />
                                </Form.Item>
                                <Form.Item name="note" label="Ghi chú"><Input.TextArea rows={3} maxLength={1000} /></Form.Item>
                                <Form.Item name="is_active" label="Đang hoạt động" valuePropName="checked"><Switch /></Form.Item>
                            </Form>
                        ),
                    },
                    ...(isEdit ? [{
                        key: 'prices', label: `Bảng giá nhập (${detail?.prices?.length ?? 0})`,
                        children: <SupplierPriceList supplier={supplier!} detail={detail} />,
                    }] : []),
                ]} />
            )}
        </Drawer>
    );
}

function SupplierPriceList({ supplier, detail }: { supplier: Supplier; detail?: Supplier }) {
    const { message } = AntApp.useApp();
    const canManage = useCan('procurement.manage');
    const [open, setOpen] = useState(false);
    const [form] = Form.useForm();
    const setPrice = useSetSupplierPrice();

    const submit = () => form.validateFields().then((v) => {
        setPrice.mutate({ supplierId: supplier.id, ...v }, {
            onSuccess: () => { message.success('Đã lưu giá nhập'); form.resetFields(); setOpen(false); },
            onError: (e) => message.error(errorMessage(e)),
        });
    });

    const rows = detail?.prices ?? [];

    return (
        <div>
            {canManage && <Button icon={<PlusOutlined />} type="dashed" block style={{ marginBottom: 12 }} onClick={() => setOpen(true)}>Thêm dòng giá</Button>}
            {rows.length === 0 ? <Empty description="Chưa cài đặt giá nhập cho NCC này" />
                : rows.map((p) => (
                    <Card size="small" key={p.id} style={{ marginBottom: 8 }} styles={{ body: { padding: 12 } }}>
                        <Space style={{ width: '100%', justifyContent: 'space-between' }} wrap>
                            {p.sku ? <SkuLine sku={p.sku} avatarSize={32} maxTextWidth={260} /> : <Avatar shape="square" size={32}>?</Avatar>}
                            <Space size={6}>
                                <MoneyText value={p.unit_cost} strong />
                                {p.is_default && <Tag color="blue">Mặc định</Tag>}
                                {p.moq > 1 && <Tag>MOQ {p.moq}</Tag>}
                                {p.valid_from && <Typography.Text type="secondary" style={{ fontSize: 12 }}>từ {p.valid_from}</Typography.Text>}
                            </Space>
                        </Space>
                    </Card>
                ))}

            <Modal title="Thêm / sửa giá nhập" open={open} onCancel={() => setOpen(false)} onOk={submit} okText="Lưu" confirmLoading={setPrice.isPending} width={480}>
                <Form form={form} layout="vertical">
                    <Form.Item name="sku_id" label="SKU hàng hoá" rules={[{ required: true, message: 'Chọn SKU' }]}>
                        <SkuPickerField placeholder="Chọn SKU…" />
                    </Form.Item>
                    <Form.Item name="unit_cost" label="Giá nhập (VND)" rules={[{ required: true, message: 'Bắt buộc' }]}>
                        <InputNumber min={0} step={1000} style={{ width: '100%' }} addonAfter="₫" formatter={(v) => `${v}`.replace(/\B(?=(\d{3})+(?!\d))/g, '.')} />
                    </Form.Item>
                    <Space.Compact block>
                        <Form.Item name="moq" label="MOQ" initialValue={1} style={{ width: '40%' }}>
                            <InputNumber min={1} style={{ width: '100%' }} />
                        </Form.Item>
                        <Form.Item name="valid_from" label="Áp dụng từ" style={{ width: '60%' }}>
                            <Input type="date" />
                        </Form.Item>
                    </Space.Compact>
                    <Form.Item name="is_default" label="Đặt làm giá mặc định khi tạo PO" valuePropName="checked"><Switch /></Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
