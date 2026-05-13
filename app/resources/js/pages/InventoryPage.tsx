import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { App as AntApp, Avatar, Button, Card, Empty, Form, Input, InputNumber, Modal, Popconfirm, Radio, Select, Space, Table, Tabs, Tag, Typography } from 'antd';
import { CloudDownloadOutlined, CloudUploadOutlined, DeleteOutlined, EditOutlined, ImportOutlined, PictureOutlined, PlusOutlined, ReloadOutlined, SearchOutlined, ShopOutlined, ThunderboltOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { MoneyText } from '@/components/MoneyText';
import { SkuLine, SkuPicker, SkuPickerField } from '@/components/SkuPicker';
import { WarehouseDocsTab } from '@/components/WarehouseDocsTab';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { useChannelAccounts } from '@/lib/channels';
import { useSyncPolling } from '@/lib/syncPolling';
import {
    ChannelListing, InventoryLevel, Sku,
    useAdjustStock, useAutoMatchSkus, useBulkAdjustStock, useBulkPushStock, useChannelListings, useCreateSku, useDeleteSku, useInventoryLevels, useSetSkuMapping, useSkus, useSyncChannelListings, useWarehouses,
} from '@/lib/inventory';

function StockBadge({ available }: { available: number }) {
    const color = available <= 0 ? 'red' : available <= 5 ? 'gold' : 'green';
    return <Tag color={color} style={{ marginInlineEnd: 0 }}>{available}</Tag>;
}

export function InventoryPage() {
    const [params, setParams] = useSearchParams();
    const tab = params.get('tab') ?? 'levels';
    const setTab = (k: string) => { const m = new URLSearchParams(); m.set('tab', k); setParams(m, { replace: true }); };
    return (
        <div>
            <PageHeader title="Tồn kho" subtitle="Master SKU là nguồn sự thật về tồn — bán sàn + đơn tay trừ chung một kho; mọi thay đổi có dòng trong sổ cái" />
            <Card styles={{ body: { padding: '8px 16px 0' } }}>
                <Tabs activeKey={tab} onChange={setTab} items={[
                    { key: 'levels', label: 'Tồn theo SKU' },
                    { key: 'skus', label: 'Danh mục SKU' },
                    { key: 'listings', label: 'Liên kết SKU (sàn)' },
                    { key: 'docs', label: 'Phiếu kho' },
                ]} />
            </Card>
            <Card style={{ marginTop: 12 }} styles={{ body: { padding: 16 } }}>
                {tab === 'levels' && <LevelsTab />}
                {tab === 'skus' && <SkusTab />}
                {tab === 'listings' && <ListingsTab />}
                {tab === 'docs' && <WarehouseDocsTab />}
            </Card>
        </div>
    );
}

function LevelsTab() {
    const { message } = AntApp.useApp();
    const [page, setPage] = useState(1);
    const [lowOnly, setLowOnly] = useState(false);
    const { data, isFetching, refetch } = useInventoryLevels({ page, per_page: 20, low_stock: lowOnly ? 5 : undefined });
    const adjust = useAdjustStock();
    const canAdjust = useCan('inventory.adjust');
    const [adjustFor, setAdjustFor] = useState<InventoryLevel | null>(null);
    const [form] = Form.useForm();

    const columns: ColumnsType<InventoryLevel> = [
        { title: 'SKU', key: 'sku', render: (_, r) => r.sku ? <SkuLine sku={r.sku} avatarSize={36} maxTextWidth={320} /> : <Typography.Text type="secondary">#{r.sku_id}</Typography.Text> },
        { title: 'Kho', key: 'wh', width: 160, render: (_, r) => <>{r.warehouse?.name ?? `#${r.warehouse_id}`}{r.warehouse?.is_default && <Tag style={{ marginLeft: 6 }}>mặc định</Tag>}</> },
        { title: 'Thực có', dataIndex: 'on_hand', key: 'on_hand', width: 90, align: 'right' },
        { title: 'Đang giữ', dataIndex: 'reserved', key: 'reserved', width: 90, align: 'right' },
        { title: 'An toàn', dataIndex: 'safety_stock', key: 'safety', width: 80, align: 'right' },
        { title: 'Khả dụng', key: 'available', width: 100, align: 'right', render: (_, r) => <Space>{r.is_negative && <Tag color="error">âm</Tag>}<StockBadge available={r.available} /></Space> },
        ...(canAdjust ? [{ title: '', key: 'a', width: 100, render: (_: unknown, r: InventoryLevel) => <Button size="small" onClick={() => { setAdjustFor(r); form.resetFields(); }}>Điều chỉnh</Button> }] : []),
    ];

    return (
        <>
            <Space style={{ marginBottom: 12 }}>
                <Button size="small" type={lowOnly ? 'primary' : 'default'} onClick={() => { setLowOnly((v) => !v); setPage(1); }}>Sắp hết (≤5)</Button>
                <Button size="small" icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>Làm mới</Button>
            </Space>
            <Table<InventoryLevel> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns}
                locale={{ emptyText: <Empty description="Chưa có tồn kho. Thêm SKU rồi điều chỉnh tồn, hoặc đơn về sẽ tạo dòng tồn." /> }}
                rowClassName={(r) => (r.is_negative ? 'row-has-issue' : '')}
                pagination={{ current: data?.meta.pagination.page ?? page, pageSize: 20, total: data?.meta.pagination.total ?? 0, onChange: setPage, showTotal: (t) => `${t} dòng` }} />

            <Modal title={`Điều chỉnh tồn — ${adjustFor?.sku?.sku_code}`} open={!!adjustFor} onCancel={() => setAdjustFor(null)} okText="Lưu"
                confirmLoading={adjust.isPending}
                onOk={() => form.validateFields().then((v) => adjust.mutate({ sku_id: adjustFor!.sku_id, warehouse_id: adjustFor!.warehouse_id, qty_change: v.qty_change, note: v.note },
                    { onSuccess: () => { message.success('Đã điều chỉnh tồn'); setAdjustFor(null); }, onError: (e) => message.error(errorMessage(e)) }))}>
                <Form form={form} layout="vertical">
                    <Form.Item name="qty_change" label="Thay đổi (+ nhập / − xuất)" rules={[{ required: true }, { validator: (_, v) => (v === 0 ? Promise.reject('Khác 0') : Promise.resolve()) }]}>
                        <InputNumber style={{ width: '100%' }} />
                    </Form.Item>
                    <Form.Item name="note" label="Ghi chú"><Input maxLength={255} /></Form.Item>
                </Form>
            </Modal>
        </>
    );
}

function SkusTab() {
    const { message } = AntApp.useApp();
    const navigate = useNavigate();
    const [q, setQ] = useState('');
    const [page, setPage] = useState(1);
    const { data, isFetching, refetch } = useSkus({ q: q || undefined, page, per_page: 20 });
    const bulkAdjust = useBulkAdjustStock();
    const bulkPush = useBulkPushStock();
    // Sau khi đẩy tồn lên sàn, push job chạy nền — poll để cập nhật sync_status / channel_stock cho danh sách
    // SKU & listing (xem `useSyncPolling`).
    const pushPoll = useSyncPolling(() => { refetch(); });
    const deleteSku = useDeleteSku();
    const { data: warehouses } = useWarehouses();
    const canManage = useCan('products.manage');
    const canAdjust = useCan('inventory.adjust');
    const canMap = useCan('inventory.map');
    const [bulkOpen, setBulkOpen] = useState(false);
    const [selectedKeys, setSelectedKeys] = useState<number[]>([]);
    const [bulkForm] = Form.useForm();

    const columns: ColumnsType<Sku> = [
        { title: '', key: 'img', width: 52, render: (_, r) => <Avatar shape="square" size={40} src={r.image_url ?? undefined} style={{ background: '#f5f5f5', color: '#bfbfbf' }} icon={<PictureOutlined />} /> },
        { title: 'Mã SKU', dataIndex: 'sku_code', key: 'code', width: 200, ellipsis: { showTitle: false }, render: (v, r) => <Space direction="vertical" size={0} style={{ minWidth: 0, maxWidth: 188 }}><Typography.Text strong ellipsis={{ tooltip: v }}>{v}</Typography.Text>{r.spu_code && <Typography.Text type="secondary" style={{ fontSize: 12 }} ellipsis={{ tooltip: r.spu_code }}>SPU: {r.spu_code}</Typography.Text>}</Space> },
        { title: 'Tên', dataIndex: 'name', key: 'name', ellipsis: { showTitle: false }, render: (v: string) => <Typography.Text ellipsis={{ tooltip: v }} style={{ display: 'block', maxWidth: 320 }}>{v}</Typography.Text> },
        { title: 'Giá vốn TK', dataIndex: 'cost_price', key: 'cost', width: 110, align: 'right', render: (v) => <MoneyText value={v} /> },
        { title: 'Giá bán TK', dataIndex: 'ref_sale_price', key: 'sale', width: 110, align: 'right', render: (v) => (v != null ? <MoneyText value={v} /> : '—') },
        { title: 'LN/đv', key: 'profit', width: 130, align: 'right', render: (_, r) => (r.ref_profit_per_unit == null ? '—' : <Typography.Text style={{ color: r.ref_profit_per_unit >= 0 ? '#389e0d' : '#cf1322' }}><MoneyText value={r.ref_profit_per_unit} />{r.ref_margin_percent != null ? ` · ${r.ref_margin_percent}%` : ''}</Typography.Text>) },
        { title: 'Thực có', dataIndex: 'on_hand_total', key: 'oh', width: 90, align: 'right', render: (v) => v ?? 0 },
        { title: 'Khả dụng', dataIndex: 'available_total', key: 'av', width: 100, align: 'right', render: (v) => <StockBadge available={v ?? 0} /> },
        ...(canManage ? [{ title: '', key: 'act', width: 90, render: (_: unknown, r: Sku) => (
            <Space size={2}>
                <Button size="small" type="text" icon={<EditOutlined />} onClick={() => navigate(`/inventory/skus/${r.id}/edit`)} title="Sửa SKU" />
                <Popconfirm title="Xoá SKU này?" description={(r.on_hand_total ?? 0) !== 0 || (r.reserved_total ?? 0) !== 0 ? 'SKU còn tồn / đang được giữ — không thể xoá.' : 'Liên kết SKU sàn của nó cũng bị gỡ.'}
                    okButtonProps={{ danger: true, disabled: (r.on_hand_total ?? 0) !== 0 || (r.reserved_total ?? 0) !== 0 }} okText="Xoá" cancelText="Huỷ"
                    onConfirm={() => deleteSku.mutate(r.id, { onSuccess: () => message.success('Đã xoá SKU'), onError: (e) => message.error(errorMessage(e)) })}>
                    <Button size="small" type="text" danger icon={<DeleteOutlined />} title="Xoá SKU" />
                </Popconfirm>
            </Space>
        ) }] : []),
    ];

    return (
        <>
            <Space style={{ marginBottom: 12 }} wrap>
                <Input.Search allowClear placeholder="Mã / tên / barcode" prefix={<SearchOutlined />} style={{ width: 260 }} onSearch={(v) => { setQ(v); setPage(1); }} />
                {canManage && <Button type="primary" icon={<PlusOutlined />} onClick={() => navigate('/inventory/skus/new')}>Thêm SKU</Button>}
                {canAdjust && <Button icon={<ImportOutlined />} onClick={() => { bulkForm.resetFields(); bulkForm.setFieldsValue({ kind: 'goods_receipt', lines: [{}] }); setBulkOpen(true); }}>Phiếu nhập/xuất hàng loạt</Button>}
                {canMap && selectedKeys.length > 0 && (
                    <Button icon={<CloudUploadOutlined />} loading={bulkPush.isPending || pushPoll.isPolling} onClick={() => bulkPush.mutate(selectedKeys, { onSuccess: (r) => { message.success(`Đã yêu cầu đẩy tồn ${r.queued} SKU — đang chờ sàn xác nhận…`); setSelectedKeys([]); pushPoll.start(); }, onError: (e) => message.error(errorMessage(e)) })}>Đẩy tồn lên sàn ({selectedKeys.length})</Button>
                )}
            </Space>
            <Table<Sku> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns}
                rowSelection={canMap ? { selectedRowKeys: selectedKeys, onChange: (k) => setSelectedKeys(k as number[]) } : undefined}
                locale={{ emptyText: <Empty description="Chưa có SKU." /> }}
                pagination={{ current: data?.meta.pagination.page ?? page, pageSize: 20, total: data?.meta.pagination.total ?? 0, onChange: setPage, showTotal: (t) => `${t} SKU` }} />

            <Modal title="Phiếu nhập / xuất kho hàng loạt" open={bulkOpen} onCancel={() => setBulkOpen(false)} okText="Áp phiếu" width={700} confirmLoading={bulkAdjust.isPending}
                onOk={() => bulkForm.validateFields().then((v) => {
                    const lines = (v.lines ?? []).filter((l: { sku_id?: number }) => l?.sku_id).map((l: { sku_id: number; qty_change: number }) => ({ sku_id: l.sku_id, qty_change: l.qty_change }));
                    if (lines.length === 0) { message.error('Thêm ít nhất một dòng.'); return; }
                    bulkAdjust.mutate({ kind: v.kind, warehouse_id: v.warehouse_id, note: v.note || undefined, lines }, { onSuccess: (r) => { message.success(`Đã áp ${r.applied} dòng tồn`); setBulkOpen(false); }, onError: (e) => message.error(errorMessage(e)) });
                })}>
                <Form form={bulkForm} layout="vertical">
                    <Space wrap>
                        <Form.Item name="kind" label="Loại phiếu"><Select style={{ width: 220 }} options={[{ value: 'goods_receipt', label: 'Nhập kho (số lượng dương)' }, { value: 'manual_adjust', label: 'Điều chỉnh tay (±)' }]} /></Form.Item>
                        <Form.Item name="warehouse_id" label="Kho"><Select allowClear style={{ width: 200 }} placeholder="Kho mặc định" options={(warehouses ?? []).map((w) => ({ value: w.id, label: w.name }))} /></Form.Item>
                    </Space>
                    <Form.Item name="note" label="Ghi chú phiếu"><Input maxLength={255} placeholder="VD: Nhập đầu kỳ tháng 5" /></Form.Item>
                    <Form.List name="lines">
                        {(fields, { add, remove }) => (
                            <>
                                {fields.map((f) => (
                                    <Space key={f.key} align="baseline" style={{ display: 'flex', marginBottom: 8 }}>
                                        <Form.Item {...f} name={[f.name, 'sku_id']} rules={[{ required: true, message: 'Chọn SKU' }]} style={{ width: 360, marginBottom: 0 }}>
                                            <SkuPickerField placeholder="Chọn SKU…" />
                                        </Form.Item>
                                        <Form.Item {...f} name={[f.name, 'qty_change']} rules={[{ required: true }, { validator: (_, n) => (n === 0 ? Promise.reject('≠ 0') : Promise.resolve()) }]} style={{ marginBottom: 0 }}>
                                            <InputNumber placeholder="Số lượng" style={{ width: 140 }} />
                                        </Form.Item>
                                        <Button type="text" danger icon={<DeleteOutlined />} onClick={() => remove(f.name)} disabled={fields.length === 1} />
                                    </Space>
                                ))}
                                <Button type="dashed" icon={<PlusOutlined />} onClick={() => add({})} block>Thêm dòng</Button>
                            </>
                        )}
                    </Form.List>
                </Form>
            </Modal>
        </>
    );
}

