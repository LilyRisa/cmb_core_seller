import { useCallback, useEffect, useRef, useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { App as AntApp, Button, Empty, Input, InputNumber, Modal, Result, Select, Space, Spin, Switch, Table, Tag, Timeline, Tooltip, Typography } from 'antd';
import type { InputRef } from 'antd';
import { ApiOutlined, CheckCircleOutlined, CloseCircleOutlined, DisconnectOutlined, ExportOutlined, InboxOutlined, PrinterOutlined, ScanOutlined, SoundOutlined, WarningOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { ChannelBadge } from '@/components/ChannelBadge';
import { CarrierAccountPicker } from '@/components/CarrierAccountPicker';
import { TemplateAliasPicker } from '@/components/shipping-labels/TemplateAliasPicker';
import { BulkProgressModal } from '@/components/BulkProgressModal';
import { useBulkAction } from '@/lib/useBulkAction';
import { MoneyText, DateText } from '@/components/MoneyText';
import { errorMessage } from '@/lib/api';
import { formatDate } from '@/lib/format';
import { withShopeePrintNotice } from '@/lib/shopeePrintNotice';
import { useCan } from '@/lib/tenant';
import type { Order } from '@/lib/orders';
import {
    type PrintJob, type Shipment, SHIPMENT_STATUS_LABEL,
    useBulkRefetchSlip, useCancelShipment, useCreatePrintJob, useMarkPrinted,
    usePackShipments, usePrintJob, useScanProcess, useShipment, useShipments, useShipOrder, useTrackShipment,
} from '@/lib/fulfillment';
import { playScanBeep, useSerialScanner } from '@/lib/scanner';

function ShipmentStatusTag({ status }: { status: string }) {
    const color = { delivered: 'green', cancelled: 'default', failed: 'red', returned: 'orange', picked_up: 'geekblue', packed: 'blue', awaiting_pickup: 'cyan', in_transit: 'cyan', created: 'gold', pending: 'default' }[status] ?? 'default';
    return <Tag color={color}>{SHIPMENT_STATUS_LABEL[status] ?? status}</Tag>;
}

/** "Đơn đã in" chip — hiện ở danh sách đơn để biết đơn đã in & in bao nhiêu lần (SPEC 0013 — domain doc §1). */
export function PrintCountBadge({ n, at }: { n: number; at?: string | null }) {
    if (!n) return null;
    return <Tooltip title={`Đã in ${n} lần${at ? ' · gần nhất ' + formatDate(at) : ''}`}><Tag icon={<PrinterOutlined />} color={n > 1 ? 'orange' : 'green'} style={{ marginInlineEnd: 0 }}>{n}×</Tag></Tooltip>;
}

const PRINT_TYPE_LABEL: Record<PrintJob['type'], string> = { label: 'tem sàn', delivery: 'phiếu giao hàng', packing: 'phiếu đóng gói', picking: 'phiếu soạn hàng', invoice: 'hoá đơn' };

function jobOrderCount(job: PrintJob): number {
    const m = (job.meta ?? {}) as { order_ids?: number[]; shipment_ids?: number[]; count?: number };
    return m.order_ids?.length ?? job.scope?.order_ids?.length ?? m.shipment_ids?.length ?? job.scope?.shipment_ids?.length ?? m.count ?? 0;
}

/**
 * In phiếu: theo dõi print-job; khi xong ⇒ mở popup → bấm "Mở để in" (mở tab PDF mới) → popup "đánh dấu các đơn
 * đã in" + số bản in (cộng print_count cho vận đơn). Vẫn theo rule "không in chung nhiều nền tảng/ĐVVC" (BE chặn).
 * SPEC 0013 (mục 3).
 */
export function PrintJobBar({ jobId, onClose }: { jobId: number; onClose: () => void }) {
    const { message } = AntApp.useApp();
    const { data: job } = usePrintJob(jobId);
    const markPrinted = useMarkPrinted();
    const [step, setStep] = useState<'open' | 'mark'>('open');
    const [copies, setCopies] = useState(1);
    const shownErr = useRef(false);

    useEffect(() => { setStep('open'); setCopies(1); shownErr.current = false; }, [jobId]);
    useEffect(() => {
        if (job?.status === 'error' && !shownErr.current) { shownErr.current = true; message.error(`Tạo phiếu in lỗi: ${job.error ?? ''}`); onClose(); }
    }, [job, message, onClose]);

    if (!job || job.status === 'error') return null;
    const typeLabel = PRINT_TYPE_LABEL[job.type] ?? job.type;
    const skipped = Array.isArray((job.meta as Record<string, unknown>)?.skipped) ? ((job.meta as Record<string, number[]>).skipped ?? []) : [];
    const canMark = job.type === 'label' || job.type === 'delivery';
    const n = jobOrderCount(job);
    const done = job.status === 'done' && !!job.file_url;

    const finish = () => { setStep('open'); onClose(); };
    const openPdf = () => { window.open(job.file_url ?? '#', '_blank', 'noopener'); if (canMark && n > 0) setStep('mark'); else finish(); };
    const doMark = () => markPrinted.mutate({ jobId, copies }, {
        onSuccess: (r) => { message.success(`Đã đánh dấu ${r.shipment_ids.length} đơn đã in${r.copies > 1 ? ` × ${r.copies} bản` : ''}`); finish(); },
        onError: (e) => message.error(errorMessage(e)),
    });

    return (
        <Modal open width={460} onCancel={finish} maskClosable={false}
            title={done ? (step === 'open' ? 'Phiếu in đã sẵn sàng' : 'Đánh dấu các đơn đã in') : 'Đang tạo phiếu in…'}
            footer={!done ? null : step === 'open' ? [
                <Button key="c" onClick={finish}>Đóng</Button>,
                <Button key="o" type="primary" icon={<ExportOutlined />} onClick={openPdf}>Mở để in</Button>,
            ] : [
                <Button key="s" onClick={finish}>Bỏ qua</Button>,
                <Button key="m" type="primary" loading={markPrinted.isPending} onClick={doMark}>Đánh dấu đã in</Button>,
            ]}>
            {!done ? (
                <Space><Spin /> <span>Đang tạo {typeLabel}…</span></Space>
            ) : step === 'open' ? (
                <Result status="success" style={{ padding: '8px 0' }}
                    title={`${typeLabel} đã sẵn sàng${n ? ` (${n} đơn)` : ''}`}
                    subTitle={<>Bấm <b>“Mở để in”</b> để mở tệp PDF ở tab mới rồi in.{skipped.length ? <><br />{skipped.length} đơn không có tem/phiếu — đã bỏ qua.</> : null}</>}
                    extra={<a href={job.file_url ?? '#'} target="_blank" rel="noreferrer"><Button size="small">Tải xuống</Button></a>} />
            ) : (
                <Space direction="vertical" size={12} style={{ width: '100%' }}>
                    <Typography.Text>Đánh dấu <b>{n}</b> đơn trong tệp này là <b>đã in</b>?</Typography.Text>
                    <Space>Số bản in mỗi đơn: <InputNumber min={1} max={50} value={copies} onChange={(v) => setCopies(Number(v) || 1)} /></Space>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>Đơn đã in sẽ hiện biểu tượng phiếu in <PrinterOutlined /> kèm số lần in trong danh sách.</Typography.Text>
                </Space>
            )}
        </Modal>
    );
}

/**
 * Inline fulfillment actions for one order row in the Orders list (BigSeller-style — không tách màn
 * "xử lý đơn" riêng; thao tác ngay ở các tab trạng thái Chờ xử lý / Đang xử lý / Chờ bàn giao).
 * Hiện nút theo trạng thái đơn + vận đơn; trạng thái đơn vẫn đồng bộ theo trạng thái gốc (canonical).
 */
export function OrderActions({ order, onPrint }: { order: Order; onPrint: (jobId: number) => void }) {
    const { message } = AntApp.useApp();
    const ship = useShipOrder();
    const pack = usePackShipments();
    const createPrint = useCreatePrintJob();
    const refetchSlip = useBulkRefetchSlip();
    const canShip = useCan('fulfillment.ship');
    const canPrint = useCan('fulfillment.print');
    const sh = order.shipment;
    const busy = ship.isPending || pack.isPending || createPrint.isPending || refetchSlip.isPending;
    // SPEC 0021 — đơn manual cần chọn ĐVVC trước khi "Chuẩn bị hàng" (đẩy sang GHN/...). Đơn sàn KHÔNG cần.
    const [carrierPicker, setCarrierPicker] = useState(false);
    const [pickerOpen, setPickerOpen] = useState<{ open: boolean; orderIds: number[] }>({ open: false, orderIds: [] });
    const isManual = order.source === 'manual' || !order.channel_account_id;
    if (busy) return <Spin size="small" />;

    const err = (e: unknown) => message.error(errorMessage(e));
    // "Nhận phiếu giao hàng" — hệ thống tự kéo/tạo phiếu; trả print_job_id để hiện thanh tiến trình + nút "Mở để in".
    const getSlip = (then?: () => void) => refetchSlip.mutate([order.id], {
        onSuccess: (r) => {
            if (r.print_job_id) onPrint(r.print_job_id);
            else if (r.ok) message.success('Phiếu giao hàng đã sẵn sàng — bấm "In phiếu giao hàng".');
            else if (r.errors.length) message.error(r.errors[0].message);
            then?.();
        },
        onError: err,
    });
    const runPrintLabel = () => createPrint.mutate({ type: 'label', shipment_ids: [sh!.id] }, { onSuccess: (j) => onPrint(j.id), onError: err });
    // Cảnh báo in lại: vận đơn đã in ≥1 lần ⇒ confirm để tránh tạo trùng phiếu vận chuyển.
    const printLabelBundle = () => withShopeePrintNotice(order.source === 'shopee', () => {
        if (sh && (sh.print_count ?? 0) > 0) {
            const code = order.order_number ?? order.external_order_id ?? `#${order.id}`;
            Modal.confirm({
                title: 'Đơn này đã từng in phiếu',
                content: <span>Đơn <b>{code}</b> đã in <b>{sh.print_count}</b> lần. In lại có thể tạo trùng phiếu vận chuyển — vẫn tiếp tục?</span>,
                okText: 'Vẫn in', okButtonProps: { danger: true }, cancelText: 'Huỷ',
                onOk: runPrintLabel,
            });
            return;
        }
        runPrintLabel();
    });
    // "In phiếu giao hàng":
    //   - Đơn manual ⇒ LUÔN mở TemplateAliasPicker (cho phép chọn template tự thiết kế hoặc fallback hệ thống).
    //     SPEC 0021 auto-render slip mặc định nên has_label=true; cần override bằng template do shop tạo.
    //   - Đơn sàn (TikTok/Shopee/…) ⇒ đã có label gốc ⇒ in tem bundle; chưa có ⇒ rơi vào picker để render fallback.
    const printDelivery = () => {
        if (isManual) {
            const open = () => setPickerOpen({ open: true, orderIds: [order.id] });
            if (sh && (sh.print_count ?? 0) > 0) {
                const code = order.order_number ?? order.external_order_id ?? `#${order.id}`;
                Modal.confirm({
                    title: 'Đơn này đã từng in phiếu',
                    content: <span>Đơn <b>{code}</b> đã in <b>{sh.print_count}</b> lần. In lại có thể tạo trùng phiếu vận chuyển — vẫn tiếp tục?</span>,
                    okText: 'Vẫn in', okButtonProps: { danger: true }, cancelText: 'Huỷ',
                    onOk: open,
                });
                return;
            }
            open();
            return;
        }
        if (sh && sh.has_label) { printLabelBundle(); return; }
        setPickerOpen({ open: true, orderIds: [order.id] });
    };
    const printInvoice = () => createPrint.mutate({ type: 'invoice', order_ids: [order.id] }, { onSuccess: (j) => onPrint(j.id), onError: err });
    // "Chuẩn bị hàng": đơn sàn ⇒ hệ thống tự lấy mã vận đơn + phiếu giao hàng của sàn. Đơn manual ⇒ FE mở
    // CarrierAccountPicker để user chọn ĐVVC (GHN / GHTK / manual / ...), sau đó BE gọi connector.createShipment
    // với `carrier_account_id` đã chọn. SPEC 0021.
    const runPrepare = (carrierAccountId?: number | null) => ship.mutate(
        { orderId: order.id, ...(carrierAccountId != null ? { carrier_account_id: carrierAccountId } : {}) },
        { onSuccess: () => { message.success('Đã chuẩn bị hàng — đang lấy phiếu giao hàng. Đơn chuyển sang "Đang xử lý".'); setCarrierPicker(false); }, onError: err },
    );
    const prepare = () => {
        const proceed = () => {
            if (isManual) setCarrierPicker(true);
            else runPrepare();
        };
        if (order.profit && order.profit.estimated_profit < 0) {
            Modal.confirm({ title: 'Đơn này lợi nhuận ước tính ÂM', content: `Lợi nhuận ước tính: ${order.profit.estimated_profit.toLocaleString('vi-VN')} ₫ (tổng tiền không bù được phí sàn + giá vốn). Vẫn chuẩn bị hàng?`, okText: 'Vẫn chuẩn bị', okButtonProps: { danger: true }, cancelText: 'Để tôi xem lại', onOk: proceed });
        } else { proceed(); }
    };
    // "Đã gói & sẵn sàng bàn giao" — bảo đảm có vận đơn rồi markPacked (processing → ready_to_ship).
    const markReady = () => (sh
        ? pack.mutate([sh.id], { onSuccess: () => message.success('Đã đánh dấu gói xong — chờ bàn giao ĐVVC'), onError: err })
        : ship.mutate({ orderId: order.id }, { onSuccess: (s) => pack.mutate([s.id], { onSuccess: () => message.success('Đã đánh dấu gói xong — chờ bàn giao ĐVVC'), onError: err }), onError: err }));
    const isWaiting = ['pending', 'unpaid'].includes(order.status);   // tab "Chờ xử lý"
    const preShipment = !['shipped', 'delivery_failed', 'delivered', 'completed', 'returning', 'returned_refunded', 'cancelled'].includes(order.status);
    const shOpen = sh && !['cancelled', 'returned', 'failed'].includes(sh.status);
    // "SKU chưa ghép" KHÔNG còn chặn fulfillment — OrderInventoryService tự skip items chưa ghép (không
    // đụng ledger / tồn kho), in phiếu & bàn giao vẫn bình thường. Các issue khác (lỗi sàn / sai địa chỉ) vẫn
    // gắn cờ has_issue để user xem; nhưng nếu chỉ là "SKU chưa ghép" thì coi như không có vấn đề về luồng.
    const onlyUnmappedIssue = order.has_issue && order.issue_reason === 'SKU chưa ghép';
    const blockingIssue = order.has_issue && !onlyUnmappedIssue;
    const actions: ReactNode[] = [];
    if (preShipment && !shOpen && canShip) {
        if (order.out_of_stock) {
            actions.push(<Tooltip key="oos" title="Đơn có SKU âm tồn — không thể chuẩn bị hàng / lấy phiếu giao hàng. Hãy nhập thêm hàng."><Typography.Text type="secondary"><WarningOutlined /> Hết hàng</Typography.Text></Tooltip>);
        } else if (isWaiting && !blockingIssue) {
            // "Chờ xử lý" ⇒ "Chuẩn bị hàng" (đẩy trạng thái lên sàn + lấy mã vận đơn / phiếu).
            actions.push(<a key="prep" onClick={prepare}>Chuẩn bị hàng (lấy phiếu)</a>);
        } else if (!isWaiting) {
            // "Đang xử lý" (kể cả khi có vận đơn đã huỷ) ⇒ lấy / thử lại phiếu giao hàng.
            actions.push(<a key="prep2" style={{ color: blockingIssue ? '#cf1322' : undefined }} onClick={() => runPrepare()}>Lấy phiếu giao hàng</a>);
        }
    } else if (shOpen && ['pending', 'created'].includes(sh!.status)) {
        // Đã chuẩn bị / có vận đơn, chờ đóng gói + quét nội bộ.
        // Tình trạng phiếu giao hàng (per-đơn):
        //   - label_unavailable (vd Lazada DBS/SOF) ⇒ sàn không cấp tem, retry vô ích ⇒ chỉ báo, KHÔNG nút.
        //   - slip_state==='loading' ⇒ job nền đang tự kéo ⇒ hiện spinner; VẪN cho "Nhận phiếu ngay" để user
        //     chủ động retry thay vì chờ tới ~15' (tránh cảm giác treo không thao tác được). getSlip idempotent.
        //   - còn lại (failed / chưa có tem / có issue chặn) ⇒ cho bấm "Nhận phiếu giao hàng".
        if (sh!.label_unavailable) {
            actions.push(<Tooltip key="lblna" title="Sàn không cấp tem/AWB cho loại đơn này (vd DBS/SOF) — xử lý theo luồng giao của sàn."><Typography.Text type="warning"><WarningOutlined /> Sàn không cấp tem</Typography.Text></Tooltip>);
        } else if (sh!.slip_state === 'loading') {
            actions.push(<Typography.Text key="lblld" type="secondary"><Spin size="small" /> Đang lấy phiếu…</Typography.Text>);
            if (canShip) actions.push(<a key="rsnow" onClick={() => getSlip()}>Nhận phiếu ngay</a>);
        } else if (canShip && (blockingIssue || !sh!.has_label)) {
            actions.push(<a key="rs" style={{ color: blockingIssue ? '#cf1322' : undefined }} onClick={() => getSlip()}>Nhận phiếu giao hàng</a>);
        }
        if (canShip) actions.push(<a key="ready" onClick={markReady}>Đã gói & sẵn sàng bàn giao</a>);
    }
    // Vận đơn `packed` = "chờ bàn giao ĐVVC": KHÔNG còn thao tác tay. Đơn tự chuyển sang
    // "đang giao" khi trạng thái thực tế đồng bộ về (sàn / carrier tracking khi ĐVVC lấy hàng).
    // In lại phiếu/tem KHÔNG phụ thuộc trạng thái vận đơn (in lại được kể cả khi đã giao/huỷ/đang vận chuyển),
    // miễn là có vận đơn (phiếu giao hàng) / có tem sàn (label_url). Giữ cảnh báo in lại khi print_count > 0.
    if (canPrint && sh) actions.push(<a key="ds" onClick={printDelivery}>In phiếu giao hàng</a>);
    if (canPrint && sh?.label_url) actions.push(<a key="lbl" onClick={printLabelBundle}>In tem sàn</a>);
    // "In hoá đơn" CHỈ áp dụng cho đơn manual (tự nhập). Đơn sàn (TikTok/Shopee/Lazada) đã có hoá đơn
    // điện tử / receipt do sàn cấp cho người mua — app tự sinh hoá đơn nội bộ sẽ trùng lặp & gây nhầm
    // lẫn. Đơn sàn chỉ cần "In phiếu giao hàng" + "In tem sàn".
    if (canPrint && !order.channel_account_id) actions.push(<a key="inv" onClick={printInvoice}>In hoá đơn</a>);
    return (
        <>
            <Space size={8} wrap>{actions}</Space>
            <CarrierAccountPicker
                open={carrierPicker}
                count={1}
                loading={ship.isPending}
                preferredAccountId={typeof order.meta?.preferred_carrier_account_id === 'number' ? order.meta.preferred_carrier_account_id : null}
                onCancel={() => setCarrierPicker(false)}
                onConfirm={(cid) => runPrepare(cid)}
            />
            <TemplateAliasPicker
                open={pickerOpen.open}
                onCancel={() => setPickerOpen({ open: false, orderIds: [] })}
                onConfirm={(templateId) => {
                    const ids = pickerOpen.orderIds;
                    setPickerOpen({ open: false, orderIds: [] });
                    createPrint.mutate({ type: 'delivery', order_ids: ids, template_id: templateId },
                        { onSuccess: (j) => onPrint(j.id), onError: err });
                }}
            />
        </>
    );
}

// ---- "Quét" tab (chỉ đóng gói) ----------------------------------------------

/** Ngưỡng (ms) giữa 2 phím để coi là "tốc độ máy quét" — người gõ tay luôn chậm hơn. */
const SCAN_KEY_GAP_MS = 35;
/** Im lặng bao lâu (ms) sau chuỗi gõ nhanh thì tự gửi (cho máy quét không phát Enter). */
const SCAN_IDLE_SUBMIT_MS = 120;
/** Mã ngắn hơn mức này không tự gửi (tránh nhiễu). */
const SCAN_MIN_LEN = 4;

export function ScanTab() {
    const scan = useScanProcess();
    const [code, setCode] = useState('');
    const [log, setLog] = useState<Array<{ ok: boolean; text: string; at: string }>>([]);
    const [flash, setFlash] = useState<'ok' | 'err' | null>(null);
    const [sound, setSound] = useState(true);
    const inputRef = useRef<InputRef>(null);

    // Bám focus liên tục để không "rớt" ký tự máy quét bắn ra.
    const focusSoon = useCallback(() => { window.setTimeout(() => inputRef.current?.focus(), 30); }, []);
    useEffect(() => { inputRef.current?.focus(); }, []);

    // Refs cho mọi callback ổn định (serial / timer không phụ thuộc render).
    const soundRef = useRef(sound); soundRef.current = sound;
    const flashTimer = useRef<number>();

    const runScan = useCallback((raw: string) => {
        const c = raw.trim();
        if (!c) return;
        setCode('');
        scan.mutate(c, {
            onSuccess: (r) => {
                if (soundRef.current) playScanBeep(true);
                setFlash('ok');
                setLog((l) => [{ ok: true, text: `${r.message}: ${r.order?.order_number ?? '#' + (r.order?.id ?? '')} · ${r.shipment.tracking_no ?? '#' + r.shipment.id} → ${SHIPMENT_STATUS_LABEL[r.shipment.status] ?? r.shipment.status}`, at: new Date().toLocaleTimeString() }, ...l].slice(0, 60));
            },
            onError: (e) => {
                if (soundRef.current) playScanBeep(false);
                setFlash('err');
                setLog((l) => [{ ok: false, text: `${c}: ${errorMessage(e)}`, at: new Date().toLocaleTimeString() }, ...l].slice(0, 60));
            },
            onSettled: () => {
                inputRef.current?.focus();
                window.clearTimeout(flashTimer.current);
                flashTimer.current = window.setTimeout(() => setFlash(null), 450);
            },
        });
    }, [scan]);

    // Kết nối máy quét qua Web Serial (đọc được cả khi ô nhập không focus).
    const serial = useSerialScanner(runScan);

    // Tự gửi khi phát hiện chuỗi gõ tốc độ máy quét rồi ngừng (kể cả máy không bắn Enter).
    const lastKeyAt = useRef(0);
    const fastBurst = useRef(true);
    const liveCode = useRef('');
    const autoTimer = useRef<number>();

    const onChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const v = e.target.value;
        const now = performance.now();
        const gap = now - lastKeyAt.current;
        lastKeyAt.current = now;
        if (liveCode.current === '') fastBurst.current = true;      // bắt đầu chuỗi mới
        else if (gap > SCAN_KEY_GAP_MS) fastBurst.current = false;  // có nhịp gõ chậm → coi là gõ tay
        liveCode.current = v;
        setCode(v);

        window.clearTimeout(autoTimer.current);
        autoTimer.current = window.setTimeout(() => {
            if (fastBurst.current && liveCode.current === v && v.trim().length >= SCAN_MIN_LEN) {
                liveCode.current = '';
                runScan(v);
            }
        }, SCAN_IDLE_SUBMIT_MS);
    };

    const onSubmit = () => { window.clearTimeout(autoTimer.current); liveCode.current = ''; runScan(code); };

    const serialBtn = (() => {
        if (!serial.supported) {
            return <Tooltip title="Trình duyệt không hỗ trợ Web Serial — dùng Chrome/Edge trên máy tính. Máy quét giả bàn phím vẫn quét bình thường."><Button icon={<ApiOutlined />} disabled>Kết nối máy quét</Button></Tooltip>;
        }
        if (serial.status === 'connected') {
            return <Button icon={<DisconnectOutlined />} danger onClick={() => void serial.disconnect()}>Ngắt máy quét</Button>;
        }
        return <Button icon={<ApiOutlined />} loading={serial.status === 'connecting'} onClick={() => void serial.connect()}>Kết nối máy quét</Button>;
    })();

    const flashStyle: React.CSSProperties = flash === 'ok'
        ? { boxShadow: '0 0 0 3px #b7eb8f', borderRadius: 8 }
        : flash === 'err' ? { boxShadow: '0 0 0 3px #ffa39e', borderRadius: 8 } : {};

    return (
        <div style={{ maxWidth: 760 }}>
            <Typography.Paragraph type="secondary">
                Quét (hoặc gõ) mã vận đơn / mã đơn rồi Enter để đánh dấu ĐÃ ĐÓNG GÓI (đơn → "chờ bàn giao"). App quét đơn cũng gọi API này. Đơn tự chuyển sang "đang giao" khi ĐVVC lấy hàng (đồng bộ từ sàn / hãng vận chuyển) — không cần bàn giao thủ công.
            </Typography.Paragraph>
            <Space style={{ marginBottom: 8 }} wrap>
                {serialBtn}
                {serial.status === 'connected' && <Tag color="green">Máy quét đã kết nối</Tag>}
                {serial.status === 'error' && <Tag color="red">Lỗi kết nối máy quét</Tag>}
                <Space size={4}><SoundOutlined /><Switch size="small" checked={sound} onChange={setSound} /><Typography.Text type="secondary" style={{ fontSize: 12 }}>Bíp khi quét</Typography.Text></Space>
            </Space>
            <div style={{ transition: 'box-shadow .15s', ...flashStyle }}>
                <Input.Search ref={inputRef} size="large" allowClear enterButton={<><ScanOutlined /> Đóng gói</>} placeholder="Quét mã vận đơn / mã đơn…" value={code} onChange={onChange} onSearch={onSubmit} onBlur={focusSoon} loading={scan.isPending} />
            </div>
            <div style={{ marginTop: 16 }}>
                {log.length === 0 ? <Empty description="Chưa quét gì trong phiên này." /> : log.map((r, i) => (
                    <div key={i} style={{ padding: '6px 10px', borderRadius: 6, marginBottom: 6, background: r.ok ? '#f6ffed' : '#fff1f0', border: `1px solid ${r.ok ? '#b7eb8f' : '#ffa39e'}` }}>
                        {r.ok ? <CheckCircleOutlined style={{ color: '#52c41a', marginInlineEnd: 6 }} /> : <CloseCircleOutlined style={{ color: '#cf1322', marginInlineEnd: 6 }} />}
                        <Typography.Text style={{ fontSize: 13 }}>{r.text}</Typography.Text> <Typography.Text type="secondary" style={{ fontSize: 11 }}>· {r.at}</Typography.Text>
                    </div>
                ))}
            </div>
        </div>
    );
}

// ---- "Vận đơn" tab ----------------------------------------------------------

export function ShipmentsTab({ onPrint }: { onPrint: (id: number) => void }) {
    const { message } = AntApp.useApp();
    const [status, setStatus] = useState<string | undefined>();
    const [q, setQ] = useState('');
    const [page, setPage] = useState(1);
    const { data, isFetching } = useShipments({ status, q: q || undefined, page, per_page: 20 });
    const track = useTrackShipment();
    const cancel = useCancelShipment();
    const pack = usePackShipments();
    const createPrint = useCreatePrintJob();
    const canShip = useCan('fulfillment.ship');
    const canPrint = useCan('fulfillment.print');
    const [sel, setSel] = useState<number[]>([]);
    const [detail, setDetail] = useState<Shipment | null>(null);
    const shipBulk = useBulkAction();

    // Map các vận đơn đang chọn → item cho popup tiến trình (label = mã đơn/mã vận đơn, sub = ĐVVC).
    const bulkItems = () => (data?.data ?? []).filter((s) => sel.includes(s.id)).map((s) => ({
        id: s.id,
        label: String(s.order?.order_number ?? s.order?.external_order_id ?? s.tracking_no ?? `#${s.order_id}`),
        sub: s.carrier,
    }));
    const doBulkPack = () => {
        if (!sel.length) return;
        const items = bulkItems();
        setSel([]);
        void shipBulk.start({ title: 'Đóng gói vận đơn', items, runner: async (ids) => (await pack.mutateAsync(ids)).results });
    };

    const columns: ColumnsType<Shipment> = [
        { title: 'Mã đơn', key: 'o', render: (_, s) => <Link to={`/orders/${s.order_id}`} style={{ fontWeight: 600 }}>{s.order?.order_number ?? s.order?.external_order_id ?? `#${s.order_id}`}</Link> },
        { title: 'Nền tảng', key: 'src', width: 110, render: (_, s) => s.order ? <ChannelBadge provider={s.order.source} /> : '—' },
        { title: 'ĐVVC', dataIndex: 'carrier', key: 'c', width: 90, render: (v) => <Tag>{v}</Tag> },
        { title: 'Mã vận đơn', dataIndex: 'tracking_no', key: 't', width: 170, render: (v) => v ?? '—' },
        { title: 'Trạng thái', key: 's', width: 200, render: (_, s) => <Space size={4}><ShipmentStatusTag status={s.status} /><PrintCountBadge n={s.print_count} /></Space> },
        { title: 'COD', dataIndex: 'cod_amount', key: 'cod', width: 100, align: 'right', render: (v) => (v ? <MoneyText value={v} /> : '—') },
        { title: '', key: 'a', width: 250, render: (_, s) => (
            <Space size={4} wrap>
                <a onClick={() => setDetail(s)}>Chi tiết</a>
                {s.has_label && <a href={`/api/v1/shipments/${s.id}/label`} target="_blank" rel="noreferrer">Tem</a>}
                {canShip && s.carrier !== 'manual' && !['delivered', 'cancelled'].includes(s.status) && <a onClick={() => track.mutate(s.id, { onSuccess: () => message.success('Đã cập nhật'), onError: (e) => message.error(errorMessage(e)) })}>Track</a>}
                {canShip && !['delivered', 'cancelled', 'returned'].includes(s.status) && <a style={{ color: '#cf1322' }} onClick={() => Modal.confirm({ title: 'Huỷ vận đơn này?', onOk: () => cancel.mutateAsync(s.id) })}>Huỷ</a>}
            </Space>
        ) },
    ];

    return (
        <>
            <Space style={{ marginBottom: 12 }} wrap>
                <Input.Search allowClear placeholder="Mã vận đơn / mã đơn" style={{ width: 240 }} onSearch={(v) => { setQ(v); setPage(1); }} />
                <Select allowClear placeholder="Trạng thái" style={{ width: 180 }} value={status} onChange={(v) => { setStatus(v); setPage(1); }} options={Object.entries(SHIPMENT_STATUS_LABEL).map(([v, l]) => ({ value: v, label: l }))} />
                {canShip && <Button icon={<InboxOutlined />} disabled={!sel.length} loading={pack.isPending} onClick={doBulkPack}>Đóng gói ({sel.length})</Button>}
                {canPrint && <Button icon={<PrinterOutlined />} disabled={!sel.length} loading={createPrint.isPending} onClick={() => {
                    const items = (data?.data ?? []).filter((s) => sel.includes(s.id));
                    const sources = Array.from(new Set(items.map((s) => s.order?.source).filter(Boolean) as string[]));
                    const carriers = Array.from(new Set(items.map((s) => s.carrier).filter(Boolean)));
                    if (sources.length > 1 || carriers.length > 1) {
                        Modal.warning({
                            title: 'Không thể in chung tem của nhiều nền tảng / đơn vị vận chuyển',
                            width: 520,
                            content: (
                                <div>
                                    <p style={{ marginTop: 0 }}>Mỗi sàn và mỗi đơn vị vận chuyển dùng định dạng tem riêng — không thể ghép chung 1 tệp PDF để in.</p>
                                    <p>Bạn đang chọn các vận đơn thuộc{' '}
                                        {sources.length > 1 && <><b>{sources.length} sàn</b> ({sources.join(', ')})</>}
                                        {sources.length > 1 && carriers.length > 1 && ' và '}
                                        {carriers.length > 1 && <><b>{carriers.length} đơn vị vận chuyển</b> ({carriers.join(', ')})</>}.
                                    </p>
                                    <p style={{ marginBottom: 0 }}>Hãy lọc bằng ô <b>Trạng thái</b> / cột <b>Nền tảng</b>, <b>ĐVVC</b> rồi chọn lại và in từng đợt.</p>
                                </div>
                            ),
                            okText: 'Đã hiểu',
                        });
                        return;
                    }
                    const runPrint = () => createPrint.mutate({ type: 'label', shipment_ids: sel }, {
                        onSuccess: (j) => onPrint(j.id),
                        onError: (e) => Modal.warning({ title: 'Không in được tem', content: errorMessage(e), okText: 'Đã hiểu' }),
                    });
                    const reprinted = items.filter((s) => (s.print_count ?? 0) > 0);
                    const proceed = () => {
                        if (reprinted.length === 0) { runPrint(); return; }
                        Modal.confirm({
                            title: `${reprinted.length} vận đơn đã từng in tem — vẫn in tiếp?`,
                            width: 540,
                            content: (
                                <div>
                                    <p style={{ marginTop: 0 }}>Trong <b>{items.length}</b> vận đơn sắp in, <b>{reprinted.length}</b> vận đơn đã được in trước đó. In lại có thể tạo trùng phiếu vận chuyển — kiểm tra trước khi giao cho đơn vị vận chuyển.</p>
                                    <p style={{ marginBottom: 4 }}>Danh sách vận đơn đã in:</p>
                                    <ul style={{ margin: 0, paddingInlineStart: 18, maxHeight: 220, overflowY: 'auto' }}>
                                        {reprinted.map((s) => <li key={s.id}>{s.order?.order_number ?? s.order?.external_order_id ?? `#${s.order_id}`} — <span style={{ color: '#8c8c8c' }}>{s.tracking_no ?? '(chưa có mã)'}</span> — đã in <b>{s.print_count}</b> lần</li>)}
                                    </ul>
                                </div>
                            ),
                            okText: 'Vẫn in', okButtonProps: { danger: true }, cancelText: 'Huỷ',
                            onOk: runPrint,
                        });
                    };
                    // Đơn Shopee: nhắc bật in nhiệt trước khi mở tab in (khổ tem do cài đặt shop trên Shopee).
                    withShopeePrintNotice(sources.includes('shopee'), proceed);
                }}>In tem ({sel.length})</Button>}
            </Space>
            <Table<Shipment> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns}
                rowSelection={{ selectedRowKeys: sel, onChange: (k) => setSel(k as number[]), getCheckboxProps: (s) => ({ disabled: ['delivered', 'cancelled'].includes(s.status) }) }}
                locale={{ emptyText: <Empty description="Chưa có vận đơn nào." /> }}
                pagination={{ current: data?.meta.pagination.page ?? page, pageSize: 20, total: data?.meta.pagination.total ?? 0, onChange: setPage, showTotal: (t) => `${t} vận đơn` }} />

            <Modal title={detail ? `Vận đơn ${detail.tracking_no ?? '#' + detail.id}` : ''} open={!!detail} onCancel={() => setDetail(null)} footer={null} width={560} destroyOnClose>
                {detail && <ShipmentDetailBody shipmentId={detail.id} initial={detail} />}
            </Modal>
            <BulkProgressModal title={shipBulk.title} open={shipBulk.open} items={shipBulk.items} running={shipBulk.running} onRetry={shipBulk.retryErrors} onClose={shipBulk.close} />
        </>
    );
}

function ShipmentDetailBody({ shipmentId, initial }: { shipmentId: number; initial: Shipment }) {
    const { data } = useShipment(shipmentId);
    const s = data ?? initial;
    const events = s.events ?? [];
    return (
        <>
            <p><b>ĐVVC:</b> {s.carrier} · <ShipmentStatusTag status={s.status} /> <PrintCountBadge n={s.print_count} /> {s.cod_amount ? <>· COD <MoneyText value={s.cod_amount} /></> : null}</p>
            {events.length === 0 ? <Empty description="Chưa có cập nhật." /> : (
                <Timeline items={events.map((e) => ({
                    children: <Space direction="vertical" size={0}>
                        <span><b>{e.description ?? e.code}</b> {e.status ? <Typography.Text type="secondary">({SHIPMENT_STATUS_LABEL[e.status] ?? e.status})</Typography.Text> : null}</span>
                        <Typography.Text type="secondary" style={{ fontSize: 12 }}><DateText value={e.occurred_at} /> · {e.source}</Typography.Text>
                    </Space>,
                }))} />
            )}
        </>
    );
}
