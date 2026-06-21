import { useMemo, useState } from 'react';
import { Avatar, Checkbox, Input, Modal, Space, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { PictureOutlined, SearchOutlined } from '@ant-design/icons';
import { MoneyText } from '@/components/MoneyText';
import { type ChannelListing, useChannelListings } from '@/lib/inventory';

/**
 * Chọn nhiều SKU cho chiến dịch giảm giá. SKU đã thuộc chương trình đang/sắp chạy
 * (busySkuIds) bị TÔ XÁM + không cho chọn (giống trên sàn). Có ô lọc theo tên/SKU và
 * toggle ẩn SKU không chọn được. SKU đã thêm vào chiến dịch (selectedSkuIds) cũng khoá.
 */
export function SkuPickerModal({
    open,
    channelAccountId,
    busyPromos,
    selectedSkuIds,
    onClose,
    onConfirm,
}: {
    open: boolean;
    channelAccountId: number | null;
    busyPromos?: { ids: string[]; prices: Record<string, number> };
    selectedSkuIds: string[];
    onClose: () => void;
    onConfirm: (rows: ChannelListing[]) => void;
}) {
    const [q, setQ] = useState('');
    const [page, setPage] = useState(1);
    const [hideBusy, setHideBusy] = useState(true);
    const [picked, setPicked] = useState<Record<string, ChannelListing>>({});

    // Khoá-bận = sku_id HOẶC product_id (item Shopee no-variant giảm theo item_id). prices: khoá → giá giảm.
    const busy = useMemo(() => new Set(busyPromos?.ids ?? []), [busyPromos]);
    const prices = busyPromos?.prices ?? {};
    const already = useMemo(() => new Set(selectedSkuIds), [selectedSkuIds]);

    const { data, isFetching } = useChannelListings({
        channel_account_id: channelAccountId ?? undefined,
        q: q || undefined,
        page,
        per_page: 20,
    });

    const isLocked = (r: ChannelListing) => {
        const sid = r.external_sku_id ?? '';
        const pid = r.external_product_id ?? '';
        return busy.has(sid) || (pid !== '' && busy.has(pid)) || already.has(sid);
    };
    const busyPrice = (r: ChannelListing): number | undefined => prices[r.external_sku_id ?? ''] ?? prices[r.external_product_id ?? ''];

    const rows = useMemo(() => {
        const all = data?.data ?? [];
        return hideBusy ? all.filter((r) => !isLocked(r)) : all;
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data, hideBusy, busy, already]);

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
                <Input
                    allowClear prefix={<SearchOutlined />} placeholder="Lọc theo tên / SKU sàn" style={{ width: 320 }}
                    value={q} onChange={(e) => { setQ(e.target.value); setPage(1); }}
                />
                <Checkbox checked={hideBusy} onChange={(e) => setHideBusy(e.target.checked)}>Chỉ hiện SKU chọn được</Checkbox>
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
