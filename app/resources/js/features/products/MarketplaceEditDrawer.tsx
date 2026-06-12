import { useEffect, useMemo, useState } from 'react';
import { App as AntApp, Button, Divider, Drawer, Input, InputNumber, List, Space, Spin, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { DeleteOutlined, PlusOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import type { ChannelListing } from '@/lib/inventory';
import { useMarketplaceDetail, useUpdateMarketplaceListing } from './hooks';
import type { MarketplaceEditPayload } from './api';

interface PriceRow {
    external_sku_id: string;
    seller_sku: string;
    price: number;
}

/**
 * Sửa một sản phẩm đã có trên sàn: tiêu đề, mô tả, ảnh, giá từng SKU — đẩy thẳng
 * lên sàn (Lazada / Shopee / TikTok). KHÔNG sửa tồn (tồn đẩy theo master SKU).
 *
 * Chỉ gửi field đã thay đổi để tránh ghi đè / upload lại ảnh không cần thiết.
 */
export function MarketplaceEditDrawer({
    listing,
    open,
    onClose,
}: {
    listing: ChannelListing | null;
    open: boolean;
    onClose: (changed: boolean) => void;
}) {
    const { message } = AntApp.useApp();
    const { data: detail, isLoading } = useMarketplaceDetail(open ? (listing?.id ?? null) : null);
    const update = useUpdateMarketplaceListing();

    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [images, setImages] = useState<string[]>([]);
    const [prices, setPrices] = useState<PriceRow[]>([]);
    const [newImageUrl, setNewImageUrl] = useState('');

    useEffect(() => {
        if (!detail) return;
        setTitle(detail.title);
        setDescription(detail.description);
        setImages(detail.images);
        setPrices(detail.skus.map((s) => ({ external_sku_id: s.external_sku_id, seller_sku: s.seller_sku, price: s.price })));
    }, [detail]);

    // Diff so chỉ field thay đổi mới gửi lên.
    const payload = useMemo<MarketplaceEditPayload>(() => {
        const p: MarketplaceEditPayload = {};
        if (!detail) return p;
        if (title !== detail.title) p.title = title;
        if (description !== detail.description) p.description = description;
        if (JSON.stringify(images) !== JSON.stringify(detail.images)) p.images = images;
        const changedPrices = prices
            .filter((row) => {
                const orig = detail.skus.find((s) => s.external_sku_id === row.external_sku_id);
                return orig && orig.price !== row.price;
            })
            .map((row) => ({ external_sku_id: row.external_sku_id, price: row.price }));
        if (changedPrices.length) p.prices = changedPrices;
        return p;
    }, [detail, title, description, images, prices]);

    const hasChanges = Object.keys(payload).length > 0;

    const setPrice = (skuId: string, price: number) =>
        setPrices((prev) => prev.map((r) => (r.external_sku_id === skuId ? { ...r, price } : r)));

    const addImage = () => {
        const u = newImageUrl.trim();
        if (u) {
            setImages((prev) => [...prev, u]);
            setNewImageUrl('');
        }
    };

    const handleSave = () => {
        if (!listing || !hasChanges) return;
        update.mutate(
            { id: listing.id, payload },
            {
                onSuccess: () => {
                    message.success('Đã cập nhật sản phẩm trên sàn.');
                    onClose(true);
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
            width: 180,
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

    return (
        <Drawer
            title={
                <Space>
                    <span>Sửa sản phẩm trên sàn</span>
                    {listing && <Tag>{listing.title ?? listing.external_sku_id}</Tag>}
                </Space>
            }
            width={720}
            open={open}
            onClose={() => onClose(false)}
            extra={
                <Button type="primary" disabled={!hasChanges} loading={update.isPending} onClick={handleSave}>
                    Lưu & đẩy lên sàn
                </Button>
            }
        >
            {isLoading || !detail ? (
                <Spin />
            ) : (
                <div>
                    <Typography.Text type="secondary">Tiêu đề</Typography.Text>
                    <div style={{ marginBottom: 12, marginTop: 4 }}>
                        <Input value={title} onChange={(e) => setTitle(e.target.value)} maxLength={300} showCount />
                    </div>

                    <Typography.Text type="secondary">Mô tả</Typography.Text>
                    <div style={{ marginBottom: 12, marginTop: 4 }}>
                        <Input.TextArea rows={6} value={description} onChange={(e) => setDescription(e.target.value)} />
                    </div>

                    <Divider orientation="left" plain>Hình ảnh</Divider>
                    <List
                        size="small"
                        dataSource={images}
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
                                        onClick={() => setImages((prev) => prev.filter((_, i) => i !== idx))}
                                    />,
                                ]}
                            >
                                <Typography.Text ellipsis style={{ maxWidth: 560 }}>{url}</Typography.Text>
                            </List.Item>
                        )}
                    />
                    <Space.Compact style={{ width: '100%', marginTop: 8 }}>
                        <Input placeholder="Dán URL ảnh rồi bấm Thêm" value={newImageUrl} onChange={(e) => setNewImageUrl(e.target.value)} onPressEnter={addImage} />
                        <Button icon={<PlusOutlined />} onClick={addImage}>Thêm</Button>
                    </Space.Compact>

                    <Divider orientation="left" plain>Giá theo SKU</Divider>
                    <Table<PriceRow> rowKey="external_sku_id" size="small" dataSource={prices} columns={priceColumns} pagination={false} />

                    <Typography.Paragraph type="secondary" style={{ marginTop: 16, fontSize: 12 }}>
                        Tồn kho không sửa ở đây — tồn được đẩy lên sàn theo master SKU (mục Tồn kho → Liên kết SKU).
                    </Typography.Paragraph>
                </div>
            )}
        </Drawer>
    );
}
