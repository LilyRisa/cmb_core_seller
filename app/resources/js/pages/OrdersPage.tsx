import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Alert, App as AntApp, Avatar, Badge, Button, Card, DatePicker, Empty, Input, Modal, Radio, Select, Space, Table, Tabs, Tag, Tooltip, Typography } from 'antd';
import { BarcodeOutlined, CheckCircleOutlined, FileTextOutlined, LinkOutlined, PrinterOutlined, ReloadOutlined, ScanOutlined, SearchOutlined, SyncOutlined, WarningOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { StatusTag } from '@/components/StatusTag';
import { ChannelBadge } from '@/components/ChannelBadge';
import { MoneyText, DateText } from '@/components/MoneyText';
import { FilterChipRow, type ChipItem } from '@/components/FilterChipRow';
import { LinkSkusModal } from '@/components/LinkSkusModal';
import { OrderDetailModal } from '@/components/OrderDetailModal';
import { OrderActions, PrintCountBadge, PrintJobBar, ScanTab, ShipmentsTab } from '@/components/OrderProcessing';
import { errorMessage } from '@/lib/api';
import { CHANNEL_META, ORDER_STATUS_TABS } from '@/lib/format';
import { Order, useOrders, useOrderStats, useSyncOrders } from '@/lib/orders';
import { useBulkCreateShipments, useBulkRefetchSlip, useCreatePrintJob, usePackShipments } from '@/lib/fulfillment';
import { useChannelAccounts } from '@/lib/channels';
import { useSyncPolling } from '@/lib/syncPolling';
import { useSyncRuns } from '@/lib/syncLogs';
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

/** Sub-tab "tình trạng phiếu giao hàng" — chỉ hiện ở tab "Đang xử lý" khi có ≥1 đơn "Chuẩn bị hàng" lỗi (SPEC 0013). */
const SLIP_TABS = [
    { key: '', label: 'Tất cả' },
    { key: 'printable', label: 'Có thể in' },
    { key: 'loading', label: 'Đang tải lại' },
    { key: 'failed', label: 'Nhận phiếu giao hàng' },
] as const;

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
    const bulkPrepare = useBulkCreateShipments();
    const bulkPack = usePackShipments();
    const refetchSlip = useBulkRefetchSlip();
    const createPrintJob = useCreatePrintJob();
    const canCreate = useCan('orders.create');
    const canMap = useCan('inventory.map');
    const canShip = useCan('fulfillment.ship');
    const canPrint = useCan('fulfillment.print');
    const [selectedKeys, setSelectedKeys] = useState<number[]>([]);
    const [linkModal, setLinkModal] = useState<{ open: boolean; orderIds?: number[] }>({ open: false });
    const [viewOrderId, setViewOrderId] = useState<number | null>(null);
    // fulfillment: print-job progress bar + scan-to-pack/handover modal (BigSeller-style — thao tác ngay trên list)
    const [printJobId, setPrintJobId] = useState<number | null>(null);
    const [scan, setScan] = useState<{ open: boolean; mode: 'pack' | 'handover' }>({ open: false, mode: 'pack' });

    const tabKey = params.get('tab') ?? (params.get('has_issue') ? 'issue' : '');
    const statusParam = params.get('status') ?? '';
    const slipParam = params.get('slip') ?? '';
    const printedParam = params.get('printed') ?? '';
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
    const effectiveStatus = tabKey === 'issue' || tabKey === 'out_of_stock' ? '' : (statusParam || (activeTab.statuses ?? []).join(','));
    const isProcessingTab = tabKey === 'processing';
    const slipFilter = isProcessingTab && (['printable', 'loading', 'failed'] as string[]).includes(slipParam) ? (slipParam as 'printable' | 'loading' | 'failed') : undefined;
    const printedFilter = isProcessingTab && (printedParam === '1' || printedParam === '0') ? printedParam === '1' : undefined;

    const filters = useMemo(() => ({
        status: effectiveStatus || undefined,
        q: q || undefined, sku: skuQ || undefined, product: productQ || undefined,
        source: source || undefined,
        channel_account_id: channelAccountId ? Number(channelAccountId) : undefined,
        carrier: carrier || undefined,
        placed_from: placedFrom || undefined, placed_to: placedTo || undefined,
        has_issue: tabKey === 'issue' || params.get('has_issue') === '1' ? true : undefined,
        out_of_stock: tabKey === 'out_of_stock' ? true : undefined,
        slip: slipFilter,
        printed: printedFilter,
        sort, page, per_page: perPage,
    }), [effectiveStatus, q, skuQ, productQ, source, channelAccountId, carrier, placedFrom, placedTo, tabKey, slipFilter, printedFilter, params, sort, page, perPage]);

    // stats: gửi kèm trạng thái/tab hiện tại ⇒ các chip "Lọc" (Sàn / Gian hàng / ĐVVC) đếm theo đúng tab đang xem
    // (BE: by_status/by_stage/by_slip vẫn bỏ qua status/stage/slip nên badge các tab vẫn đúng tổng riêng).
    const statsFilters = useMemo(() => ({
        status: effectiveStatus || undefined,
        q: q || undefined, sku: skuQ || undefined, product: productQ || undefined,
        source: source || undefined,
        channel_account_id: channelAccountId ? Number(channelAccountId) : undefined,
        carrier: carrier || undefined,
        placed_from: placedFrom || undefined, placed_to: placedTo || undefined,
        has_issue: tabKey === 'issue' || params.get('has_issue') === '1' ? true : undefined,
        out_of_stock: tabKey === 'out_of_stock' ? true : undefined,
        slip: slipFilter,
        printed: printedFilter,
    }), [effectiveStatus, q, skuQ, productQ, source, channelAccountId, carrier, placedFrom, placedTo, tabKey, slipFilter, printedFilter, params]);

    const isShipmentsTab = tabKey === 'shipments';
    // tab làm việc: "Chờ xử lý" / "Đang xử lý" / "Chờ bàn giao" — cho chọn nhiều đơn + bulk actions
    const isWorkTab = tabKey === 'pending' || tabKey === 'processing' || tabKey === 'ready_to_ship';
    const isShipTab = tabKey === 'ready_to_ship';
    const canBulkWork = isWorkTab && (canShip || canPrint);

    // skip the (unused) orders list when on the shipments tab
    const { data, isFetching, refetch } = useOrders(isShipmentsTab ? { ...filters, page: 1, per_page: 1 } : filters);
    const { data: stats, refetch: refetchStats } = useOrderStats(statsFilters);
    // Sync orders dispatch job chạy nền — poll list + stats để đơn mới về tự render, không cần reload trang.
    const syncPoll = useSyncPolling(() => { refetch(); refetchStats(); }, { durationMs: 90_000 });
    // Theo dõi sync runs đang chạy để hiện thanh tiến trình (refetch 15s/lần qua hook).
    const runningSyncs = useSyncRuns({ status: 'running', per_page: 10 });
    const runningSyncsList = runningSyncs.data?.data ?? [];
    const showSyncBanner = syncPoll.isPolling || runningSyncsList.length > 0;
    // sub-tab "tình trạng phiếu giao hàng" chỉ hiện khi có ≥1 đơn "Chuẩn bị hàng" lỗi (SPEC 0013 — như ui_example)
    const showSlipTabs = isProcessingTab && (stats?.by_slip?.failed ?? 0) > 0;
    // Xử lý xong các đơn lỗi ⇒ sub-tab "Nhận phiếu giao hàng" biến mất; tự bỏ filter `slip=failed` còn sót lại
    // để quay về bộ lọc gốc của "Đang xử lý" (không kẹt ở danh sách rỗng). Cũng dọn slip khi rời tab này.
    useEffect(() => {
        if (!slipParam) return;
        const stuck = !isProcessingTab || (slipParam === 'failed' && (stats?.by_slip?.failed ?? 0) === 0);
        if (stuck) {
            const m = new URLSearchParams(params);
            m.delete('slip');
            setParams(m, { replace: true });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isProcessingTab, slipParam, stats?.by_slip?.failed]);

    const countForTab = (t: { statuses?: string[] }) => (t.statuses ?? []).reduce((s, st) => s + (stats?.by_status?.[st] ?? 0), 0);
    const shopName = (id: number) => accounts.find((a) => a.id === id)?.name ?? `#${id}`;

    // bulk actions: "Chuẩn bị hàng" + "In phiếu giao hàng" (tem của sàn) trên các đơn đã chọn
    const selectedOrders = (data?.data ?? []).filter((o) => selectedKeys.includes(o.id));
    const selWithShipment = selectedOrders.filter((o) => o.shipment);
    const selWithoutShipment = selectedOrders.filter((o) => !o.shipment);
    // Orders whose open shipment is in created/pending state → can be bulk-packed (→ ready_to_ship).
    const selPackable = selWithShipment.filter((o) => o.shipment && ['created', 'pending'].includes(o.shipment.status));
    const negProfit = selectedOrders.filter((o) => o.profit && o.profit.estimated_profit < 0);
    const runBulkPrepare = () => bulkPrepare.mutate({ order_ids: selectedKeys }, {
        onSuccess: (r) => {
            message.success(r.created.length > 0 ? `Đã chuẩn bị hàng ${r.created.length} đơn — đang lấy phiếu giao hàng của sàn. Các đơn chuyển sang "Đang xử lý".` : 'Không có đơn nào được chuẩn bị');
            if (r.errors.length) Modal.warning({ title: `${r.errors.length} đơn không chuẩn bị được`, content: <ul style={{ margin: 0, paddingInlineStart: 18 }}>{r.errors.map((e) => <li key={e.order_id}>Đơn #{e.order_id}: {e.message}</li>)}</ul> });
            setSelectedKeys([]);
        },
        onError: (e) => message.error(errorMessage(e)),
    });
    const doBulkPrepare = () => {
        if (negProfit.length > 0) {
            Modal.confirm({
                title: `${negProfit.length} đơn có lợi nhuận ước tính ÂM`,
                content: 'Tổng tiền các đơn này không bù được phí sàn + giá vốn hàng. Vẫn tiếp tục chuẩn bị hàng (tạo vận đơn / lấy phiếu)?',
                okText: 'Vẫn chuẩn bị', okButtonProps: { danger: true }, cancelText: 'Để tôi xem lại',
                onOk: runBulkPrepare,
            });
        } else { runBulkPrepare(); }
    };
    // Tab "Chờ bàn giao" (ready_to_ship): đơn đã RTS trên sàn trước khi sync về — gọi prepareChannelOrder để
    // tạo shipment record + lấy label; order KHÔNG chuyển sang "Đang xử lý" (stay in ready_to_ship).
    const runBulkPrepareShipTab = () => bulkPrepare.mutate({ order_ids: selWithoutShipment.map((o) => o.id) }, {
        onSuccess: (r) => {
            message.success(r.created.length > 0 ? `Đã lấy phiếu giao hàng cho ${r.created.length} đơn — sẵn sàng để in.` : 'Không có đơn nào cần lấy phiếu');
            if (r.errors.length) Modal.warning({ title: `${r.errors.length} đơn không lấy được phiếu`, content: <ul style={{ margin: 0, paddingInlineStart: 18 }}>{r.errors.map((e) => <li key={e.order_id}>Đơn #{e.order_id}: {e.message}</li>)}</ul> });
            setSelectedKeys([]);
        },
        onError: (e) => message.error(errorMessage(e)),
    });
    const doBulkPrepareShipTab = () => {
        const neg = selWithoutShipment.filter((o) => o.profit && o.profit.estimated_profit < 0);
        if (neg.length > 0) {
            Modal.confirm({
                title: `${neg.length} đơn có lợi nhuận ước tính ÂM`,
                content: 'Tổng tiền các đơn này không bù được phí sàn + giá vốn hàng. Vẫn tiếp tục lấy phiếu giao hàng?',
                okText: 'Vẫn tiếp tục', okButtonProps: { danger: true }, cancelText: 'Để tôi xem lại',
                onOk: runBulkPrepareShipTab,
            });
        } else { runBulkPrepareShipTab(); }
    };
    // "Nhận phiếu giao hàng" — kéo/tạo phiếu cho các đơn; có print_job_id ⇒ mở thanh tiến trình + nút "Mở để in".
    const runRefetchSlip = (ids: number[]) => refetchSlip.mutate(ids, {
        onSuccess: (r) => {
            if (r.print_job_id) setPrintJobId(r.print_job_id);
            else if (r.ok > 0) message.success(`Phiếu giao hàng đã sẵn sàng cho ${r.ok} đơn — bấm "In phiếu giao hàng".`);
            if (r.errors.length) Modal.warning({ title: `${r.errors.length} đơn không lấy được phiếu giao hàng`, content: <ul style={{ margin: 0, paddingInlineStart: 18 }}>{r.errors.map((e) => <li key={e.order_id}>Đơn #{e.order_id}: {e.message}</li>)}</ul> });
            setSelectedKeys([]);
        },
        onError: (e) => message.error(errorMessage(e)),
    });
    const doRefetchSlip = () => runRefetchSlip(selWithShipment.map((o) => o.id));
    const doBulkPack = () => bulkPack.mutate(selPackable.map((o) => o.shipment!.id), {
        onSuccess: (r) => { message.success(`Đã đánh dấu ${r.packed} đơn sẵn sàng bàn giao — chuyển sang "Chờ bàn giao".`); setSelectedKeys([]); },
        onError: (e) => message.error(errorMessage(e)),
    });
    // "In phiếu giao hàng": chỉ in được đơn đã có phiếu; đơn nào chưa có ⇒ popup hướng dẫn bấm "Nhận phiếu giao hàng".
    const doBulkPrintSlip = () => {
        const ready = selWithShipment.filter((o) => o.shipment!.has_label);
        const notReady = selWithShipment.filter((o) => !o.shipment!.has_label);
        if (notReady.length > 0) {
            Modal.confirm({
                title: `${notReady.length} đơn chưa có phiếu giao hàng`,
                content: ready.length > 0
                    ? `Trong ${selWithShipment.length} đơn đã chọn, ${notReady.length} đơn chưa có phiếu giao hàng để in. Bấm "Nhận phiếu giao hàng" để hệ thống tự tải phiếu về (sẽ có thanh tiến trình); khi xong bấm "Mở để in". Các đơn đã có phiếu vẫn in được sau khi tải xong.`
                    : `Các đơn này chưa có phiếu giao hàng để in. Bấm "Nhận phiếu giao hàng" để hệ thống tự tải về — sẽ có thanh tiến trình; khi xong bấm "Mở để in".`,
                okText: 'Nhận phiếu giao hàng', cancelText: 'Để sau',
                onOk: () => runRefetchSlip(notReady.map((o) => o.id)),
            });
            return;
        }
        // Quy tắc gom phiếu in: cùng 1 nền tảng + cùng 1 ĐVVC mới ghép chung được (khác nền tảng/ĐVVC ⇒ định
        // dạng tem khác nhau, không ghép vào 1 PDF được). Khác gian hàng trong CÙNG nền tảng thì vẫn in chung.
        const sources = Array.from(new Set(ready.map((o) => o.source)));
        const carriers = Array.from(new Set(ready.map((o) => o.shipment!.carrier)));
        if (sources.length > 1 || carriers.length > 1) {
            Modal.warning({
                title: 'Không thể in chung phiếu của nhiều nền tảng / đơn vị vận chuyển',
                width: 520,
                content: (
                    <div>
                        <p style={{ marginTop: 0 }}>Mỗi sàn (TikTok / Shopee / Lazada / …) và mỗi đơn vị vận chuyển (GHN / TikTok Logistics / …) dùng định dạng tem riêng — không thể ghép chung vào một tệp PDF để in được.</p>
                        <p>Bạn đang chọn các đơn thuộc{' '}
                            {sources.length > 1 && <><b>{sources.length} sàn</b> ({sources.map((s) => CHANNEL_META[s]?.name ?? s).join(', ')})</>}
                            {sources.length > 1 && carriers.length > 1 && ' và '}
                            {carriers.length > 1 && <><b>{carriers.length} đơn vị vận chuyển</b> ({carriers.join(', ')})</>}.
                        </p>
                        <p style={{ marginBottom: 0 }}>Hãy dùng chip <b>“Sàn TMĐT”</b> / <b>“Vận chuyển”</b> trong phần Lọc để lọc theo từng nhóm, rồi in lần lượt từng đợt.</p>
                    </div>
                ),
                okText: 'Đã hiểu',
            });
            return;
        }
        const runPrint = () => createPrintJob.mutate({ type: 'label', shipment_ids: ready.map((o) => o.shipment!.id) }, {
            onSuccess: (j) => { setPrintJobId(j.id); setSelectedKeys([]); },
            onError: (e) => Modal.warning({ title: 'Không in được tem', content: errorMessage(e), okText: 'Đã hiểu' }),
        });
        // Cảnh báo in lại: ≥1 đơn đã được in trước đó (`print_count > 0`) ⇒ tránh in trùng phiếu vận chuyển.
        const reprinted = ready.filter((o) => (o.shipment!.print_count ?? 0) > 0);
        if (reprinted.length > 0) {
            Modal.confirm({
                title: `${reprinted.length} đơn đã từng in phiếu — vẫn in tiếp?`,
                width: 540,
                content: (
                    <div>
                        <p style={{ marginTop: 0 }}>Trong <b>{ready.length}</b> đơn sắp in, <b>{reprinted.length}</b> đơn đã được in phiếu giao hàng trước đó. In lại có thể tạo trùng phiếu vận chuyển — kiểm tra trước khi giao cho đơn vị vận chuyển.</p>
                        <p style={{ marginBottom: 4 }}>Danh sách đơn đã in:</p>
                        <ul style={{ margin: 0, paddingInlineStart: 18, maxHeight: 220, overflowY: 'auto' }}>
                            {reprinted.map((o) => <li key={o.id}>{o.order_number ?? o.external_order_id ?? `#${o.id}`} — đã in <b>{o.shipment!.print_count}</b> lần</li>)}
                        </ul>
                    </div>
                ),
                okText: 'Vẫn in', okButtonProps: { danger: true }, cancelText: 'Huỷ',
                onOk: runPrint,
            });
            return;
        }
        runPrint();
    };

    // chip-row items
    const sourceChips: ChipItem[] = (stats?.by_source ?? []).map((s) => ({ value: s.source, label: CHANNEL_META[s.source]?.name ?? s.source, count: s.count }));
    const shopChips: ChipItem[] = (stats?.by_shop ?? []).map((s) => ({ value: String(s.channel_account_id), label: shopName(s.channel_account_id), count: s.count }));
    const carrierChips: ChipItem[] = (stats?.by_carrier ?? []).map((c) => ({ value: c.carrier, label: c.carrier, count: c.count }));
    const printedChips: ChipItem[] = [
        { value: '1', label: 'Đã in phiếu', count: stats?.by_printed?.yes ?? 0 },
        { value: '0', label: 'Chưa in phiếu', count: stats?.by_printed?.no ?? 0 },
    ];
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
                            // "SKU chưa ghép" KHÔNG còn chặn in / fulfillment — đơn vẫn xử lý bình thường, chỉ là
                            // không có dòng nào động vào tồn kho. Đổi sang warning (vàng) thay vì error (đỏ).
                            ? <Tooltip title="Đơn vẫn in & bàn giao bình thường, nhưng không trừ tồn cho dòng chưa ghép SKU. Bấm để liên kết."><Tag color="warning" icon={<LinkOutlined />} style={{ cursor: 'pointer' }} onClick={() => setLinkModal({ open: true, orderIds: [o.id] })}>Chưa ghép SKU — Liên kết</Tag></Tooltip>
                            : o.has_issue && <Tooltip title={o.issue_reason ?? 'Đơn có vấn đề'}><Tag color="error" icon={<WarningOutlined />}>Lỗi</Tag></Tooltip>}
                        {o.shipment && o.shipment.print_count > 0 && <PrintCountBadge n={o.shipment.print_count} at={o.shipment.last_printed_at} />}
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
        {
            title: 'Tổng tiền', dataIndex: 'grand_total', key: 'total', width: 160, align: 'right',
            render: (v, o) => {
                const p = o.profit;
                return (
                    <Space direction="vertical" size={0} style={{ width: '100%' }} align="end">
                        <MoneyText value={v} currency={o.currency} strong />
                        {p && (
                            <Tooltip title={<div style={{ lineHeight: 1.7 }}>
                                Lợi nhuận ước tính sau phí sàn:<br />
                                Phí sàn ({p.platform_fee_pct}%): −{p.platform_fee.toLocaleString('vi-VN')} ₫<br />
                                Phí vận chuyển: −{p.shipping_fee.toLocaleString('vi-VN')} ₫<br />
                                Giá vốn hàng: −{p.cogs.toLocaleString('vi-VN')} ₫{!p.cost_complete && ' (chưa đủ — thiếu giá vốn SKU)'}
                            </div>}>
                                <span style={{ fontSize: 12, color: p.estimated_profit >= 0 ? '#389e0d' : '#cf1322' }}>
                                    {!p.cost_complete && <WarningOutlined style={{ color: '#faad14', marginRight: 3 }} />}
                                    LN: {p.estimated_profit.toLocaleString('vi-VN')} ₫
                                </span>
                            </Tooltip>
                        )}
                    </Space>
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
                        <Button icon={<SyncOutlined />} loading={syncOrders.isPending || syncPoll.isPolling} onClick={() => syncOrders.mutate(undefined, {
                            onSuccess: (r) => { if (r.queued > 0) { message.success(`Đã yêu cầu đồng bộ ${r.queued} gian hàng — đơn mới sẽ tự xuất hiện khi sàn trả về.`); syncPoll.start(); } else { message.info('Chưa có gian hàng nào hoạt động'); } },
                            onError: (e) => message.error(errorMessage(e)),
                        })}>Đồng bộ đơn</Button>
                        <Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>Làm mới</Button>
                    </Space>
                )}
            />

            {showSyncBanner && (
                <Alert
                    type={runningSyncsList.some((r) => (r.stats?.errors ?? 0) > 0) ? 'warning' : 'info'}
                    showIcon icon={<SyncOutlined spin />}
                    style={{ marginTop: 8 }}
                    message={runningSyncsList.length === 0
                        ? 'Đã gửi yêu cầu đồng bộ, đang chờ tiến hành...'
                        : `Đang đồng bộ đơn từ ${runningSyncsList.length} gian hàng`}
                    description={runningSyncsList.length > 0 ? (
                        <Space size={16} wrap>
                            <span>{runningSyncsList.map((r) => r.shop_name ?? `#${r.channel_account_id}`).join(' · ')}</span>
                            <span>Đã nhận: <b>{runningSyncsList.reduce((s, r) => s + (r.stats?.fetched ?? 0), 0)}</b> đơn</span>
                            <span>Mới: <b>{runningSyncsList.reduce((s, r) => s + (r.stats?.created ?? 0), 0)}</b></span>
                            {runningSyncsList.reduce((s, r) => s + (r.stats?.errors ?? 0), 0) > 0 && (
                                <span style={{ color: '#cf1322' }}>Lỗi: <b>{runningSyncsList.reduce((s, r) => s + (r.stats?.errors ?? 0), 0)}</b></span>
                            )}
                        </Space>
                    ) : undefined}
                />
            )}

            {/* Tabs: 3 tab "công việc" đầu lọc theo bước xử lý/vận đơn (SPEC 0013), còn lại theo trạng thái đơn. */}
            <Card styles={{ body: { padding: '8px 16px 0' } }}>
                <Tabs
                    activeKey={tabKey}
                    onChange={(k) => { setSelectedKeys([]); set({ tab: k || undefined, status: undefined, slip: undefined, printed: undefined, has_issue: k === 'issue' ? '1' : undefined }); }}
                    items={[
                        ...ORDER_STATUS_TABS.map((t) => ({
                            key: t.key,
                            label: <span>{t.label}{t.key !== '' && stats ? <Badge count={countForTab(t)} overflowCount={9999} showZero={false} style={{ marginInlineStart: 6, background: '#f0f0f0', color: '#595959' }} /> : null}</span>,
                        })),
                        { key: 'issue', label: <span>Có vấn đề{stats?.has_issue ? <Badge count={stats.has_issue} style={{ marginInlineStart: 6 }} /> : null}</span> },
                        // Đơn có SKU âm tồn — chặn "Chuẩn bị hàng / lấy phiếu giao hàng" cho đến khi nhập thêm hàng (SPEC 0013).
                        { key: 'out_of_stock', label: <span><WarningOutlined style={{ marginInlineEnd: 4 }} />Hết hàng{stats?.out_of_stock ? <Badge count={stats.out_of_stock} style={{ marginInlineStart: 6 }} /> : null}</span> },
                        { key: 'shipments', label: <span><BarcodeOutlined style={{ marginInlineEnd: 4 }} />Vận đơn</span> },
                    ]}
                />
            </Card>

            {showSlipTabs && (
                <Card style={{ marginTop: 8 }} size="small" styles={{ body: { padding: '8px 16px' } }}>
                    <Space wrap>
                        <FileTextOutlined />
                        <Typography.Text type="secondary">Tình trạng phiếu giao hàng:</Typography.Text>
                        <Radio.Group
                            size="small" optionType="button" buttonStyle="solid"
                            value={slipParam}
                            onChange={(e) => { setSelectedKeys([]); set({ slip: e.target.value || undefined }); }}
                            options={SLIP_TABS.map((t) => ({ value: t.key, label: `${t.label}${t.key && stats?.by_slip ? ' ' + (stats.by_slip[t.key as 'printable' | 'loading' | 'failed'] ?? 0) : ''}` }))}
                        />
                        <Tooltip title="Làm mới"><Button size="small" type="text" icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching} /></Tooltip>
                    </Space>
                </Card>
            )}

            {isShipmentsTab ? (
                <div style={{ marginTop: 12 }}>
                    <Card styles={{ body: { padding: 16 } }}>
                        <ShipmentsTab onPrint={setPrintJobId} />
                    </Card>
                </div>
            ) : (<>

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

                {/*
                  * Lọc theo cây cha→con: nền tảng → gian hàng → vận chuyển. Đổi cha thì clear con (để khỏi
                  * kẹt ở 1 chip con không còn hợp lệ với cha mới); BE stats cũng đã cascade theo các filter cha
                  * (xem OrderController::stats — `sourceBase` / `shopBase` / `carrierBase`).
                  */}
                <FilterChipRow label="Sàn TMĐT" items={sourceChips} value={source || undefined} onChange={(v) => set({ source: v, channel_account_id: undefined, carrier: undefined })} />
                <FilterChipRow label="Gian hàng" items={shopChips} value={channelAccountId || undefined} onChange={(v) => set({ channel_account_id: v, carrier: undefined })} />
                <FilterChipRow label="Vận chuyển" items={carrierChips} value={carrier || undefined} onChange={(v) => set({ carrier: v })} />
                {isProcessingTab && <FilterChipRow label="Phiếu in" items={printedChips} value={printedParam || undefined} onChange={(v) => set({ printed: v })} />}
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
                    type="warning" showIcon style={{ marginTop: 12 }}
                    message={<>Có <b>{stats!.unmapped}</b> đơn chưa liên kết SKU — vẫn in & bàn giao bình thường, chỉ KHÔNG trừ tồn cho các dòng chưa ghép. Liên kết để theo dõi tồn kho chính xác.</>}
                    action={<Button size="small" icon={<LinkOutlined />} onClick={() => setLinkModal({ open: true, orderIds: undefined })}>Liên kết hàng loạt</Button>}
                />
            )}

            <Card style={{ marginTop: 12 }} styles={{ body: { padding: 16 } }}>
                {selectedKeys.length > 0 && (canBulkWork ? (
                    <Space style={{ marginBottom: 12 }} wrap>
                        {canShip && tabKey === 'pending' && <Button type="primary" loading={bulkPrepare.isPending} onClick={doBulkPrepare}>
                            Chuẩn bị hàng ({selectedKeys.length}){negProfit.length > 0 && <WarningOutlined style={{ marginInlineStart: 4 }} />}
                        </Button>}
                        {canShip && (isShipTab || isProcessingTab) && selWithoutShipment.length > 0 && <Button type="primary" icon={<FileTextOutlined />} loading={bulkPrepare.isPending} onClick={doBulkPrepareShipTab}>
                            Lấy phiếu giao hàng ({selWithoutShipment.length})
                        </Button>}
                        {canShip && isProcessingTab && selPackable.length > 0 && <Button icon={<CheckCircleOutlined />} style={{ background: '#52c41a', borderColor: '#52c41a', color: '#fff' }} loading={bulkPack.isPending} onClick={doBulkPack}>Sẵn sàng bàn giao ({selPackable.length})</Button>}
                        {canShip && (isProcessingTab || isShipTab) && selWithShipment.length > 0 && <Button icon={<FileTextOutlined />} loading={refetchSlip.isPending} onClick={doRefetchSlip}>Nhận lại phiếu ({selWithShipment.length})</Button>}
                        {canPrint && selWithShipment.length > 0 && <Button icon={<PrinterOutlined />} loading={createPrintJob.isPending} onClick={doBulkPrintSlip}>In phiếu giao hàng ({selWithShipment.length})</Button>}
                        <Button onClick={() => setSelectedKeys([])}>Bỏ chọn</Button>
                    </Space>
                ) : canMap ? (
                    <Space style={{ marginBottom: 12 }}>
                        <Button type="primary" icon={<LinkOutlined />} onClick={() => setLinkModal({ open: true, orderIds: selectedKeys })}>Liên kết SKU ({selectedKeys.length})</Button>
                        <Button onClick={() => setSelectedKeys([])}>Bỏ chọn</Button>
                    </Space>
                ) : null)}
                <Table<Order>
                    rowKey="id" size="middle" loading={isFetching}
                    dataSource={data?.data ?? []} columns={columns}
                    rowSelection={canBulkWork || canMap ? {
                        selectedRowKeys: selectedKeys,
                        onChange: (keys) => setSelectedKeys(keys as number[]),
                        getCheckboxProps: (o) => ({ disabled: canBulkWork ? o.out_of_stock : o.issue_reason !== UNMAPPED_REASON }),
                    } : undefined}
                    locale={{ emptyText: <Empty description={isWorkTab ? 'Không có đơn nào.' : 'Chưa có đơn hàng. Kết nối gian hàng để đơn tự về, hoặc bấm “Đồng bộ đơn”.'} /> }}
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

            {printJobId != null && <PrintJobBar jobId={printJobId} onClose={() => setPrintJobId(null)} />}
            <LinkSkusModal open={linkModal.open} orderIds={linkModal.orderIds} onClose={() => { setLinkModal({ open: false }); setSelectedKeys([]); }} />
            <OrderDetailModal orderId={viewOrderId} open={viewOrderId != null} onClose={() => setViewOrderId(null)} />
            <Modal title="Quét đơn" open={scan.open} onCancel={() => setScan((s) => ({ ...s, open: false }))} footer={null} width={760} destroyOnClose>
                <ScanTab initialMode={scan.mode} />
            </Modal>
        </div>
    );
}
