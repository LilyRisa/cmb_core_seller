import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
    Alert, App as AntApp, Button, Card, Checkbox, Image, Input, InputNumber, List, Modal, Radio, Result, Select, Space, Spin, Switch, Table, Tag, Typography, Upload,
} from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { ArrowLeftOutlined, CloudUploadOutlined, DeleteOutlined, PictureOutlined, PlusOutlined, RobotOutlined, SaveOutlined, VideoCameraOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { ImageResizer } from '@/components/ImageResizer';
import { errorMessage, tenantApi } from '@/lib/api';
import { useCurrentTenantId } from '@/lib/tenant';
import { CategoryPicker } from '@/features/products/CategoryPicker';
import { AttributeForm } from '@/features/products/AttributeForm';
import { PushProgressModal } from '@/features/products/PushProgressModal';
import { useAiSuggestDescription, useBrands, useListing, useListingLimits, usePushListing, useShippingOptions, useUpdateListing } from '@/features/products/hooks';
import { searchMasterSkus, uploadListingVideo } from '@/features/products/api';
import type { ListingDraftSku, MasterSkuRef, ShippingOptions, UpdateListingPayload } from '@/features/products/api';

const STATUS_TAG: Record<string, { color: string; label: string }> = {
    draft: { color: 'default', label: 'Nháp' },
    ready: { color: 'green', label: 'Sẵn sàng' },
    pushing: { color: 'blue', label: 'Đang đẩy' },
    reviewing: { color: 'gold', label: 'Đang duyệt' },
    live: { color: 'success', label: 'Đã đăng' },
    published: { color: 'success', label: 'Đã đăng' },
    failed: { color: 'red', label: 'Lỗi' },
};

/**
 * Trang riêng soạn nháp đăng sàn (thay cho drawer cũ). Gồm: thông tin, ngành hàng,
 * thương hiệu, hình ảnh (lấy từ data đã copy + tải/resize thêm), thuộc tính, SKU
 * (giá/tồn/đóng gói/kho), và vận chuyển (chọn kênh/kho/phương thức lấy từ sàn).
 */
export function ListingDraftEditorPage() {
    const { id } = useParams();
    const listingId = Number(id);
    const navigate = useNavigate();
    const { message } = AntApp.useApp();
    const tenantId = useCurrentTenantId();

    const { data: listing, isLoading, isError, error } = useListing(Number.isFinite(listingId) ? listingId : null);
    const updateListing = useUpdateListing();
    const pushListing = usePushListing();
    const aiDescribe = useAiSuggestDescription();
    const [aiSuggestion, setAiSuggestion] = useState<string | null>(null);

    const [description, setDescription] = useState('');
    const [categoryId, setCategoryId] = useState<string | null>(null);
    const [brandId, setBrandId] = useState<string | null>(null);
    const [attributes, setAttributes] = useState<Record<string, unknown>>({});
    const [mediaRefs, setMediaRefs] = useState<string[]>([]);
    const [logistics, setLogistics] = useState<Record<string, unknown>>({});
    const [skus, setSkus] = useState<ListingDraftSku[]>([]);
    const [videoUrl, setVideoUrl] = useState<string | null>(null);
    const [videoUploading, setVideoUploading] = useState(false);
    const [resizerOpen, setResizerOpen] = useState(false);
    const [pushBatchId, setPushBatchId] = useState<number | null>(null);
    const [pushModalOpen, setPushModalOpen] = useState(false);

    useEffect(() => {
        if (!listing) return;
        setDescription(listing.description ?? '');
        setCategoryId(listing.category_id);
        setBrandId(listing.brand_id);
        setAttributes(listing.attributes ?? {});
        setMediaRefs(listing.media_refs ?? []);
        setLogistics(listing.logistics ?? {});
        setSkus(listing.skus ?? []);
        setVideoUrl(listing.video_url ?? null);
    }, [listing]);

    const provider = listing?.provider ?? '';
    const uploadClient = useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
    const { data: limits } = useListingLimits(provider || null);
    const maxImages = limits?.max_images ?? 9;
    const maxVideos = limits?.max_videos ?? 1;
    const channelAccountId = listing?.channel_account_id ?? null;
    const { data: brands } = useBrands(provider || null, channelAccountId, categoryId);

    const updateSku = (sid: number, patch: Partial<ListingDraftSku>) =>
        setSkus((prev) => prev.map((s) => (s.id === sid ? { ...s, ...patch } : s)));

    const buildPayload = (): UpdateListingPayload => ({
        description,
        video_url: videoUrl,
        category_id: categoryId,
        brand_id: brandId,
        attributes,
        media_refs: mediaRefs,
        logistics,
        skus: skus.map((s) => ({
            id: s.id, seller_sku: s.seller_sku, sale_props: s.sale_props, price: s.price, stock: s.stock,
            package_weight: s.package_weight, package_dims: s.package_dims, warehouse_id: s.warehouse_id,
            master_variant_id: s.master_variant_id ?? null, image_ref: s.image_ref ?? null,
        })),
    });

    const handleSave = (after?: () => void) => {
        if (!listing) return;
        updateListing.mutate({ id: listing.id, payload: buildPayload() }, {
            onSuccess: (draft) => {
                if (draft.status === 'ready') message.success('Đã lưu — bản nháp sẵn sàng đẩy lên sàn.');
                else message.warning('Đã lưu nháp — còn lỗi cần sửa trước khi đẩy.');
                after?.();
            },
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const handlePush = () => {
        if (!listing) return;
        pushListing.mutate(listing.id, {
            onSuccess: ({ batch_id }) => { setPushBatchId(batch_id); setPushModalOpen(true); },
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const handleAiSuggest = () => {
        if (!listing) return;
        aiDescribe.mutate(listing.id, {
            onSuccess: (r) => setAiSuggestion(r.description),
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const addImage = (url: string) => {
        const u = url.trim();
        if (u) setMediaRefs((prev) => (prev.includes(u) ? prev : [...prev, u]));
    };

    // Upload ảnh trực tiếp (square tile). Trả false để chặn antd tự upload.
    const uploadImageFile = async (file: File) => {
        if (!uploadClient) return false;
        if (mediaRefs.length >= maxImages) {
            message.warning(`Tối đa ${maxImages} ảnh cho sàn này.`);
            return false;
        }
        try {
            const form = new FormData();
            form.append('image', file);
            form.append('folder', 'listings');
            const { data } = await uploadClient.post<{ data: { url: string } }>('/media/image', form);
            addImage(data.data.url);
        } catch (e) {
            message.error(errorMessage(e));
        }
        return false;
    };

    const uploadVideoFile = async (file: File) => {
        if (!uploadClient) return false;
        setVideoUploading(true);
        try {
            const { url } = await uploadListingVideo(uploadClient, file);
            setVideoUrl(url);
        } catch (e) {
            message.error(errorMessage(e));
        } finally {
            setVideoUploading(false);
        }
        return false;
    };

    const back = () => navigate('/marketplace/products');
    const status = listing?.status ?? 'draft';
    const statusMeta = STATUS_TAG[status] ?? STATUS_TAG.draft;
    const validationErrors = listing?.validation_errors ?? [];

    const skuColumns: ColumnsType<ListingDraftSku> = useMemo(() => {
        const cols: ColumnsType<ListingDraftSku> = [
            { title: 'Ảnh', key: 'image', width: 76, render: (_: unknown, r) => <SkuImageCell url={r.image_ref ?? null} onChange={(u) => updateSku(r.id, { image_ref: u })} /> },
            { title: 'SKU người bán', dataIndex: 'seller_sku', render: (v: string, r) => <Input size="small" value={v} onChange={(e) => updateSku(r.id, { seller_sku: e.target.value })} /> },
            {
                title: 'Phân loại', dataIndex: 'sale_props',
                render: (sp: Record<string, string>) => Object.values(sp ?? {}).length
                    ? <Space size={4} wrap>{Object.values(sp).map((val, i) => <Tag key={i}>{val}</Tag>)}</Space>
                    : <Typography.Text type="secondary">—</Typography.Text>,
            },
            { title: 'Giá (VND)', dataIndex: 'price', width: 120, render: (v: number, r) => <InputNumber size="small" style={{ width: '100%' }} min={0} value={v} onChange={(val) => updateSku(r.id, { price: Number(val ?? 0) })} /> },
            { title: 'Tồn đẩy sàn', dataIndex: 'stock', width: 110, render: (v: number, r) => <InputNumber size="small" style={{ width: '100%' }} min={0} value={v} onChange={(val) => updateSku(r.id, { stock: Number(val ?? 0) })} /> },
            {
                title: 'Liên kết tồn kho (master SKU)', key: 'link', width: 240,
                render: (_: unknown, r) => <SkuLinkSelect sku={r} onLink={(mid) => updateSku(r.id, { master_variant_id: mid })} />,
            },
            { title: 'KL (g)', dataIndex: 'package_weight', width: 90, render: (v: number | null, r) => <InputNumber size="small" style={{ width: '100%' }} min={0} value={v ?? undefined} onChange={(val) => updateSku(r.id, { package_weight: val == null ? null : Number(val) })} /> },
            {
                title: 'KT D×R×C (cm)', key: 'dims', width: 190,
                render: (_: unknown, r) => {
                    const d = r.package_dims ?? {};
                    const setDim = (k: 'length' | 'width' | 'height', val: number | null) => updateSku(r.id, { package_dims: { ...d, [k]: val ?? undefined } });
                    return (
                        <Space size={2}>
                            <InputNumber size="small" style={{ width: 54 }} min={0} value={d.length} placeholder="D" onChange={(v) => setDim('length', v as number | null)} />
                            <InputNumber size="small" style={{ width: 54 }} min={0} value={d.width} placeholder="R" onChange={(v) => setDim('width', v as number | null)} />
                            <InputNumber size="small" style={{ width: 54 }} min={0} value={d.height} placeholder="C" onChange={(v) => setDim('height', v as number | null)} />
                        </Space>
                    );
                },
            },
        ];
        if (provider === 'tiktok') {
            cols.push({ title: 'Kho (warehouse_id)', dataIndex: 'warehouse_id', width: 150, render: (v: string | null, r) => <Input size="small" value={v ?? ''} onChange={(e) => updateSku(r.id, { warehouse_id: e.target.value })} /> });
        }
        return cols;
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [provider, skus]);

    const header = (
        <PageHeader
            title={<Space><Button icon={<ArrowLeftOutlined />} onClick={back}>Quay lại</Button><span>Soạn nháp đăng sàn</span><Tag color={statusMeta.color}>{statusMeta.label}</Tag>{provider && <Tag>{provider}</Tag>}</Space>}
            subtitle="Soạn nội dung, ảnh, SKU và vận chuyển rồi đẩy sản phẩm lên gian hàng."
            extra={
                <Space>
                    <Button icon={<SaveOutlined />} onClick={() => handleSave()} loading={updateListing.isPending}>Lưu nháp</Button>
                    <Button type="primary" icon={<CloudUploadOutlined />} disabled={status !== 'ready'} loading={pushListing.isPending} onClick={handlePush}>Đẩy lên sàn</Button>
                </Space>
            }
        />
    );

    if (isError) return <div>{header}<Result status="error" title="Không tải được bản nháp" subTitle={errorMessage(error)} extra={<Button onClick={back}>Quay lại</Button>} /></div>;
    if (isLoading || !listing) return <div>{header}<div style={{ textAlign: 'center', padding: 48 }}><Spin /></div></div>;

    return (
        <div>
            {header}

            {validationErrors.length > 0 && (
                <Alert type="warning" showIcon style={{ marginBottom: 16 }} message="Cần sửa các lỗi sau trước khi đẩy lên sàn"
                    description={<List size="small" dataSource={validationErrors} renderItem={(err) => <List.Item style={{ padding: '2px 0', border: 'none' }}>{err}</List.Item>} />} />
            )}

            <Card title="Thông tin" style={{ marginBottom: 16 }}>
                <Typography.Text type="secondary">Tên sản phẩm</Typography.Text>
                <Input style={{ marginTop: 4, marginBottom: 12 }} value={listing.name ?? ''} disabled />
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <Typography.Text type="secondary">Mô tả</Typography.Text>
                    <Button size="small" icon={<RobotOutlined />} loading={aiDescribe.isPending} onClick={handleAiSuggest}>AI gợi ý mô tả</Button>
                </div>
                <Input.TextArea style={{ marginTop: 4, marginBottom: 12 }} rows={5} value={description} onChange={(e) => setDescription(e.target.value)} placeholder="Mô tả sản phẩm hiển thị trên sàn" />
                <Typography.Text type="secondary">Ngành hàng</Typography.Text>
                <div style={{ marginTop: 4, marginBottom: 12 }}>
                    {channelAccountId != null && <CategoryPicker provider={provider} channelAccountId={channelAccountId} value={categoryId} onChange={(cid) => { setCategoryId(cid); setBrandId(null); }} />}
                </div>
                <Typography.Text type="secondary">Thương hiệu</Typography.Text>
                <div style={{ marginTop: 4 }}>
                    <Select style={{ width: '100%' }} placeholder="Chọn thương hiệu" disabled={!categoryId} value={brandId ?? undefined} onChange={setBrandId} showSearch optionFilterProp="label" allowClear
                        options={(brands ?? []).map((b) => ({ value: b.id, label: b.mandatory ? `${b.name} (bắt buộc)` : b.name }))} />
                </div>
            </Card>

            <Card
                title="Hình ảnh"
                style={{ marginBottom: 16 }}
                extra={(
                    <Space>
                        <Typography.Text type={mediaRefs.length >= maxImages ? 'danger' : 'secondary'}>{mediaRefs.length}/{maxImages} ảnh</Typography.Text>
                        <Button icon={<PictureOutlined />} disabled={mediaRefs.length >= maxImages} onClick={() => setResizerOpen(true)}>Tải & resize ảnh</Button>
                    </Space>
                )}
            >
                <Space wrap size={12}>
                    {mediaRefs.map((url, idx) => (
                        <div key={`${url}-${idx}`} style={{ position: 'relative', width: 104, height: 104 }}>
                            <Image src={url} width={104} height={104} style={{ objectFit: 'cover', borderRadius: 8, border: '1px solid #f0f0f0' }} />
                            <Button size="small" danger type="primary" shape="circle" icon={<DeleteOutlined />} style={{ position: 'absolute', top: -8, right: -8 }} onClick={() => setMediaRefs((prev) => prev.filter((_, i) => i !== idx))} />
                        </div>
                    ))}
                    {mediaRefs.length < maxImages && (
                        <Upload accept="image/*" showUploadList={false} multiple beforeUpload={(file) => uploadImageFile(file as unknown as File)}>
                            <div style={{ width: 104, height: 104, border: '1px dashed #d9d9d9', borderRadius: 8, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', color: '#8c8c8c', background: '#fafafa' }}>
                                <PlusOutlined style={{ fontSize: 20 }} />
                                <span style={{ fontSize: 12, marginTop: 4 }}>Tải ảnh</span>
                            </div>
                        </Upload>
                    )}
                </Space>
                <Typography.Paragraph type="secondary" style={{ fontSize: 12, marginTop: 12, marginBottom: 0 }}>
                    Ảnh nên vuông (1:1). Dùng “Tải & resize ảnh” để cắt vuông trước khi đăng.
                </Typography.Paragraph>
            </Card>

            {maxVideos > 0 && (
                <Card title="Video" style={{ marginBottom: 16 }}>
                    {videoUrl ? (
                        <Space direction="vertical">
                            <video src={videoUrl} controls style={{ width: 280, maxWidth: '100%', borderRadius: 8, background: '#000' }} />
                            <Button danger size="small" icon={<DeleteOutlined />} onClick={() => setVideoUrl(null)}>Xóa video</Button>
                        </Space>
                    ) : (
                        <Upload accept="video/mp4,video/webm" showUploadList={false} beforeUpload={(file) => uploadVideoFile(file as unknown as File)}>
                            <Button icon={<VideoCameraOutlined />} loading={videoUploading}>Thêm video</Button>
                        </Upload>
                    )}
                    <Typography.Paragraph type="secondary" style={{ fontSize: 12, marginTop: 8, marginBottom: 0 }}>
                        MP4/WebM, tối đa 50MB. Video sẽ được đẩy lên sàn ở bước sau.
                    </Typography.Paragraph>
                </Card>
            )}

            <Card title="Thuộc tính ngành hàng" style={{ marginBottom: 16 }}>
                {channelAccountId != null && <AttributeForm provider={provider} channelAccountId={channelAccountId} categoryId={categoryId} value={attributes} onChange={setAttributes} />}
            </Card>

            <Card title="Phân loại & tồn kho (SKU)" style={{ marginBottom: 16 }}>
                <Alert type="info" showIcon style={{ marginBottom: 12 }}
                    message="Ô “Tồn đẩy sàn” là số lượng khởi tạo đẩy lên sàn, không phải tồn kho của app. Muốn đồng bộ tồn kho về sau, hãy liên kết mỗi dòng với một master SKU có sẵn." />
                <Table size="small" rowKey="id" dataSource={skus} columns={skuColumns} pagination={false} scroll={{ x: true }} />
            </Card>

            <Card title="Vận chuyển">
                <ShippingSection provider={provider} channelAccountId={channelAccountId} value={logistics} onChange={setLogistics}
                    onApplyWarehouse={(wid) => setSkus((prev) => prev.map((s) => ({ ...s, warehouse_id: wid })))} />
            </Card>

            <Modal
                title="Mô tả do AI gợi ý"
                open={aiSuggestion !== null}
                onCancel={() => setAiSuggestion(null)}
                okText="Chấp nhận & thay thế"
                cancelText="Bỏ"
                onOk={() => { if (aiSuggestion !== null) setDescription(aiSuggestion); setAiSuggestion(null); }}
                width={640}
            >
                <Typography.Paragraph type="secondary">Xem trước nội dung AI gợi ý. Chấp nhận sẽ thay thế mô tả hiện tại (nhớ bấm “Lưu nháp”).</Typography.Paragraph>
                <Input.TextArea rows={12} value={aiSuggestion ?? ''} onChange={(e) => setAiSuggestion(e.target.value)} />
            </Modal>

            <ImageResizer open={resizerOpen} onClose={() => setResizerOpen(false)} onUploaded={(url) => addImage(url)} />
            <PushProgressModal batchId={pushBatchId} open={pushModalOpen} onClose={() => { setPushModalOpen(false); back(); }} />
        </div>
    );
}

/** Ô ảnh cho từng SKU/phân loại: xem + tải ảnh thay thế (vuông). */
function SkuImageCell({ url, onChange }: { url: string | null; onChange: (url: string | null) => void }) {
    const tenantId = useCurrentTenantId();
    const client = useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
    const { message } = AntApp.useApp();
    const [busy, setBusy] = useState(false);

    const upload = async (file: File) => {
        if (!client) return false;
        setBusy(true);
        try {
            const form = new FormData();
            form.append('image', file);
            form.append('folder', 'listings');
            const { data } = await client.post<{ data: { url: string } }>('/media/image', form);
            onChange(data.data.url);
        } catch (e) {
            message.error(errorMessage(e));
        } finally {
            setBusy(false);
        }
        return false;
    };

    return (
        <Space direction="vertical" size={2} align="center">
            <Upload accept="image/*" showUploadList={false} beforeUpload={(f) => upload(f as unknown as File)}>
                {url ? (
                    <Image src={url} width={48} height={48} preview={false} style={{ objectFit: 'cover', borderRadius: 6, border: '1px solid #f0f0f0', cursor: 'pointer' }} />
                ) : (
                    <div style={{ width: 48, height: 48, border: '1px dashed #d9d9d9', borderRadius: 6, display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', color: '#8c8c8c' }}>
                        {busy ? <Spin size="small" /> : <PictureOutlined />}
                    </div>
                )}
            </Upload>
            {url && <Button type="link" size="small" danger style={{ padding: 0, height: 'auto', fontSize: 11 }} onClick={() => onChange(null)}>Xóa</Button>}
        </Space>
    );
}

/** Ô tìm & liên kết một dòng SKU nháp với master SKU có sẵn (thủ công, không auto-tạo). */
function SkuLinkSelect({ sku, onLink }: { sku: ListingDraftSku; onLink: (masterVariantId: number | null) => void }) {
    const tenantId = useCurrentTenantId();
    const client = useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
    const [hits, setHits] = useState<MasterSkuRef[]>([]);
    const [loading, setLoading] = useState(false);
    const debounce = useRef<ReturnType<typeof setTimeout> | null>(null);

    const initial = sku.master_sku ? [{ value: sku.master_sku.id, label: `${sku.master_sku.sku_code} — ${sku.master_sku.name}` }] : [];
    const options = hits.length ? hits.map((h) => ({ value: h.id, label: `${h.sku_code} — ${h.name}` })) : initial;

    const run = (term: string) => {
        if (debounce.current) clearTimeout(debounce.current);
        const q = term.trim();
        if (!client || q.length < 1) {
            setHits([]);
            return;
        }
        debounce.current = setTimeout(async () => {
            setLoading(true);
            try {
                setHits(await searchMasterSkus(client, q));
            } finally {
                setLoading(false);
            }
        }, 350);
    };

    return (
        <Select
            size="small"
            style={{ width: '100%' }}
            showSearch
            allowClear
            filterOption={false}
            placeholder="Tìm & liên kết master SKU"
            notFoundContent={loading ? <Spin size="small" /> : null}
            value={sku.master_variant_id ?? undefined}
            onSearch={run}
            onChange={(v) => onLink(v == null ? null : Number(v))}
            options={options}
        />
    );
}

/** Khối vận chuyển — fetch tùy chọn từ sàn theo `mode`. */
function ShippingSection({
    provider, channelAccountId, value, onChange, onApplyWarehouse,
}: {
    provider: string;
    channelAccountId: number | null;
    value: Record<string, unknown>;
    onChange: (v: Record<string, unknown>) => void;
    onApplyWarehouse: (warehouseId: string) => void;
}) {
    const { data: opts, isLoading } = useShippingOptions(provider || null, channelAccountId);

    if (isLoading) return <Spin />;
    if (!opts) return <Typography.Text type="secondary">Không tải được tùy chọn vận chuyển của sàn.</Typography.Text>;

    if (opts.mode === 'channels') return <ShopeeShipping opts={opts} value={value} onChange={onChange} />;
    if (opts.mode === 'warehouse_delivery') return <TikTokShipping opts={opts} value={value} onChange={onChange} onApplyWarehouse={onApplyWarehouse} />;
    return <LazadaShipping notes={opts.notes} />;
}

function ShopeeShipping({ opts, value, onChange }: { opts: ShippingOptions; value: Record<string, unknown>; onChange: (v: Record<string, unknown>) => void }) {
    const channels = (value.channels as Array<{ logistics_channel_id: string }> | undefined) ?? [];
    const selectedIds = channels.map((c) => c.logistics_channel_id);
    const setSelected = (ids: string[]) => onChange({ ...value, channels: ids.map((logistics_channel_id) => ({ logistics_channel_id, enabled: true, is_free: false })) });

    const preOrder = (value.pre_order as { is_pre_order?: boolean; days_to_ship?: number } | undefined) ?? {};
    const isPreOrder = !!preOrder.is_pre_order;
    const setPreOrder = (patch: { is_pre_order?: boolean; days_to_ship?: number }) =>
        onChange({ ...value, pre_order: { ...preOrder, ...patch } });

    return (
        <Space direction="vertical" style={{ width: '100%' }} size="middle">
            <div>
                <Typography.Text type="secondary">Kênh vận chuyển (bật ít nhất 1)</Typography.Text>
                <Checkbox.Group style={{ display: 'flex', flexDirection: 'column', gap: 6, marginTop: 6 }} value={selectedIds} onChange={(v) => setSelected(v as string[])}
                    options={(opts.channels ?? []).map((c) => ({ value: c.id, label: <span>{c.name} <Tag>{c.fee_type}</Tag></span> }))} />
                {(opts.channels ?? []).length === 0 && <Typography.Text type="secondary">Shop chưa bật kênh vận chuyển nào trên Shopee.</Typography.Text>}
            </div>
            <Space>
                <Typography.Text>Khối lượng kiện (g)</Typography.Text>
                <InputNumber min={0} value={value.weight as number | undefined} onChange={(v) => onChange({ ...value, weight: v == null ? undefined : Number(v) })} />
            </Space>
            <Space wrap>
                <Typography.Text>Hàng đặt trước</Typography.Text>
                <Switch checked={isPreOrder} onChange={(checked) => setPreOrder({ is_pre_order: checked, days_to_ship: checked ? (preOrder.days_to_ship ?? 7) : undefined })} />
                {isPreOrder && (
                    <>
                        <Typography.Text>Số ngày chuẩn bị hàng</Typography.Text>
                        <InputNumber min={7} max={30} value={preOrder.days_to_ship ?? 7} onChange={(v) => setPreOrder({ days_to_ship: v == null ? undefined : Number(v) })} />
                        <Typography.Text type="secondary">(7–30 ngày)</Typography.Text>
                    </>
                )}
            </Space>
        </Space>
    );
}

function TikTokShipping({
    opts, value, onChange, onApplyWarehouse,
}: { opts: ShippingOptions; value: Record<string, unknown>; onChange: (v: Record<string, unknown>) => void; onApplyWarehouse: (w: string) => void }) {
    const deliveryIds = (value.delivery_option_ids as string[] | undefined) ?? [];
    return (
        <Space direction="vertical" style={{ width: '100%' }} size="middle">
            <div>
                <Typography.Text type="secondary">Kho xuất hàng (áp dụng cho mọi SKU)</Typography.Text>
                <div style={{ marginTop: 6 }}>
                    <Radio.Group onChange={(e) => { onChange({ ...value, warehouse_id: e.target.value }); onApplyWarehouse(e.target.value); }} value={value.warehouse_id}>
                        <Space direction="vertical">
                            {(opts.warehouses ?? []).map((w) => <Radio key={w.id} value={w.id}>{w.name} {w.is_default && <Tag color="blue">mặc định</Tag>}</Radio>)}
                        </Space>
                    </Radio.Group>
                    {(opts.warehouses ?? []).length === 0 && <Typography.Text type="secondary">Chưa có kho nào.</Typography.Text>}
                </div>
            </div>
            <div>
                <Typography.Text type="secondary">Phương thức giao hàng</Typography.Text>
                <Checkbox.Group style={{ display: 'flex', flexDirection: 'column', gap: 6, marginTop: 6 }} value={deliveryIds}
                    onChange={(v) => onChange({ ...value, delivery_option_ids: v as string[] })}
                    options={(opts.delivery_options ?? []).map((d) => ({ value: d.id, label: d.name }))} />
            </div>
            <Space>
                <Typography.Text>Khối lượng kiện (kg)</Typography.Text>
                <InputNumber min={0} step={0.1} value={value.package_weight as number | undefined} onChange={(v) => onChange({ ...value, package_weight: v == null ? undefined : Number(v), weight_unit: 'KILOGRAM' })} />
            </Space>
        </Space>
    );
}

function LazadaShipping({ notes }: { notes?: string }) {
    // Lazada VN: KHÔNG có hình thức người bán tự vận chuyển (SOF) — đơn dùng vận chuyển của sàn.
    return (
        <Space direction="vertical" style={{ width: '100%' }} size="middle">
            {notes && <Typography.Text type="secondary">{notes}</Typography.Text>}
            <Alert
                type="info"
                showIcon
                message="Lazada Việt Nam dùng vận chuyển của sàn — không có tùy chọn người bán tự giao (SOF). Không cần cấu hình thêm ở đây."
            />
        </Space>
    );
}
