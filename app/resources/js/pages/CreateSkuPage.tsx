import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import {
    Alert, Anchor, App as AntApp, Avatar, Button, Card, Checkbox, Col, DatePicker, Form, Input, InputNumber,
    Radio, Row, Select, Skeleton, Space, Table, Tag, Typography, Upload,
} from 'antd';
import type { RcFile } from 'antd/es/upload';
import { ArrowLeftOutlined, DeleteOutlined, PlusOutlined, ShopOutlined } from '@ant-design/icons';
import dayjs, { type Dayjs } from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import {
    useChannelListings, useCreateSku, useDeleteSkuImage, useSku, useUpdateSku, useUploadSkuImage, useWarehouses,
    type ChannelListing, type CreateSkuPayload, type Sku, type UpdateSkuPayload,
} from '@/lib/inventory';
import { useChannelAccounts } from '@/lib/channels';
import { ChannelLogo } from '@/components/ChannelLogo';

/** Thông tin listing sàn đã chọn cho 1 dòng ghép nối — chỉ để hiển thị (tên/ảnh/biến thể), không gửi lên server. */
interface PickedListing { external_sku_id: string; seller_sku: string | null; title: string | null; image: string | null; variation: string | null; channel_stock: number | null }

/**
 * Picker chọn SKU gian hàng để ghép nối — tìm theo tên SP / mã SKU trong các listing đã đồng bộ của gian hàng,
 * hiện ảnh + tên + biến thể + tồn trên sàn. Cho phép gõ mã thủ công nếu listing chưa đồng bộ. SPEC 0005.
 * Form-controlled qua `value`/`onChange` (= external_sku_id); `onPick` để side-effect (điền seller_sku + hiện preview).
 */
function ChannelListingPicker({ shopId, value, onChange, onPick }: { shopId?: number; value?: string; onChange?: (v?: string) => void; onPick?: (l: PickedListing | null) => void }) {
    const [term, setTerm] = useState('');
    const { data, isFetching } = useChannelListings({ channel_account_id: shopId, q: term || undefined, per_page: 20 });
    const listings: ChannelListing[] = shopId ? (data?.data ?? []) : [];
    const opts: Array<{ value: string; label: ReactNode; listing?: ChannelListing }> = listings.map((l) => ({
        value: l.external_sku_id, listing: l,
        label: (
            <Space size={6}>
                <Avatar shape="square" size={22} src={l.image ?? undefined}>{l.image ? null : '?'}</Avatar>
                <span>{l.title ?? '(không tên)'}{l.variation ? ` — ${l.variation}` : ''}</span>
                <Typography.Text type="secondary" style={{ fontSize: 11 }}>· {l.external_sku_id}{l.channel_stock != null ? ` · tồn ${l.channel_stock}` : ''}</Typography.Text>
            </Space>
        ),
    }));
    if (term.trim() && !listings.some((l) => l.external_sku_id === term.trim())) {
        opts.push({ value: term.trim(), label: <span>Dùng mã sàn thủ công: <b>{term.trim()}</b></span> });
    }
    return (
        <Select
            showSearch filterOption={false} onSearch={setTerm} loading={isFetching} allowClear
            disabled={!shopId} style={{ minWidth: 320, width: '100%' }}
            placeholder={shopId ? 'Tìm sản phẩm trên sàn (tên / mã SKU)…' : 'Chọn gian hàng trước'}
            value={value || undefined}
            options={opts}
            notFoundContent={!shopId ? null : isFetching ? 'Đang tìm…' : 'Không thấy listing — đồng bộ ở trang "Sản phẩm sàn", hoặc gõ mã SKU sàn rồi chọn "Dùng mã thủ công".'}
            onChange={(v?: string) => {
                onChange?.(v || undefined);
                if (!v) { onPick?.(null); return; }
                const o = opts.find((x) => x.value === v);
                onPick?.({ external_sku_id: v, seller_sku: o?.listing?.seller_sku ?? null, title: o?.listing?.title ?? null, image: o?.listing?.image ?? null, variation: o?.listing?.variation ?? null, channel_stock: o?.listing?.channel_stock ?? null });
            }}
        />
    );
}

