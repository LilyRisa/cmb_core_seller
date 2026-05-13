import { useEffect, useRef, useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { App as AntApp, Button, Empty, Input, InputNumber, Modal, Result, Segmented, Select, Space, Spin, Table, Tag, Timeline, Tooltip, Typography } from 'antd';
import type { InputRef } from 'antd';
import { CarOutlined, CheckCircleOutlined, CloseCircleOutlined, ExportOutlined, InboxOutlined, PrinterOutlined, ReloadOutlined, ScanOutlined, WarningOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { ChannelBadge } from '@/components/ChannelBadge';
import { MoneyText, DateText } from '@/components/MoneyText';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import type { Order } from '@/lib/orders';
import {
    type PrintJob, type Shipment, SHIPMENT_STATUS_LABEL,
    useBulkRefetchSlip, useCancelShipment, useCreatePrintJob, useHandoverShipments, useMarkPrinted,
    usePackShipments, usePrintJob, useScanProcess, useShipment, useShipments, useShipOrder, useTrackShipment,
} from '@/lib/fulfillment';

function ShipmentStatusTag({ status }: { status: string }) {
    const color = { delivered: 'green', cancelled: 'default', failed: 'red', returned: 'orange', picked_up: 'geekblue', packed: 'blue', in_transit: 'cyan', created: 'gold', pending: 'default' }[status] ?? 'default';
    return <Tag color={color}>{SHIPMENT_STATUS_LABEL[status] ?? status}</Tag>;
}

/** "Đơn đã in" chip — hiện ở danh sách đơn để biết đơn đã in & in bao nhiêu lần (SPEC 0013 — domain doc §1). */
export function PrintCountBadge({ n, at }: { n: number; at?: string | null }) {
    if (!n) return null;
    return <Tooltip title={`Đã in ${n} lần${at ? ' · gần nhất ' + new Date(at).toLocaleString('vi-VN') : ''}`}><Tag icon={<PrinterOutlined />} color={n > 1 ? 'orange' : 'green'} style={{ marginInlineEnd: 0 }}>{n}×</Tag></Tooltip>;
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
    const handover = useHandoverShipments();
    const createPrint = useCreatePrintJob();
    const refetchSlip = useBulkRefetchSlip();
    const canShip = useCan('fulfillment.ship');
    const canPrint = useCan('fulfillment.print');
    const sh = order.shipment;
    const busy = ship.isPending || pack.isPending || handover.isPending || createPrint.isPending || refetchSlip.isPending;
    if (busy) return <Spin size="small" />;

    const err = (e: unknown) => message.error(errorMessage(e));
    const printDelivery = () => createPrint.mutate(sh ? { type: 'label', shipment_ids: [sh.id] } : { type: 'delivery', order_ids: [order.id] }, { onSuccess: (j) => onPrint(j.id), onError: err });
    const doRefetchSlip = () => refetchSlip.mutate([order.id], { onSuccess: () => message.success('Đã yêu cầu lấy lại phiếu giao hàng'), onError: err });
    // "Chuẩn bị hàng" = đẩy trạng thái "sắp xếp vận chuyển" lên sàn + lấy AWB/phiếu của sàn → đơn pending → processing.
    // Phiếu giao hàng tự chạy queue lưu R2 (BE) ⇒ FE chỉ báo, không cần render lại lúc in.
    const runPrepare = () => ship.mutate({ orderId: order.id }, { onSuccess: () => message.success('Đã chuẩn bị hàng — đang đẩy trạng thái lên sàn & lấy phiếu giao hàng'), onError: err });
    const prepare = () => {
        if (order.profit && order.profit.estimated_profit < 0) {
            Modal.confirm({ title: 'Đơn này lợi nhuận ước tính ÂM', content: `Lợi nhuận ước tính: ${order.profit.estimated_profit.toLocaleString('vi-VN')} ₫ (tổng tiền không bù được phí sàn + giá vốn). Vẫn chuẩn bị hàng?`, okText: 'Vẫn chuẩn bị', okButtonProps: { danger: true }, cancelText: 'Để xem lại', onOk: runPrepare });
        } else { runPrepare(); }
    };
    // "Đã gói & sẵn sàng bàn giao" — bảo đảm có vận đơn rồi markPacked (processing → ready_to_ship).
    const markReady = () => (sh
        ? pack.mutate([sh.id], { onSuccess: () => message.success('Đã đánh dấu gói xong — chờ bàn giao ĐVVC'), onError: err })
        : ship.mutate({ orderId: order.id }, { onSuccess: (s) => pack.mutate([s.id], { onSuccess: () => message.success('Đã đánh dấu gói xong — chờ bàn giao ĐVVC'), onError: err }), onError: err }));
    const doHandover = () => (sh
        ? handover.mutate([sh.id], { onSuccess: () => message.success('Đã bàn giao ĐVVC'), onError: err })
        : ship.mutate({ orderId: order.id }, { onSuccess: (s) => handover.mutate([s.id], { onSuccess: () => message.success('Đã bàn giao ĐVVC'), onError: err }), onError: err }));

    // Trạng thái "công việc" theo vận đơn (SPEC 0013): chưa có vận đơn = "chưa chuẩn bị hàng" (mọi nguồn).
    const preShipment = !['shipped', 'delivery_failed', 'delivered', 'completed', 'returning', 'returned_refunded', 'cancelled'].includes(order.status);
    const shOpen = sh && !['cancelled', 'returned', 'failed'].includes(sh.status);
    const actions: ReactNode[] = [];
    if (preShipment && ! sh && canShip && !order.has_issue) {
        // "Chuẩn bị hàng" — đơn chưa có mã vận đơn (chưa in/arrange phiếu). Đơn âm tồn ⇒ chặn.
        actions.push(order.out_of_stock
            ? <Tooltip key="prep" title="Đơn có SKU âm tồn — không thể chuẩn bị hàng / lấy phiếu giao hàng. Hãy nhập thêm hàng."><Typography.Text type="secondary"><WarningOutlined /> Hết hàng</Typography.Text></Tooltip>
            : <a key="prep" onClick={prepare}>Chuẩn bị hàng (lấy phiếu)</a>);
    } else if (shOpen && ['pending', 'created'].includes(sh!.status)) {
        // Đã chuẩn bị / có vận đơn, chờ đóng gói + quét nội bộ.
        if (canShip && (order.has_issue || !sh!.has_label)) actions.push(<a key="rs" style={{ color: order.has_issue ? '#cf1322' : undefined }} onClick={doRefetchSlip}>Nhận phiếu giao hàng lại</a>);
        if (canPrint && sh!.has_label) actions.push(<a key="ds1" onClick={printDelivery}>In phiếu giao hàng</a>);
        if (canPrint && sh!.label_url) actions.push(<a key="lbl1" onClick={() => createPrint.mutate({ type: 'label', shipment_ids: [sh!.id] }, { onSuccess: (j) => onPrint(j.id), onError: err })}>In tem sàn</a>);
        if (canShip) actions.push(<a key="ready" onClick={markReady}>Đã gói & sẵn sàng bàn giao</a>);
    } else if (shOpen && sh!.status === 'packed') {
        // Đã đóng gói, chờ bàn giao ĐVVC.
        if (canShip) actions.push(<a key="ho" onClick={doHandover}>Bàn giao ĐVVC</a>);
        if (canPrint) actions.push(<a key="ds2" onClick={printDelivery}>In lại phiếu</a>);
        if (canPrint && sh!.label_url) actions.push(<a key="lbl2" onClick={() => createPrint.mutate({ type: 'label', shipment_ids: [sh!.id] }, { onSuccess: (j) => onPrint(j.id), onError: err })}>In tem sàn</a>);
    }
    if (canPrint) actions.push(<a key="inv" onClick={() => createPrint.mutate({ type: 'invoice', order_ids: [order.id] }, { onSuccess: (j) => onPrint(j.id), onError: err })}>In hoá đơn</a>);
    return <Space size={8} wrap>{actions}</Space>;
}

// ---- "Quét" tab (pack / handover modes) -------------------------------------

export function ScanTab({ initialMode }: { initialMode: 'pack' | 'handover' }) {
    const [mode, setMode] = useState<'pack' | 'handover'>(initialMode);
    const scan = useScanProcess(mode);
    const [code, setCode] = useState('');
    const [log, setLog] = useState<Array<{ ok: boolean; text: string; at: string }>>([]);
    const inputRef = useRef<InputRef>(null);
    useEffect(() => { inputRef.current?.focus(); }, [mode]);

    const submit = () => {
        const c = code.trim();
        if (!c) return;
        setCode('');
        scan.mutate(c, {
            onSuccess: (r) => setLog((l) => [{ ok: true, text: `${r.message}: ${r.order?.order_number ?? '#' + (r.order?.id ?? '')} · ${r.shipment.tracking_no ?? '#' + r.shipment.id} → ${SHIPMENT_STATUS_LABEL[r.shipment.status] ?? r.shipment.status}`, at: new Date().toLocaleTimeString() }, ...l].slice(0, 60)),
            onError: (e) => setLog((l) => [{ ok: false, text: `${c}: ${errorMessage(e)}`, at: new Date().toLocaleTimeString() }, ...l].slice(0, 60)),
            onSettled: () => inputRef.current?.focus(),
        });
    };

    return (
        <div style={{ maxWidth: 760 }}>
            <Space style={{ marginBottom: 12 }}>
                <Segmented value={mode} onChange={(v) => setMode(v as 'pack' | 'handover')} options={[{ label: 'Đóng gói', value: 'pack', icon: <InboxOutlined /> }, { label: 'Bàn giao ĐVVC', value: 'handover', icon: <CarOutlined /> }]} />
            </Space>
            <Typography.Paragraph type="secondary">
                {mode === 'pack'
                    ? 'Quét (hoặc gõ) mã vận đơn / mã đơn rồi Enter để đánh dấu ĐÃ ĐÓNG GÓI. (App quét đơn cũng gọi API này.)'
                    : 'Quét mã vận đơn / mã đơn rồi Enter để BÀN GIAO ĐVVC — đơn chuyển sang "Đang vận chuyển" và trừ tồn.'}
            </Typography.Paragraph>
            <Input.Search ref={inputRef} size="large" allowClear enterButton={<><ScanOutlined /> {mode === 'pack' ? 'Đóng gói' : 'Bàn giao'}</>} placeholder="Quét mã vận đơn / mã đơn…" value={code} onChange={(e) => setCode(e.target.value)} onSearch={submit} loading={scan.isPending} />
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
    const handover = useHandoverShipments();
    const pack = usePackShipments();
    const createPrint = useCreatePrintJob();
    const canShip = useCan('fulfillment.ship');
    const canPrint = useCan('fulfillment.print');
    const [sel, setSel] = useState<number[]>([]);
    const [detail, setDetail] = useState<Shipment | null>(null);

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
                {canShip && <Button icon={<InboxOutlined />} disabled={!sel.length} loading={pack.isPending} onClick={() => pack.mutate(sel, { onSuccess: (r) => { message.success(`Đã đóng gói ${r.packed} đơn`); setSel([]); } })}>Đóng gói ({sel.length})</Button>}
                {canShip && <Button icon={<ReloadOutlined />} disabled={!sel.length} loading={handover.isPending} onClick={() => handover.mutate(sel, { onSuccess: (r) => { message.success(`Đã bàn giao ${r.handed_over} đơn`); setSel([]); }, onError: (e) => message.error(errorMessage(e)) })}>Bàn giao ({sel.length})</Button>}
                {canPrint && <Button icon={<PrinterOutlined />} disabled={!sel.length} loading={createPrint.isPending} onClick={() => createPrint.mutate({ type: 'label', shipment_ids: sel }, { onSuccess: (j) => onPrint(j.id), onError: (e) => message.error(errorMessage(e)) })}>In tem ({sel.length})</Button>}
            </Space>
            <Table<Shipment> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns}
                rowSelection={{ selectedRowKeys: sel, onChange: (k) => setSel(k as number[]), getCheckboxProps: (s) => ({ disabled: ['delivered', 'cancelled'].includes(s.status) }) }}
                locale={{ emptyText: <Empty description="Chưa có vận đơn nào." /> }}
                pagination={{ current: data?.meta.pagination.page ?? page, pageSize: 20, total: data?.meta.pagination.total ?? 0, onChange: setPage, showTotal: (t) => `${t} vận đơn` }} />

            <Modal title={detail ? `Vận đơn ${detail.tracking_no ?? '#' + detail.id}` : ''} open={!!detail} onCancel={() => setDetail(null)} footer={null} width={560} destroyOnClose>
                {detail && <ShipmentDetailBody shipmentId={detail.id} initial={detail} />}
            </Modal>
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
