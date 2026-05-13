import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { App as AntApp, Button, Card, Empty, Input, InputNumber, Modal, Progress, Radio, Space, Statistic, Table, Tag, Tooltip, Typography } from 'antd';
import { CheckCircleOutlined, ExclamationCircleOutlined, FundProjectionScreenOutlined, ReloadOutlined, SearchOutlined, ShoppingCartOutlined, WarningOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { SkuLine } from '@/components/SkuPicker';
import { MoneyText } from '@/components/MoneyText';
import { tenantApi, errorMessage } from '@/lib/api';
import { useCan, useCurrentTenantId } from '@/lib/tenant';
import { useSuppliers } from '@/lib/procurement';
import { useWarehouses } from '@/lib/inventory';

interface Row {
    sku: { id: number; sku_code: string; name: string; image_url: string | null; category: string | null };
    avg_daily_sold: number;
    sold_in_window: number;
    window_days: number;
    on_hand: number;
    reserved: number;
    available: number;
    safety_stock: number;
    on_order: number;
    days_left: number;
    urgency: 'urgent' | 'soon' | 'ok';
    suggested_qty: number;
    suggested_supplier: { id: number; code: string; name: string } | null;
    suggested_unit_cost: number;
    suggested_cost_total: number;
}

const URGENCY_META: Record<Row['urgency'], { color: string; label: string; icon: React.ReactNode }> = {
    urgent: { color: 'red', label: 'Khẩn cấp', icon: <WarningOutlined /> },
    soon: { color: 'gold', label: 'Sắp hết', icon: <ExclamationCircleOutlined /> },
    ok: { color: 'green', label: 'Đủ hàng', icon: <CheckCircleOutlined /> },
};

/**
 * /procurement/demand-planning — đề xuất nhập hàng (Phase 6.3 / SPEC 0014b).
 * Hiển thị tốc độ bán, tồn khả dụng, đang về, số ngày còn hàng, đề xuất nhập + NCC gợi ý. Cho chọn nhiều
 * dòng → tạo PO nháp 1 click (chia theo NCC).
 */
export function DemandPlanningPage() {
    const { message } = AntApp.useApp();
    const qc = useQueryClient();
    const navigate = useNavigate();
    const canManage = useCan('procurement.manage');
    const tenantId = useCurrentTenantId();
    const api = useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
    const { data: warehouses } = useWarehouses();
    const { data: suppliersData } = useSuppliers({ is_active: true, per_page: 100 });

    const [windowDays, setWindowDays] = useState(30);
    const [leadTime, setLeadTime] = useState(7);
    const [coverDays, setCoverDays] = useState(14);
    const [urgency, setUrgency] = useState<string>('');
    const [supplierId, setSupplierId] = useState<number | undefined>();
    const [q, setQ] = useState('');
    const [selected, setSelected] = useState<Record<number, number>>({});   // sku_id → qty override

    const { data, isFetching, refetch } = useQuery({
        queryKey: ['demand-planning', tenantId, windowDays, leadTime, coverDays, urgency, supplierId, q],
        enabled: api != null,
        placeholderData: (p) => p,
        queryFn: async () => {
            const params: Record<string, string | number> = {
                window_days: windowDays, lead_time: leadTime, cover_days: coverDays, per_page: 200,
            };
            if (urgency) params.urgency = urgency;
            if (supplierId) params.supplier_id = supplierId;
            if (q) params.q = q;
            const { data: r } = await api!.get<{ data: Row[]; meta: { pagination: { total: number } } }>('/procurement/demand-planning', { params });
            return r;
        },
    });

    const createPo = useMutation({
        mutationFn: async (vars: { warehouse_id: number; rows: Array<{ sku_id: number; qty: number; supplier_id: number; unit_cost?: number }> }) => {
            const { data: r } = await api!.post<{ data: { purchase_order_ids: number[]; count: number } }>('/procurement/demand-planning/create-po', vars);
            return r.data;
        },
        onSuccess: (r) => {
            message.success(`Đã tạo ${r.count} đơn mua nháp — mở để chốt giá & confirm.`);
            qc.invalidateQueries({ queryKey: ['purchase-orders', tenantId] });
            setSelected({});
            if (r.purchase_order_ids[0]) navigate('/procurement/purchase-orders');
        },
        onError: (e) => message.error(errorMessage(e)),
    });

    const rows = data?.data ?? [];
    const selectedRows = rows.filter((r) => r.suggested_supplier && selected[r.sku.id] != null);
    const selectedCount = selectedRows.length;
    const selectedTotal = selectedRows.reduce((s, r) => s + (selected[r.sku.id] ?? r.suggested_qty) * r.suggested_unit_cost, 0);

    const submitPo = () => {
        if (selectedCount === 0) { message.warning('Chọn ít nhất một dòng có NCC gợi ý.'); return; }
        const wh = warehouses?.[0]?.id;
        if (!wh) { message.error('Chưa có kho — vào Cài đặt/kho để thêm.'); return; }
        Modal.confirm({
            title: 'Tạo đơn mua nháp?', width: 480,
            content: (
                <div>
                    <p>Sẽ tạo PO nháp chia theo NCC — bạn rà soát giá & chốt trước khi gửi.</p>
                    <ul style={{ paddingInlineStart: 18 }}>
                        <li>Số dòng: <b>{selectedCount}</b></li>
                        <li>Tổng giá trị: <b>{selectedTotal.toLocaleString('vi-VN')} ₫</b></li>
                        <li>Kho nhập: <b>{warehouses?.[0]?.name}</b></li>
                    </ul>
                </div>
            ),
            okText: 'Tạo nháp', cancelText: 'Huỷ',
            onOk: () => createPo.mutate({
                warehouse_id: wh,
                rows: selectedRows.map((r) => ({
                    sku_id: r.sku.id, qty: selected[r.sku.id] ?? r.suggested_qty,
                    supplier_id: r.suggested_supplier!.id, unit_cost: r.suggested_unit_cost,
                })),
            }),
        });
    };

    const columns: ColumnsType<Row> = [
        { title: 'SKU', key: 'sku', fixed: 'left', width: 320, render: (_, r) => <SkuLine sku={r.sku} avatarSize={36} maxTextWidth={260} /> },
        { title: 'Mức độ', key: 'urgency', width: 130, fixed: 'left', render: (_, r) => <Tag color={URGENCY_META[r.urgency].color} icon={URGENCY_META[r.urgency].icon}>{URGENCY_META[r.urgency].label}</Tag> },
        { title: 'Bán/ngày', key: 'avg', width: 110, align: 'right', render: (_, r) => <Tooltip title={`${r.sold_in_window} đơn vị bán trong ${r.window_days} ngày qua`}><Typography.Text strong>{r.avg_daily_sold.toFixed(2)}</Typography.Text></Tooltip> },
        { title: 'Tồn khả dụng', dataIndex: 'available', key: 'avail', width: 110, align: 'right', render: (v, r) => <Tooltip title={`Thực có ${r.on_hand} − Đang giữ ${r.reserved}${r.safety_stock ? ` · Tồn an toàn ${r.safety_stock}` : ''}`}><Typography.Text strong style={{ color: v <= r.safety_stock ? '#cf1322' : undefined }}>{v}</Typography.Text></Tooltip> },
        { title: 'Đang về (PO)', dataIndex: 'on_order', key: 'on_order', width: 110, align: 'right', render: (v) => v > 0 ? <Tag color="blue">+{v}</Tag> : <Typography.Text type="secondary">—</Typography.Text> },
        { title: 'Số ngày còn hàng', dataIndex: 'days_left', key: 'days', width: 140, align: 'center', render: (v, r) => (
            <Tooltip title={`Với tốc độ ${r.avg_daily_sold.toFixed(2)}/ngày`}>
                <Progress percent={Math.min(100, Math.round((v / (leadTime + coverDays || 1)) * 100))}
                    size="small" style={{ width: 100 }}
                    strokeColor={r.urgency === 'urgent' ? '#cf1322' : r.urgency === 'soon' ? '#faad14' : '#52c41a'}
                    format={() => v >= 9999 ? '∞' : `${v}d`} />
            </Tooltip>
        ) },
        { title: 'Đề xuất nhập', key: 'qty', width: 140, align: 'right', render: (_, r) => r.suggested_qty > 0 ? (
            <InputNumber size="small" min={0} value={selected[r.sku.id] ?? r.suggested_qty}
                onChange={(v) => setSelected((s) => ({ ...s, [r.sku.id]: Number(v) || 0 }))}
                style={{ width: 110 }} disabled={!r.suggested_supplier} />
        ) : <Typography.Text type="secondary">—</Typography.Text> },
        { title: 'NCC gợi ý', key: 'sup', width: 240, render: (_, r) => r.suggested_supplier ? (
            <Space direction="vertical" size={0}>
                <Typography.Text strong>{r.suggested_supplier.name}</Typography.Text>
                <Typography.Text type="secondary" style={{ fontSize: 12 }}>{r.suggested_supplier.code} · <MoneyText value={r.suggested_unit_cost} />/đv</Typography.Text>
            </Space>
        ) : <Typography.Text type="warning">Chưa có NCC mặc định</Typography.Text> },
        { title: 'Thành tiền', key: 'total', width: 140, align: 'right', render: (_, r) => {
            const qty = selected[r.sku.id] ?? r.suggested_qty;

            return <MoneyText value={qty * r.suggested_unit_cost} strong />;
        } },
    ];

    const rowSelection = canManage ? {
        selectedRowKeys: Object.keys(selected).map(Number),
        onChange: (keys: React.Key[]) => {
            const next: Record<number, number> = {};
            keys.forEach((k) => {
                const id = Number(k);
                const r = rows.find((x) => x.sku.id === id);
                if (r && r.suggested_supplier) next[id] = selected[id] ?? r.suggested_qty;
            });
            setSelected(next);
        },
        getCheckboxProps: (r: Row) => ({ disabled: !r.suggested_supplier || r.suggested_qty <= 0 }),
    } : undefined;

    // Statistic tổng
    const totalUrgent = rows.filter((r) => r.urgency === 'urgent').length;
    const totalSoon = rows.filter((r) => r.urgency === 'soon').length;
    const totalSuggestQty = rows.reduce((s, r) => s + r.suggested_qty, 0);
    const totalSuggestCost = rows.reduce((s, r) => s + r.suggested_cost_total, 0);

    return (
        <div>
            <PageHeader title="Đề xuất nhập hàng" subtitle="Dựa trên tốc độ bán + tồn kho + đang về + tồn an toàn → gợi ý số lượng cần đặt cho từng NCC."
                extra={(
                    <Space>
                        <Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>Tính lại</Button>
                        {canManage && <Button type="primary" icon={<ShoppingCartOutlined />} disabled={selectedCount === 0} onClick={submitPo} loading={createPo.isPending}>Tạo PO nháp ({selectedCount})</Button>}
                    </Space>
                )}
            />

            <Card size="small" style={{ marginBottom: 12 }}>
                <Space wrap size={24}>
                    <Statistic title="Khẩn cấp" value={totalUrgent} valueStyle={{ color: '#cf1322' }} prefix={<WarningOutlined />} />
                    <Statistic title="Sắp hết" value={totalSoon} valueStyle={{ color: '#faad14' }} prefix={<ExclamationCircleOutlined />} />
                    <Statistic title="Tổng SL đề xuất" value={totalSuggestQty} />
                    <Statistic title="Tổng giá trị ƯT" value={totalSuggestCost} formatter={(v) => Number(v).toLocaleString('vi-VN')} suffix="₫" prefix={<FundProjectionScreenOutlined />} />
                </Space>
            </Card>

            <Card style={{ marginBottom: 12 }} size="small">
                <Space wrap>
                    <Space>
                        <Typography.Text type="secondary" style={{ fontSize: 13 }}>Phân tích bán hàng:</Typography.Text>
                        <Radio.Group size="small" value={windowDays} onChange={(e) => setWindowDays(e.target.value)} optionType="button"
                            options={[{ value: 7, label: '7 ngày' }, { value: 14, label: '14 ngày' }, { value: 30, label: '30 ngày' }, { value: 60, label: '60 ngày' }, { value: 90, label: '90 ngày' }]} />
                    </Space>
                    <Space>
                        <Typography.Text type="secondary" style={{ fontSize: 13 }}>Lead time NCC:</Typography.Text>
                        <InputNumber size="small" min={0} max={120} value={leadTime} onChange={(v) => setLeadTime(Number(v) || 0)} addonAfter="ngày" style={{ width: 110 }} />
                    </Space>
                    <Space>
                        <Typography.Text type="secondary" style={{ fontSize: 13 }}>Đệm an toàn:</Typography.Text>
                        <InputNumber size="small" min={0} max={120} value={coverDays} onChange={(v) => setCoverDays(Number(v) || 0)} addonAfter="ngày" style={{ width: 110 }} />
                    </Space>
                </Space>
                <Space wrap style={{ marginTop: 8 }}>
                    <Input.Search allowClear placeholder="Tìm SKU / tên" prefix={<SearchOutlined />} style={{ width: 220 }} onSearch={setQ} />
                    <Radio.Group size="small" value={urgency} onChange={(e) => setUrgency(e.target.value)} optionType="button" buttonStyle="solid"
                        options={[{ value: '', label: 'Tất cả' }, { value: 'urgent', label: 'Khẩn cấp' }, { value: 'soon', label: 'Sắp hết' }]} />
                    <Radio.Group size="small" value={supplierId ?? 0} onChange={(e) => setSupplierId(e.target.value || undefined)} optionType="button"
                        options={[{ value: 0, label: 'Mọi NCC' }, ...(suppliersData?.data ?? []).map((s) => ({ value: s.id, label: s.name }))]} />
                </Space>
            </Card>

            <Card>
                <Table<Row> rowKey={(r) => r.sku.id} size="middle" loading={isFetching} dataSource={rows} columns={columns}
                    rowSelection={rowSelection}
                    scroll={{ x: 1280 }}
                    locale={{ emptyText: <Empty description="Không có SKU nào cần nhập trong khoảng đã chọn." /> }}
                    pagination={{ pageSize: 50, showTotal: (t) => `${t} SKU` }} />
            </Card>
        </div>
    );
}
