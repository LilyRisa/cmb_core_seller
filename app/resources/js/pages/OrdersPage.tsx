import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Alert, App as AntApp, Badge, Button, Card, DatePicker, Empty, Input, Modal, Radio, Select, Space, Table, Tabs, Tag, Tooltip, Typography } from 'antd';
import { BarcodeOutlined, CheckCircleOutlined, CloseCircleOutlined, DeleteOutlined, FileTextOutlined, LinkOutlined, PrinterOutlined, ReloadOutlined, ScanOutlined, SearchOutlined, SyncOutlined, WarningOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { HoverPreviewImage } from '@/components/HoverPreviewImage';
import { StatusTag } from '@/components/StatusTag';
import { ChannelBadge } from '@/components/ChannelBadge';
import { ChannelLogo } from '@/components/ChannelLogo';
import { CarrierBadge, carrierDisplayName, parseCarrier } from '@/components/CarrierBadge';
import { MoneyText, DateText } from '@/components/MoneyText';
import { FilterChipRow, type ChipItem } from '@/components/FilterChipRow';
import { LinkSkusModal } from '@/components/LinkSkusModal';
import { OrderDetailModal } from '@/components/OrderDetailModal';
import { PrintCountBadge, PrintJobBar, ScanTab, ShipmentsTab } from '@/components/OrderProcessing';
import { CarrierAccountPicker } from '@/components/CarrierAccountPicker';
import { TemplateAliasPicker } from '@/components/shipping-labels/TemplateAliasPicker';
import { errorMessage } from '@/lib/api';
import { CHANNEL_META, ORDER_STATUS_TABS } from '@/lib/format';
import { withShopeePrintNotice } from '@/lib/shopeePrintNotice';
import { Order, useBulkCancelOrders, useBulkDeleteOrders, useFetchAllOrders, useOrders, useOrderStats, useSyncOrders } from '@/lib/orders';
import { useBulkCreateShipments, useBulkRefetchSlip, useCreatePrintJob, usePackShipments, type BulkActionResult } from '@/lib/fulfillment';
import { useChannelAccounts } from '@/lib/channels';
import { useBulkAction } from '@/lib/useBulkAction';
import { BulkProgressModal } from '@/components/BulkProgressModal';
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
    const fetchAllOrders = useFetchAllOrders();
    const canCreate = useCan('orders.create');
    const canMap = useCan('inventory.map');
    const canShip = useCan('fulfillment.ship');
    const canPrint = useCan('fulfillment.print');
    const canCancel = useCan('orders.update');
    const canDelete = useCan('orders.delete');
    const bulkCancel = useBulkCancelOrders();
    const bulkDelete = useBulkDeleteOrders();
    const [selectedKeys, setSelectedKeys] = useState<number[]>([]);
    // Cache đơn đã "thấy" (trang đang xem + đơn fetch khi "chọn tất cả trang") để bulk action lấy được object
    // (source/shipment/status) cho cả đơn KHÔNG nằm ở trang hiện tại. Reset khi đổi bộ lọc/tab. SPEC 0009.
    const [orderCache, setOrderCache] = useState<Map<number, Order>>(new Map());
    const MAX_SELECT_ALL = 500; // giới hạn mỗi lượt "chọn tất cả trang" (khớp giới hạn bulk của backend)
    const [linkModal, setLinkModal] = useState<{ open: boolean; orderIds?: number[] }>({ open: false });
    const [viewOrderId, setViewOrderId] = useState<number | null>(null);
    // fulfillment: print-job progress bar + scan-to-pack/handover modal (BigSeller-style — thao tác ngay trên list)
    const [printJobId, setPrintJobId] = useState<number | null>(null);
    const bulkProgress = useBulkAction();
    const [scan, setScan] = useState<{ open: boolean }>({ open: false });
    // SPEC 0021 — popup chọn ĐVVC khi "Chuẩn bị hàng" cho đơn manual; lưu các id manual đang chờ confirm.
    const [carrierPicker, setCarrierPicker] = useState<{ open: boolean; orderIds: number[] }>({ open: false, orderIds: [] });
    // Shipping-label designer — bulk print phiếu giao hàng cho đơn manual đi qua picker để chọn template.
    const [bulkLabelPicker, setBulkLabelPicker] = useState<{ open: boolean; orderIds: number[] }>({ open: false, orderIds: [] });

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
    const effectiveStatus = tabKey === 'issue' || tabKey === 'out_of_stock' || tabKey === 'returning' ? '' : (statusParam || (activeTab.statuses ?? []).join(','));
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
        has_return: tabKey === 'returning' ? true : undefined,
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
        has_return: tabKey === 'returning' ? true : undefined,
        out_of_stock: tabKey === 'out_of_stock' ? true : undefined,
        slip: slipFilter,
        printed: printedFilter,
    }), [effectiveStatus, q, skuQ, productQ, source, channelAccountId, carrier, placedFrom, placedTo, tabKey, slipFilter, printedFilter, params]);

    const isShipmentsTab = tabKey === 'shipments';
    const isCancelledTab = tabKey === 'cancelled';   // nút Xoá đơn chỉ ở tab Đã huỷ (đơn khác không xoá được)
    // tab làm việc: "Chờ xử lý" / "Đang xử lý" / "Chờ bàn giao" — cho chọn nhiều đơn + bulk actions
    const isWorkTab = tabKey === 'pending' || tabKey === 'processing' || tabKey === 'ready_to_ship';
    const canBulkWork = !isShipmentsTab && (canShip || canPrint);

    // skip the (unused) orders list when on the shipments tab
    const { data, isFetching, refetch } = useOrders(isShipmentsTab ? { ...filters, page: 1, per_page: 1 } : filters);
    const { data: stats, refetch: refetchStats } = useOrderStats(statsFilters);
    // Badge số đơn của TAB trạng thái phải luôn là *tổng đơn theo status* — KHÔNG ăn theo source / carrier
    // / shop / q / placed_*. Bộ lọc chỉ ảnh hưởng list + chip "Lọc" (`stats` filtered ở trên), không can
    // thiệp tab. Query thứ 2 không truyền filter ⇒ Laravel cache-friendly + 1 round-trip ngắn (chỉ aggregate).
    const { data: tabStats, refetch: refetchTabStats } = useOrderStats({});
    // Sync orders dispatch job chạy nền — poll list + stats để đơn mới về tự render, không cần reload trang.
    const syncPoll = useSyncPolling(() => { refetch(); refetchStats(); refetchTabStats(); }, { durationMs: 90_000 });
    // Theo dõi sync runs đang chạy để hiện thanh tiến trình (refetch 15s/lần qua hook).
    const runningSyncs = useSyncRuns({ status: 'running', per_page: 10 });
    // Bỏ qua run "treo": job chết giữa chừng để lại sync_run ở `running` mãi (không có `finished_at`,
    // stats đứng yên) ⇒ banner "đang đồng bộ" sẽ treo vô hạn. Một sync thật luôn xong trong vài phút
    // (uniqueness lock 900s); coi run bắt đầu quá 1 giờ trước mà vẫn `running` là treo → không tính vào banner.
    const STALE_RUNNING_MS = 60 * 60 * 1000;
    const runningSyncsList = (runningSyncs.data?.data ?? []).filter(
        (r) => !r.started_at || Date.now() - new Date(r.started_at).getTime() < STALE_RUNNING_MS,
    );
    const showSyncBanner = syncPoll.isPolling || runningSyncsList.length > 0;
    // sub-tab "tình trạng phiếu giao hàng" LUÔN hiện ở tab "Đang xử lý" để user lọc theo trạng thái phiếu kể
    // cả khi một nhóm = 0 (trường hợp không cần tải lại phiếu thì vẫn phải thấy bộ lọc). Trước đây gate theo
    // slipTotal>0 nên với đơn sàn chưa có vận đơn (by_slip toàn 0) hàng này biến mất. SPEC 0013.
    const showSlipTabs = isProcessingTab;
    // Chỉ tự bỏ filter `slip` khi RỜI tab "Đang xử lý" (slip không áp ở tab khác). KHÔNG bỏ khi nhóm đang chọn
    // = 0 — nhất quán "hiện tất cả chip kể cả rỗng" (bấm vào nhóm rỗng vẫn ở đó, không bị bật về Tất cả).
    useEffect(() => {
        if (slipParam && !isProcessingTab) {
            const m = new URLSearchParams(params);
            m.delete('slip');
            setParams(m, { replace: true });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isProcessingTab, slipParam]);

    // Badge dùng `tabStats` (không filter) ⇒ luôn hiển thị tổng theo status, click sang tab khác luôn thấy
    // số đơn thật sự ở trạng thái đó dù trước đó user đang lọc source/carrier nào.
    const countForTab = (t: { key: string; statuses?: string[] }) => (
        t.key === 'returning'
            ? (tabStats?.has_return ?? 0)   // tab "Trả/hoàn" đếm theo đơn có trả/hoàn, không theo status (SPEC 0025)
            : (t.statuses ?? []).reduce((s, st) => s + (tabStats?.by_status?.[st] ?? 0), 0)
    );
    const shopName = (id: number) => accounts.find((a) => a.id === id)?.name ?? `#${id}`;

    // Gộp đơn trang hiện tại vào cache (để chọn xuyên trang vẫn giữ object). react-query trả ref ổn định ⇒
    // effect chỉ chạy khi đổi trang/đổi data, không lặp vô hạn.
    useEffect(() => {
        if (!data?.data?.length) return;
        setOrderCache((prev) => { const m = new Map(prev); data.data.forEach((o) => m.set(o.id, o)); return m; });
    }, [data]);
    // Đổi bộ lọc / tab ⇒ selection cũ không còn ý nghĩa ⇒ xoá chọn (KHÔNG xoá khi chỉ đổi trang). Cache giữ
    // nguyên (id đơn là duy nhất; xem lại trang sẽ ghi đè object mới nhất) — selection đã clear nên không lo stale.
    useEffect(() => {
        setSelectedKeys([]);
    }, [effectiveStatus, q, skuQ, productQ, source, channelAccountId, carrier, placedFrom, placedTo, tabKey, slipFilter, printedFilter]);

    // bulk actions: "Chuẩn bị hàng" + "In phiếu giao hàng" (tem của sàn) trên các đơn đã chọn — lấy object từ
    // cache để chọn-tất-cả-trang / chọn xuyên trang vẫn có đủ dữ liệu đơn.
    const selectedOrders = selectedKeys.map((id) => orderCache.get(id)).filter((o): o is Order => Boolean(o));
    // Phân loại đơn theo nguồn để áp đúng luồng (key truth = `source`):
    //   - manual (source='manual') → cần chọn ĐVVC qua CarrierAccountPicker, BE gọi GHN createOrder ngay.
    //   - sàn (source!='manual')    → BE tự gọi `prepareChannelOrder` lấy AWB/tem, KHÔNG cần chọn ĐVVC.
    // SPEC 0021: không cho phép trộn lẫn 2 nhóm trong cùng một lần "Chuẩn bị hàng" vì payload & UX khác nhau.
    const selManual = selectedOrders.filter((o) => o.source === 'manual');
    // Trạng thái đơn còn "chuẩn bị hàng" được (chưa giao cho ĐVVC). Ngoài tập này ⇒ bỏ qua khi bấm Chuẩn bị hàng.
    const PREPARE_OK_STATUSES = ['pending', 'unpaid', 'processing', 'ready_to_ship'];
    // ─── Validate thao tác theo TỪNG đơn đang chọn. Nút thao tác LUÔN hiển thị (thanh cố định dưới phần lọc);
    //     chỉ BẬT khi trong lô chọn có ≥1 đơn hợp lệ. Đơn không hợp lệ trong lô sẽ bị BỎ QUA + ghi lý do ở thanh
    //     tiến trình (không chặn cả lô). SPEC 0009 (bản cải tiến 2026-06).
    const SHIP_PACK_STATUSES = ['pending', 'created'];      // vận đơn có thể đánh dấu "đã gói"
    // Chuẩn bị hàng: chỉ đơn MỚI (tiền-giao) CHƯA có vận đơn & không âm tồn. Đơn đã có phiếu / đang giao / đã
    // giao / hoàn / huỷ ⇒ không chuẩn bị được.
    const eliPrepare = selectedOrders.filter((o) => !o.shipment && PREPARE_OK_STATUSES.includes(o.status) && !o.out_of_stock);
    // Nhận phiếu giao hàng: đơn ĐÃ có vận đơn nhưng CHƯA có phiếu VÀ KHÔNG đang tự tải lại (slip_state==='loading'
    // ⇒ job nền đang kéo, không cần bấm tay) VÀ KHÔNG phải loại đơn sàn không cấp tem (label_unavailable — vd
    // Lazada DBS/SOF; bấm lại cũng vô ích). slip_state thiếu (payload cũ) ⇒ giữ hành vi cũ (chỉ check has_label).
    const eliGetSlip = selectedOrders.filter(
        (o) => o.shipment && !o.shipment.has_label && o.shipment.slip_state !== 'loading' && !o.shipment.label_unavailable,
    );
    // In phiếu giao hàng: MỌI đơn ĐÃ có phiếu (has_label) — kể cả đang giao / đã giao / hoàn / huỷ.
    const eliPrint = selectedOrders.filter((o) => o.shipment?.has_label);
    const eliPack = selectedOrders.filter((o) => o.shipment && SHIP_PACK_STATUSES.includes(o.shipment.status));
    const eliLink = selectedOrders.filter((o) => o.issue_reason === UNMAPPED_REASON);
    // Huỷ hàng loạt (local "ngừng theo dõi"): mọi đơn CHƯA huỷ. Xoá: chỉ đơn ĐÃ huỷ.
    const eliCancel = selectedOrders.filter((o) => o.status !== 'cancelled');
    const eliDelete = selectedOrders.filter((o) => o.status === 'cancelled');
    const negPrepare = eliPrepare.filter((o) => o.profit && o.profit.estimated_profit < 0).length;
    // "Chọn tất cả N đơn (mọi trang)" — fetch hết đơn khớp lọc, đưa vào cache + chọn. Giới hạn MAX_SELECT_ALL/lượt.
    const selectAllPages = () => {
        const hide = message.loading('Đang tải tất cả đơn để chọn…', 0);
        fetchAllOrders.mutate({ filters, max: MAX_SELECT_ALL }, {
            onSuccess: ({ orders, total }) => {
                hide();
                setOrderCache((prev) => { const m = new Map(prev); orders.forEach((o) => m.set(o.id, o)); return m; });
                setSelectedKeys(orders.map((o) => o.id));
                if (total > orders.length) message.warning(`Đã chọn ${orders.length}/${total} đơn (tối đa ${MAX_SELECT_ALL} mỗi lượt). Lọc hẹp hơn để xử lý các đơn còn lại.`);
                else message.success(`Đã chọn ${orders.length} đơn (mọi trang).`);
            },
            onError: (e) => { hide(); message.error(errorMessage(e)); },
        });
    };
    // B7 fix (Sprint 1 P0) — helper chặn trộn manual + sàn cho mọi bulk action liên quan tạo vận đơn.
    // Đơn sàn (`prepareChannelOrder`) dùng AWB & tem của sàn; đơn manual (`createForOrder` qua connector ĐVVC)
    // cần user chọn ĐVVC qua picker. Hai luồng nhập đầu vào khác hẳn → không gộp 1 lượt.
    const assertHomogeneousSource = (manual: Order[], channel: Order[], actionLabel: string): boolean => {
        if (manual.length > 0 && channel.length > 0) {
            Modal.error({
                title: `Không thể "${actionLabel}" lẫn lộn đơn sàn và đơn thủ công`,
                width: 540,
                content: (
                    <div>
                        <p style={{ marginTop: 0 }}>Đơn sàn (TikTok / Shopee / Lazada) dùng tem & mã vận đơn của sàn — hệ thống tự kéo về. Đơn thủ công cần bạn chọn đơn vị vận chuyển trước khi đẩy sang ĐVVC (vd GHN). Hai luồng khác nhau, không thể gộp 1 lượt thao tác.</p>
                        <p>Bạn đang chọn: <b>{channel.length}</b> đơn sàn và <b>{manual.length}</b> đơn thủ công.</p>
                        <p style={{ marginBottom: 0 }}>Hãy bỏ chọn 1 nhóm, thao tác xong rồi quay lại chọn nhóm còn lại.</p>
                    </div>
                ),
                okText: 'Đã hiểu',
            });
            return false;
        }
        return true;
    };
    // "Chuẩn bị hàng (lấy phiếu)" qua popup: đơn đã có vận đơn / không ở trạng thái chuẩn bị ⇒ bỏ qua (ghi rõ);
    // còn lại gọi bulk-create. Đơn manual cần chọn ĐVVC trước (picker) — sau khi chọn mới chạy popup.
    const startPreparePopup = (carrierAccountId: number | null) => {
        const targets = selectedOrders;
        if (targets.length === 0) return;
        setSelectedKeys([]);
        // "Chuẩn bị hàng" tạo vận đơn (đồng bộ — invalidate đã tự refetch) RỒI job nền `FetchChannelLabel` kéo
        // tem/AWB thật của sàn (bất đồng bộ). Poll ngắn để slip_state ("đang lấy phiếu" → "có thể in") tự render
        // mà không cần bấm "Làm mới". "Kéo xong là render lại" — như sync orders.
        void bulkProgress.start({
            title: 'Chuẩn bị hàng (lấy phiếu)',
            items: targets.map((o) => ({ id: o.id, label: String(o.order_number ?? o.external_order_id ?? o.id), sub: CHANNEL_META[o.source]?.name ?? o.source })),
            runner: async (orderIds) => {
                const chunk = targets.filter((o) => orderIds.includes(o.id));
                const skips: BulkActionResult[] = [];
                const actionable: number[] = [];
                for (const o of chunk) {
                    if (o.shipment) skips.push({ id: o.id, status: 'skipped', reason: 'Đã có phiếu giao hàng — bỏ qua.' });
                    else if (!PREPARE_OK_STATUSES.includes(o.status)) skips.push({ id: o.id, status: 'skipped', reason: 'Đơn không ở trạng thái chuẩn bị — bỏ qua.' });
                    else actionable.push(o.id);
                }
                if (actionable.length === 0) return skips;
                const r = await bulkPrepare.mutateAsync({ order_ids: actionable, carrier_account_id: carrierAccountId ?? undefined });
                if (r.created.length > 0) syncPoll.start();   // poll cho tem nền kéo về → slip_state tự render
                const ok: BulkActionResult[] = r.created.map((s) => ({ id: s.order_id, status: 'ok' }));
                const errs: BulkActionResult[] = r.errors.map((e) => ({ id: e.order_id, status: 'error', reason: e.message }));
                return [...skips, ...ok, ...errs];
            },
        });
    };
    const doBulkPrepare = () => {
        const actionable = selectedOrders.filter((o) => !o.shipment && PREPARE_OK_STATUSES.includes(o.status));
        const manual = actionable.filter((o) => o.source === 'manual');
        const channel = actionable.filter((o) => o.source !== 'manual');
        if (!assertHomogeneousSource(manual, channel, 'Chuẩn bị hàng')) return;
        const proceed = () => {
            if (manual.length > 0) setCarrierPicker({ open: true, orderIds: selectedOrders.map((o) => o.id) });
            else startPreparePopup(null);
        };
        const negChannel = channel.filter((o) => o.profit && o.profit.estimated_profit < 0);
        if (negChannel.length > 0) {
            Modal.confirm({
                title: `${negChannel.length} đơn có lợi nhuận ước tính ÂM`,
                content: 'Tổng tiền các đơn này không bù được phí sàn + giá vốn hàng. Vẫn tiếp tục chuẩn bị hàng?',
                okText: 'Vẫn chuẩn bị', okButtonProps: { danger: true }, cancelText: 'Để tôi xem lại',
                onOk: proceed,
            });
        } else { proceed(); }
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
    const doRefetchSlip = () => runRefetchSlip(eliGetSlip.map((o) => o.id));
    // Thao tác đổi trạng thái dựa trên vận đơn (đóng gói / bàn giao). Key popup theo order.id; đơn chưa có vận
    // đơn ⇒ bỏ qua; gọi backend theo shipment.id rồi map kết quả về order.id. Backend tự bỏ qua đơn đã xử lý.
    const runShipmentAction = (title: string, mutate: (shipmentIds: number[]) => Promise<{ results: BulkActionResult[] }>) => {
        const targets = selectedOrders;
        if (targets.length === 0) return;
        setSelectedKeys([]);
        void bulkProgress.start({
            title,
            items: targets.map((o) => ({ id: o.id, label: String(o.order_number ?? o.external_order_id ?? o.id), sub: CHANNEL_META[o.source]?.name ?? o.source })),
            runner: async (orderIds) => {
                const chunk = targets.filter((o) => orderIds.includes(o.id));
                const skips: BulkActionResult[] = [];
                const shToOrder = new Map<number, number>();
                const shipmentIds: number[] = [];
                for (const o of chunk) {
                    if (o.shipment) { shipmentIds.push(o.shipment.id); shToOrder.set(o.shipment.id, o.id); }
                    else skips.push({ id: o.id, status: 'skipped', reason: 'Chưa có phiếu giao hàng — bỏ qua.' });
                }
                const res = shipmentIds.length ? (await mutate(shipmentIds)).results : [];
                const mapped = res.map((r) => ({ ...r, id: shToOrder.get(r.id) ?? r.id }));
                return [...skips, ...mapped];
            },
        });
    };
    const doBulkPack = () => runShipmentAction('Đánh dấu sẵn sàng bàn giao', (ids) => bulkPack.mutateAsync(ids));

    const doBulkCancel = () => {
        const ids = eliCancel.map((o) => o.id);
        if (ids.length === 0) return;
        Modal.confirm({
            title: `Huỷ ${ids.length} đơn?`,
            content: 'Đẩy trạng thái về Đã huỷ TRONG APP và ngừng theo dõi — KHÔNG đẩy thao tác huỷ lên sàn / ĐVVC. Đơn đã huỷ sẽ bị bỏ qua.',
            okText: 'Huỷ đơn', okButtonProps: { danger: true }, cancelText: 'Đóng',
            onOk: async () => {
                try {
                    const r = await bulkCancel.mutateAsync({ ids });
                    message.success(`Đã huỷ ${r.cancelled} đơn${r.skipped > 0 ? `, bỏ qua ${r.skipped}` : ''}.`);
                    setSelectedKeys([]);
                } catch (e) { message.error(errorMessage(e)); }
            },
        });
    };
    const doBulkDelete = () => {
        const ids = eliDelete.map((o) => o.id);
        if (ids.length === 0) return;
        Modal.confirm({
            title: `Xoá ${ids.length} đơn đã huỷ?`,
            content: 'Đơn đã huỷ sẽ bị xoá khỏi danh sách (xoá mềm). Chỉ áp dụng đơn ĐÃ huỷ.',
            okText: 'Xoá đơn', okButtonProps: { danger: true }, cancelText: 'Đóng',
            onOk: async () => {
                try {
                    const r = await bulkDelete.mutateAsync({ ids });
                    message.success(`Đã xoá ${r.deleted} đơn${r.skipped > 0 ? `, bỏ qua ${r.skipped}` : ''}.`);
                    setSelectedKeys([]);
                } catch (e) { message.error(errorMessage(e)); }
            },
        });
    };
    // "In phiếu giao hàng": in cho MỌI đơn ĐÃ có phiếu (has_label) — kể cả đang giao / đã giao / hoàn. Đơn trong
    // lô CHƯA có phiếu ⇒ BỎ QUA (báo nhẹ). CHẶN HẲN nếu lẫn nhiều nền tảng / nhiều ĐVVC (khổ tem khác nhau).
    const doBulkPrintSlip = () => {
        const ready = selectedOrders.filter((o) => o.shipment?.has_label);
        const skipped = selectedOrders.length - ready.length;
        const notifySkipped = () => { if (skipped > 0) message.info(`Đã bỏ qua ${skipped} đơn chưa có phiếu.`); };
        if (ready.length === 0) { message.info('Không có đơn nào đã có phiếu để in — hãy bấm "Nhận phiếu giao hàng" trước.'); return; }
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
        // Đơn manual ⇒ render qua template do shop tự thiết kế (type='delivery' + template_id);
        // đơn sàn ⇒ in tem bundle của sàn (type='label' + shipment_ids).
        const allManual = ready.every((o) => o.source === 'manual');
        const reprinted = ready.filter((o) => (o.shipment!.print_count ?? 0) > 0);
        const runChannelPrint = () => createPrintJob.mutate({ type: 'label', shipment_ids: ready.map((o) => o.shipment!.id) }, {
            onSuccess: (j) => { setPrintJobId(j.id); setSelectedKeys([]); notifySkipped(); },
            onError: (e) => Modal.warning({ title: 'Không in được tem', content: errorMessage(e), okText: 'Đã hiểu' }),
        });
        const openManualPicker = () => { setBulkLabelPicker({ open: true, orderIds: ready.map((o) => o.id) }); notifySkipped(); };
        const proceed = allManual ? openManualPicker : runChannelPrint;
        // Cảnh báo in lại: ≥1 đơn đã được in trước đó (`print_count > 0`) ⇒ tránh in trùng phiếu vận chuyển.
        const proceedWithReprintCheck = () => {
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
                    onOk: proceed,
                });
                return;
            }
            proceed();
        };
        // Đơn Shopee: nhắc bật in nhiệt (khổ tem do cài đặt shop trên Shopee quyết định) trước khi mở tab in.
        withShopeePrintNotice(ready.some((o) => o.source === 'shopee'), proceedWithReprintCheck);
    };

    // chip-row items — kèm logo gian hàng để nhận diện trực quan (logo sàn cho cả "Sàn TMĐT" lẫn "Gian hàng")
    const sourceChips: ChipItem[] = (stats?.by_source ?? []).map((s) => ({ value: s.source, label: CHANNEL_META[s.source]?.name ?? s.source, count: s.count, icon: <ChannelLogo provider={s.source} size={14} /> }));
    const shopChips: ChipItem[] = (stats?.by_shop ?? []).map((s) => {
        const acc = accounts.find((a) => a.id === s.channel_account_id);
        return { value: String(s.channel_account_id), label: shopName(s.channel_account_id), count: s.count, icon: acc ? <ChannelLogo provider={acc.provider} size={14} /> : undefined };
    });
    // SPEC 0021 — chip "Vận chuyển": carrier code có thể là 'ghn' (đơn sàn) hoặc 'manual_ghn' (đơn tự tạo).
    // Hiển thị label phân biệt rõ để vận hành kho biết đơn nào in tem sàn vs in phiếu giao hàng tự tạo.
    const carrierChips: ChipItem[] = (stats?.by_carrier ?? []).map((c) => {
        const parsed = parseCarrier(c.carrier);
        const name = carrierDisplayName(c.carrier);
        const label = parsed.isManual && parsed.base !== 'manual' ? `${name} (Tự tạo)` : name;
        return { value: c.carrier, label, count: c.count };
    });
    // Luôn hiện cả 2 lựa chọn (kể cả 0 đơn) — chip không được biến mất khi đổi bộ lọc.
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
                        {(o.channel_account?.name ?? (o.channel_account_id ? shopName(o.channel_account_id) : null)) && (
                            <Tag style={{ display: 'inline-flex', alignItems: 'center', gap: 4, paddingInline: 6 }}>
                                <ChannelLogo provider={o.channel_account?.provider ?? o.source} size={12} />
                                <span>{o.channel_account?.name ?? shopName(o.channel_account_id!)}</span>
                            </Tag>
                        )}
                        {o.is_cod && <Tag color="gold">COD</Tag>}
                        {o.out_of_stock && <Tooltip title="Tồn kho đang âm — không thể chuẩn bị hàng / tạo vận đơn. Hãy nhập thêm hàng rồi thử lại."><Tag color="error" icon={<WarningOutlined />}>Âm tồn — không thao tác được</Tag></Tooltip>}
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
                    <HoverPreviewImage src={o.thumbnail} size={40} fallback={o.thumbnail ? null : (o.items_count ?? 0)} alt={`Đơn ${o.order_number ?? o.id}`} />
                    <Typography.Text type="secondary">{o.items_count ?? 0} mặt hàng</Typography.Text>
                </Space>
            ),
        },
        { title: 'Người mua', dataIndex: 'buyer_name', key: 'buyer', width: 180, render: (v, o) => <Space direction="vertical" size={0}><span>{v ?? '—'}</span><Typography.Text type="secondary" style={{ fontSize: 12 }}>{o.buyer_phone_masked ?? ''}</Typography.Text></Space> },
        // SPEC 0021 — Badge ĐVVC + nhãn "Chờ lấy hàng" khi shipment.status='awaiting_pickup'.
        { title: 'ĐVVC', dataIndex: 'carrier', key: 'carrier', width: 140, render: (v, o) => (
            <Space direction="vertical" size={2} align="start">
                <CarrierBadge code={v} />
                {o.shipment?.status === 'awaiting_pickup' && (
                    <Tag color="cyan" style={{ marginInlineEnd: 0, fontSize: 11 }}>Chờ lấy hàng</Tag>
                )}
            </Space>
        ) },
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
            // Mọi thao tác fulfillment/in đã dồn lên thanh cố định dưới phần lọc (chọn đơn rồi bấm). Cột này chỉ
            // còn "Xem chi tiết" để xem nhanh từng đơn.
            title: 'Thao tác', key: 'action', width: 110,
            render: (_, o) => <Typography.Link onClick={() => setViewOrderId(o.id)}>Xem chi tiết</Typography.Link>,
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
                        <Button icon={<ScanOutlined />} onClick={() => setScan({ open: true })}>Quét đơn</Button>
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
                    // Đổi tab ⇒ dọn `source` + `carrier` + `channel_account_id` (filter "Lọc" của tab cũ thường
                    // không hợp lý với tab mới — vd lọc Shopee ở "Đang xử lý" rồi sang "Đã giao" sẽ thấy rỗng
                    // dù tab badge bảo có đơn). Giữ `q`/`sku`/`product`/`placed_*` để user search xuyên tab.
                    onChange={(k) => { setSelectedKeys([]); set({ tab: k || undefined, status: undefined, slip: undefined, printed: undefined, source: undefined, channel_account_id: undefined, carrier: undefined, has_issue: k === 'issue' ? '1' : undefined }); }}
                    items={[
                        ...ORDER_STATUS_TABS.map((t) => ({
                            key: t.key,
                            label: <span>{t.label}{t.key !== '' && tabStats ? <Badge count={countForTab(t)} overflowCount={9999} showZero={false} style={{ marginInlineStart: 6, background: '#f0f0f0', color: '#595959' }} /> : null}</span>,
                        })),
                        { key: 'issue', label: <span>Có vấn đề{tabStats?.has_issue ? <Badge count={tabStats.has_issue} style={{ marginInlineStart: 6 }} /> : null}</span> },
                        // Đơn có SKU âm tồn — chặn "Chuẩn bị hàng / lấy phiếu giao hàng" cho đến khi nhập thêm hàng (SPEC 0013).
                        { key: 'out_of_stock', label: <span><WarningOutlined style={{ marginInlineEnd: 4 }} />Hết hàng{tabStats?.out_of_stock ? <Badge count={tabStats.out_of_stock} style={{ marginInlineStart: 6 }} /> : null}</span> },
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

            {/* Thanh thao tác cố định — LUÔN hiển thị ngay dưới phần lọc, áp cho MỌI tab trạng thái. Nút phẳng
                (không màu mè); chỉ BẬT khi lô chọn có ≥1 đơn hợp lệ; đơn không hợp lệ sẽ bị bỏ qua + báo ở tiến trình. */}
            {!isShipmentsTab && (canShip || canPrint || canMap || canCancel || canDelete) && (
                <Card size="small" style={{ marginTop: 12 }} styles={{ body: { padding: '10px 12px' } }}>
                    <Space wrap size={8} style={{ width: '100%' }}>
                        {canShip && (
                            <Tooltip title={selectedKeys.length === 0 ? 'Chọn đơn để thao tác' : 'Chỉ áp dụng cho đơn MỚI chưa có phiếu (Chờ thanh toán / Chờ xử lý / Đang xử lý / Chờ bàn giao). Đơn đã có phiếu, đang giao, đã giao, hoàn, huỷ sẽ bị bỏ qua.'}>
                                <span><Button icon={<FileTextOutlined />} disabled={eliPrepare.length === 0} loading={bulkProgress.running} onClick={doBulkPrepare}>
                                    Chuẩn bị hàng{eliPrepare.length > 0 ? ` (${eliPrepare.length})` : ''}{negPrepare > 0 && <WarningOutlined style={{ marginInlineStart: 4, color: '#faad14' }} />}
                                </Button></span>
                            </Tooltip>
                        )}
                        {canShip && (
                            <Tooltip title={selectedKeys.length === 0 ? 'Chọn đơn để thao tác' : 'Kéo lại tem/phiếu của sàn cho đơn đã có vận đơn nhưng CHƯA có phiếu (lần trước lấy lỗi).'}>
                                <span><Button icon={<ReloadOutlined />} disabled={eliGetSlip.length === 0} loading={refetchSlip.isPending} onClick={doRefetchSlip}>
                                    Nhận phiếu giao hàng{eliGetSlip.length > 0 ? ` (${eliGetSlip.length})` : ''}
                                </Button></span>
                            </Tooltip>
                        )}
                        {canPrint && (
                            <Tooltip title={selectedKeys.length === 0 ? 'Chọn đơn để thao tác' : 'In phiếu/tem cho MỌI đơn ĐÃ có phiếu — kể cả đang giao, đã giao, hoàn. Không in chung nhiều sàn / ĐVVC.'}>
                                <span><Button icon={<PrinterOutlined />} disabled={eliPrint.length === 0} loading={createPrintJob.isPending} onClick={doBulkPrintSlip}>
                                    In phiếu giao hàng{eliPrint.length > 0 ? ` (${eliPrint.length})` : ''}
                                </Button></span>
                            </Tooltip>
                        )}
                        {canShip && (
                            <Tooltip title={selectedKeys.length === 0 ? 'Chọn đơn để thao tác' : 'Đánh dấu đã đóng gói cho đơn đã có vận đơn (chuyển sang chờ bàn giao ĐVVC).'}>
                                <span><Button icon={<CheckCircleOutlined />} disabled={eliPack.length === 0} loading={bulkProgress.running} onClick={doBulkPack}>
                                    Sẵn sàng bàn giao{eliPack.length > 0 ? ` (${eliPack.length})` : ''}
                                </Button></span>
                            </Tooltip>
                        )}
                        {canMap && (
                            <Tooltip title={selectedKeys.length === 0 ? 'Chọn đơn để thao tác' : 'Liên kết SKU cho các đơn chưa ghép trong lô chọn.'}>
                                <span><Button icon={<LinkOutlined />} disabled={eliLink.length === 0} onClick={() => setLinkModal({ open: true, orderIds: eliLink.map((o) => o.id) })}>
                                    Liên kết SKU{eliLink.length > 0 ? ` (${eliLink.length})` : ''}
                                </Button></span>
                            </Tooltip>
                        )}
                        {canCancel && (
                            <Tooltip title={selectedKeys.length === 0 ? 'Chọn đơn để thao tác' : 'Huỷ đơn (đẩy về Đã huỷ) — CHỈ trong app, không đẩy thao tác huỷ lên sàn/ĐVVC. Đơn đã huỷ sẽ bị bỏ qua.'}>
                                <span><Button danger icon={<CloseCircleOutlined />} disabled={eliCancel.length === 0} loading={bulkCancel.isPending} onClick={doBulkCancel}>
                                    Huỷ đơn{eliCancel.length > 0 ? ` (${eliCancel.length})` : ''}
                                </Button></span>
                            </Tooltip>
                        )}
                        {canDelete && isCancelledTab && (
                            <Tooltip title={selectedKeys.length === 0 ? 'Chọn đơn để thao tác' : 'Xoá đơn đã huỷ khỏi danh sách (xoá mềm).'}>
                                <span><Button danger icon={<DeleteOutlined />} disabled={eliDelete.length === 0} loading={bulkDelete.isPending} onClick={doBulkDelete}>
                                    Xoá đơn{eliDelete.length > 0 ? ` (${eliDelete.length})` : ''}
                                </Button></span>
                            </Tooltip>
                        )}
                        <span style={{ marginInlineStart: 'auto', display: 'inline-flex', alignItems: 'center' }}>
                            <Typography.Text type="secondary">{selectedKeys.length > 0 ? `Đã chọn ${selectedKeys.length} đơn` : 'Chưa chọn đơn'}</Typography.Text>
                            {selectedKeys.length > 0 && <Button type="link" size="small" onClick={() => setSelectedKeys([])}>Bỏ chọn</Button>}
                        </span>
                    </Space>
                </Card>
            )}

            {canMap && (stats?.unmapped ?? 0) > 0 && (
                <Alert
                    type="warning" showIcon style={{ marginTop: 12 }}
                    message={<>Có <b>{stats!.unmapped}</b> đơn chưa liên kết SKU — vẫn in & bàn giao bình thường, chỉ KHÔNG trừ tồn cho các dòng chưa ghép. Liên kết để theo dõi tồn kho chính xác.</>}
                    action={<Button size="small" icon={<LinkOutlined />} onClick={() => setLinkModal({ open: true, orderIds: undefined })}>Liên kết hàng loạt</Button>}
                />
            )}

            <Card style={{ marginTop: 12 }} styles={{ body: { padding: 16 } }}>
                <Table<Order>
                    rowKey="id" size="middle" loading={isFetching}
                    dataSource={data?.data ?? []} columns={columns}
                    rowSelection={canBulkWork || canMap || canCancel || canDelete ? {
                        selectedRowKeys: selectedKeys,
                        preserveSelectedRowKeys: true, // giữ chọn xuyên trang (server phân trang) — bulk dùng orderCache
                        onChange: (keys) => setSelectedKeys(keys as number[]),
                        selections: [
                            { key: 'page', text: 'Chọn trang hiện tại', onSelect: () => setSelectedKeys((data?.data ?? []).map((o) => o.id)) },
                            ...((data?.meta.pagination.total_pages ?? 1) > 1 ? [{
                                key: 'all-pages',
                                text: (data!.meta.pagination.total > MAX_SELECT_ALL)
                                    ? `Chọn ${MAX_SELECT_ALL} đơn đầu (mọi trang)`
                                    : `Chọn tất cả ${data!.meta.pagination.total} đơn (mọi trang)`,
                                onSelect: selectAllPages,
                            }] : []),
                            Table.SELECTION_NONE,
                        ],
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

            <CarrierAccountPicker
                open={carrierPicker.open}
                count={carrierPicker.orderIds.length}
                loading={bulkPrepare.isPending}
                preferredAccountId={(() => {
                    // U11 (Sprint 1 P0) — nếu tất cả đơn manual đang chọn cùng preferred carrier ⇒ pre-select đó.
                    const ids = selManual
                        .filter((o) => carrierPicker.orderIds.includes(o.id))
                        .map((o) => o.meta && typeof (o.meta as Record<string, unknown>).preferred_carrier_account_id === 'number' ? (o.meta as Record<string, unknown>).preferred_carrier_account_id as number : null);
                    const distinct = Array.from(new Set(ids.filter((v) => v != null) as number[]));
                    return distinct.length === 1 ? distinct[0] : null;
                })()}
                onCancel={() => setCarrierPicker({ open: false, orderIds: [] })}
                onConfirm={(cid) => { setCarrierPicker({ open: false, orderIds: [] }); startPreparePopup(cid); }}
            />
            <TemplateAliasPicker
                open={bulkLabelPicker.open}
                onCancel={() => setBulkLabelPicker({ open: false, orderIds: [] })}
                onConfirm={(templateId) => {
                    const ids = bulkLabelPicker.orderIds;
                    setBulkLabelPicker({ open: false, orderIds: [] });
                    createPrintJob.mutate({ type: 'delivery', order_ids: ids, template_id: templateId }, {
                        onSuccess: (j) => { setPrintJobId(j.id); setSelectedKeys([]); },
                        onError: (e) => Modal.warning({ title: 'Không in được phiếu', content: errorMessage(e), okText: 'Đã hiểu' }),
                    });
                }}
            />
            {printJobId != null && <PrintJobBar jobId={printJobId} onClose={() => setPrintJobId(null)} />}
            <BulkProgressModal title={bulkProgress.title} open={bulkProgress.open} items={bulkProgress.items} running={bulkProgress.running} onRetry={bulkProgress.retryErrors} onClose={bulkProgress.close} />
            <LinkSkusModal open={linkModal.open} orderIds={linkModal.orderIds} onClose={() => { setLinkModal({ open: false }); setSelectedKeys([]); }} />
            <OrderDetailModal orderId={viewOrderId} open={viewOrderId != null} onClose={() => setViewOrderId(null)} />
            <Modal title="Quét đơn" open={scan.open} onCancel={() => setScan({ open: false })} footer={null} width={760} destroyOnClose>
                <ScanTab />
            </Modal>
        </div>
    );
}
