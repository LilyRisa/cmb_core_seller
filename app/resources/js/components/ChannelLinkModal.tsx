import { useEffect, useMemo, useState } from 'react';
import { Avatar, Button, Empty, Input, List, Modal, Select, Space, Tag, Typography } from 'antd';
import { CheckCircleFilled, DeleteOutlined, PlusOutlined, SearchOutlined } from '@ant-design/icons';
import { useChannelListings, type ChannelListing } from '@/lib/inventory';
import { ChannelLogo } from '@/components/ChannelLogo';

/** Listing sàn đã chọn (chỉ để hiển thị tên/ảnh/biến thể — không gửi lên server). */
export interface PickedListing {
    external_sku_id: string;
    seller_sku: string | null;
    title: string | null;
    image: string | null;
    variation: string | null;
    channel_stock: number | null;
}

/** Một dòng ghép nối SKU ⇄ SKU sàn. */
export interface LinkRow {
    channel_account_id?: number;
    external_sku_id?: string;
    seller_sku?: string;
    _listing?: PickedListing;
}

export interface ShopRef {
    id: number;
    name: string;
    provider: string;
}

const { Text } = Typography;

function keyOf(accountId: number, ext: string): string {
    return `${accountId}::${ext}`;
}

function rowKey(r: LinkRow): string {
    return keyOf(r.channel_account_id ?? 0, r.external_sku_id ?? '');
}

/**
 * Modal liên kết SKU hàng hoá với SKU/biến thể trên sàn — 2 phần:
 *  - TRÁI: bộ lọc gian hàng + ô tìm theo tên sản phẩm sàn → danh sách listing (bấm để chọn).
 *  - PHẢI: danh sách đã chọn (gỡ nhanh).
 * Thao tác chọn nhiều, nhiều gian hàng, rồi "Xong" một lần — nhanh hơn form từng dòng.
 */
export function ChannelLinkModal({ open, onClose, value, onChange, shops }: {
    open: boolean;
    onClose: () => void;
    value: LinkRow[];
    onChange: (rows: LinkRow[]) => void;
    shops: ShopRef[];
}) {
    return (
        <Modal open={open} onCancel={onClose} width={940} footer={null} destroyOnClose
            title={<Space><PlusOutlined />Liên kết sản phẩm sàn</Space>}>
            {open && <ChannelLinkBody onClose={onClose} value={value} onChange={onChange} shops={shops} />}
        </Modal>
    );
}