/** Một dòng "SKU trên sàn" — ảnh + tên SP + (gian hàng · seller_sku · biến thể). Dùng trong bảng & modal. */
function ListingLine({ listing, shopName, avatarSize = 40 }: { listing: ChannelListing; shopName?: string; avatarSize?: number }) {
    const meta = [shopName, listing.seller_sku ? `SKU sàn: ${listing.seller_sku}` : `SKU sàn: ${listing.external_sku_id}`, listing.variation || null].filter(Boolean).join(' · ');
    return (
        <Space size={10} align="center" style={{ minWidth: 0 }}>
            <Avatar shape="square" size={avatarSize} src={listing.image ?? undefined} icon={<PictureOutlined />} style={{ background: '#f5f5f5', color: '#bfbfbf', flex: 'none' }} />
            <Space direction="vertical" size={0} style={{ minWidth: 0 }}>
                <Typography.Text strong ellipsis={{ tooltip: listing.title ?? listing.external_sku_id }} style={{ display: 'block', maxWidth: 320 }}>{listing.title ?? listing.external_sku_id}</Typography.Text>
                <Typography.Text type="secondary" style={{ fontSize: 12, display: 'block', maxWidth: 320 }} ellipsis={{ tooltip: meta }}>{meta}</Typography.Text>
            </Space>
        </Space>
    );
}

