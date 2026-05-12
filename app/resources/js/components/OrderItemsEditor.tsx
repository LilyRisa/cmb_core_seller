import { useState } from 'react';
import { App as AntApp, Avatar, Button, Empty, Input, InputNumber, Popover, Space, Spin, Table, Typography, Upload } from 'antd';
import type { RcFile } from 'antd/es/upload';
import { DeleteOutlined, PictureOutlined, PlusOutlined, SearchOutlined, ThunderboltOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { errorMessage } from '@/lib/api';
import { useSkus, useUploadImage, type Sku } from '@/lib/inventory';

/**
 * One order line being composed. Either a master-SKU line (`sku_id` set, `name`/`image`/`available`
 * come from the SKU) or an ad-hoc "quick product" line (`sku_id` undefined — `name` typed by the user,
 * `image` uploaded via the generic media endpoint, not tracked in inventory). `quantity`/`unit_price`/
 * `discount` are editable for both. See ManualOrderService + docs/03-domain/manual-orders-and-finance.md.
 */
export interface OrderLineInput {
    key: string;
    sku_id?: number;
    name: string;
    image?: string;       // public URL
    sku_code?: string;
    available?: number;   // display only (SKU lines)
    quantity: number;
    unit_price: number;
    discount: number;
    uploading?: boolean;  // an image upload is in flight (block submit)
}

let _seq = 0;
const newKey = () => `line-${++_seq}-${Date.now()}`;
const IMAGE_TYPES = ['image/png', 'image/jpeg', 'image/webp'];
const IMAGE_MAX_MB = 5;
const vnd = (n: number) => `${n.toLocaleString('vi-VN')}₫`;

/** "Tìm & thêm sản phẩm" panel: a search box, a pinned "quick product" row, then matching SKUs (image · name · code · stock · ref price). */
function PickerPanel({ onPickSku, onQuickCreate, taken }: { onPickSku: (s: Sku) => void; onQuickCreate: () => void; taken: Set<number> }) {
    const [q, setQ] = useState('');
    const { data, isFetching } = useSkus({ q: q || undefined, per_page: 50 });
    const items = data?.data ?? [];
    return (
        <div style={{ width: 440 }}>
            <Input allowClear autoFocus prefix={<SearchOutlined style={{ color: '#bfbfbf' }} />} placeholder="Tìm SKU theo mã / tên / barcode…" onChange={(e) => setQ(e.target.value)} style={{ marginBottom: 8 }} />
            <div onClick={onQuickCreate} style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '8px 10px', cursor: 'pointer', borderRadius: 6, background: '#f6ffed', border: '1px dashed #b7eb8f', marginBottom: 8 }}>
                <Avatar shape="square" size={36} style={{ background: '#52c41a', flex: 'none' }} icon={<ThunderboltOutlined />} />
                <Space direction="vertical" size={0} style={{ minWidth: 0 }}>
                    <Typography.Text strong style={{ color: '#389e0d' }}>Tạo sản phẩm nhanh</Typography.Text>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>Thêm dòng hàng không cần liên kết SKU — đặt tên, ảnh, giá bán, số lượng</Typography.Text>
                </Space>
            </div>
            <div style={{ maxHeight: 300, overflowY: 'auto', borderTop: '1px solid #f0f0f0' }}>
                {isFetching && items.length === 0 ? (
                    <div style={{ padding: 24, textAlign: 'center' }}><Spin size="small" /></div>
                ) : items.length === 0 ? (
                    <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Không có SKU phù hợp" style={{ margin: 16 }} />
                ) : items.map((s) => (
                    <div key={s.id} onClick={() => onPickSku(s)} style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '8px 10px', cursor: 'pointer', borderBottom: '1px solid #fafafa' }}>
                        <Avatar shape="square" size={40} src={s.image_url ?? undefined} icon={<PictureOutlined />} style={{ background: '#f5f5f5', color: '#bfbfbf', flex: 'none' }} />
                        <div style={{ minWidth: 0, flex: 'auto' }}>
                            <Typography.Text strong ellipsis={{ tooltip: s.name }} style={{ display: 'block' }}>{s.name}</Typography.Text>
                            <Typography.Text type="secondary" style={{ fontSize: 12 }} ellipsis>SKU: {s.sku_code} · Tồn khả dụng: {s.available_total ?? 0}{s.ref_sale_price != null ? ` · Giá TK: ${vnd(s.ref_sale_price)}` : ''}</Typography.Text>
                        </div>
                        {taken.has(s.id) ? <Typography.Text type="secondary" style={{ fontSize: 12, flex: 'none' }}>+1</Typography.Text> : <PlusOutlined style={{ color: '#1677ff', flex: 'none' }} />}
                    </div>
                ))}
            </div>
        </div>
    );
}

