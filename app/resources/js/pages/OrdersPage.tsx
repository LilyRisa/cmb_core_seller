import { useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Alert, App as AntApp, Avatar, Badge, Button, Card, DatePicker, Empty, Input, Modal, Select, Space, Table, Tabs, Tag, Tooltip, Typography } from 'antd';
import { LinkOutlined, ReloadOutlined, ScanOutlined, SearchOutlined, SyncOutlined, WarningOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { StatusTag } from '@/components/StatusTag';
import { ChannelBadge } from '@/components/ChannelBadge';
import { MoneyText, DateText } from '@/components/MoneyText';
import { FilterChipRow, type ChipItem } from '@/components/FilterChipRow';
import { LinkSkusModal } from '@/components/LinkSkusModal';
import { OrderDetailModal } from '@/components/OrderDetailModal';
import { OrderActions, PrintJobBar, ScanTab, ShipmentsTab } from '@/components/OrderProcessing';
import { errorMessage } from '@/lib/api';
import { CHANNEL_META, ORDER_STATUS_TABS } from '@/lib/format';
import { Order, useOrders, useOrderStats, useSyncOrders } from '@/lib/orders';
import { useChannelAccounts } from '@/lib/channels';
import { useCan } from '@/lib/tenant';

const UNMAPPED_REASON = 'SKU chưa ghép';

const { RangePicker } = DatePicker;

/** Quick-range presets for the "Thời gian" chip row → {from,to} as YYYY-MM-DD. */
const TIME_PRESETS: Array<{ key: string; label: string; range: () => [string, string] }> = [
    { key: 'today', label: 'Hôm nay', range: () => [dayjs().format('YYYY-MM-DD'), dayjs().format('YYYY-MM-DD')] },
    { key: 'yesterday', label: 'Hôm qua', range: () => [dayjs().subtract(1, 'day').format('YYYY-MM-DD'), dayjs().subtract(1, 'day').format('YYYY-MM-DD')] },
    { key: '7d', label: '7 ngày', range: () => [dayjs().subtract(6, 'day').format('YYYY-MM-DD'), dayjs().format('YYYY-MM-DD')] },
    { key: '30d', label: '30 ngày', range: () => [dayjs().subtract(29, 'day').format('YYYY-MM-DD'), dayjs().format('YYYY-MM-DD')] },
    { key: '90d', label: '90 ngày', range: () => [dayjs().subtract(89, 'day').format('YYYY-MM-DD'), dayjs().format('YYYY-MM-DD')] },
];

/** Which search-box param the dropdown targets. */
const SEARCH_FIELDS = [
    { key: 'q', label: 'Mã đơn / người mua' },
    { key: 'sku', label: 'Mã SKU' },
    { key: 'product', label: 'Tên sản phẩm' },
] as const;

export function OrdersPage() {
    const { message } = AntApp.useApp();
    const [params, setParams] = useSearchParams();
    const { data: channelsData } = useChannelAccounts();
    const accounts = channelsData?.data ?? [];
    const syncOrders = useSyncOrders();
    const canCreate = useCan('orders.create');
    const canMap = useCan('inventory.map');
    const [selectedKeys, setSelectedKeys] = useState<number[]>([]);
    const [linkModal, setLinkModal] = useState<{ open: boolean; orderIds?: number[] }>({ open: false });
    const [viewOrderId, setViewOrderId] = useState<number | null>(null);
    // fulfillment: print-job progress bar + scan-to-pack/handover modal (BigSeller-style — thao tác ngay trên list)
    const [printJobId, setPrintJobId] = useState<number | null>(null);
    const [scan, setScan] = useState<{ open: boolean; mode: 'pack' | 'handover' }>({ open: false, mode: 'pack' });

    const tabKey = params.get('tab') ?? (params.get('has_issue') ? 'issue' : '');
    const statusParam = params.get('status') ?? '';
    const q = params.get('q') ?? '';
    const skuQ = params.get('sku') ?? '';
    const productQ = params.get('product') ?? '';
    const source = params.get('source') ?? '';
    const channelAccountId = params.get('channel_account_id') ?? '';
    const carrier = params.get('carrier') ?? '';
    const placedFrom = params.get('placed_from') ?? '';
    const placedTo = params.get('placed_to') ?? '';
    const page = Number(params.get('page') ?? 1);
    const perPage = Number(params.get('per_page') ?? 20);
    const sort = params.get('sort') ?? '-placed_at';

    const set = (next: Record<string, string | number | undefined | null>) => {
        const merged = new URLSearchParams(params);
        Object.entries(next).forEach(([k, v]) => { if (v == null || v === '') merged.delete(k); else merged.set(k, String(v)); });
        if (!('page' in next)) merged.set('page', '1');
        setParams(merged, { replace: true });
    };

    const activeTab = ORDER_STATUS_TABS.find((t) => t.key === tabKey) ?? ORDER_STATUS_TABS[0];
    const effectiveStatus = tabKey === 'issue' || tabKey === 'out_of_stock' ? '' : (statusParam || activeTab.statuses.join(','));

    const filters = useMemo(() => ({
        status: effectiveStatus || undefined,
        q: q || undefined, sku: skuQ || undefined, product: productQ || undefined,
        source: source || undefined,
        channel_account_id: channelAccountId ? Number(channelAccountId) : undefined,
        carrier: carrier || undefined,
        placed_from: placedFrom || undefined, placed_to: placedTo || undefined,
        has_issue: tabKey === 'issue' || params.get('has_issue') === '1' ? true : undefined,
        out_of_stock: tabKey === 'out_of_stock' ? true : undefined,
        sort, page, per_page: perPage,
    }), [effectiveStatus, q, skuQ, productQ, source, channelAccountId, carrier, placedFrom, placedTo, tabKey, params, sort, page, perPage]);

    // stats: facet chip counts (source/shop/carrier) ignore the chip facets themselves; status counts ignore status.
    const statsFilters = useMemo(() => ({
        status: effectiveStatus || undefined,
        q: q || undefined, sku: skuQ || undefined, product: productQ || undefined,
        source: source || undefined,
        channel_account_id: channelAccountId ? Number(channelAccountId) : undefined,
        carrier: carrier || undefined,
        placed_from: placedFrom || undefined, placed_to: placedTo || undefined,
        has_issue: tabKey === 'issue' || params.get('has_issue') === '1' ? true : undefined,
    }), [effectiveStatus, q, skuQ, productQ, source, channelAccountId, carrier, placedFrom, placedTo, tabKey, params]);

    const isShipmentsTab = tabKey === 'shipments';

    // skip the (unused) orders list when on the shipments tab
    const { data, isFetching, refetch } = useOrders(isShipmentsTab ? { ...filters, page: 1, per_page: 1 } : filters);
    const { data: stats } = useOrderStats(statsFilters);

    const countFor = (statuses: string[]) => statuses.reduce((s, st) => s + (stats?.by_status?.[st] ?? 0), 0);
    const shopName = (id: number) => accounts.find((a) => a.id === id)?.name ?? `#${id}`;

    // chip-row items
    const sourceChips: ChipItem[] = (stats?.by_source ?? []).map((s) => ({ value: s.source, label: CHANNEL_META[s.source]?.name ?? s.source, count: s.count }));
    const shopChips: ChipItem[] = (stats?.by_shop ?? []).map((s) => ({ value: String(s.channel_account_id), label: shopName(s.channel_account_id), count: s.count }));
    const carrierChips: ChipItem[] = (stats?.by_carrier ?? []).map((c) => ({ value: c.carrier, label: c.carrier, count: c.count }));
    const timeChips: ChipItem[] = TIME_PRESETS.map((p) => ({ value: p.key, label: p.label }));

    const activeTimePreset = useMemo(() => {
        if (!placedFrom || !placedTo) return undefined;
        return TIME_PRESETS.find((p) => { const [f, t] = p.range(); return f === placedFrom && t === placedTo; })?.key;
    }, [placedFrom, placedTo]);

    // search box: which param the input targets + its current value
    const searchField = (['q', 'sku', 'product'] as const).find((f) => params.get(f)) ?? 'q';
    const searchValue = params.get(searchField) ?? '';
    const onSearch = (field: string, value: string) => set({ q: undefined, sku: undefined, product: undefined, [field]: value || undefined });

    const columns: ColumnsType<Order> = [
        {
            title: 'Đơn hàng', key: 'order', width: 240,
            render: (_, o) => (
                <Space direction="vertical" size={2}>
                    <Link to={`/orders/${o.id}`} style={{ fontWeight: 600 }}>{o.order_number ?? o.external_order_id ?? `#${o.id}`}</Link>
                    <Space size={4} wrap>
                        <ChannelBadge provider={o.source} />
                        {(o.channel_account?.name ?? (o.channel_account_id ? shopName(o.channel_account_id) : null)) && <Tag>{o.channel_account?.name ?? shopName(o.channel_account_id!)}</Tag>}
                        {o.is_cod && <Tag color="gold">COD</Tag>}
                        {o.issue_reason === UNMAPPED_REASON
                            ? <Tag color="error" icon={<LinkOutlined />} style={{ cursor: 'pointer' }} onClick={() => setLinkModal({ open: true, orderIds: [o.id] })}>Chưa liên kết SKU — Liên kết</Tag>
                            : o.has_issue && <Tooltip title={o.issue_reason ?? 'Đơn có vấn đề'}><Tag color="error" icon={<WarningOutlined />}>Lỗi</Tag></Tooltip>}
                    </Space>
                </Space>
            ),
        },
        {
            title: 'Sản phẩm', key: 'items',
            render: (_, o) => (
                <Space>
                    <Avatar shape="square" size={40} src={o.thumbnail ?? undefined} style={{ background: '#f0f0f0' }}>{o.thumbnail ? null : (o.items_count ?? 0)}</Avatar>
                    <Typography.Text type="secondary">{o.items_count ?? 0} mặt hàng</Typography.Text>
                </Space>
            ),
        },
        { title: 'Người mua', dataIndex: 'buyer_name', key: 'buyer', width: 180, render: (v, o) => <Space direction="vertical" size={0}><span>{v ?? '—'}</span><Typography.Text type="secondary" style={{ fontSize: 12 }}>{o.buyer_phone_masked ?? ''}</Typography.Text></Space> },
        { title: 'ĐVVC', dataIndex: 'carrier', key: 'carrier', width: 110, render: (v) => (v ? <Tag>{v}</Tag> : '—') },
        { title: 'Tổng tiền', dataIndex: 'grand_total', key: 'total', width: 130, align: 'right', render: (v, o) => <MoneyText value={v} currency={o.currency} strong /> },
        {
            title: 'Lợi nhuận ƯT', key: 'profit', width: 140, align: 'right',
            render: (_, o) => {
                const p = o.profit;
                if (!p) return <Typography.Text type="secondary">—</Typography.Text>;
                return (
                    <Tooltip title={<div style={{ lineHeight: 1.7 }}>
                        Phí sàn ({p.platform_fee_pct}%): −{p.platform_fee.toLocaleString('vi-VN')} ₫<br />
                        Phí vận chuyển: −{p.shipping_fee.toLocaleString('vi-VN')} ₫<br />
                        Giá vốn hàng: −{p.cogs.toLocaleString('vi-VN')} ₫{!p.cost_complete && ' (chưa đủ — thiếu giá vốn SKU)'}
                    </div>}>
                        <span style={{ color: p.estimated_profit >= 0 ? '#389e0d' : '#cf1322', fontWeight: 600 }}>
                            {!p.cost_complete && <WarningOutlined style={{ color: '#faad14', marginRight: 4 }} />}
                            {p.estimated_profit.toLocaleString('vi-VN')} ₫
                        </span>
                    </Tooltip>
                );
            },
        },
        { title: 'Trạng thái', dataIndex: 'status', key: 'status', width: 140, render: (v, o) => <StatusTag status={v} label={o.status_label} rawStatus={o.raw_status} /> },
        { title: 'Đặt lúc', dataIndex: 'placed_at', key: 'placed_at', width: 150, render: (v) => <DateText value={v} /> },
        {
            title: 'Thao tác', key: 'action', width: 220,
            render: (_, o) => (
                <Space direction="vertical" size={2}>
                    <OrderActions order={o} onPrint={setPrintJobId} />
                    <Typography.Link onClick={() => setViewOrderId(o.id)}>Xem chi tiết</Typography.Link>
                </Space>
            ),
        },
    ];

    return (
        <div>
            <PageHeader
                title="Đơn hàng"
                subtitle="Đơn từ tất cả gian hàng — lọc theo sàn / shop / SKU / sản phẩm / đơn vị vận chuyển"
                extra={(
                    <Space>
                        {canCreate && <Link to="/orders/new"><Button type="primary">Tạo đơn</Button></Link>}
                        <Button icon={<ScanOutlined />} onClick={() => setScan({ open: true, mode: 'pack' })}>Quét đơn</Button>
                        <Button icon={<SyncOutlined />} loading={syncOrders.isPending} onClick={() => syncOrders.mutate(undefined, {
                            onSuccess: (r) => message.success(r.queued > 0 ? `Đã yêu cầu đồng bộ ${r.queued} gian hàng` : 'Chưa có gian hàng nào hoạt động'),
                            onError: (e) => message.error(errorMessage(e)),
                        })}>Đồng bộ đơn</Button>
                        <Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>Làm mới</Button>
                    </Space>
                )}
            />

            {/* Status tabs (curated subset, BigSeller-style) */}
            <Card styles={{ body: { padding: '8px 16px 0' } }}>
                <Tabs
                    activeKey={tabKey}
                    onChange={(k) => set({ tab: k || undefined, status: undefined, has_issue: k === 'issue' ? '1' : undefined })}
                    items={[
                        ...ORDER_STATUS_TABS.map((t) => ({
                            key: t.key,
                            label: <span>{t.label}{t.key !== '' && stats ? <Badge count={countFor(t.statuses)} overflowCount={9999} showZero={false} style={{ marginInlineStart: 6, background: '#f0f0f0', color: '#595959' }} /> : null}</span>,
                        })),
                        { key: 'issue', label: <span>Có vấn đề{stats?.has_issue ? <Badge count={stats.has_issue} style={{ marginInlineStart: 6 }} /> : null}</span> },
                        // Đơn có SKU âm tồn — chặn "Chuẩn bị hàng / lấy phiếu giao hàng" cho đến khi nhập thêm hàng (SPEC 0013).
                        { key: 'out_of_stock', label: <span>⚠️ Hết hàng{stats?.out_of_stock ? <Badge count={stats.out_of_stock} style={{ marginInlineStart: 6 }} /> : null}</span> },
                        // Thao tác xử lý đơn (chuẩn bị hàng / in phiếu / đóng gói / bàn giao ĐVVC) làm ngay trên các tab
                        // trạng thái Chờ xử lý · Đang xử lý · Chờ bàn giao — không tách stage riêng (xem cột "Thao tác").
                        { key: 'shipments', label: <span>🏷 Vận đơn</span> },
                    ]}
                />
            </Card>

            {isShipmentsTab ? (
                <div style={{ marginTop: 12 }}>
                    {printJobId != null && <PrintJobBar jobId={printJobId} onClose={() => setPrintJobId(null)} />}
                    <Card styles={{ body: { padding: 16 } }}>
                        <ShipmentsTab onPrint={setPrintJobId} />
                    </Card>
                </div>
            ) : (<>

            {printJobId != null && <div style={{ marginTop: 12 }}><PrintJobBar jobId={printJobId} onClose={() => setPrintJobId(null)} /></div>}

            {/* "Lọc" panel — one inline group: a search box + chip rows (xem docs/06-frontend/orders-filter-panel.md) */}
            <Card style={{ marginTop: 12 }} title="Lọc" size="small" styles={{ body: { padding: '8px 16px 12px' } }}>
                <div style={{ display: 'flex', gap: 8, marginBottom: 8, flexWrap: 'wrap' }}>
                    <Select
                        value={searchField} style={{ width: 180 }}
                        onChange={(f) => onSearch(f, searchValue)}
                        options={SEARCH_FIELDS.map((f) => ({ value: f.key, label: f.label }))}
                    />
                    <Input.Search
                        allowClear key={searchField} defaultValue={searchValue} style={{ flex: 1, minWidth: 260 }}
                        placeholder={SEARCH_FIELDS.find((f) => f.key === searchField)?.label}
                        prefix={<SearchOutlined />}
                        onSearch={(v) => onSearch(searchField, v)}
                    />
                </div>

                <FilterChipRow label="Sàn TMĐT" items={sourceChips} value={source || undefined} onChange={(v) => set({ source: v })} />
                <FilterChipRow label="Gian hàng" items={shopChips} value={channelAccountId || undefined} onChange={(v) => set({ channel_account_id: v })} />
                <FilterChipRow label="Vận chuyển" items={carrierChips} value={carrier || undefined} onChange={(v) => set({ carrier: v })} />
                <FilterChipRow
                    label="Thời gian" items={timeChips}
                    value={activeTimePreset}
                    onChange={(k) => { const p = TIME_PRESETS.find((x) => x.key === k); if (!p) { set({ placed_from: undefined, placed_to: undefined }); } else { const [f, t] = p.range(); set({ placed_from: f, placed_to: t }); } }}
                    extra={(
                        <RangePicker
                            size="small" allowEmpty={[true, true]}
                            value={placedFrom && placedTo && !activeTimePreset ? [dayjs(placedFrom), dayjs(placedTo)] : null}
                            onChange={(v) => set({ placed_from: v?.[0]?.format('YYYY-MM-DD'), placed_to: v?.[1]?.format('YYYY-MM-DD') })}
                            placeholder={['Tuỳ chỉnh từ', 'đến']}
                        />
                    )}
                />
                <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: 8 }}>
                    <Select value={sort} size="small" style={{ width: 170 }} onChange={(v) => set({ sort: v })} options={[
                        { value: '-placed_at', label: 'Mới đặt trước' },
                        { value: 'placed_at', label: 'Cũ trước' },
                        { value: '-grand_total', label: 'Giá trị cao trước' },
                        { value: 'grand_total', label: 'Giá trị thấp trước' },
                    ]} />
                </div>
            </Card>

            {canMap && (stats?.unmapped ?? 0) > 0 && (
                <Alert
                    type="error" showIcon style={{ marginTop: 12 }}
                    message={<>Có <b>{stats!.unmapped}</b> đơn chưa liên kết SKU — chưa thể trừ tồn cho các đơn này.</>}
                    action={<Button danger size="small" icon={<LinkOutlined />} onClick={() => setLinkModal({ open: true, orderIds: undefined })}>Liên kết hàng loạt</Button>}
                />
            )}

            <Card style={{ marginTop: 12 }} styles={{ body: { padding: 16 } }}>
                {canMap && selectedKeys.length > 0 && (
                    <Space style={{ marginBottom: 12 }}>
                        <Button type="primary" icon={<LinkOutlined />} onClick={() => setLinkModal({ open: true, orderIds: selectedKeys })}>Liên kết SKU ({selectedKeys.length})</Button>
                        <Button onClick={() => setSelectedKeys([])}>Bỏ chọn</Button>
                    </Space>
                )}
                <Table<Order>
                    rowKey="id" size="middle" loading={isFetching}
                    dataSource={data?.data ?? []} columns={columns}
                    rowSelection={canMap ? { selectedRowKeys: selectedKeys, onChange: (keys) => setSelectedKeys(keys as number[]), getCheckboxProps: (o) => ({ disabled: o.issue_reason !== UNMAPPED_REASON }) } : undefined}
                    locale={{ emptyText: <Empty description="Chưa có đơn hàng. Kết nối gian hàng để đơn tự về, hoặc bấm “Đồng bộ đơn”." /> }}
                    rowClassName={(o) => (o.has_issue ? 'row-has-issue' : '')}
                    pagination={{
                        current: data?.meta.pagination.page ?? page,
                        pageSize: data?.meta.pagination.per_page ?? perPage,
                        total: data?.meta.pagination.total ?? 0,
                        showSizeChanger: true, pageSizeOptions: [20, 50, 100],
                        showTotal: (t) => `${t} đơn`,
                        onChange: (p, ps) => set({ page: p, per_page: ps }),
                    }}
                />
            </Card>
            </>)}

            <LinkSkusModal open={linkModal.open} orderIds={linkModal.orderIds} onClose={() => { setLinkModal({ open: false }); setSelectedKeys([]); }} />
            <OrderDetailModal orderId={viewOrderId} open={viewOrderId != null} onClose={() => setViewOrderId(null)} />
            <Modal title="Quét đơn" open={scan.open} onCancel={() => setScan((s) => ({ ...s, open: false }))} footer={null} width={760} destroyOnClose>
                <ScanTab initialMode={scan.mode} />
            </Modal>
        </div>
    );
}
