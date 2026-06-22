import { useMemo, useState } from 'react';
import { App, Avatar, Button, Checkbox, Input, Modal, Space, Table, Tag, Tooltip, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { PictureOutlined, ReloadOutlined, SearchOutlined } from '@ant-design/icons';
import { MoneyText } from '@/components/MoneyText';
import { type ChannelListing, useChannelListings, useSyncChannelListings } from '@/lib/inventory';

/**
 * Chọn nhiều SKU cho chiến dịch giảm giá. SKU đã thuộc chương trình đang/sắp chạy bị TÔ XÁM + không cho chọn
 * (giống trên sàn) + hiện giá giảm. Toggle "Chỉ hiện SKU chọn được" lọc SERVER-SIDE (exclude_busy) để đúng qua
 * mọi trang. Nút "Đồng bộ từ sàn" kéo lại listing+giá giảm mới nhất (endpoint /channel-listings chỉ đọc DB).
 */
export function SkuPickerModal({
    open,
    channelAccountId,
    busyPromos,
    selectedSkuIds,
    exceptPromotionId,
    onClose,
    onConfirm,
}: {
    open: boolean;
    channelAccountId: number | null;
    busyPromos?: { ids: string[]; prices: Record<string, number> };
    selectedSkuIds: string[];
    exceptPromotionId?: number;
    onClose: () => void;
    onConfirm: (rows: ChannelListing[]) => void;
}) {
    const { message } = App.useApp();
    const [q, setQ] = useState('');
    const [page, setPage] = useState(1);
    // MẶC ĐỊNH hiện TẤT CẢ SKU + TÔ XÁM cái đã giảm giá (đúng yêu cầu "thấy SKU đã có giảm giá bị tô xám").
    // Bật toggle "Chỉ hiện SKU chọn được" ⇒ ẩn hẳn SKU bận (lọc server exclude_busy).
    const [hideBusy, setHideBusy] = useState(false);
    const [picked, setPicked] = useState<Record<string, ChannelListing>>({});

    // Khoá-bận = sku_id HOẶC product_id (item Shopee no-variant giảm theo item_id). prices: khoá → giá giảm.
    // map(String): ids từ API có thể là SỐ (PHP ép key chuỗi-số → int) trong khi external_sku_id là chuỗi —
    // ép cùng kiểu để Set.has khớp, nếu không SKU sàn (id toàn số) không bao giờ bị tô xám.
    const busy = useMemo(() => new Set((busyPromos?.ids ?? []).map(String)), [busyPromos]);
    const prices = busyPromos?.prices ?? {};
    const already = useMemo(() => new Set(selectedSkuIds), [selectedSkuIds]);
    const syncListings = useSyncChannelListings();

    // `exclude_busy` lọc SKU bận ở SERVER (qua mọi trang); khi tắt toggle ⇒ hiện hết, tô xám SKU bận client-side.
    const { data, isFetching } = useChannelListings({
        channel_account_id: channelAccountId ?? undefined,
        q: q || undefined,
        page,
        per_page: 20,
        exclude_busy: hideBusy ? 1 : 0,
        except: exceptPromotionId,
    });

    const isLocked = (r: ChannelListing) => {
        const sid = r.external_sku_id ?? '';
        const pid = r.external_product_id ?? '';
        return busy.has(sid) || (pid !== '' && busy.has(pid)) || already.has(sid);
    };
    const busyPrice = (r: ChannelListing): number | undefined => prices[r.external_sku_id ?? ''] ?? prices[r.external_product_id ?? ''];

    // Server đã loại SKU bận khi `hideBusy`; chỉ cần lọc thêm SKU đã thêm vào chiến dịch này (client).
    const rows = useMemo(() => {
        const all = data?.data ?? [];
        return hideBusy ? all.filter((r) => !already.has(r.external_sku_id ?? '')) : all;
    }, [data, hideBusy, already]);

    const reset = () => { setPicked({}); setQ(''); setPage(1); };

    const columns: ColumnsType<ChannelListing> = [
        {
            title: 'Sản phẩm',
            key: 'product',
            render: (_, r) => (
                <Space>
                    <Avatar shape="square" size={40} src={r.image ?? undefined} icon={<PictureOutlined />} style={{ background: '#f5f5f5', flex: 'none' }} />
                    <div style={{ minWidth: 0 }}>
                        <Typography.Text ellipsis={{ tooltip: r.title ?? r.external_sku_id }} style={{ display: 'block', maxWidth: 320 }}>
                            {r.title ?? r.external_sku_id}
                        </Typography.Text>
                        <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                            {[r.variation, r.seller_sku].filter(Boolean).join(' · ') || '—'}
                        </Typography.Text>
                    </div>
                </Space>
            ),
        },
        { title: 'Giá gốc', key: 'price', width: 120, align: 'right', render: (_, r) => { const p = r.original_price ?? r.price; return p == null ? '—' : <MoneyText value={p} currency={r.currency} />; } },
        {
            title: 'Trạng thái', key: 'st', width: 170,
            render: (_, r) => {
                if (!isLocked(r)) {
                    return <Tag color="green">Chọn được</Tag>;
                }
                const bp = busyPrice(r);
                return (
                    <Space direction="vertical" size={0}>
                        <Tag color="orange" style={{ marginInlineEnd: 0 }}>Đang giảm giá</Tag>
                        {bp != null && bp > 0 && <Typography.Text type="success" style={{ fontSize: 12 }}>còn <MoneyText value={bp} currency={r.currency} /></Typography.Text>}
                    </Space>
                );
            },
        },
    ];

    return (
        <Modal
            title="Chọn SKU cho chiến dịch"
            open={open}
            width={760}
            onCancel={() => { reset(); onClose(); }}
            okText={`Thêm ${Object.keys(picked).length} SKU`}
            okButtonProps={{ disabled: Object.keys(picked).length === 0 }}
            onOk={() => { onConfirm(Object.values(picked)); reset(); onClose(); }}
            destroyOnClose
        >
            <Space style={{ marginBottom: 12, width: '100%', justifyContent: 'space-between' }}>
                <Space>
                    <Input
                        allowClear prefix={<SearchOutlined />} placeholder="Lọc theo tên / SKU sàn" style={{ width: 280 }}
                        value={q} onChange={(e) => { setQ(e.target.value); setPage(1); }}
                    />
                    <Tooltip title="Kéo lại sản phẩm + giá giảm mới nhất từ sàn (danh sách đang xem là dữ liệu đã đồng bộ trước đó).">
                        <Button icon={<ReloadOutlined />} loading={syncListings.isPending}
                            onClick={() => syncListings.mutate(undefined, {
                                onSuccess: (r) => message.success(`Đã yêu cầu đồng bộ ${r.queued} gian hàng — làm mới sau ít phút để thấy SKU + giá giảm mới.`),
                                onError: () => message.error('Không đồng bộ được sản phẩm.'),
                            })}>Đồng bộ từ sàn</Button>
                    </Tooltip>
                </Space>
                <Checkbox checked={hideBusy} onChange={(e) => { setHideBusy(e.target.checked); setPage(1); }}>Chỉ hiện SKU chọn được</Checkbox>
            </Space>

            <Table<ChannelListing>
                rowKey="id"
                size="small"
                loading={isFetching}
                dataSource={rows}
                columns={columns}
                rowSelection={{
                    selectedRowKeys: Object.values(picked).map((r) => r.id),
                    getCheckboxProps: (r) => ({ disabled: isLocked(r) }),
                    onSelect: (r, sel) => setPicked((prev) => {
                        const next = { ...prev };
                        const key = r.external_sku_id ?? String(r.id);
                        if (sel) next[key] = r; else delete next[key];
                        return next;
                    }),
                    onSelectAll: (sel, _all, changed) => setPicked((prev) => {
                        const next = { ...prev };
                        changed.forEach((r) => {
                            if (isLocked(r)) return;
                            const key = r.external_sku_id ?? String(r.id);
                            if (sel) next[key] = r; else delete next[key];
                        });
                        return next;
                    }),
                }}
                rowClassName={(r) => (isLocked(r) ? 'promo-row-locked' : '')}
                pagination={{ current: page, pageSize: 20, total: data?.meta?.pagination?.total ?? 0, showSizeChanger: false, onChange: setPage }}
            />
            <Typography.Paragraph type="secondary" style={{ fontSize: 12, marginTop: 8, marginBottom: 0 }}>
                SKU “Đang giảm giá” đã thuộc chương trình khác — không thể thêm (tô xám, khoá chọn).
            </Typography.Paragraph>
        </Modal>
    );
}
