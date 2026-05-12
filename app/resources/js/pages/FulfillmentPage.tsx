import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { Alert, App as AntApp, Button, Card, Empty, Form, Input, Modal, Select, Space, Table, Tabs, Tag, Timeline, Typography } from 'antd';
import type { InputRef } from 'antd';
import { CarOutlined, PrinterOutlined, ReloadOutlined, ScanOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { StatusTag } from '@/components/StatusTag';
import { ChannelBadge } from '@/components/ChannelBadge';
import { MoneyText, DateText } from '@/components/MoneyText';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import type { Order } from '@/lib/orders';
import {
    type Shipment, SHIPMENT_STATUS_LABEL,
    useBulkCreateShipments, useCancelShipment, useCarrierAccounts, useCreatePrintJob, useHandoverShipments,
    usePrintJob, useReadyOrders, useScanPack, useShipment, useShipments, useTrackShipment,
} from '@/lib/fulfillment';

function ShipmentStatusTag({ status }: { status: string }) {
    const color = { delivered: 'green', cancelled: 'default', failed: 'red', returned: 'orange', picked_up: 'blue', in_transit: 'cyan', created: 'gold', pending: 'default' }[status] ?? 'default';
    return <Tag color={color}>{SHIPMENT_STATUS_LABEL[status] ?? status}</Tag>;
}

/** A small bar showing the running print job + a "tải file" button when it finishes. */
function PrintJobBar({ jobId, onClose }: { jobId: number; onClose: () => void }) {
    const { data: job } = usePrintJob(jobId);
    const opened = useRef(false);
    useEffect(() => {
        if (job?.status === 'done' && job.file_url && !opened.current) { opened.current = true; window.open(job.file_url, '_blank'); }
    }, [job]);
    if (!job) return null;
    if (job.status === 'error') return <Alert type="error" showIcon closable onClose={onClose} style={{ marginBottom: 12 }} message={`Tạo phiếu in lỗi: ${job.error ?? ''}`} />;
    if (job.status === 'done') return <Alert type="success" showIcon closable onClose={onClose} style={{ marginBottom: 12 }} message={`Phiếu in (${job.type}) đã sẵn sàng`} action={<a href={job.file_url ?? '#'} target="_blank" rel="noreferrer"><Button size="small" type="primary">Tải / In</Button></a>} />;
    return <Alert type="info" showIcon style={{ marginBottom: 12 }} message={`Đang tạo phiếu in (${job.type})…`} />;
}

export function FulfillmentPage() {
    const [tab, setTab] = useState('ready');
    const [printJobId, setPrintJobId] = useState<number | null>(null);
    return (
        <div>
            <PageHeader title="Giao hàng & in" subtitle="Tạo vận đơn, in tem hàng loạt, picking/packing list, quét đóng gói" />
            {printJobId && <PrintJobBar jobId={printJobId} onClose={() => setPrintJobId(null)} />}
            <Card>
                <Tabs activeKey={tab} onChange={setTab} items={[
                    { key: 'ready', label: <span><CarOutlined /> Cần giao</span>, children: tab === 'ready' && <ReadyTab onPrint={setPrintJobId} /> },
                    { key: 'shipments', label: 'Vận đơn', children: tab === 'shipments' && <ShipmentsTab onPrint={setPrintJobId} /> },
                    { key: 'scan', label: <span><ScanOutlined /> Quét đóng gói</span>, children: tab === 'scan' && <ScanTab /> },
                ]} />
            </Card>
        </div>
    );
}

// ---- Tab "Cần giao" ---------------------------------------------------------

function ReadyTab({ onPrint }: { onPrint: (id: number) => void }) {
    const { message } = AntApp.useApp();
    const [q, setQ] = useState('');
    const [page, setPage] = useState(1);
    const { data, isFetching } = useReadyOrders({ q: q || undefined, page, per_page: 20 });
    const { data: accounts } = useCarrierAccounts();
    const bulkCreate = useBulkCreateShipments();
    const createPrint = useCreatePrintJob();
    const canShip = useCan('fulfillment.ship');
    const canPrint = useCan('fulfillment.print');
    const [sel, setSel] = useState<number[]>([]);
    const [shipModal, setShipModal] = useState(false);
    const [form] = Form.useForm();

    const print = (type: 'picking' | 'packing') => createPrint.mutate({ type, order_ids: sel }, { onSuccess: (j) => onPrint(j.id), onError: (e) => message.error(errorMessage(e)) });

    const submitShip = () => form.validateFields().then((v) => bulkCreate.mutate({ order_ids: sel, carrier_account_id: v.carrier_account_id ?? null }, {
        onSuccess: (r) => {
            message.success(`Đã tạo ${r.created.length} vận đơn` + (r.errors.length ? ` · ${r.errors.length} lỗi` : ''));
            if (r.errors.length) Modal.warning({ title: 'Một số đơn không tạo được vận đơn', content: <ul>{r.errors.map((e) => <li key={e.order_id}>Đơn #{e.order_id}: {e.message}</li>)}</ul> });
            setShipModal(false); setSel([]);
        },
        onError: (e) => message.error(errorMessage(e)),
    }));

    const columns: ColumnsType<Order> = [
        { title: 'Mã đơn', key: 'no', render: (_, o) => <Space direction="vertical" size={0}><Link to={`/orders/${o.id}`} style={{ fontWeight: 600 }}>{o.order_number ?? o.external_order_id ?? `#${o.id}`}</Link><ChannelBadge provider={o.source} /></Space> },
        { title: 'Người mua', dataIndex: 'buyer_name', key: 'b', width: 200, render: (v, o) => <Space direction="vertical" size={0}><span>{v ?? '—'}</span><Typography.Text type="secondary" style={{ fontSize: 12 }}>{o.buyer_phone_masked ?? ''}</Typography.Text></Space> },
        { title: 'Mặt hàng', dataIndex: 'items_count', key: 'i', width: 100, align: 'center', render: (v) => `${v ?? 0}` },
        { title: 'Tổng tiền', dataIndex: 'grand_total', key: 't', width: 130, align: 'right', render: (v, o) => <MoneyText value={v} currency={o.currency} strong /> },
        { title: 'Trạng thái', dataIndex: 'status', key: 's', width: 130, render: (v, o) => <StatusTag status={v} label={o.status_label} rawStatus={o.raw_status} /> },
        { title: 'Đặt lúc', dataIndex: 'placed_at', key: 'p', width: 150, render: (v) => <DateText value={v} /> },
    ];

    return (
        <>
            <Space style={{ marginBottom: 12 }} wrap>
                <Input.Search allowClear placeholder="Mã đơn / người mua" style={{ width: 240 }} onSearch={(v) => { setQ(v); setPage(1); }} />
                {canShip && <Button type="primary" icon={<CarOutlined />} disabled={!sel.length} onClick={() => { form.resetFields(); setShipModal(true); }}>Tạo vận đơn ({sel.length})</Button>}
                {canPrint && <Button icon={<PrinterOutlined />} disabled={!sel.length} loading={createPrint.isPending} onClick={() => print('picking')}>Picking list ({sel.length})</Button>}
                {canPrint && <Button icon={<PrinterOutlined />} disabled={!sel.length} loading={createPrint.isPending} onClick={() => print('packing')}>Packing list ({sel.length})</Button>}
            </Space>
            <Table<Order> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns}
                rowSelection={{ selectedRowKeys: sel, onChange: (k) => setSel(k as number[]) }}
                locale={{ emptyText: <Empty description="Không có đơn nào đang chờ giao." /> }}
                pagination={{ current: data?.meta.pagination.page ?? page, pageSize: 20, total: data?.meta.pagination.total ?? 0, onChange: setPage, showTotal: (t) => `${t} đơn` }} />

            <Modal title={`Tạo vận đơn cho ${sel.length} đơn`} open={shipModal} onCancel={() => setShipModal(false)} okText="Tạo vận đơn" confirmLoading={bulkCreate.isPending} onOk={submitShip}>
                <Form form={form} layout="vertical">
                    <Form.Item name="carrier_account_id" label="Tài khoản ĐVVC" extra="Để trống = dùng tài khoản mặc định (hoặc 'Tự vận chuyển' nếu chưa cấu hình ĐVVC).">
                        <Select allowClear placeholder="Mặc định" options={(accounts ?? []).map((a) => ({ value: a.id, label: `${a.name} (${a.carrier})${a.is_default ? ' · mặc định' : ''}` }))} />
                    </Form.Item>
                    {(accounts ?? []).length === 0 && <Alert type="info" showIcon message="Chưa cấu hình ĐVVC nào — đơn sẽ tạo vận đơn dạng 'Tự vận chuyển' (bạn tự nhập mã vận đơn / quản lý tracking)." action={<Link to="/settings/carriers"><Button size="small">Cấu hình ĐVVC</Button></Link>} />}
                </Form>
            </Modal>
        </>
    );
}

// ---- Tab "Vận đơn" ----------------------------------------------------------

function ShipmentsTab({ onPrint }: { onPrint: (id: number) => void }) {
    const { message } = AntApp.useApp();
    const [status, setStatus] = useState<string | undefined>();
    const [q, setQ] = useState('');
    const [page, setPage] = useState(1);
    const { data, isFetching } = useShipments({ status, q: q || undefined, page, per_page: 20 });
    const track = useTrackShipment();
    const cancel = useCancelShipment();
    const handover = useHandoverShipments();
    const createPrint = useCreatePrintJob();
    const canShip = useCan('fulfillment.ship');
    const canPrint = useCan('fulfillment.print');
    const [sel, setSel] = useState<number[]>([]);
    const [detail, setDetail] = useState<Shipment | null>(null);

    const columns: ColumnsType<Shipment> = [
        { title: 'Mã đơn', key: 'o', render: (_, s) => <Link to={`/orders/${s.order_id}`} style={{ fontWeight: 600 }}>{s.order?.order_number ?? s.order?.external_order_id ?? `#${s.order_id}`}</Link> },
        { title: 'ĐVVC', dataIndex: 'carrier', key: 'c', width: 100, render: (v) => <Tag>{v}</Tag> },
        { title: 'Mã vận đơn', dataIndex: 'tracking_no', key: 't', width: 180, render: (v) => v ?? '—' },
        { title: 'Trạng thái', dataIndex: 'status', key: 's', width: 150, render: (v) => <ShipmentStatusTag status={v} /> },
        { title: 'COD', dataIndex: 'cod_amount', key: 'cod', width: 110, align: 'right', render: (v) => (v ? <MoneyText value={v} /> : '—') },
        { title: 'Tạo lúc', dataIndex: 'created_at', key: 'cr', width: 150, render: (v) => <DateText value={v} /> },
        { title: '', key: 'a', width: 230, render: (_, s) => (
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
                <Select allowClear placeholder="Trạng thái" style={{ width: 180 }} value={status} onChange={(v) => { setStatus(v); setPage(1); }}
                    options={Object.entries(SHIPMENT_STATUS_LABEL).map(([v, l]) => ({ value: v, label: l }))} />
                {canShip && <Button icon={<ReloadOutlined />} disabled={!sel.length} loading={handover.isPending} onClick={() => handover.mutate(sel, { onSuccess: (r) => { message.success(`Đã bàn giao ${r.handed_over} vận đơn`); setSel([]); }, onError: (e) => message.error(errorMessage(e)) })}>Bàn giao ({sel.length})</Button>}
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
            <p><b>ĐVVC:</b> {s.carrier} · <ShipmentStatusTag status={s.status} /> {s.cod_amount ? <>· COD <MoneyText value={s.cod_amount} /></> : null}</p>
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

// ---- Tab "Quét đóng gói" ----------------------------------------------------

function ScanTab() {
    const scan = useScanPack();
    const [code, setCode] = useState('');
    const [log, setLog] = useState<Array<{ ok: boolean; text: string; at: string }>>([]);
    const inputRef = useRef<InputRef>(null);
    useEffect(() => { inputRef.current?.focus(); }, []);

    const submit = () => {
        const c = code.trim();
        if (!c) return;
        setCode('');
        scan.mutate(c, {
            onSuccess: (r) => setLog((l) => [{ ok: true, text: `✔ Đã đóng gói đơn ${r.order?.order_number ?? '#' + (r.order?.id ?? '')} · vận đơn ${r.shipment.tracking_no ?? '#' + r.shipment.id} → đơn ${SHIPMENT_STATUS_LABEL[r.shipment.status] ?? r.shipment.status}`, at: new Date().toLocaleTimeString() }, ...l].slice(0, 50)),
            onError: (e) => setLog((l) => [{ ok: false, text: `✗ ${c}: ${errorMessage(e)}`, at: new Date().toLocaleTimeString() }, ...l].slice(0, 50)),
            onSettled: () => inputRef.current?.focus(),
        });
    };

    return (
        <div style={{ maxWidth: 720 }}>
            <Typography.Paragraph type="secondary">Quét (hoặc gõ) <b>mã vận đơn</b> hoặc <b>mã đơn</b> rồi Enter. Mỗi lần quét thành công sẽ đánh dấu vận đơn đã đóng gói/bàn giao, chuyển đơn sang "Đang vận chuyển" và trừ tồn.</Typography.Paragraph>
            <Input.Search ref={inputRef} size="large" allowClear enterButton={<><ScanOutlined /> Xác nhận</>} placeholder="Quét mã vận đơn / mã đơn…" value={code} onChange={(e) => setCode(e.target.value)} onSearch={submit} loading={scan.isPending} />
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