function ListingsTab() {
    const { message } = AntApp.useApp();
    const [page, setPage] = useState(1);
    const [mappedFilter, setMappedFilter] = useState<'' | '0' | '1'>('');
    const [shopId, setShopId] = useState<number | undefined>();
    const [q, setQ] = useState('');
    const { data, isFetching, refetch } = useChannelListings({ page, per_page: 20, q: q || undefined, channel_account_id: shopId, mapped: mappedFilter === '' ? undefined : (Number(mappedFilter) as 0 | 1) });
    const { data: shopsData } = useChannelAccounts();
    const shopName = (id: number) => shopsData?.data?.find((s) => s.id === id)?.name ?? `#${id}`;
    const autoMatch = useAutoMatchSkus();
    const syncListings = useSyncChannelListings();
    const setMapping = useSetSkuMapping();
    const canMap = useCan('inventory.map');
    const [mapFor, setMapFor] = useState<ChannelListing | null>(null);
    // Sync listing là job chạy nền (FetchChannelListings) → mutation chỉ trả "queued"; phải poll để
    // bảng tự cập nhật khi job xong, không bắt user reload trang.
    const syncPoll = useSyncPolling(() => { refetch(); }, { durationMs: 60_000 });
    const currentSku = (l: ChannelListing | null) => l?.mappings?.[0]?.sku ?? null;

    const save = (l: ChannelListing, skuId: number | null) => setMapping.mutate({ channel_listing_id: l.id, sku_id: skuId }, {
        onSuccess: () => { message.success(skuId ? 'Đã ghép SKU' : 'Đã bỏ liên kết'); setMapFor(null); },
        onError: (e) => message.error(errorMessage(e)),
    });

    const columns: ColumnsType<ChannelListing> = [
        { title: 'SKU trên sàn', key: 'listing', render: (_, r) => <ListingLine listing={r} shopName={shopName(r.channel_account_id)} /> },
        { title: 'Tồn sàn', dataIndex: 'channel_stock', key: 'cs', width: 90, align: 'right', render: (v) => v ?? '—' },
        { title: 'Đẩy tồn', dataIndex: 'sync_status', key: 'ss', width: 110, render: (v, r) => <Space size={4}><Tag color={v === 'ok' ? 'green' : v === 'error' ? 'red' : 'default'}>{v === 'ok' ? 'OK' : v === 'error' ? 'Lỗi' : v}</Tag>{r.is_stock_locked && <Tag>ghim</Tag>}</Space> },
        { title: 'Ghép với SKU hàng hoá', key: 'mapped', render: (_, r) => {
            const sku = currentSku(r);
            if (sku) return <Space size={8}><SkuLine sku={sku} avatarSize={28} maxTextWidth={220} />{canMap && <Typography.Link onClick={() => setMapFor(r)}>Đổi</Typography.Link>}</Space>;
            return canMap ? <Button type="primary" size="small" onClick={() => setMapFor(r)}>Ghép SKU</Button> : <Tag color="warning">Chưa ghép</Tag>;
        } },
    ];

    return (
        <>
            <Space style={{ marginBottom: 12 }} wrap>
                <Input.Search allowClear placeholder="Tìm tên SP / mã SKU sàn" prefix={<SearchOutlined />} style={{ width: 240 }} onSearch={(v) => { setQ(v); setPage(1); }} />
                <Select allowClear placeholder="Gian hàng" suffixIcon={<ShopOutlined />} style={{ width: 180 }} value={shopId} onChange={(v) => { setShopId(v); setPage(1); }}
                    options={(shopsData?.data ?? []).map((s) => ({ value: s.id, label: s.name }))} />
                <Select value={mappedFilter} style={{ width: 150 }} onChange={(v) => { setMappedFilter(v); setPage(1); }} options={[{ value: '', label: 'Tất cả' }, { value: '0', label: 'Chưa ghép' }, { value: '1', label: 'Đã ghép' }]} />
                {canMap && <Button icon={<CloudDownloadOutlined />} loading={syncListings.isPending || syncPoll.isPolling} onClick={() => syncListings.mutate(undefined, { onSuccess: (r) => { if (r.queued > 0) { message.success(`Đang đồng bộ listing từ ${r.queued} gian hàng…`); syncPoll.start(); } else { message.info('Chưa có gian hàng nào hỗ trợ đồng bộ listing'); } }, onError: (e) => message.error(errorMessage(e)) })}>Đồng bộ listing từ sàn</Button>}
                {canMap && <Button icon={<ThunderboltOutlined />} loading={autoMatch.isPending} onClick={() => autoMatch.mutate(undefined, { onSuccess: (r) => message.success(`Đã tự ghép ${r.matched} listing theo mã`), onError: (e) => message.error(errorMessage(e)) })}>Tự ghép theo mã</Button>}
                <Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>Làm mới</Button>
            </Space>
            <Table<ChannelListing> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns}
                locale={{ emptyText: <Empty description="Chưa có SKU sàn nào. Bấm “Đồng bộ listing từ sàn” để kéo sản phẩm/SKU của gian hàng về." /> }}
                pagination={{ current: data?.meta.pagination.page ?? page, pageSize: 20, total: data?.meta.pagination.total ?? 0, onChange: setPage, showTotal: (t) => `${t} SKU sàn` }} />

            <MapListingModal listing={mapFor} shopName={mapFor ? shopName(mapFor.channel_account_id) : ''} onClose={() => setMapFor(null)} onSave={save} setMappingPending={setMapping.isPending} />
        </>
    );
}

