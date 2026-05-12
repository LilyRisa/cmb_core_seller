import { useEffect, useRef, useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { Alert, App as AntApp, Button, Empty, Input, Modal, Segmented, Select, Space, Spin, Table, Tag, Timeline, Tooltip, Typography } from 'antd';
import type { InputRef } from 'antd';
import { CarOutlined, InboxOutlined, PrinterOutlined, ReloadOutlined, ScanOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { ChannelBadge } from '@/components/ChannelBadge';
import { MoneyText, DateText } from '@/components/MoneyText';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import type { Order } from '@/lib/orders';
import {
    type Shipment, SHIPMENT_STATUS_LABEL,
    useCancelShipment, useCreatePrintJob, useHandoverShipments,
    usePackShipments, usePrintJob, useScanProcess, useShipment, useShipments, useShipOrder, useTrackShipment,
} from '@/lib/fulfillment';

function ShipmentStatusTag({ status }: { status: string }) {
    const color = { delivered: 'green', cancelled: 'default', failed: 'red', returned: 'orange', picked_up: 'geekblue', packed: 'blue', in_transit: 'cyan', created: 'gold', pending: 'default' }[status] ?? 'default';
    return <Tag color={color}>{SHIPMENT_STATUS_LABEL[status] ?? status}</Tag>;
}

function PrintCountBadge({ n }: { n: number }) {
    if (!n) return null;
    return <Tooltip title={`Đã in ${n} lần`}><Tag icon={<PrinterOutlined />} color={n > 1 ? 'orange' : 'default'} style={{ marginInlineEnd: 0 }}>{n}×</Tag></Tooltip>;
}

/** Running print-job bar (poll → "Tải / In" when ready). */
export function PrintJobBar({ jobId, onClose }: { jobId: number; onClose: () => void }) {
    const { data: job } = usePrintJob(jobId);
    const opened = useRef(false);
    useEffect(() => { if (job?.status === 'done' && job.file_url && !opened.current) { opened.current = true; window.open(job.file_url, '_blank'); } }, [job]);
    if (!job) return null;
    if (job.status === 'error') return <Alert type="error" showIcon closable onClose={onClose} style={{ marginBottom: 12 }} message={`Tạo phiếu in lỗi: ${job.error ?? ''}`} />;
    if (job.status === 'done') {
        const skipped = Array.isArray((job.meta as Record<string, unknown>)?.skipped) ? ((job.meta as Record<string, number[]>).skipped ?? []) : [];
        return <Alert type="success" showIcon closable onClose={onClose} style={{ marginBottom: 12 }}
            message={`Phiếu in (${job.type}) đã sẵn sàng${skipped.length ? ` — ${skipped.length} đơn không có tem, bị bỏ qua` : ''}`}
            action={<a href={job.file_url ?? '#'} target="_blank" rel="noreferrer"><Button size="small" type="primary">Tải / In</Button></a>} />;
    }
    return <Alert type="info" showIcon style={{ marginBottom: 12 }} message={`Đang tạo phiếu in (${job.type})…`} />;
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
    const canShip = useCan('fulfillment.ship');
    const canPrint = useCan('fulfillment.print');
    const sh = order.shipment;
    const busy = ship.isPending || pack.isPending || handover.isPending || createPrint.isPending;
    if (busy) return <Spin size="small" />;

    const actions: ReactNode[] = [];
    if (!sh && !order.has_issue && (order.status === 'pending' || order.status === 'processing') && canShip) {
        actions.push(<a key="ship" onClick={() => ship.mutate({ orderId: order.id }, { onSuccess: () => message.success('Đã tạo vận đơn'), onError: (e) => message.error(errorMessage(e)) })}>Tạo vận đơn</a>);
    }
    if (sh && !['delivered', 'cancelled', 'returned', 'failed'].includes(sh.status)) {
        if (canPrint) actions.push(<a key="label" onClick={() => createPrint.mutate({ type: 'label', shipment_ids: [sh.id] }, { onSuccess: (j) => onPrint(j.id), onError: (e) => message.error(errorMessage(e)) })}>In tem</a>);
        if (canShip && ['pending', 'created'].includes(sh.status)) actions.push(<a key="pack" onClick={() => pack.mutate([sh.id], { onSuccess: () => message.success('Đã đóng gói'), onError: (e) => message.error(errorMessage(e)) })}>Đóng gói</a>);
        if (canShip && sh.status === 'packed') actions.push(<a key="ho" onClick={() => handover.mutate([sh.id], { onSuccess: () => message.success('Đã bàn giao ĐVVC'), onError: (e) => message.error(errorMessage(e)) })}>Bàn giao ĐVVC</a>);
    }
    if (canPrint) actions.push(<a key="inv" onClick={() => createPrint.mutate({ type: 'invoice', order_ids: [order.id] }, { onSuccess: (j) => onPrint(j.id), onError: (e) => message.error(errorMessage(e)) })}>In hoá đơn</a>);
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
            onSuccess: (r) => setLog((l) => [{ ok: true, text: `✔ ${r.message}: ${r.order?.order_number ?? '#' + (r.order?.id ?? '')} · ${r.shipment.tracking_no ?? '#' + r.shipment.id} → ${SHIPMENT_STATUS_LABEL[r.shipment.status] ?? r.shipment.status}`, at: new Date().toLocaleTimeString() }, ...l].slice(0, 60)),
            onError: (e) => setLog((l) => [{ ok: false, text: `✗ ${c}: ${errorMessage(e)}`, at: new Date().toLocaleTimeString() }, ...l].slice(0, 60)),
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