const IMAGE_TYPES = ['image/png', 'image/jpeg', 'image/webp'];
const IMAGE_MAX_MB = 5;

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
    cost_method?: 'average' | 'latest';
    ref_sale_price?: number;
    sale_start_date?: Dayjs;
    note?: string;
    weight_grams?: number;
    length_cm?: number;
    width_cm?: number;
    height_cm?: number;
    is_active?: boolean;
    mappings?: Array<{ channel_account_id?: number; external_sku_id?: string; seller_sku?: string; _listing?: PickedListing }>;
}

interface WhRow { included: boolean; on_hand: number; cost_price: number }

/**
 * "Thêm / Sửa SKU đơn độc" — full-page form mirroring the BigSeller layout (ui_example/them_sku.png):
 * Thông tin cơ bản → Ghép nối với SKU gian hàng → Thông tin cân nặng → Kho, with a sticky anchor nav.
 * Route `/inventory/skus/new` creates; `/inventory/skus/:id/edit` edits — same form, every field editable
 * EXCEPT `sku_code` (locked on edit). On edit the "Kho" section is read-only (stock changes go through the
 * inventory ledger). See SPEC 0005 & docs/06-frontend/create-sku-form.md.
 */
export function CreateSkuPage() {
    const { message } = AntApp.useApp();
    const navigate = useNavigate();
    const { id } = useParams();
    const isEdit = id != null;
    const [form] = Form.useForm<BasicForm>();
    const create = useCreateSku();
    const update = useUpdateSku();
    const uploadImage = useUploadSkuImage();
    const deleteImage = useDeleteSkuImage();
    const { data: warehouses } = useWarehouses();
    const { data: channelData } = useChannelAccounts();
    const shopOptions = useMemo(() => (channelData?.data ?? []).map((s) => ({
        value: s.id,
        label: <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}><ChannelLogo provider={s.provider} size={14} />{s.name}</span>,
    })), [channelData]);

    const skuQ = useSku(isEdit ? id : undefined);
    const editing: Sku | undefined = isEdit ? skuQ.data : undefined;

    // Per-warehouse opening stock + cost (the "Kho" section — create only; on edit it's read-only). Keyed by warehouse id.
    const [whRows, setWhRows] = useState<Record<number, WhRow>>({});
    const whRow = (id: number): WhRow => whRows[id] ?? { included: !isEdit, on_hand: 0, cost_price: 0 };
    const patchWh = (id: number, patch: Partial<WhRow>) => setWhRows((m) => ({ ...m, [id]: { ...whRow(id), ...patch } }));

    // Image. On create it's held client-side until the SKU exists; on edit it's the SKU's current image and
    // can be replaced (new file) or removed (imageDeleted) — applied after PATCH succeeds.
    const [imageFile, setImageFile] = useState<File | null>(null);
    const [imagePreview, setImagePreview] = useState<string | undefined>(undefined);
    const [imageDeleted, setImageDeleted] = useState(false);
    const pickImage = (file: RcFile) => {
        if (!IMAGE_TYPES.includes(file.type)) { message.error('Chỉ chấp nhận ảnh PNG / JPG / WEBP.'); return Upload.LIST_IGNORE; }
        if (file.size / 1024 / 1024 >= IMAGE_MAX_MB) { message.error(`Ảnh tối đa ${IMAGE_MAX_MB}MB.`); return Upload.LIST_IGNORE; }
        setImageFile(file);
        setImagePreview(URL.createObjectURL(file));
        setImageDeleted(false);
        return false; // don't auto-upload
    };
    const clearImage = () => { setImageFile(null); setImagePreview(undefined); setImageDeleted(true); return true; };

    // Prefill on edit when the SKU loads.
    useEffect(() => {
        if (!editing) return;
        form.setFieldsValue({
            sku_code: editing.sku_code, name: editing.name, spu_code: editing.spu_code ?? undefined, category: editing.category ?? undefined,
            gtins: editing.gtins ?? [], base_unit: editing.base_unit, cost_price: editing.cost_price ?? 0, cost_method: editing.cost_method ?? 'average', ref_sale_price: editing.ref_sale_price ?? undefined,
            sale_start_date: editing.sale_start_date ? dayjs(editing.sale_start_date) : undefined, note: editing.note ?? undefined,
            weight_grams: editing.weight_grams ?? undefined,
            length_cm: editing.length_cm != null ? Number(editing.length_cm) : undefined,
            width_cm: editing.width_cm != null ? Number(editing.width_cm) : undefined,
            height_cm: editing.height_cm != null ? Number(editing.height_cm) : undefined,
            is_active: editing.is_active,
            mappings: (editing.mappings ?? [])
                .filter((m) => m.channel_listing)
                .map((m) => ({
                    channel_account_id: m.channel_listing!.channel_account_id, external_sku_id: m.channel_listing!.external_sku_id,
                    seller_sku: m.channel_listing!.seller_sku ?? undefined,
                    _listing: { external_sku_id: m.channel_listing!.external_sku_id, seller_sku: m.channel_listing!.seller_sku, title: m.channel_listing!.title, image: m.channel_listing!.image, variation: m.channel_listing!.variation, channel_stock: m.channel_listing!.channel_stock },
                })),
        });
        setImageFile(null); setImageDeleted(false);
        setImagePreview(editing.image_url ?? undefined);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [editing?.id]);

    const submitCreate = (v: BasicForm) => {
        const payload: CreateSkuPayload = {
            sku_code: v.sku_code.trim(),
            name: v.name.trim(),
            spu_code: v.spu_code?.trim() || null,
            category: v.category?.trim() || null,
            gtins: (v.gtins ?? []).map((g) => g.trim()).filter(Boolean),
            base_unit: v.base_unit || 'PCS',
            cost_price: v.cost_price ?? 0,
            cost_method: v.cost_method ?? 'average',
            ref_sale_price: v.ref_sale_price ?? null,
            sale_start_date: v.sale_start_date ? v.sale_start_date.format('YYYY-MM-DD') : null,
            note: v.note?.trim() || null,
            weight_grams: v.weight_grams ?? null,
            length_cm: v.length_cm ?? null,
            width_cm: v.width_cm ?? null,
            height_cm: v.height_cm ?? null,
            mappings: cleanMappings(v.mappings),
            levels: (warehouses ?? []).filter((w) => whRow(w.id).included).map((w) => ({ warehouse_id: w.id, on_hand: whRow(w.id).on_hand || 0, cost_price: whRow(w.id).cost_price || 0 })),
        };
        create.mutate(payload, {
            onSuccess: async (sku) => { await applyImage(sku.id); message.success('Đã tạo SKU'); navigate('/inventory?tab=skus'); },
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const submitEdit = (v: BasicForm) => {
        const patch: UpdateSkuPayload = {
            // sku_code intentionally NOT sent — locked on edit.
            name: v.name.trim(),
            spu_code: v.spu_code?.trim() || null,
            category: v.category?.trim() || null,
            gtins: (v.gtins ?? []).map((g) => g.trim()).filter(Boolean),
            base_unit: v.base_unit || 'PCS',
            cost_price: v.cost_price ?? 0,
            cost_method: v.cost_method ?? 'average',
            ref_sale_price: v.ref_sale_price ?? null,
            sale_start_date: v.sale_start_date ? v.sale_start_date.format('YYYY-MM-DD') : null,
            note: v.note?.trim() || null,
            weight_grams: v.weight_grams ?? null,
            length_cm: v.length_cm ?? null,
            width_cm: v.width_cm ?? null,
            height_cm: v.height_cm ?? null,
            is_active: v.is_active !== false,
            mappings: cleanMappings(v.mappings), // replaces the SKU's channel-SKU links
        };
        update.mutate({ id: editing!.id, patch }, {
            onSuccess: async () => { await applyImage(editing!.id); message.success('Đã lưu SKU'); navigate('/inventory?tab=skus'); },
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const cleanMappings = (rows?: BasicForm['mappings']) => (rows ?? [])
        .filter((m) => m?.channel_account_id && m?.external_sku_id?.trim())
        .map((m) => ({ channel_account_id: m.channel_account_id!, external_sku_id: m.external_sku_id!.trim(), seller_sku: m.seller_sku?.trim() || null }));

    const applyImage = async (skuId: number) => {
        try {
            if (imageFile) await uploadImage.mutateAsync({ skuId, file: imageFile });
            else if (isEdit && imageDeleted && editing?.image_url) await deleteImage.mutateAsync(skuId);
        } catch (e) { message.warning(`SKU đã lưu nhưng cập nhật ảnh thất bại: ${errorMessage(e)}`); }
    };

    const submit = () => form.validateFields()
        .then((v) => (isEdit ? submitEdit(v) : submitCreate(v)))
        .catch(() => message.error('Vui lòng kiểm tra các trường bắt buộc.'));

    const createWhColumns = [
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

    const editLevelRows = (editing?.levels ?? []).map((l) => ({ ...l, warehouse_name: l.warehouse?.name ?? `#${l.warehouse_id}` }));
    const editWhColumns = [
        { title: 'Kho', dataIndex: 'warehouse_name', key: 'wh', render: (v: string, l: { warehouse?: { is_default?: boolean } }) => <>{v}{l.warehouse?.is_default ? <Tag style={{ marginLeft: 6 }}>mặc định</Tag> : null}</> },
        { title: 'Thực có', dataIndex: 'on_hand', key: 'oh', width: 110, align: 'right' as const },
        { title: 'Đang giữ', dataIndex: 'reserved', key: 'rs', width: 110, align: 'right' as const },
        { title: 'Khả dụng', dataIndex: 'available', key: 'av', width: 110, align: 'right' as const },
        { title: 'Giá vốn (₫)', dataIndex: 'cost_price', key: 'cp', width: 130, align: 'right' as const, render: (v: number) => (v ?? 0).toLocaleString('vi-VN') },
    ];

    const heading = isEdit ? `SKU hàng hoá › Sửa SKU${editing ? ` — ${editing.sku_code}` : ''}` : 'SKU hàng hoá › Thêm SKU đơn độc';

    if (isEdit && skuQ.isLoading) {
        return <div><PageHeader title={<Space size="middle"><Link to="/inventory?tab=skus"><Button type="text" icon={<ArrowLeftOutlined />} /></Link><span>{heading}</span></Space>} /><Card><Skeleton active /></Card></div>;
    }
    if (isEdit && !skuQ.isLoading && !editing) {
        return <div><PageHeader title="Không tìm thấy SKU" /><Card><Alert type="error" showIcon message="SKU không tồn tại hoặc bạn không có quyền." /><Button style={{ marginTop: 12 }} onClick={() => navigate('/inventory?tab=skus')}>Về danh sách SKU</Button></Card></div>;
    }

    return (
        <div>
            <PageHeader
                title={<Space size="middle"><Link to="/inventory?tab=skus"><Button type="text" icon={<ArrowLeftOutlined />} /></Link><span>{heading}</span></Space>}
                extra={<Space><Button onClick={() => navigate('/inventory?tab=skus')}>Hủy</Button><Button type="primary" loading={create.isPending || update.isPending} onClick={submit}>Lưu</Button></Space>}
            />
            <Row gutter={16}>
                <Col xs={24} lg={19}>
                    <Form form={form} layout="horizontal" labelCol={{ flex: '170px' }} wrapperCol={{ flex: 1 }} labelAlign="left" requiredMark initialValues={{ base_unit: 'PCS', cost_method: 'average', is_active: true }}>
                        <Card id="basic" title="Thông tin cơ bản" style={{ marginBottom: 16 }}>
                            <Row gutter={16}>
                                <Col flex="120px">
                                    <Upload
                                        listType="picture-card"
                                        accept={IMAGE_TYPES.join(',')}
                                        maxCount={1}
                                        beforeUpload={pickImage}
                                        fileList={imagePreview ? [{ uid: 'sku-image', name: imageFile?.name ?? 'image', status: 'done' as const, url: imagePreview }] : []}
                                        onRemove={clearImage}
                                    >
                                        {!imagePreview && <div><PlusOutlined /><div style={{ marginTop: 6, fontSize: 12 }}>Tải ảnh</div></div>}
                                    </Upload>
                                    <Typography.Text type="secondary" style={{ fontSize: 11 }}>PNG/JPG/WEBP ≤ {IMAGE_MAX_MB}MB</Typography.Text>
                                </Col>
                                <Col flex="auto">
                                    <Form.Item name="sku_code" label="Mã SKU" rules={[{ required: true, message: 'Nhập mã SKU' }, { max: 100 }]}
                                        extra={isEdit ? 'Mã SKU không sửa được sau khi tạo (là khoá định danh hàng hoá — đổi mã sẽ phá liên kết).' : 'Nếu sau này dùng kho của bên thứ ba (3PL/WMS), mã SKU phải tuân theo quy tắc đặt tên của họ.'}>
                                        <Input placeholder="VD: AOTHUN-DEN-M" maxLength={100} disabled={isEdit} />
                                    </Form.Item>
                                    <Form.Item name="name" label="Tên" rules={[{ required: true, message: 'Nhập tên SKU' }, { max: 255 }]}><Input placeholder="Tên hàng hoá" maxLength={255} showCount /></Form.Item>
                                    <Form.Item name="spu_code" label="Liên kết SPU" rules={[{ max: 100 }]} extra="Mã nhóm sản phẩm (SPU) — các SKU cùng một sản phẩm dùng chung mã này. Để trống nếu chưa dùng."><Input placeholder="VD: AOTHUN" maxLength={100} allowClear /></Form.Item>
                                    <Form.Item name="category" label="Danh mục" rules={[{ max: 120 }]}><Input placeholder="VD: Thời trang nam" maxLength={120} allowClear /></Form.Item>
                                    <Form.Item name="gtins" label="GTIN" extra="Mã GTIN/EAN/UPC — tối đa 10 mã, nhấn Enter để thêm từng mã.">
                                        <Select mode="tags" tokenSeparators={[',', ' ']} placeholder="Nhập rồi Enter…" maxTagCount={10} maxCount={10} />
                                    </Form.Item>
                                    <Form.Item name="base_unit" label="Đơn vị cơ bản" rules={[{ required: true }]}>
                                        <Select options={BASE_UNITS.map((u) => ({ value: u, label: u }))} style={{ maxWidth: 200 }} />
                                    </Form.Item>
                                    <Form.Item name="cost_price" label="Giá vốn"><InputNumber<number> min={0} addonBefore="₫" style={{ maxWidth: 280, width: '100%' }} /></Form.Item>
                                    <Form.Item name="cost_method" label="Cách tính giá vốn" extra="Dùng để ước tính lợi nhuận sau phí sàn của đơn. Bình quân: giá vốn trung bình gia quyền (cập nhật khi nhập kho). Lô gần nhất: đơn giá của lô nhập kho mới nhất.">
                                        <Radio.Group optionType="button" buttonStyle="solid" options={[{ value: 'average', label: 'Bình quân' }, { value: 'latest', label: 'Lô nhập gần nhất' }]} />
                                    </Form.Item>
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
                                    {isEdit && <Form.Item name="is_active" label="Đang hoạt động" valuePropName="checked"><Checkbox /></Form.Item>}
                                </Col>
                            </Row>
                        </Card>

                        <Card id="mappings" title="Ghép nối với SKU gian hàng" style={{ marginBottom: 16 }}>
                            <Alert type="info" showIcon style={{ marginBottom: 12 }}
                                message={isEdit ? 'Danh sách ghép nối ở đây là toàn bộ liên kết của SKU này — lưu lại sẽ thay thế các liên kết cũ.' : 'Ghép SKU hàng hoá này với SKU/biến thể trên gian hàng để giám sát & đồng bộ tồn kho.'}
                                description="Một SKU hàng hoá có thể ghép với nhiều SKU sàn (từ nhiều gian hàng); mỗi SKU sàn chỉ thuộc đúng 1 SKU hàng hoá. Tồn của SKU này tự được đẩy lên các listing đã ghép — không cần nhập số tồn ở đây." />
                            <Form.List name="mappings">
                                {(fields, { add, remove }) => (
                                    <>
                                        {fields.length === 0 && <Typography.Text type="secondary">Chưa có ghép nối.</Typography.Text>}
                                        {fields.map((f) => (
                                            <Form.Item noStyle key={f.key} shouldUpdate>
                                                {() => {
                                                    const row = (form.getFieldValue(['mappings', f.name]) ?? {}) as NonNullable<BasicForm['mappings']>[number];
                                                    const shopId = row.channel_account_id;
                                                    const picked = row._listing;
                                                    const shopLabel = shopOptions.find((o) => o.value === shopId)?.label;
                                                    const setRow = (patch: Partial<NonNullable<BasicForm['mappings']>[number]>) =>
                                                        Object.entries(patch).forEach(([k, v]) => form.setFieldValue(['mappings', f.name, k] as never, v));
                                                    return (
                                                        <div style={{ border: '1px solid #f0f0f0', borderRadius: 8, padding: 12, marginBottom: 10 }}>
                                                            <Space align="start" wrap style={{ width: '100%' }}>
                                                                <Form.Item {...f} name={[f.name, 'channel_account_id']} rules={[{ required: true, message: 'Chọn gian hàng' }]} style={{ marginBottom: 0, minWidth: 200 }}>
                                                                    <Select showSearch optionFilterProp="label" placeholder="Gian hàng" suffixIcon={<ShopOutlined />} options={shopOptions}
                                                                        onChange={() => setRow({ external_sku_id: undefined, seller_sku: undefined, _listing: undefined })} />
                                                                </Form.Item>
                                                                <Form.Item {...f} name={[f.name, 'external_sku_id']} rules={[{ required: true, message: 'Chọn SKU gian hàng' }, { max: 191 }]} style={{ marginBottom: 0, flex: 1, minWidth: 320 }}>
                                                                    <ChannelListingPicker shopId={shopId} onPick={(l) => setRow({ seller_sku: l?.seller_sku ?? row.seller_sku, _listing: l ?? undefined })} />
                                                                </Form.Item>
                                                                <Button type="text" danger icon={<DeleteOutlined />} onClick={() => remove(f.name)} />
                                                            </Space>
                                                            <Form.Item {...f} name={[f.name, 'seller_sku']} hidden><Input /></Form.Item>
                                                            {picked ? (
                                                                <Space size={10} style={{ marginTop: 10 }} align="start">
                                                                    <Avatar shape="square" size={44} src={picked.image ?? undefined}>{picked.image ? null : '?'}</Avatar>
                                                                    <div>
                                                                        <div style={{ fontWeight: 500 }}>{picked.title ?? '(không tên)'}{picked.variation ? <Tag style={{ marginInlineStart: 6 }}>{picked.variation}</Tag> : null}</div>
                                                                        <Typography.Text type="secondary" style={{ fontSize: 12 }}>{shopLabel ? <>Gian hàng: {shopLabel} · </> : null}SKU sàn: {row.external_sku_id}{picked.seller_sku ? ` · Seller SKU: ${picked.seller_sku}` : ''}{picked.channel_stock != null ? ` · Tồn trên sàn: ${picked.channel_stock}` : ''}</Typography.Text>
                                                                    </div>
                                                                </Space>
                                                            ) : row.external_sku_id ? (
                                                                <Typography.Text type="secondary" style={{ fontSize: 12, display: 'block', marginTop: 8 }}>
                                                                    SKU sàn: <b>{row.external_sku_id}</b>{shopLabel ? <> · Gian hàng: {shopLabel}</> : null} <Typography.Text type="warning">(listing chưa đồng bộ — chưa xem được tên/ảnh; bấm “Đồng bộ listing” ở trang Sản phẩm sàn)</Typography.Text>
                                                                </Typography.Text>
                                                            ) : null}
                                                        </div>
                                                    );
                                                }}
                                            </Form.Item>
                                        ))}
                                        <Button type="dashed" icon={<PlusOutlined />} onClick={() => add({})} style={{ marginTop: 8 }}>Thêm ghép nối SKU gian hàng</Button>
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
                        {isEdit ? (
                            <>
                                <Typography.Paragraph type="secondary" style={{ marginBottom: 12 }}>Tồn kho theo từng kho (chỉ xem). Điều chỉnh tồn ở tab <Link to="/inventory">Tồn theo SKU</Link>; nhập/xuất hàng loạt ở tab <Link to="/inventory?tab=skus">Danh mục SKU</Link>.</Typography.Paragraph>
                                <Table rowKey="id" size="small" pagination={false} dataSource={editLevelRows} columns={editWhColumns} locale={{ emptyText: 'Chưa có dòng tồn nào cho SKU này.' }} />
                            </>
                        ) : (
                            <>
                                <Typography.Paragraph type="secondary" style={{ marginBottom: 12 }}>Tồn đầu kỳ &amp; giá vốn theo từng kho. Tồn nhập ở đây sẽ tạo phiếu nhập kho “Tồn đầu kỳ”.</Typography.Paragraph>
                                <Table rowKey="id" size="small" pagination={false} dataSource={warehouses ?? []} columns={createWhColumns} />
                            </>
                        )}
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
