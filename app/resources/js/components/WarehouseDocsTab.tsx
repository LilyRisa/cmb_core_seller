import { useState } from 'react';
import { App as AntApp, Button, Empty, Form, Input, InputNumber, Modal, Radio, Segmented, Space, Table, Tag, Typography } from 'antd';
import { DeleteOutlined, PlusOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { SkuPickerField } from '@/components/SkuPicker';
import { MoneyText, DateText } from '@/components/MoneyText';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import {
    type WarehouseDoc, type WarehouseDocType, WAREHOUSE_DOC_LABEL, WAREHOUSE_DOC_STATUS_LABEL,
    useCancelWarehouseDoc, useConfirmWarehouseDoc, useCreateWarehouseDoc, useWarehouseDoc, useWarehouseDocs, useWarehouses,
} from '@/lib/inventory';

const STATUS_COLOR: Record<string, string> = { draft: 'default', confirmed: 'green', cancelled: 'red' };
const TYPES: WarehouseDocType[] = ['goods-receipts', 'stock-transfers', 'stocktakes'];
const PERM: Record<WarehouseDocType, string> = { 'goods-receipts': 'inventory.adjust', 'stock-transfers': 'inventory.transfer', 'stocktakes': 'inventory.stocktake' };

/** "Phiếu kho" — Phase 5 WMS: nhập kho / chuyển kho / kiểm kê. Tab trong trang Tồn kho. */
export function WarehouseDocsTab() {
    const { message } = AntApp.useApp();
    const [type, setType] = useState<WarehouseDocType>('goods-receipts');
    const [page, setPage] = useState(1);
    const { data, isFetching } = useWarehouseDocs(type, { page, per_page: 20 });
    const { data: warehouses } = useWarehouses();
    const create = useCreateWarehouseDoc();
    const confirm = useConfirmWarehouseDoc();
    const cancel = useCancelWarehouseDoc();
    const canWrite = useCan(PERM[type]);
    const [createOpen, setCreateOpen] = useState(false);
    const [viewId, setViewId] = useState<number | null>(null);
    const [form] = Form.useForm();
    const whs = warehouses ?? [];

    const submit = () => form.validateFields().then((v) => {
        const items = (v.items ?? []).filter((i: { sku_id?: number }) => i?.sku_id).map((i: Record<string, number>) => {
            if (type === 'goods-receipts') return { sku_id: i.sku_id, qty: i.qty ?? 1, unit_cost: i.unit_cost ?? 0 };
            if (type === 'stock-transfers') return { sku_id: i.sku_id, qty: i.qty ?? 1 };
            return { sku_id: i.sku_id, counted_qty: i.counted_qty ?? 0 };
        });
        if (items.length === 0) { message.error('Thêm ít nhất một dòng hàng.'); return; }
        const body: Record<string, unknown> = { type, note: v.note || undefined, items };
        if (type === 'stock-transfers') { body.from_warehouse_id = v.from_warehouse_id; body.to_warehouse_id = v.to_warehouse_id; }
        else { body.warehouse_id = v.warehouse_id; if (type === 'goods-receipts') body.supplier = v.supplier || undefined; }
        create.mutate(body as { type: WarehouseDocType }, { onSuccess: () => { message.success('Đã tạo phiếu (nháp)'); setCreateOpen(false); }, onError: (e) => message.error(errorMessage(e)) });
    });

    const columns: ColumnsType<WarehouseDoc> = [
        { title: 'Mã phiếu', dataIndex: 'code', key: 'code', render: (v, d) => <Typography.Link onClick={() => setViewId(d.id)} style={{ fontWeight: 600 }}>{v}</Typography.Link> },
        { title: 'Kho', key: 'wh', render: (_, d) => type === 'stock-transfers'
            ? <>{whName(whs, d.from_warehouse_id)} <Typography.Text type="secondary">→</Typography.Text> {whName(whs, d.to_warehouse_id)}</>
            : whName(whs, d.warehouse_id) },
        ...(type === 'goods-receipts' ? [{ title: 'NCC', dataIndex: 'supplier', key: 'sup', render: (v: string | null) => v ?? '—' } as const, { title: 'Giá trị', dataIndex: 'total_cost', key: 'tc', align: 'right' as const, render: (v: number) => <MoneyText value={v} /> } as const] : []),
        { title: 'Số dòng', dataIndex: 'item_count', key: 'ic', width: 90, align: 'center' },
        { title: 'Trạng thái', dataIndex: 'status', key: 'st', width: 130, render: (v) => <Tag color={STATUS_COLOR[v]}>{WAREHOUSE_DOC_STATUS_LABEL[v] ?? v}</Tag> },
        { title: 'Tạo lúc', dataIndex: 'created_at', key: 'ca', width: 150, render: (v) => <DateText value={v} /> },
        ...(canWrite ? [{ title: '', key: 'a', width: 170, render: (_: unknown, d: WarehouseDoc) => d.status === 'draft' ? (
            <Space>
                <Typography.Link onClick={() => confirm.mutate({ type, id: d.id }, { onSuccess: () => message.success('Đã xác nhận phiếu'), onError: (e) => message.error(errorMessage(e)) })}>Xác nhận</Typography.Link>
                <Typography.Link onClick={() => Modal.confirm({ title: `Huỷ phiếu ${d.code}?`, onOk: () => cancel.mutateAsync({ type, id: d.id }) })} style={{ color: '#cf1322' }}>Huỷ</Typography.Link>
            </Space>
        ) : null } as const] : []),
    ];

    const itemColumns = type === 'goods-receipts' ? ['qty', 'unit_cost'] : type === 'stock-transfers' ? ['qty'] : ['counted_qty'];

    return (
        <>
            <Space style={{ marginBottom: 12 }} wrap>
                <Segmented value={type} onChange={(v) => { setType(v as WarehouseDocType); setPage(1); }} options={TYPES.map((t) => ({ value: t, label: WAREHOUSE_DOC_LABEL[t] }))} />
                {canWrite && <Button type="primary" icon={<PlusOutlined />} onClick={() => { form.resetFields(); form.setFieldsValue({ items: [{}], warehouse_id: whs.find((w) => w.is_default)?.id ?? whs[0]?.id, from_warehouse_id: whs[0]?.id, to_warehouse_id: whs[1]?.id }); setCreateOpen(true); }}>Tạo {WAREHOUSE_DOC_LABEL[type].toLowerCase()}</Button>}
            </Space>
            <Table<WarehouseDoc> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns}
                locale={{ emptyText: <Empty description={`Chưa có ${WAREHOUSE_DOC_LABEL[type].toLowerCase()} nào.`} /> }}
                pagination={{ current: data?.meta.pagination.page ?? page, pageSize: 20, total: data?.meta.pagination.total ?? 0, onChange: setPage, showTotal: (t) => `${t} phiếu` }} />

            <Modal title={`Tạo ${WAREHOUSE_DOC_LABEL[type].toLowerCase()}`} open={createOpen} onCancel={() => setCreateOpen(false)} okText="Tạo phiếu (nháp)" confirmLoading={create.isPending} onOk={submit} width={760} destroyOnClose>
                <Form form={form} layout="vertical">
                    {type === 'stock-transfers' ? (
                        <Space size="large" wrap>
                            <Form.Item name="from_warehouse_id" label="Kho nguồn" rules={[{ required: true, message: 'Chọn kho nguồn' }]}>
                                <Radio.Group optionType="button" buttonStyle="solid" options={whs.map((w) => ({ value: w.id, label: w.name }))} />
                            </Form.Item>
                            <Form.Item name="to_warehouse_id" label="Kho đích" dependencies={['from_warehouse_id']} rules={[{ required: true, message: 'Chọn kho đích' }, ({ getFieldValue }) => ({ validator: (_, v) => (v !== getFieldValue('from_warehouse_id') ? Promise.resolve() : Promise.reject('Kho đích phải khác kho nguồn')) })]}>
                                <Radio.Group optionType="button" buttonStyle="solid" options={whs.map((w) => ({ value: w.id, label: w.name }))} />
                            </Form.Item>
                        </Space>
                    ) : (
                        <Form.Item name="warehouse_id" label="Kho" rules={[{ required: true, message: 'Chọn kho' }]}>
                            <Radio.Group optionType="button" buttonStyle="solid" options={whs.map((w) => ({ value: w.id, label: w.name + (w.is_default ? ' (mặc định)' : '') }))} />
                        </Form.Item>
                    )}
                    {type === 'goods-receipts' && <Form.Item name="supplier" label="Nhà cung cấp (tuỳ chọn)"><Input placeholder="VD: Xưởng A" /></Form.Item>}
                    <Form.Item name="note" label="Ghi chú"><Input maxLength={255} placeholder="VD: Nhập đầu kỳ tháng 5 / Chuyển hàng sang kho 2 / Kiểm kê quý" /></Form.Item>

                    <Form.List name="items">
                        {(fields, { add, remove }) => (
                            <>
                                {fields.map((f) => (
                                    <Space key={f.key} align="baseline" wrap style={{ display: 'flex', marginBottom: 8 }}>
                                        <Form.Item {...f} name={[f.name, 'sku_id']} rules={[{ required: true, message: 'Chọn SKU' }]} style={{ marginBottom: 0, minWidth: 320 }}>
                                            <SkuPickerField placeholder="Chọn master SKU…" width={320} />
                                        </Form.Item>
                                        {itemColumns.includes('qty') && <Form.Item {...f} name={[f.name, 'qty']} rules={[{ required: true }]} initialValue={1} style={{ marginBottom: 0 }}><InputNumber min={1} addonAfter="cái" placeholder="Số lượng" style={{ width: 140 }} /></Form.Item>}
                                        {itemColumns.includes('unit_cost') && <Form.Item {...f} name={[f.name, 'unit_cost']} style={{ marginBottom: 0 }}><InputNumber<number> min={0} addonBefore="₫" placeholder="Giá vốn" style={{ width: 160 }} /></Form.Item>}
                                        {itemColumns.includes('counted_qty') && <Form.Item {...f} name={[f.name, 'counted_qty']} rules={[{ required: true }]} style={{ marginBottom: 0 }}><InputNumber min={0} addonAfter="cái" placeholder="Số đếm thực tế" style={{ width: 170 }} /></Form.Item>}
                                        <Button type="text" danger icon={<DeleteOutlined />} onClick={() => remove(f.name)} disabled={fields.length === 1} />
                                    </Space>
                                ))}
                                <Button type="dashed" icon={<PlusOutlined />} onClick={() => add({ qty: 1 })} block>Thêm dòng</Button>
                            </>
                        )}
                    </Form.List>
                    <Typography.Text type="secondary" style={{ display: 'block', marginTop: 8 }}>Phiếu tạo ở trạng thái <b>nháp</b> — bấm "Xác nhận" trong danh sách để áp vào tồn kho (không thể sửa sau khi xác nhận; muốn điều chỉnh thì ra phiếu mới).</Typography.Text>
                </Form>
            </Modal>

            <Modal title={null} open={viewId != null} onCancel={() => setViewId(null)} footer={null} width={720} destroyOnClose>
                {viewId != null && <WarehouseDocDetail type={type} id={viewId} whs={whs} />}
            </Modal>
        </>
    );
}