/**
 * Modal "Ghép SKU sàn với SKU hàng hoá" — bên cạnh chọn SKU có sẵn, còn 1 form "Tạo nhanh SKU mới"
 * (pre-fill từ chính listing: mã = seller_sku || external_sku_id, tên = title, giá vốn 0) → tạo SKU
 * + tự ghép luôn trong 1 nhịp, để khỏi phải sang trang Tồn kho rồi quay lại.
 */
function MapListingModal({ listing, shopName, onClose, onSave, setMappingPending }: {
    listing: ChannelListing | null;
    shopName: string;
    onClose: () => void;
    onSave: (l: ChannelListing, skuId: number | null) => void;
    setMappingPending: boolean;
}) {
    const { message } = AntApp.useApp();
    const [tab, setTab] = useState<'pick' | 'create'>('pick');
    const [createForm] = Form.useForm<{ sku_code: string; name: string; cost_price?: number }>();
    const createSku = useCreateSku();
    const currentSkuId = listing?.mappings?.[0]?.sku_id;
    const currentSku = listing?.mappings?.[0]?.sku ?? null;

    // Khi mở modal cho listing mới: reset về tab "Chọn SKU" + pre-fill form tạo nhanh từ dữ liệu listing.
    useEffect(() => {
        if (!listing) return;
        setTab('pick');
        const code = (listing.seller_sku || listing.external_sku_id || '').trim();
        const name = (listing.title || code).trim();
        createForm.setFieldsValue({ sku_code: code, name, cost_price: 0 });
    }, [listing?.id]);  // eslint-disable-line react-hooks/exhaustive-deps

    const submitCreate = () => createForm.validateFields().then((v) => {
        if (!listing) return;
        createSku.mutate({
            sku_code: v.sku_code.trim(),
            name: v.name.trim(),
            cost_price: v.cost_price ?? 0,
            mappings: [{
                channel_account_id: listing.channel_account_id,
                external_sku_id: listing.external_sku_id,
                seller_sku: listing.seller_sku,
            }],
        }, {
            onSuccess: () => { message.success(`Đã tạo SKU "${v.sku_code}" và ghép với listing này.`); onClose(); },
            onError: (e) => message.error(errorMessage(e)),
        });
    });

    return (
        <Modal title="Ghép SKU sàn với SKU hàng hoá" open={!!listing} onCancel={onClose} footer={null} width={460} destroyOnClose>
            {listing && (
                <>
                    <div style={{ background: '#fafafa', borderRadius: 8, padding: '8px 12px', marginBottom: 12 }}><ListingLine listing={listing} shopName={shopName} avatarSize={36} /></div>
                    {currentSku && (
                        <div style={{ marginBottom: 12 }}>
                            <Typography.Text type="secondary" style={{ fontSize: 12 }}>Đang ghép với:</Typography.Text>
                            <Space style={{ display: 'flex', justifyContent: 'space-between', marginTop: 4 }}>
                                <SkuLine sku={currentSku} avatarSize={30} maxTextWidth={220} />
                                <Button danger size="small" loading={setMappingPending} onClick={() => onSave(listing, null)}>Bỏ liên kết</Button>
                            </Space>
                        </div>
                    )}
                    <Radio.Group value={tab} onChange={(e) => setTab(e.target.value)} optionType="button" buttonStyle="solid" style={{ marginBottom: 10 }}
                        options={[{ value: 'pick', label: 'Chọn SKU có sẵn' }, { value: 'create', label: 'Tạo nhanh SKU mới' }]} />
                    {tab === 'pick' ? (
                        <>
                            <Typography.Text type="secondary" style={{ fontSize: 12 }}>{currentSku ? 'Chọn SKU khác để thay liên kết:' : 'Chọn SKU hàng hoá để ghép:'}</Typography.Text>
                            <div style={{ marginTop: 6 }}>
                                <SkuPicker width="100%" height={300} value={currentSkuId} onChange={(id) => { if (id && id !== currentSkuId) onSave(listing, id); }} />
                            </div>
                            <Typography.Paragraph type="secondary" style={{ fontSize: 12, marginTop: 8, marginBottom: 0 }}>Mỗi SKU sàn chỉ thuộc đúng 1 SKU hàng hoá; 1 SKU hàng hoá có thể nhận nhiều SKU sàn. Tồn của SKU hàng hoá sẽ tự đẩy lên listing này.</Typography.Paragraph>
                        </>
                    ) : (
                        <Form form={createForm} layout="vertical" requiredMark={false}>
                            <Typography.Paragraph type="secondary" style={{ fontSize: 12, marginBottom: 10 }}>Tạo SKU hàng hoá mới ngay từ thông tin listing này, rồi tự ghép. Có thể chỉnh thêm sau ở trang Tồn kho.</Typography.Paragraph>
                            <Form.Item name="sku_code" label="Mã SKU" rules={[{ required: true, message: 'Nhập mã SKU' }, { max: 100 }]}>
                                <Input placeholder="VD: AOTHUN-DEN-M" maxLength={100} />
                            </Form.Item>
                            <Form.Item name="name" label="Tên" rules={[{ required: true, message: 'Nhập tên SKU' }, { max: 255 }]}>
                                <Input placeholder="Tên hàng hoá" maxLength={255} />
                            </Form.Item>
                            <Form.Item name="cost_price" label="Giá vốn (tuỳ chọn)">
                                <InputNumber<number> min={0} addonBefore="₫" style={{ width: '100%' }} />
                            </Form.Item>
                            <Space style={{ display: 'flex', justifyContent: 'flex-end' }}>
                                <Button onClick={onClose}>Huỷ</Button>
                                <Button type="primary" loading={createSku.isPending} onClick={submitCreate}>Tạo & ghép</Button>
                            </Space>
                        </Form>
                    )}
                </>
            )}
        </Modal>
    );
}
