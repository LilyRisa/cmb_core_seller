import { useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
    Alert, Anchor, App as AntApp, Button, Card, Checkbox, Col, DatePicker, Form, Input, InputNumber,
    Row, Select, Space, Table, Tooltip, Typography,
} from 'antd';
import { ArrowLeftOutlined, DeleteOutlined, PictureOutlined, PlusOutlined } from '@ant-design/icons';
import type { Dayjs } from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useCreateSku, useWarehouses, type CreateSkuPayload } from '@/lib/inventory';
import { useChannelAccounts } from '@/lib/channels';

// Base units shown in "Đơn vị cơ bản". Free-text is allowed too (mode not used; keep it a simple Select).
const BASE_UNITS = ['PCS', 'Cái', 'Bộ', 'Hộp', 'Thùng', 'Đôi', 'Kg', 'Gói', 'Cuộn', 'Mét'];

interface BasicForm {
    sku_code: string;
    name: string;
    spu_code?: string;
    category?: string;
    gtins?: string[];
    base_unit?: string;
    cost_price?: number;
    ref_sale_price?: number;
    sale_start_date?: Dayjs;
    note?: string;
    weight_grams?: number;
    length_cm?: number;
    width_cm?: number;
    height_cm?: number;
    mappings?: Array<{ channel_account_id?: number; external_sku_id?: string; seller_sku?: string; quantity?: number }>;
}

interface WhRow { included: boolean; on_hand: number; cost_price: number }

/**
 * "Thêm SKU đơn độc" — full-page form mirroring the BigSeller layout (ui_example/them_sku.png):
 * Thông tin cơ bản → Ghép nối với SKU gian hàng → Thông tin cân nặng → Kho, with a sticky anchor
 * nav on the right. Cost/sale price feed the (future) profit reporting. See docs/06-frontend/create-sku-form.md.
 */
