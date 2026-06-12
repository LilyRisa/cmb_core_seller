import { useEffect, useMemo, useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { Alert, App as AntApp, Button, Card, Image, Input, InputNumber, Result, Space, Spin, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { ArrowLeftOutlined, DeleteOutlined, PictureOutlined, PlusOutlined, SaveOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { ImageResizer } from '@/components/ImageResizer';
import { errorMessage } from '@/lib/api';
import type { ChannelListing } from '@/lib/inventory';
import { useMarketplaceDetail, useUpdateMarketplaceListing } from '@/features/products/hooks';
import type { MarketplaceEditPayload, MarketplaceListingDetail } from '@/features/products/api';

interface PriceRow {
    external_sku_id: string;
    seller_sku: string;
    price: number;
}

/**
 * Trang riêng sửa một sản phẩm đã có trên sàn (tiêu đề / mô tả / ảnh / giá theo SKU).
 *
 * KHÔNG chặn UI bằng lời gọi live API: form được seed ngay từ dữ liệu `ChannelListing`
 * truyền qua navigation state; chi tiết đầy đủ (mô tả, mọi ảnh, mọi SKU) tải ở nền và
 * bổ sung khi tới. Lỗi/chậm khi tải chi tiết chỉ hiện cảnh báo, vẫn sửa được phần cơ bản.
 * Tồn KHÔNG sửa ở đây (đẩy theo master SKU).
 */
export function MarketplaceEditPage() {
    const { id } = useParams();
    const listingId = Number(id);
    const navigate = useNavigate();
    const location = useLocation();
    const { message } = AntApp.useApp();

    const seed = (location.state as { listing?: ChannelListing } | null)?.listing;

    const { data: detail, isLoading, isError, error } = useMarketplaceDetail(Number.isFinite(listingId) ? listingId : null);
    const update = useUpdateMarketplaceListing();

    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [images, setImages] = useState<string[]>([]);
    const [prices, setPrices] = useState<PriceRow[]>([]);
    const [newImageUrl, setNewImageUrl] = useState('');
    const [resizerOpen, setResizerOpen] = useState(false);
    const [touched, setTouched] = useState(false);
    const touch = () => setTouched(true);

    // Baseline để hiển thị & tính diff: ưu tiên chi tiết từ sàn, nếu chưa có thì dùng
    // dữ liệu listing đã truyền sang (tiêu đề/ảnh/1 SKU). null = chưa có gì để sửa.
    const baseline = useMemo<MarketplaceListingDetail | null>(() => {
        if (detail) return detail;
        if (seed) {
            return {
                external_product_id: seed.external_product_id ?? '',
                title: seed.title ?? '',
                description: '',
                images: seed.image ? [seed.image] : [],
                skus: [{ external_sku_id: seed.external_sku_id, seller_sku: seed.seller_sku ?? '', price: seed.price ?? 0 }],
            };
        }
        return null;
    }, [detail, seed]);

    // Seed form từ baseline; khi chi tiết đầy đủ tới sẽ tái-seed (chỉ khi user CHƯA sửa).
    useEffect(() => {
        if (touched) return;
        if (!baseline) return;
        setTitle(baseline.title);
        setDescription(baseline.description);
        setImages(baseline.images);
        setPrices(baseline.skus.map((s) => ({ external_sku_id: s.external_sku_id, seller_sku: s.seller_sku, price: s.price })));
    }, [baseline, detail, touched]);

    const payload = useMemo<MarketplaceEditPayload>(() => {
        const p: MarketplaceEditPayload = {};
        if (!baseline) return p;
        if (title !== baseline.title) p.title = title;
        if (description !== baseline.description) p.description = description;
        if (JSON.stringify(images) !== JSON.stringify(baseline.images)) p.images = images;
        const changed = prices
            .filter((row) => {
                const orig = baseline.skus.find((s) => s.external_sku_id === row.external_sku_id);
                return orig && orig.price !== row.price;
            })
            .map((row) => ({ external_sku_id: row.external_sku_id, price: row.price }));
        if (changed.length) p.prices = changed;
        return p;
    }, [baseline, title, description, images, prices]);

    const hasChanges = Object.keys(payload).length > 0;
    const back = () => navigate('/marketplace/on-channel');

    const setPrice = (skuId: string, price: number) => {
        touch();
        setPrices((prev) => prev.map((r) => (r.external_sku_id === skuId ? { ...r, price } : r)));
    };

    const addImageUrl = () => {
        const u = newImageUrl.trim();
        if (u) {
            touch();
            setImages((prev) => [...prev, u]);
            setNewImageUrl('');
        }
    };

    const handleSave = () => {
        if (!hasChanges) return;
        update.mutate(
            { id: listingId, payload },
            {
                onSuccess: () => {
                    message.success('Đã cập nhật sản phẩm trên sàn.');
                    back();
                },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    };

    const priceColumns: ColumnsType<PriceRow> = [
        {
            title: 'SKU',
            key: 'sku',
            render: (_, r) => (
                <Space size={4}>
                    <span>{r.seller_sku || r.external_sku_id}</span>
                    {!r.seller_sku && <Tag>{r.external_sku_id}</Tag>}
                </Space>
            ),
        },
        {
            title: 'Giá (VND)',
            key: 'price',
            width: 220,
            render: (_, r) => (
                <InputNumber
                    style={{ width: '100%' }}
                    min={0}
                    value={r.price}
                    formatter={(v) => `${v}`.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}
                    parser={(v) => Number((v ?? '').replace(/\./g, ''))}
                    onChange={(v) => setPrice(r.external_sku_id, Number(v ?? 0))}
                />
            ),
        },
    ];

    const header = (
        <PageHeader
            title={
                <Space>
                    <Button icon={<ArrowLeftOutlined />} onClick={back}>Quay lại</Button>
                    <span>Sửa sản phẩm trên sàn</span>
                </Space>
            }
            subtitle="Chỉnh tiêu đề / mô tả / ảnh / giá rồi đẩy lên sàn. Tồn kho đẩy theo master SKU ở mục Tồn kho."
            extra={
                <Button type="primary" icon={<SaveOutlined />} disabled={!hasChanges} loading={update.isPending} onClick={handleSave}>
                    Lưu & đẩy lên sàn
                </Button>
            }
        />
    );

    // Không có seed lẫn chi tiết: chỉ khi mở trực tiếp/refresh URL. Chờ tải, lỗi thì báo.
    if (!baseline) {
        return (
            <div>
                {header}
                {isError ? (
                    <Result status="error" title="Không tải được sản phẩm từ sàn" subTitle={errorMessage(error)} extra={<Button onClick={back}>Quay lại</Button>} />
                ) : (
                    <div style={{ textAlign: 'center', padding: 48 }}>
                        <Spin tip="Đang tải sản phẩm từ sàn…"><div style={{ height: 1 }} /></Spin>
                    </div>
                )}
            </div>
        );
    }

    return (
        <div>
            {header}

            {isLoading && !detail && (
                <Alert style={{ marginBottom: 16 }} type="info" showIcon message="Đang tải mô tả & các SKU khác từ sàn…" />
            )}
            {isError && (
                <Alert
                    style={{ marginBottom: 16 }}
                    type="warning"
                    showIcon
                    message="Không tải được chi tiết đầy đủ từ sàn"
                    description="Bạn vẫn sửa được tiêu đề, ảnh và giá cơ bản. Mô tả và các SKU khác (nếu có) tạm thời không hiển thị."
                />
            )}

            <Card title="Thông tin" style={{ marginBottom: 16 }}>
                <Typography.Text type="secondary">Tiêu đề</Typography.Text>
                <Input style={{ marginTop: 4, marginBottom: 16 }} value={title} onChange={(e) => { touch(); setTitle(e.target.value); }} maxLength={300} showCount />

                <Typography.Text type="secondary">Mô tả</Typography.Text>
                <Input.TextArea style={{ marginTop: 4 }} rows={6} value={description} onChange={(e) => { touch(); setDescription(e.target.value); }} />
            </Card>

            <Card
                title="Hình ảnh"
                style={{ marginBottom: 16 }}
                extra={<Button icon={<PictureOutlined />} onClick={() => setResizerOpen(true)}>Tải & resize ảnh</Button>}
            >
                <Space wrap size={12}>
                    {images.map((url, idx) => (
                        <div key={`${url}-${idx}`} style={{ position: 'relative', width: 110, height: 110 }}>
                            <Image src={url} width={110} height={110} style={{ objectFit: 'cover', borderRadius: 8 }} />
                            <Button
                                size="small"
                                danger
                                type="primary"
                                shape="circle"
                                icon={<DeleteOutlined />}
                                style={{ position: 'absolute', top: -8, right: -8 }}
                                onClick={() => { touch(); setImages((prev) => prev.filter((_, i) => i !== idx)); }}
                            />
                        </div>
                    ))}
                    {images.length === 0 && <Typography.Text type="secondary">Chưa có ảnh</Typography.Text>}
                </Space>
                <Space.Compact style={{ width: '100%', maxWidth: 520, marginTop: 16 }}>
                    <Input placeholder="Hoặc dán URL ảnh rồi bấm Thêm" value={newImageUrl} onChange={(e) => setNewImageUrl(e.target.value)} onPressEnter={addImageUrl} />
                    <Button icon={<PlusOutlined />} onClick={addImageUrl}>Thêm</Button>
                </Space.Compact>
            </Card>

            <Card title="Giá theo SKU">
                <Table<PriceRow> rowKey="external_sku_id" size="small" dataSource={prices} columns={priceColumns} pagination={false} />
            </Card>

            <ImageResizer
                open={resizerOpen}
                onClose={() => setResizerOpen(false)}
                onUploaded={(url) => { touch(); setImages((prev) => [...prev, url]); }}
            />
        </div>
    );
}