function whName(whs: Array<{ id: number; name: string }>, id?: number): string {
    return id ? (whs.find((w) => w.id === id)?.name ?? `#${id}`) : '—';
}

function WarehouseDocDetail({ type, id, whs }: { type: WarehouseDocType; id: number; whs: Array<{ id: number; name: string }> }) {
    const { data: doc } = useWarehouseDoc(type, id);
    if (!doc) return <Empty description="Đang tải…" />;
    const cols: ColumnsType<NonNullable<WarehouseDoc['items']>[number]> = [
        { title: 'SKU', key: 'sku', render: (_, it) => <Space direction="vertical" size={0}><Typography.Text strong>{it.sku?.sku_code ?? `#${it.sku_id}`}</Typography.Text><Typography.Text type="secondary" style={{ fontSize: 12 }}>{it.sku?.name}</Typography.Text></Space> },
        ...(type === 'stocktakes'
            ? [{ title: 'Hệ thống', dataIndex: 'system_qty', key: 's', align: 'right' as const }, { title: 'Đếm thực tế', dataIndex: 'counted_qty', key: 'c', align: 'right' as const }, { title: 'Chênh lệch', dataIndex: 'diff', key: 'd', align: 'right' as const, render: (v: number) => <span style={{ color: v < 0 ? '#cf1322' : v > 0 ? '#389e0d' : undefined }}>{v > 0 ? `+${v}` : v}</span> }]
            : [{ title: 'Số lượng', dataIndex: 'qty', key: 'q', align: 'right' as const }, ...(type === 'goods-receipts' ? [{ title: 'Giá vốn', dataIndex: 'unit_cost', key: 'u', align: 'right' as const, render: (v: number) => <MoneyText value={v} /> } as const] : [])]),
    ];
    return (
        <div>
            <Typography.Title level={5} style={{ marginTop: 0 }}>{WAREHOUSE_DOC_LABEL[type]} — {doc.code} <Tag color={STATUS_COLOR[doc.status]}>{WAREHOUSE_DOC_STATUS_LABEL[doc.status]}</Tag></Typography.Title>
            <Typography.Paragraph type="secondary" style={{ marginBottom: 8 }}>
                {type === 'stock-transfers' ? <>Từ <b>{whName(whs, doc.from_warehouse_id)}</b> → <b>{whName(whs, doc.to_warehouse_id)}</b></> : <>Kho: <b>{whName(whs, doc.warehouse_id)}</b></>}
                {doc.supplier ? <> · NCC: <b>{doc.supplier}</b></> : null}{doc.note ? <> · {doc.note}</> : null}{doc.confirmed_at ? <> · xác nhận: <DateText value={doc.confirmed_at} /></> : null}
            </Typography.Paragraph>
            <Table rowKey="id" size="small" pagination={false} dataSource={doc.items ?? []} columns={cols} />
        </div>
    );
}