export function CreateSkuPage() {
    const { message } = AntApp.useApp();
    const navigate = useNavigate();
    const [form] = Form.useForm<BasicForm>();
    const create = useCreateSku();
    const { data: warehouses } = useWarehouses();
    const { data: channelData } = useChannelAccounts();
    const shopOptions = useMemo(() => (channelData?.data ?? []).map((s) => ({ value: s.id, label: s.name })), [channelData]);

    // Per-warehouse opening stock + cost (the "Kho" section). Keyed by warehouse id.
    const [whRows, setWhRows] = useState<Record<number, WhRow>>({});
    const whRow = (id: number): WhRow => whRows[id] ?? { included: true, on_hand: 0, cost_price: 0 };
    const patchWh = (id: number, patch: Partial<WhRow>) => setWhRows((m) => ({ ...m, [id]: { ...whRow(id), ...patch } }));

    const submit = () => form.validateFields().then((v) => {
        const payload: CreateSkuPayload = {
            sku_code: v.sku_code.trim(),
            name: v.name.trim(),
            spu_code: v.spu_code?.trim() || null,
            category: v.category?.trim() || null,
            gtins: (v.gtins ?? []).map((g) => g.trim()).filter(Boolean),
            base_unit: v.base_unit || 'PCS',
            cost_price: v.cost_price ?? 0,
            ref_sale_price: v.ref_sale_price ?? null,
            sale_start_date: v.sale_start_date ? v.sale_start_date.format('YYYY-MM-DD') : null,
            note: v.note?.trim() || null,
            weight_grams: v.weight_grams ?? null,
            length_cm: v.length_cm ?? null,
            width_cm: v.width_cm ?? null,
            height_cm: v.height_cm ?? null,
            mappings: (v.mappings ?? [])
                .filter((m) => m?.channel_account_id && m?.external_sku_id?.trim())
                .map((m) => ({ channel_account_id: m.channel_account_id!, external_sku_id: m.external_sku_id!.trim(), seller_sku: m.seller_sku?.trim() || null, quantity: m.quantity ?? 1 })),
            levels: (warehouses ?? [])
                .filter((w) => whRow(w.id).included)
                .map((w) => ({ warehouse_id: w.id, on_hand: whRow(w.id).on_hand || 0, cost_price: whRow(w.id).cost_price || 0 })),
        };
        create.mutate(payload, {
            onSuccess: () => { message.success('Đã tạo SKU'); navigate('/inventory?tab=skus'); },
            onError: (e) => message.error(errorMessage(e)),
        });
    }).catch(() => message.error('Vui lòng kiểm tra các trường bắt buộc.'));

    const whColumns = [
        { title: 'Kho', key: 'wh', render: (_: unknown, w: { id: number; name: string; is_default: boolean }) => (
            <Checkbox checked={whRow(w.id).included} onChange={(e) => patchWh(w.id, { included: e.target.checked })}>{w.name}{w.is_default ? ' (mặc định)' : ''}</Checkbox>
        ) },
        { title: 'Tồn kho', key: 'oh', width: 180, render: (_: unknown, w: { id: number }) => (
            <InputNumber min={0} style={{ width: '100%' }} disabled={!whRow(w.id).included} value={whRow(w.id).on_hand} onChange={(n) => patchWh(w.id, { on_hand: Number(n ?? 0) })} />
        ) },
        { title: 'Giá vốn', key: 'cost', width: 200, render: (_: unknown, w: { id: number }) => (
            <InputNumber<number> min={0} addonBefore="₫" style={{ width: '100%' }} disabled={!whRow(w.id).included} value={whRow(w.id).cost_price} onChange={(n) => patchWh(w.id, { cost_price: Number(n ?? 0) })} />
        ) },
    ];

    return (
        <div>
            <PageHeader
                title={<Space size="middle"><Link to="/inventory?tab=skus"><Button type="text" icon={<ArrowLeftOutlined />} /></Link><span>SKU hàng hoá › Thêm SKU đơn độc</span></Space>}
                extra={<Space><Button onClick={() => navigate('/inventory?tab=skus')}>Hủy</Button><Button type="primary" loading={create.isPending} onClick={submit}>Lưu</Button></Space>}
            />
            <Row gutter={16}>
                <Col xs={24} lg={19}>
                    <Form form={form} layout="horizontal" labelCol={{ flex: '170px' }} wrapperCol={{ flex: 1 }} labelAlign="left" requiredMark>
                        <Card id="basic" title="Thông tin cơ bản" style={{ marginBottom: 16 }}>
                            <Row gutter={16}>
                                <Col flex="120px">
                                    <Tooltip title="Tải ảnh sản phẩm — tính năng sắp có">
                                        <div style={{ width: 96, height: 96, border: '1px dashed #d9d9d9', borderRadius: 8, display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#bfbfbf', cursor: 'not-allowed' }}>
                                            <PictureOutlined style={{ fontSize: 24 }} />
                                        </div>
                                    </Tooltip>
                                </Col>
                                <Col flex="auto">
                                    <Form.Item name="sku_code" label="Mã SKU" rules={[{ required: true, message: 'Nhập mã SKU' }, { max: 100 }]}
                                        extra="Nếu sau này dùng kho của bên thứ ba (3PL/WMS), mã SKU phải tuân theo quy tắc đặt tên của họ.">
                                        <Input placeholder="VD: AOTHUN-DEN-M" />
                                    </Form.Item>
                                    <Form.Item name="name" label="Tên" rules={[{ required: true, message: 'Nhập tên SKU' }, { max: 255 }]}><Input placeholder="Tên hàng hoá" /></Form.Item>
                                    <Form.Item name="spu_code" label="Liên kết SPU" extra="Mã nhóm sản phẩm (SPU) — các SKU cùng một sản phẩm dùng chung mã này. Để trống nếu chưa dùng."><Input placeholder="VD: AOTHUN" allowClear /></Form.Item>
                                    <Form.Item name="category" label="Danh mục"><Input placeholder="VD: Thời trang nam" allowClear /></Form.Item>
                                    <Form.Item name="gtins" label="GTIN" extra="Mã GTIN/EAN/UPC — tối đa 10 mã, nhấn Enter để thêm từng mã.">
                                        <Select mode="tags" tokenSeparators={[',', ' ']} placeholder="Nhập rồi Enter…" maxTagCount={10} maxCount={10} />
                                    </Form.Item>
                                    <Form.Item name="base_unit" label="Đơn vị cơ bản" initialValue="PCS" rules={[{ required: true }]}>
                                        <Select options={BASE_UNITS.map((u) => ({ value: u, label: u }))} style={{ maxWidth: 200 }} />
                                    </Form.Item>
                                    <Form.Item name="cost_price" label="Giá vốn tham khảo"><InputNumber<number> min={0} addonBefore="₫" style={{ maxWidth: 280, width: '100%' }} /></Form.Item>
                                    <Form.Item name="ref_sale_price" label="Giá bán tham khảo"><InputNumber<number> min={0} addonBefore="₫" style={{ maxWidth: 280, width: '100%' }} /></Form.Item>
                                    <Form.Item shouldUpdate={(p, c) => p.cost_price !== c.cost_price || p.ref_sale_price !== c.ref_sale_price} label=" " colon={false}>
                                        {() => {
                                            const cost = form.getFieldValue('cost_price') ?? 0;
                                            const sale = form.getFieldValue('ref_sale_price');
                                            if (!sale) return <Typography.Text type="secondary">Nhập giá bán để xem lợi nhuận tham khảo.</Typography.Text>;
                                            const profit = sale - cost;
                                            const margin = sale > 0 ? Math.round((profit / sale) * 1000) / 10 : 0;
                                            return <Typography.Text>Lợi nhuận tham khảo: <b style={{ color: profit >= 0 ? '#389e0d' : '#cf1322' }}>{profit.toLocaleString('vi-VN')} ₫</b> · biên {margin}%</Typography.Text>;
                                        }}
                                    </Form.Item>
                                    <Form.Item name="sale_start_date" label="Ngày bắt đầu bán"><DatePicker style={{ maxWidth: 220, width: '100%' }} format="DD/MM/YYYY" placeholder="Chọn ngày" /></Form.Item>
                                    <Form.Item name="note" label="Ghi chú SKU hàng hoá"><Input.TextArea rows={3} maxLength={500} showCount placeholder="Ghi chú nội bộ cho SKU này…" /></Form.Item>
                                </Col>
                            </Row>
                        </Card>

                        <Card id="mappings" title="Ghép nối với SKU gian hàng" style={{ marginBottom: 16 }}>
                            <Alert type="info" showIcon style={{ marginBottom: 12 }} message="Để dùng chức năng giám sát & đồng bộ tồn kho, hãy thiết lập mối quan hệ ghép nối giữa SKU gian hàng và SKU hàng hoá. Tồn của SKU này sẽ được đẩy lên các listing đã ghép." />
                            <Form.List name="mappings">
                                {(fields, { add, remove }) => (
                                    <>
                                        {fields.length === 0 && <Typography.Text type="secondary">Chưa có ghép nối.</Typography.Text>}
                                        {fields.map((f) => (
                                            <Space key={f.key} align="baseline" wrap style={{ display: 'flex', marginBottom: 8 }}>
                                                <Form.Item {...f} name={[f.name, 'channel_account_id']} rules={[{ required: true, message: 'Chọn gian hàng' }]} style={{ marginBottom: 0, minWidth: 220 }}>
                                                    <Select showSearch optionFilterProp="label" placeholder="Gian hàng" options={shopOptions} />
                                                </Form.Item>
                                                <Form.Item {...f} name={[f.name, 'external_sku_id']} rules={[{ required: true, message: 'Nhập SKU sàn' }, { max: 191 }]} style={{ marginBottom: 0, minWidth: 200 }}>
                                                    <Input placeholder="Mã SKU trên sàn (Seller SKU ID)" />
                                                </Form.Item>
                                                <Form.Item {...f} name={[f.name, 'seller_sku']} style={{ marginBottom: 0, minWidth: 160 }}>
                                                    <Input placeholder="Seller SKU (tuỳ chọn)" />
                                                </Form.Item>
                                                <Form.Item {...f} name={[f.name, 'quantity']} initialValue={1} style={{ marginBottom: 0 }}>
                                                    <InputNumber min={1} addonBefore="×" style={{ width: 110 }} />
                                                </Form.Item>
                                                <Button type="text" danger icon={<DeleteOutlined />} onClick={() => remove(f.name)} />
                                            </Space>
                                        ))}
                                        <Button type="dashed" icon={<PlusOutlined />} onClick={() => add({ quantity: 1 })} style={{ marginTop: 8 }}>Thêm ghép nối SKU gian hàng</Button>
                                    </>
                                )}
                            </Form.List>
                        </Card>

                        <Card id="weight" title="Thông tin cân nặng" style={{ marginBottom: 16 }}>
                            <Form.Item name="weight_grams" label="Cân nặng"><InputNumber min={0} addonAfter="g" style={{ maxWidth: 220, width: '100%' }} /></Form.Item>
                            <Form.Item label="Kích thước">
                                <Space wrap>
                                    <Form.Item name="length_cm" noStyle><InputNumber min={0} addonAfter="cm" placeholder="Dài" style={{ width: 140 }} /></Form.Item>
                                    <Form.Item name="width_cm" noStyle><InputNumber min={0} addonAfter="cm" placeholder="Rộng" style={{ width: 140 }} /></Form.Item>
                                    <Form.Item name="height_cm" noStyle><InputNumber min={0} addonAfter="cm" placeholder="Cao" style={{ width: 140 }} /></Form.Item>
                                </Space>
                            </Form.Item>
                        </Card>
                    </Form>

                    <Card id="warehouses" title="Kho" style={{ marginBottom: 16 }}>
                        <Typography.Paragraph type="secondary" style={{ marginBottom: 12 }}>Tồn đầu kỳ &amp; giá vốn theo từng kho. Tồn nhập ở đây sẽ tạo phiếu nhập kho “Tồn đầu kỳ”.</Typography.Paragraph>
                        <Table rowKey="id" size="small" pagination={false} dataSource={warehouses ?? []} columns={whColumns} />
                    </Card>
                </Col>

                <Col xs={0} lg={5}>
                    <Anchor
                        offsetTop={80}
                        items={[
                            { key: 'basic', href: '#basic', title: 'Thông tin cơ bản' },
                            { key: 'mappings', href: '#mappings', title: 'Ghép nối với SKU gian hàng' },
                            { key: 'weight', href: '#weight', title: 'Thông tin cân nặng' },
                            { key: 'warehouses', href: '#warehouses', title: 'Kho' },
                        ]}
                    />
                </Col>
            </Row>
        </div>
    );
}