/**
 * Editor for an order's line items — used in the "Tạo đơn thủ công" page (BigSeller-style):
 * search opens a list of SKUs (image / name / stock / ref price) plus a pinned "quick product"
 * entry; picked lines show below in an editable table (qty, unit price, discount apply to both
 * SKU and ad-hoc lines). Acts as an AntD form control (`value` / `onChange` = `OrderLineInput[]`).
 */
export function OrderItemsEditor({ value = [], onChange }: { value?: OrderLineInput[]; onChange?: (v: OrderLineInput[]) => void }) {
    const { message } = AntApp.useApp();
    const upload = useUploadImage();
    const [open, setOpen] = useState(false);
    const set = (v: OrderLineInput[]) => onChange?.(v);
    const patch = (key: string, p: Partial<OrderLineInput>) => set(value.map((r) => (r.key === key ? { ...r, ...p } : r)));
    const remove = (key: string) => set(value.filter((r) => r.key !== key));

    const addSku = (s: Sku) => {
        const existing = value.find((r) => r.sku_id === s.id);
        if (existing) patch(existing.key, { quantity: existing.quantity + 1 });
        else set([...value, { key: newKey(), sku_id: s.id, name: s.name, image: s.image_url ?? undefined, sku_code: s.sku_code, available: s.available_total ?? 0, quantity: 1, unit_price: s.ref_sale_price ?? 0, discount: 0 }]);
        setOpen(false);
    };
    const addQuick = () => { set([...value, { key: newKey(), name: '', quantity: 1, unit_price: 0, discount: 0 }]); setOpen(false); };

    const pickImage = (key: string, file: RcFile) => {
        if (!IMAGE_TYPES.includes(file.type)) { message.error('Chỉ chấp nhận ảnh PNG / JPG / WEBP.'); return Upload.LIST_IGNORE; }
        if (file.size / 1024 / 1024 >= IMAGE_MAX_MB) { message.error(`Ảnh tối đa ${IMAGE_MAX_MB}MB.`); return Upload.LIST_IGNORE; }
        patch(key, { uploading: true });
        upload.mutate({ file, folder: 'order-items' }, {
            onSuccess: (r) => patch(key, { image: r.url, uploading: false }),
            onError: (e) => { patch(key, { uploading: false }); message.error(errorMessage(e)); },
        });
        return false; // handled manually
    };

    const renderImage = (r: OrderLineInput) => {
        if (r.sku_id) return <Avatar shape="square" size={46} src={r.image ?? undefined} icon={<PictureOutlined />} style={{ background: '#f5f5f5', color: '#bfbfbf' }} />;
        const inner = r.uploading ? <Spin size="small" /> : r.image ? <img src={r.image} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover' }} /> : <PlusOutlined style={{ color: '#bfbfbf' }} />;
        return (
            <Upload accept={IMAGE_TYPES.join(',')} showUploadList={false} beforeUpload={(f) => pickImage(r.key, f as RcFile)}>
                <div style={{ width: 46, height: 46, border: '1px dashed #d9d9d9', borderRadius: 6, display: 'flex', alignItems: 'center', justifyContent: 'center', overflow: 'hidden', background: '#fafafa', cursor: 'pointer' }}>{inner}</div>
            </Upload>
        );
    };

    const columns: ColumnsType<OrderLineInput> = [
        { title: 'Sản phẩm', key: 'p', render: (_, r) => (
            <Space align="start">
                {renderImage(r)}
                <div style={{ minWidth: 0 }}>
                    {r.sku_id ? (
                        <>
                            <Typography.Text strong ellipsis={{ tooltip: r.name }} style={{ display: 'block', maxWidth: 300 }}>{r.name}</Typography.Text>
                            <Typography.Text type="secondary" style={{ fontSize: 12 }}>SKU: {r.sku_code} · Tồn khả dụng: {r.available ?? 0}</Typography.Text>
                        </>
                    ) : (
                        <>
                            <Input size="small" placeholder="Tên sản phẩm *" maxLength={255} value={r.name} status={r.name.trim() === '' ? 'error' : undefined} onChange={(e) => patch(r.key, { name: e.target.value })} style={{ width: 300 }} />
                            <div><Typography.Text type="secondary" style={{ fontSize: 12 }}>Sản phẩm nhanh — không theo dõi tồn kho</Typography.Text></div>
                        </>
                    )}
                </div>
            </Space>
        ) },
        { title: 'SL', key: 'q', width: 84, render: (_, r) => <InputNumber min={1} value={r.quantity} onChange={(n) => patch(r.key, { quantity: Math.max(1, Number(n ?? 1)) })} style={{ width: '100%' }} /> },
        { title: 'Đơn giá ₫', key: 'u', width: 130, render: (_, r) => <InputNumber<number> min={0} value={r.unit_price} onChange={(n) => patch(r.key, { unit_price: Math.max(0, Number(n ?? 0)) })} style={{ width: '100%' }} /> },
        { title: 'Giảm ₫', key: 'd', width: 120, render: (_, r) => <InputNumber<number> min={0} value={r.discount} onChange={(n) => patch(r.key, { discount: Math.max(0, Number(n ?? 0)) })} style={{ width: '100%' }} /> },
        { title: 'Thành tiền', key: 's', width: 120, align: 'right', render: (_, r) => <Typography.Text strong>{vnd(Math.max(0, r.unit_price * r.quantity - r.discount))}</Typography.Text> },
        { title: '', key: 'x', width: 40, render: (_, r) => <Button type="text" danger icon={<DeleteOutlined />} onClick={() => remove(r.key)} /> },
    ];

    const total = value.reduce((s, r) => s + Math.max(0, r.unit_price * r.quantity - r.discount), 0);

    return (
        <div>
            <Popover trigger="click" open={open} onOpenChange={setOpen} placement="bottomLeft" destroyTooltipOnHide
                content={<PickerPanel onPickSku={addSku} onQuickCreate={addQuick} taken={new Set(value.map((r) => r.sku_id).filter((x): x is number => x != null))} />}>
                <Button icon={<SearchOutlined />} style={{ marginBottom: 12 }}>Tìm &amp; thêm sản phẩm…</Button>
            </Popover>
            {value.length === 0 ? (
                <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Chưa có dòng hàng — bấm “Tìm & thêm sản phẩm”." />
            ) : (
                <Table<OrderLineInput> rowKey="key" size="small" pagination={false} dataSource={value} columns={columns}
                    summary={() => (
                        <Table.Summary.Row>
                            <Table.Summary.Cell index={0} colSpan={4}><Typography.Text type="secondary">{value.length} dòng hàng</Typography.Text></Table.Summary.Cell>
                            <Table.Summary.Cell index={1} align="right"><Typography.Text strong>{vnd(total)}</Typography.Text></Table.Summary.Cell>
                            <Table.Summary.Cell index={2} />
                        </Table.Summary.Row>
                    )} />
            )}
        </div>
    );
}
