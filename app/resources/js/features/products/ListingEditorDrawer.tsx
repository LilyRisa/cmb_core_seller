import { useEffect, useMemo, useState } from 'react';
import {
    Alert,
    App as AntApp,
    Button,
    Divider,
    Drawer,
    Input,
    InputNumber,
    List,
    Radio,
    Select,
    Space,
    Spin,
    Table,
    Tag,
    Typography,
} from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { DeleteOutlined, PlusOutlined, CloudUploadOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { useBrands, useListing, usePushListing, useUpdateListing } from './hooks';
import type { ListingDraftSku, UpdateListingPayload } from './api';
import { CategoryPicker } from './CategoryPicker';
import { AttributeForm } from './AttributeForm';
import { PushProgressModal } from './PushProgressModal';

const STATUS_TAG: Record<string, { color: string; label: string }> = {
    draft: { color: 'default', label: 'Nháp' },
    ready: { color: 'green', label: 'Sẵn sàng' },
    pushing: { color: 'blue', label: 'Đang đẩy' },
    published: { color: 'success', label: 'Đã đăng' },
    failed: { color: 'red', label: 'Lỗi' },
};

export function ListingEditorDrawer({
    listingId,
    open,
    onClose,
}: {
    listingId: number | null;
    open: boolean;
    onClose: () => void;
}) {
    const { message } = AntApp.useApp();
    const { data: listing, isLoading } = useListing(open ? listingId : null);
    const updateListing = useUpdateListing();
    const pushListing = usePushListing();

    // Local editable state
    const [description, setDescription] = useState('');
    const [categoryId, setCategoryId] = useState<string | null>(null);
    const [brandId, setBrandId] = useState<string | null>(null);
    const [attributes, setAttributes] = useState<Record<string, unknown>>({});
    const [mediaRefs, setMediaRefs] = useState<string[]>([]);
    const [logistics, setLogistics] = useState<Record<string, unknown>>({});
    const [skus, setSkus] = useState<ListingDraftSku[]>([]);
    const [newImageUrl, setNewImageUrl] = useState('');
    const [pushBatchId, setPushBatchId] = useState<number | null>(null);
    const [pushModalOpen, setPushModalOpen] = useState(false);

    // Hydrate khi listing tải xong / đổi
    useEffect(() => {
        if (!listing) return;
        setDescription(listing.description ?? '');
        setCategoryId(listing.category_id);
        setBrandId(listing.brand_id);
        setAttributes(listing.attributes ?? {});
        setMediaRefs(listing.media_refs ?? []);
        setLogistics(listing.logistics ?? {});
        setSkus(listing.skus ?? []);
    }, [listing]);

    const provider = listing?.provider ?? '';
    const channelAccountId = listing?.channel_account_id ?? null;

    const { data: brands } = useBrands(provider || null, channelAccountId, categoryId);

    const updateSku = (id: number, patch: Partial<ListingDraftSku>) => {
        setSkus((prev) => prev.map((s) => (s.id === id ? { ...s, ...patch } : s)));
    };

    const buildPayload = (): UpdateListingPayload => ({
        description,
        category_id: categoryId,
        brand_id: brandId,
        attributes,
        media_refs: mediaRefs,
        logistics,
        skus: skus.map((s) => ({
            id: s.id,
            seller_sku: s.seller_sku,
            sale_props: s.sale_props,
            price: s.price,
            stock: s.stock,
            package_weight: s.package_weight,
            package_dims: s.package_dims,
            warehouse_id: s.warehouse_id,
        })),
    });

    const handleSave = () => {
        if (!listing) return;
        updateListing.mutate(
            { id: listing.id, payload: buildPayload() },
            {
                onSuccess: (draft) => {
                    if (draft.status === 'ready') message.success('Đã lưu — bản nháp sẵn sàng đẩy lên sàn.');
                    else message.warning('Đã lưu nháp — còn lỗi cần sửa trước khi đẩy.');
                },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    };

    const handlePush = () => {
        if (!listing) return;
        pushListing.mutate(listing.id, {
            onSuccess: ({ batch_id }) => {
                setPushBatchId(batch_id);
                setPushModalOpen(true);
            },
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const status = listing?.status ?? 'draft';
    const statusMeta = STATUS_TAG[status] ?? STATUS_TAG.draft;
    const validationErrors = listing?.validation_errors ?? [];

    const skuColumns: ColumnsType<ListingDraftSku> = useMemo(() => {
        const cols: ColumnsType<ListingDraftSku> = [
            {
                title: 'SKU người bán',
                dataIndex: 'seller_sku',
                render: (v: string, r) => (
                    <Input
                        size="small"
                        value={v}
                        onChange={(e) => updateSku(r.id, { seller_sku: e.target.value })}
                    />
                ),
            },
            {
                title: 'Phân loại',
                dataIndex: 'sale_props',
                render: (sp: Record<string, string>) =>
                    Object.values(sp ?? {}).length ? (
                        <Space size={4} wrap>
                            {Object.values(sp).map((val, i) => (
                                <Tag key={i}>{val}</Tag>
                            ))}
                        </Space>
                    ) : (
                        <Typography.Text type="secondary">—</Typography.Text>
                    ),
            },
            {
                title: 'Giá (VND)',
                dataIndex: 'price',
                width: 130,
                render: (v: number, r) => (
                    <InputNumber
                        size="small"
                        style={{ width: '100%' }}
                        min={0}
                        value={v}
                        onChange={(val) => updateSku(r.id, { price: Number(val ?? 0) })}
                    />
                ),
            },
            {
                title: 'Tồn',
                dataIndex: 'stock',
                width: 100,
                render: (v: number, r) => (
                    <InputNumber
                        size="small"
                        style={{ width: '100%' }}
                        min={0}
                        value={v}
                        onChange={(val) => updateSku(r.id, { stock: Number(val ?? 0) })}
                    />
                ),
            },
            {
                title: 'KL (g)',
                dataIndex: 'package_weight',
                width: 100,
                render: (v: number | null, r) => (
                    <InputNumber
                        size="small"
                        style={{ width: '100%' }}
                        min={0}
                        value={v ?? undefined}
                        onChange={(val) => updateSku(r.id, { package_weight: val == null ? null : Number(val) })}
                    />
                ),
            },
            {
                title: 'KT D×R×C (cm)',
                key: 'dims',
                width: 200,
                render: (_: unknown, r) => {
                    const d = r.package_dims ?? {};
                    const setDim = (k: 'length' | 'width' | 'height', val: number | null) =>
                        updateSku(r.id, { package_dims: { ...d, [k]: val ?? undefined } });
                    return (
                        <Space size={2}>
                            <InputNumber size="small" style={{ width: 56 }} min={0} value={d.length} placeholder="D" onChange={(v) => setDim('length', v as number | null)} />
                            <InputNumber size="small" style={{ width: 56 }} min={0} value={d.width} placeholder="R" onChange={(v) => setDim('width', v as number | null)} />
                            <InputNumber size="small" style={{ width: 56 }} min={0} value={d.height} placeholder="C" onChange={(v) => setDim('height', v as number | null)} />
                        </Space>
                    );
                },
            },
        ];
        // TikTok: warehouse_id per SKU
        if (provider === 'tiktok') {
            cols.push({
                title: 'Kho (warehouse_id)',
                dataIndex: 'warehouse_id',
                width: 160,
                render: (v: string | null, r) => (
                    <Input
                        size="small"
                        value={v ?? ''}
                        onChange={(e) => updateSku(r.id, { warehouse_id: e.target.value })}
                    />
                ),
            });
        }
        return cols;
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [provider, skus]);

    return (
        <Drawer
            title={
                <Space>
                    <span>{listing?.name ?? 'Soạn bản nháp đăng sàn'}</span>
                    <Tag color={statusMeta.color}>{statusMeta.label}</Tag>
                    {provider && <Tag>{provider}</Tag>}
                </Space>
            }
            width={860}
            open={open}
            onClose={onClose}
            extra={
                <Space>
                    <Button onClick={handleSave} loading={updateListing.isPending}>
                        Lưu nháp
                    </Button>
                    <Button
                        type="primary"
                        icon={<CloudUploadOutlined />}
                        disabled={status !== 'ready'}
                        loading={pushListing.isPending}
                        onClick={handlePush}
                    >
                        Đẩy lên sàn
                    </Button>
                </Space>
            }
        >
            {isLoading || !listing ? (
                <Spin />
            ) : (
                <div>
                    {validationErrors.length > 0 && (
                        <Alert
                            type="warning"
                            showIcon
                            style={{ marginBottom: 16 }}
                            message="Cần sửa các lỗi sau trước khi đẩy lên sàn"
                            description={
                                <List
                                    size="small"
                                    dataSource={validationErrors}
                                    renderItem={(err) => <List.Item style={{ padding: '2px 0', border: 'none' }}>{err}</List.Item>}
                                />
                            }
                        />
                    )}

                    <Typography.Text type="secondary">Tên sản phẩm</Typography.Text>
                    <div style={{ marginBottom: 12 }}>
                        <Input value={listing.name ?? ''} disabled />
                    </div>

                    <Typography.Text type="secondary">Mô tả</Typography.Text>
                    <div style={{ marginBottom: 12, marginTop: 4 }}>
                        <Input.TextArea
                            rows={4}
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            placeholder="Mô tả sản phẩm hiển thị trên sàn"
                        />
                    </div>

                    <Typography.Text type="secondary">Ngành hàng</Typography.Text>
                    <div style={{ marginBottom: 12, marginTop: 4 }}>
                        {channelAccountId != null && (
                            <CategoryPicker
                                provider={provider}
                                channelAccountId={channelAccountId}
                                value={categoryId}
                                onChange={(id) => {
                                    setCategoryId(id);
                                    setBrandId(null);
                                }}
                            />
                        )}
                    </div>

                    <Typography.Text type="secondary">Thương hiệu</Typography.Text>
                    <div style={{ marginBottom: 12, marginTop: 4 }}>
                        <Select
                            style={{ width: '100%' }}
                            placeholder="Chọn thương hiệu"
                            disabled={!categoryId}
                            value={brandId ?? undefined}
                            onChange={(v) => setBrandId(v)}
                            showSearch
                            optionFilterProp="label"
                            allowClear
                            options={(brands ?? []).map((b) => ({
                                value: b.id,
                                label: b.mandatory ? `${b.name} (bắt buộc)` : b.name,
                            }))}
                        />
                    </div>

                    <Divider orientation="left" plain>
                        Hình ảnh
                    </Divider>
                    <List
                        size="small"
                        dataSource={mediaRefs}
                        locale={{ emptyText: 'Chưa có ảnh' }}
                        renderItem={(url, idx) => (
                            <List.Item
                                actions={[
                                    <Button
                                        key="del"
                                        size="small"
                                        type="text"
                                        danger
                                        icon={<DeleteOutlined />}
                                        onClick={() => setMediaRefs((prev) => prev.filter((_, i) => i !== idx))}
                                    />,
                                ]}
                            >
                                <Typography.Text ellipsis style={{ maxWidth: 600 }}>
                                    {url}
                                </Typography.Text>
                            </List.Item>
                        )}
                    />
                    <Space.Compact style={{ width: '100%', marginTop: 8 }}>
                        <Input
                            placeholder="Dán URL ảnh rồi bấm Thêm"
                            value={newImageUrl}
                            onChange={(e) => setNewImageUrl(e.target.value)}
                            onPressEnter={() => {
                                const u = newImageUrl.trim();
                                if (u) {
                                    setMediaRefs((prev) => [...prev, u]);
                                    setNewImageUrl('');
                                }
                            }}
                        />
                        <Button
                            icon={<PlusOutlined />}
                            onClick={() => {
                                const u = newImageUrl.trim();
                                if (u) {
                                    setMediaRefs((prev) => [...prev, u]);
                                    setNewImageUrl('');
                                }
                            }}
                        >
                            Thêm
                        </Button>
                    </Space.Compact>

                    <Divider orientation="left" plain>
                        Thuộc tính ngành hàng
                    </Divider>
                    {channelAccountId != null && (
                        <AttributeForm
                            provider={provider}
                            channelAccountId={channelAccountId}
                            categoryId={categoryId}
                            value={attributes}
                            onChange={setAttributes}
                        />
                    )}

                    <Divider orientation="left" plain>
                        Phân loại & tồn kho (SKU)
                    </Divider>
                    <Table
                        size="small"
                        rowKey="id"
                        dataSource={skus}
                        columns={skuColumns}
                        pagination={false}
                        scroll={{ x: true }}
                    />

                    <Divider orientation="left" plain>
                        Vận chuyển
                    </Divider>
                    <LogisticsSection provider={provider} value={logistics} onChange={setLogistics} />
                </div>
            )}

            <PushProgressModal
                batchId={pushBatchId}
                open={pushModalOpen}
                onClose={() => {
                    setPushModalOpen(false);
                    onClose();
                }}
            />
        </Drawer>
    );
}

/** Vận chuyển tối thiểu theo provider: lazada không thêm; tiktok per-SKU (đã ở bảng) → ghi chú; shopee toggle + KL. */
function LogisticsSection({
    provider,
    value,
    onChange,
}: {
    provider: string;
    value: Record<string, unknown>;
    onChange: (v: Record<string, unknown>) => void;
}) {
    if (provider === 'lazada') {
        return <Typography.Text type="secondary">Lazada không cần cấu hình vận chuyển bổ sung ở bước này.</Typography.Text>;
    }
    if (provider === 'tiktok') {
        return (
            <Typography.Text type="secondary">
                TikTok: cấu hình kho (warehouse_id) và khối lượng theo từng SKU ở bảng phía trên.
            </Typography.Text>
        );
    }
    if (provider === 'shopee') {
        const enabled = !!(value.logistics_enabled as boolean);
        const weight = value.weight as number | undefined;
        return (
            <Space direction="vertical" style={{ width: '100%' }}>
                <Space>
                    <Typography.Text>Bật kênh vận chuyển Shopee</Typography.Text>
                    <Radio.Group
                        size="small"
                        optionType="button"
                        value={enabled ? 'on' : 'off'}
                        onChange={(e) => onChange({ ...value, logistics_enabled: e.target.value === 'on' })}
                        options={[
                            { value: 'on', label: 'Bật' },
                            { value: 'off', label: 'Tắt' },
                        ]}
                    />
                </Space>
                <Space>
                    <Typography.Text>Khối lượng kiện (g)</Typography.Text>
                    <InputNumber
                        min={0}
                        value={weight}
                        onChange={(v) => onChange({ ...value, weight: v == null ? undefined : Number(v) })}
                    />
                </Space>
            </Space>
        );
    }
    return <Typography.Text type="secondary">Không có cấu hình vận chuyển cho sàn này.</Typography.Text>;
}