function ChannelLinkBody({ onClose, value, onChange, shops }: {
    onClose: () => void;
    value: LinkRow[];
    onChange: (rows: LinkRow[]) => void;
    shops: ShopRef[];
}) {
    const [working, setWorking] = useState<LinkRow[]>(value ?? []);
    const [shopFilter, setShopFilter] = useState<number | 'all'>(shops.length === 1 ? shops[0].id : 'all');
    const [term, setTerm] = useState('');
    const [debounced, setDebounced] = useState('');
    useEffect(() => {
        const t = setTimeout(() => setDebounced(term.trim()), 300);
        return () => clearTimeout(t);
    }, [term]);

    const { data, isFetching } = useChannelListings({
        channel_account_id: shopFilter === 'all' ? undefined : shopFilter,
        q: debounced || undefined,
        per_page: 50,
    });
    const listings: ChannelListing[] = data?.data ?? [];

    const shopById = useMemo(() => {
        const m: Record<number, ShopRef> = {};
        shops.forEach((s) => { m[s.id] = s; });
        return m;
    }, [shops]);

    const selectedKeys = useMemo(() => new Set(working.map(rowKey)), [working]);

    const toggle = (l: ChannelListing) => {
        const k = keyOf(l.channel_account_id, l.external_sku_id);
        if (selectedKeys.has(k)) {
            setWorking((w) => w.filter((r) => rowKey(r) !== k));
            return;
        }
        setWorking((w) => [...w, {
            channel_account_id: l.channel_account_id,
            external_sku_id: l.external_sku_id,
            seller_sku: l.seller_sku ?? undefined,
            _listing: { external_sku_id: l.external_sku_id, seller_sku: l.seller_sku, title: l.title, image: l.image, variation: l.variation, channel_stock: l.channel_stock },
        }]);
    };

    const remove = (r: LinkRow) => setWorking((w) => w.filter((x) => rowKey(x) !== rowKey(r)));

    // Cho phép gõ mã SKU sàn thủ công khi listing chưa đồng bộ (phải chọn 1 gian hàng cụ thể).
    const canManual = shopFilter !== 'all' && debounced !== '' && ! listings.some((l) => l.external_sku_id === debounced);
    const addManual = () => {
        if (shopFilter === 'all') return;
        const k = keyOf(shopFilter, debounced);
        if (selectedKeys.has(k)) return;
        setWorking((w) => [...w, { channel_account_id: shopFilter, external_sku_id: debounced, _listing: undefined }]);
    };

    const confirm = () => { onChange(working); onClose(); };

    const shopOptions = [
        { label: 'Tất cả gian hàng', value: 'all' as const, name: 'tất cả' },
        ...shops.map((s) => ({
            label: <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}><ChannelLogo provider={s.provider} size={14} />{s.name}</span>,
            value: s.id,
            name: s.name,
        })),
    ];

    return (
        <div>
            <div style={{ display: 'flex', gap: 16 }}>
                {/* TRÁI: duyệt listing sàn */}
                <div style={{ flex: 1.3, minWidth: 0 }}>
                    <Space direction="vertical" size={8} style={{ width: '100%' }}>
                        <Select<number | 'all'>
                            options={shopOptions}
                            value={shopFilter}
                            onChange={(v) => setShopFilter(v)}
                            showSearch
                            filterOption={(input, option) => String(option?.name ?? '').toLowerCase().includes(input.toLowerCase())}
                            style={{ width: '100%' }}
                            placeholder="Chọn gian hàng"
                        />
                        <Input
                            allowClear
                            prefix={<SearchOutlined />}
                            placeholder="Tìm theo tên sản phẩm / mã SKU trên sàn…"
                            value={term}
                            onChange={(e) => setTerm(e.target.value)}
                        />
                    </Space>
                    <div style={{ height: 420, overflowY: 'auto', marginTop: 8, border: '1px solid #f0f0f0', borderRadius: 8 }}>
                        <List<ChannelListing>
                            size="small"
                            loading={isFetching}
                            dataSource={listings}
                            locale={{ emptyText: <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Không thấy listing. Đồng bộ ở trang 'Sản phẩm sàn' hoặc gõ mã SKU sàn thủ công." /> }}
                            renderItem={(l) => {
                                const selected = selectedKeys.has(keyOf(l.channel_account_id, l.external_sku_id));
                                const shop = shopById[l.channel_account_id];
                                return (
                                    <List.Item
                                        onClick={() => toggle(l)}
                                        style={{ cursor: 'pointer', paddingInline: 10, background: selected ? '#e6f4ff' : undefined }}
                                        extra={selected ? <CheckCircleFilled style={{ color: '#1677ff', fontSize: 18 }} /> : <PlusOutlined style={{ color: '#999' }} />}
                                    >
                                        <List.Item.Meta
                                            avatar={<Avatar shape="square" size={40} src={l.image ?? undefined}>{l.image ? null : '?'}</Avatar>}
                                            title={(
                                                <Space size={4} wrap>
                                                    {shop ? <ChannelLogo provider={shop.provider} size={14} /> : null}
                                                    <span>{l.title ?? '(không tên)'}</span>
                                                    {l.variation ? <Tag bordered={false}>{l.variation}</Tag> : null}
                                                    {l.is_mapped ? <Tag color="gold" bordered={false}>đã ghép</Tag> : null}
                                                </Space>
                                            )}
                                            description={<Text type="secondary" style={{ fontSize: 12 }}>{l.external_sku_id}{l.channel_stock != null ? ` · tồn ${l.channel_stock}` : ''}</Text>}
                                        />
                                    </List.Item>
                                );
                            }}
                        />
                        {canManual ? (
                            <div style={{ padding: 10, borderTop: '1px dashed #eee' }}>
                                <Button block type="dashed" icon={<PlusOutlined />} onClick={addManual}>Dùng mã SKU sàn thủ công: <b>{debounced}</b></Button>
                            </div>
                        ) : null}
                    </div>
                </div>

                {/* PHẢI: đã chọn */}
                <div style={{ flex: 1, minWidth: 0 }}>
                    <Text strong>Đã chọn ({working.length})</Text>
                    <div style={{ height: 460, overflowY: 'auto', marginTop: 8, border: '1px solid #f0f0f0', borderRadius: 8 }}>
                        <List<LinkRow>
                            size="small"
                            dataSource={working}
                            locale={{ emptyText: <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Chưa chọn sản phẩm sàn nào." /> }}
                            renderItem={(r) => {
                                const shop = r.channel_account_id != null ? shopById[r.channel_account_id] : undefined;
                                const li = r._listing;
                                return (
                                    <List.Item
                                        style={{ paddingInline: 10 }}
                                        extra={<Button type="text" danger size="small" icon={<DeleteOutlined />} onClick={() => remove(r)} />}
                                    >
                                        <List.Item.Meta
                                            avatar={<Avatar shape="square" size={40} src={li?.image ?? undefined}>{li?.image ? null : '?'}</Avatar>}
                                            title={(
                                                <Space size={4} wrap>
                                                    {shop ? <ChannelLogo provider={shop.provider} size={14} /> : null}
                                                    <span>{li?.title ?? '(mã thủ công)'}</span>
                                                    {li?.variation ? <Tag bordered={false}>{li.variation}</Tag> : null}
                                                </Space>
                                            )}
                                            description={<Text type="secondary" style={{ fontSize: 12 }}>{r.external_sku_id}{li?.channel_stock != null ? ` · tồn ${li.channel_stock}` : ''}</Text>}
                                        />
                                    </List.Item>
                                );
                            }}
                        />
                    </div>
                </div>
            </div>

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: 16 }}>
                <Button type="text" disabled={working.length === 0} onClick={() => setWorking([])}>Bỏ chọn tất cả</Button>
                <Space>
                    <Button onClick={onClose}>Hủy</Button>
                    <Button type="primary" onClick={confirm}>Xong ({working.length})</Button>
                </Space>
            </div>
        </div>
    );
}
