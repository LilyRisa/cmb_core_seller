import { useEffect, useMemo, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { Alert, App as AntApp, Button, Empty, Form, Input, Modal, Segmented, Select, Space, Table, Tag, Timeline, Tooltip, Typography } from 'antd';
import type { InputRef } from 'antd';
import { CarOutlined, InboxOutlined, PrinterOutlined, ReloadOutlined, ScanOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { ChannelBadge } from '@/components/ChannelBadge';
import { MoneyText, DateText } from '@/components/MoneyText';
import { CHANNEL_META } from '@/lib/format';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import type { Order } from '@/lib/orders';
import {
    type ProcessingFilters, type ProcessingStage, type Shipment, SHIPMENT_STATUS_LABEL, STAGE_LABEL,
    useBulkCreateShipments, useCancelShipment, useCarrierAccounts, useCreatePrintJob, useHandoverShipments,
    usePackShipments, usePrintJob, useProcessingBoard, useScanProcess, useShipment, useShipments, useTrackShipment,
} from '@/lib/fulfillment';

const PLATFORMS = ['tiktok', 'shopee', 'lazada', 'manual'] as const;

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
 * Platform filter chips + the two filter inputs (khách hàng / sản phẩm) shown above the
 * processing-stage boards. Controlled by the parent (OrdersPage). See SPEC 0009 §6.
 */
export function PlatformChips({ sources, onSources, onCustomer, onProduct }: {
    sources: string[];
    onSources: (next: string[]) => void;
    onCustomer: (v: string) => void;
    onProduct: (v: string) => void;
}) {
    return (
        <div>
            <Space wrap style={{ marginBottom: 8 }}>
                <span style={{ color: '#8c8c8c', fontSize: 13 }}>Nền tảng:</span>
                <Tag.CheckableTag checked={sources.length === 0} onChange={() => onSources([])}>Tất cả</Tag.CheckableTag>
                {PLATFORMS.map((p) => {
                    const meta = CHANNEL_META[p] ?? { name: p, color: '#8c8c8c' };
                    const on = sources.includes(p);
                    return (
                        <Tag.CheckableTag key={p} checked={on}
                            onChange={(c) => onSources(c ? [...sources, p] : sources.filter((x) => x !== p))}
                            style={on ? { background: meta.color, borderColor: meta.color } : { borderColor: meta.color, color: meta.color }}>
                            {p === 'manual' ? 'Đơn tay' : meta.name}
                        </Tag.CheckableTag>
                    );
                })}
            </Space>
            <Space wrap>
                <Input.Search allowClear placeholder="Lọc theo khách hàng (tên / mã đơn)" style={{ width: 280 }} onSearch={onCustomer} />
                <Input.Search allowClear placeholder="Lọc theo sản phẩm (tên SP / SKU sàn)" style={{ width: 280 }} onSearch={onProduct} />
            </Space>
        </div>
    );
}

// ---- one processing stage ---------------------------------------------------

export function StageBoard({ stage, filters, onPrint, onGotoScan }: { stage: ProcessingStage; filters: ProcessingFilters; onPrint: (id: number) => void; onGotoScan: (mode: 'pack' | 'handover') => void }) {
    const { message } = AntApp.useApp();
    const [page, setPage] = useState(1);
    const { data, isFetching } = useProcessingBoard(stage, { ...filters, page });
    const { data: accounts } = useCarrierAccounts();
    const bulkCreate = useBulkCreateShipments();
    const createPrint = useCreatePrintJob();
    const packShipments = usePackShipments();
    const handover = useHandoverShipments();
    const canShip = useCan('fulfillment.ship');
    const canPrint = useCan('fulfillment.print');
    const canScan = useCan('fulfillment.scan');
    const [sel, setSel] = useState<number[]>([]);
    const [shipModal, setShipModal] = useState(false);
    const [form] = Form.useForm();

    const rows: Order[] = data?.data ?? [];
    const selRows = useMemo(() => rows.filter((o) => sel.includes(o.id)), [rows, sel]);
    const selWithShipment = selRows.filter((o) => o.shipment);
    const selWithoutShipment = selRows.filter((o) => !o.shipment);
    // print guard: a label bundle must be one platform + one carrier
    const printPlatforms = new Set(selWithShipment.map((o) => o.source));
    const printCarriers = new Set(selWithShipment.map((o) => o.shipment!.carrier));
    const printMixed = printPlatforms.size > 1 || printCarriers.size > 1;
    const printHasReprint = selWithShipment.some((o) => (o.shipment?.print_count ?? 0) >= 1);
    const shipmentIds = (os: Order[]) => os.map((o) => o.shipment?.id).filter((x): x is number => !!x);

    const doPrint = () => {
        if (selWithShipment.length === 0) { message.info('Chọn đơn đã có vận đơn để in tem.'); return; }
        if (printMixed) { message.error('Không thể in tem cho nhiều nền tảng hoặc nhiều ĐVVC cùng lúc.'); return; }
        const fire = () => createPrint.mutate({ type: 'label', shipment_ids: shipmentIds(selWithShipment) }, { onSuccess: (j) => onPrint(j.id), onError: (e) => message.error(errorMessage(e)) });
        if (printHasReprint) {
            Modal.confirm({ title: 'In lại tem?', content: `Có ${selWithShipment.filter((o) => (o.shipment?.print_count ?? 0) >= 1).length} đơn đã được in trước đó. In lại có thể làm trùng tem — chỉ in lại khi thực sự cần (mất tem, kẹt giấy…). Tiếp tục?`, okText: 'In lại', onOk: fire });
        } else { fire(); }
    };
    const doPrintOther = (type: 'picking' | 'packing') => createPrint.mutate({ type, order_ids: sel }, { onSuccess: (j) => onPrint(j.id), onError: (e) => message.error(errorMessage(e)) });
    const doPack = () => packShipments.mutate(shipmentIds(selWithShipment), { onSuccess: (r) => { message.success(`Đã đóng gói ${r.packed} đơn`); setSel([]); }, onError: (e) => message.error(errorMessage(e)) });
    const doHandover = () => handover.mutate(shipmentIds(selWithShipment), { onSuccess: (r) => { message.success(`Đã bàn giao ${r.handed_over} đơn`); setSel([]); }, onError: (e) => message.error(errorMessage(e)) });
    const submitShip = () => form.validateFields().then((v) => bulkCreate.mutate({ order_ids: selWithoutShipment.map((o) => o.id), carrier_account_id: v.carrier_account_id ?? null }, {
        onSuccess: (r) => {
            message.success(`Đã tạo ${r.created.length} vận đơn` + (r.errors.length ? ` · ${r.errors.length} lỗi` : ''));
            if (r.errors.length) Modal.warning({ title: 'Một số đơn không tạo được vận đơn', content: <ul>{r.errors.map((e) => <li key={e.order_id}>Đơn #{e.order_id}: {e.message}</li>)}</ul> });
            setShipModal(false); setSel([]);
        },
        onError: (e) => message.error(errorMessage(e)),
    }));

    const columns: ColumnsType<Order> = [
        { title: 'Mã đơn', key: 'no', render: (_, o) => <Space direction="vertical" size={0}><Link to={`/orders/${o.id}`} style={{ fontWeight: 600 }}>{o.order_number ?? o.external_order_id ?? `#${o.id}`}</Link><ChannelBadge provider={o.source} /></Space> },
        { title: 'Người mua', dataIndex: 'buyer_name', key: 'b', width: 190, render: (v, o) => <Space direction="vertical" size={0}><span>{v ?? '—'}</span><Typography.Text type="secondary" style={{ fontSize: 12 }}>{o.buyer_phone_masked ?? ''}</Typography.Text></Space> },
        { title: 'Mặt hàng', dataIndex: 'items_count', key: 'i', width: 90, align: 'center', render: (v) => `${v ?? 0}` },
        { title: 'Tổng tiền', dataIndex: 'grand_total', key: 't', width: 120, align: 'right', render: (v, o) => <MoneyText value={v} currency={o.currency} strong /> },
        { title: 'Vận đơn', key: 'sh', width: 240, render: (_, o) => o.shipment
            ? <Space direction="vertical" size={2}>
                <Space size={4}><Tag>{o.shipment.carrier}</Tag><ShipmentStatusTag status={o.shipment.status} /><PrintCountBadge n={o.shipment.print_count} /></Space>
                {o.shipment.tracking_no && <Typography.Text copyable style={{ fontSize: 12 }}>{o.shipment.tracking_no}</Typography.Text>}
            </Space>
            : <Typography.Text type="secondary">Chưa có vận đơn</Typography.Text> },
        { title: 'Đặt lúc', dataIndex: 'placed_at', key: 'p', width: 145, render: (v) => <DateText value={v} /> },
    ];

    return (
        <>
            <Space style={{ marginBottom: 12 }} wrap>
                {stage === 'prepare' && canShip && <Button type="primary" icon={<CarOutlined />} disabled={!selWithoutShipment.length} onClick={() => { form.resetFields(); setShipModal(true); }}>Tạo vận đơn ({selWithoutShipment.length})</Button>}
                {canPrint && (
                    <Tooltip title={printMixed ? 'Không thể in tem nhiều nền tảng / ĐVVC cùng lúc — hãy lọc theo từng nền tảng rồi in.' : undefined}>
                        <Button icon={<PrinterOutlined />} disabled={!selWithShipment.length || printMixed} loading={createPrint.isPending} onClick={doPrint}>In tem ({selWithShipment.length}){printHasReprint ? ' • in lại' : ''}</Button>
                    </Tooltip>
                )}
                {stage === 'pack' && (canScan || canShip) && <Button icon={<InboxOutlined />} disabled={!selWithShipment.length} loading={packShipments.isPending} onClick={doPack}>Đóng gói ({selWithShipment.length})</Button>}
                {stage === 'pack' && canScan && <Button icon={<ScanOutlined />} onClick={() => onGotoScan('pack')}>Quét đóng gói</Button>}
                {stage === 'handover' && canShip && <Button type="primary" icon={<CarOutlined />} disabled={!selWithShipment.length} loading={handover.isPending} onClick={doHandover}>Bàn giao ĐVVC ({selWithShipment.length})</Button>}
                {stage === 'handover' && (canScan || canShip) && <Button icon={<ScanOutlined />} onClick={() => onGotoScan('handover')}>Quét bàn giao</Button>}
                {canPrint && <Button icon={<PrinterOutlined />} disabled={!sel.length} onClick={() => doPrintOther('picking')}>Picking list ({sel.length})</Button>}
                {canPrint && <Button icon={<PrinterOutlined />} disabled={!sel.length} onClick={() => doPrintOther('packing')}>Packing list ({sel.length})</Button>}
            </Space>
            {stage === 'prepare' && (accounts ?? []).length === 0 && <Alert type="info" showIcon style={{ marginBottom: 12 }} message="Chưa cấu hình ĐVVC nào — đơn sẽ tạo vận đơn dạng 'Tự vận chuyển'." action={<Link to="/settings/carriers"><Button size="small">Cấu hình ĐVVC</Button></Link>} />}
            <Table<Order> rowKey="id" size="middle" loading={isFetching} dataSource={rows} columns={columns}
                rowSelection={{ selectedRowKeys: sel, onChange: (k) => setSel(k as number[]) }}
                locale={{ emptyText: <Empty description={`Không có đơn ở bước "${STAGE_LABEL[stage]}".`} /> }}
                pagination={{ current: data?.meta.pagination.page ?? page, pageSize: data?.meta.pagination.per_page ?? 100, total: data?.meta.pagination.total ?? 0, onChange: setPage, showTotal: (t) => `${t} đơn` }} />

            <Modal title={`Tạo vận đơn cho ${selWithoutShipment.length} đơn`} open={shipModal} onCancel={() => setShipModal(false)} okText="Tạo vận đơn" confirmLoading={bulkCreate.isPending} onOk={submitShip}>
                <Form form={form} layout="vertical">
                    <Form.Item name="carrier_account_id" label="Tài khoản ĐVVC" extra="Để trống = tài khoản mặc định (hoặc 'Tự vận chuyển' nếu chưa cấu hình ĐVVC).">
                        <Select allowClear placeholder="Mặc định" options={(accounts ?? []).map((a) => ({ value: a.id, label: `${a.name} (${a.carrier})${a.is_default ? ' · mặc định' : ''}` }))} />
                    </Form.Item>
                    <Typography.Text type="secondary">Sau khi tạo vận đơn, dùng "In tem" để lấy phiếu in. Lưu ý: chỉ in được tem cùng một nền tảng + một ĐVVC trong một lần.</Typography.Text>
                </Form>
            </Modal>
        </>
    );
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
