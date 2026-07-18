import { useMemo, useState } from 'react';
import { Card, DatePicker, Drawer, Input, Segmented, Space, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { formatDateTimeSeconds } from '@/lib/format';
import { useAdminInvoices, type AdminInvoiceHistoryRow, type AdminPayment } from '@admin/lib/admin';

const { RangePicker } = DatePicker;

const STATUS_OPTIONS = [
    { value: '', label: 'Tất cả' },
    { value: 'pending', label: 'Chờ' },
    { value: 'paid', label: 'Đã thanh toán' },
    { value: 'void', label: 'Hủy' },
    { value: 'refunded', label: 'Hoàn tiền' },
];

const STATUS_COLOR: Record<string, string> = {
    pending: 'orange', paid: 'green', void: 'default', refunded: 'red',
};

function formatMoney(v: number): string {
    return new Intl.NumberFormat('vi-VN').format(v) + ' đ';
}

export function AdminInvoicesPage() {
    const [status, setStatus] = useState('');
    const [tenantId, setTenantId] = useState('');
    const [q, setQ] = useState('');
    const [range, setRange] = useState<[dayjs.Dayjs, dayjs.Dayjs] | null>(null);
    const [page, setPage] = useState(1);
    const [openRow, setOpenRow] = useState<AdminInvoiceHistoryRow | null>(null);

    const filters = useMemo(() => ({
        status: status || undefined,
        tenant_id: tenantId ? Number(tenantId) : undefined,
        q: q || undefined,
        date_from: range?.[0]?.toISOString(),
        date_to: range?.[1]?.toISOString(),
        page, per_page: 20,
    }), [status, tenantId, q, range, page]);

    const { data, isFetching } = useAdminInvoices(filters);

    const columns: ColumnsType<AdminInvoiceHistoryRow> = [
        { title: 'Mã HD', dataIndex: 'code', render: (v: string) => <span style={{ fontFamily: 'ui-monospace, monospace' }}>{v}</span> },
        {
            title: 'Shop', dataIndex: 'tenant',
            render: (t: AdminInvoiceHistoryRow['tenant']) => t ? `#${t.id} · ${t.name}` : '—',
        },
        { title: 'Số tiền', dataIndex: 'total', render: (v: number) => formatMoney(v) },
        {
            title: 'Trạng thái', dataIndex: 'status',
            render: (v: string) => <Tag color={STATUS_COLOR[v] ?? 'default'}>{STATUS_OPTIONS.find((o) => o.value === v)?.label ?? v}</Tag>,
        },
        { title: 'Tạo lúc', dataIndex: 'created_at', render: (v: string | null) => formatDateTimeSeconds(v) },
        { title: 'Hạn', dataIndex: 'due_at', render: (v: string | null) => formatDateTimeSeconds(v) },
        { title: 'Thanh toán lúc', dataIndex: 'paid_at', render: (v: string | null) => (v ? formatDateTimeSeconds(v) : '—') },
        { title: '', key: 'open', width: 80, render: (_, r) => <a onClick={() => setOpenRow(r)}>Xem</a> },
    ];

    const paymentColumns: ColumnsType<AdminPayment> = [
        { title: 'Cổng', dataIndex: 'gateway' },
        { title: 'Số tiền', dataIndex: 'amount', render: (v: number) => formatMoney(v) },
        {
            title: 'Trạng thái', dataIndex: 'status',
            render: (v: string) => <Tag color={v === 'succeeded' ? 'green' : v === 'failed' ? 'red' : v === 'refunded' ? 'orange' : 'default'}>{v}</Tag>,
        },
        { title: 'Lúc', dataIndex: 'occurred_at', render: (v: string | null) => (v ? formatDateTimeSeconds(v) : '—') },
    ];

    return (
        <>
            <PageHeader title="Lịch sử thanh toán" subtitle="Hóa đơn xuyên mọi shop, gồm cả yêu cầu đã mở nhưng chưa hoàn thành (Chờ)." />
            <Card>
                <Space style={{ marginBottom: 12 }} wrap>
                    <Segmented options={STATUS_OPTIONS} value={status} onChange={(v) => { setStatus(v as string); setPage(1); }} />
                    <Input placeholder="tenant_id" value={tenantId} onChange={(e) => { setTenantId(e.target.value); setPage(1); }} style={{ width: 120 }} />
                    <Input.Search placeholder="Tìm mã hóa đơn" value={q} onChange={(e) => setQ(e.target.value)} onSearch={() => setPage(1)} style={{ width: 220 }} allowClear />
                    <RangePicker showTime onChange={(v) => { setRange(v as [dayjs.Dayjs, dayjs.Dayjs] | null); setPage(1); }} />
                </Space>

                <Table
                    rowKey="id" size="small"
                    loading={isFetching}
                    columns={columns}
                    dataSource={data?.data ?? []}
                    pagination={{ current: page, pageSize: 20, total: data?.meta.pagination.total ?? 0, showSizeChanger: false, onChange: setPage }}
                />
            </Card>

            <Drawer
                open={openRow != null}
                onClose={() => setOpenRow(null)}
                width={640}
                title={openRow ? openRow.code : 'Chi tiết hóa đơn'}
            >
                {openRow && (
                    <>
                        <Typography.Paragraph><strong>Shop:</strong> {openRow.tenant ? `#${openRow.tenant.id} · ${openRow.tenant.name}` : '—'}</Typography.Paragraph>
                        <Typography.Paragraph><strong>Trạng thái:</strong> <Tag color={STATUS_COLOR[openRow.status] ?? 'default'}>{STATUS_OPTIONS.find((o) => o.value === openRow.status)?.label ?? openRow.status}</Tag></Typography.Paragraph>
                        <Typography.Paragraph><strong>Số tiền:</strong> {formatMoney(openRow.total)}</Typography.Paragraph>
                        <Typography.Paragraph><strong>Kỳ:</strong> {openRow.period_start} → {openRow.period_end}</Typography.Paragraph>
                        <Typography.Paragraph><strong>Tạo lúc:</strong> {formatDateTimeSeconds(openRow.created_at)}</Typography.Paragraph>
                        <Typography.Paragraph><strong>Hạn thanh toán:</strong> {formatDateTimeSeconds(openRow.due_at)}</Typography.Paragraph>
                        <Typography.Paragraph><strong>Thanh toán lúc:</strong> {openRow.paid_at ? formatDateTimeSeconds(openRow.paid_at) : '—'}</Typography.Paragraph>

                        <Typography.Title level={5}>Các lần thanh toán</Typography.Title>
                        <Table
                            rowKey="id" size="small" pagination={false}
                            columns={paymentColumns}
                            dataSource={openRow.payments}
                            locale={{ emptyText: 'Chưa có lần thanh toán nào' }}
                        />
                    </>
                )}
            </Drawer>
        </>
    );
}
