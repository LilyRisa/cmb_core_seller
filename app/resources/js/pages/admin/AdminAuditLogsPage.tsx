import { useMemo, useState } from 'react';
import { Card, DatePicker, Drawer, Input, Space, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { useAdminAuditLogs, type AdminAuditLogRow } from '@/lib/admin';

const { RangePicker } = DatePicker;

export function AdminAuditLogsPage() {
    const [action, setAction] = useState('');
    const [tenantId, setTenantId] = useState<string>('');
    const [userId, setUserId] = useState<string>('');
    const [range, setRange] = useState<[dayjs.Dayjs, dayjs.Dayjs] | null>(null);
    const [page, setPage] = useState(1);
    const [openRow, setOpenRow] = useState<AdminAuditLogRow | null>(null);

    const filters = useMemo(() => ({
        action: action || undefined,
        tenant_id: tenantId ? Number(tenantId) : undefined,
        user_id: userId ? Number(userId) : undefined,
        from: range?.[0]?.toISOString(),
        to: range?.[1]?.toISOString(),
        page, per_page: 50,
    }), [action, tenantId, userId, range, page]);

    const { data, isFetching } = useAdminAuditLogs(filters);

    const columns: ColumnsType<AdminAuditLogRow> = [
        {
            title: 'Thời gian', dataIndex: 'created_at', width: 160,
            render: (v: string | null) => v ? dayjs(v).format('DD/MM/YYYY HH:mm:ss') : '—',
        },
        {
            title: 'Action', dataIndex: 'action',
            render: (v: string) => <Tag color={v.startsWith('admin.') ? 'blue' : 'default'} style={{ fontFamily: 'ui-monospace, monospace' }}>{v}</Tag>,
        },
        {
            title: 'Tenant', dataIndex: 'tenant',
            render: (t: AdminAuditLogRow['tenant']) => t ? `#${t.id} · ${t.name}` : '—',
        },
        {
            title: 'User', dataIndex: 'user',
            render: (u: AdminAuditLogRow['user']) => u ? `${u.name} (${u.email})` : '—',
        },
        {
            title: 'IP', dataIndex: 'ip', width: 120,
            render: (v: string | null) => v ?? '—',
        },
        {
            title: '', key: 'open', width: 80,
            render: (_, r) => <a onClick={() => setOpenRow(r)}>Xem</a>,
        },
    ];

    return (
        <>
            <PageHeader title="Audit log" subtitle="Search xuyên tenant. Action `admin.*` để xem mọi thao tác super-admin." />
            <Card>
                <Space style={{ marginBottom: 12 }} wrap>
                    <Input
                        placeholder="action (vd admin.* hoặc admin.voucher.create)"
                        value={action}
                        onChange={(e) => { setAction(e.target.value); setPage(1); }}
                        style={{ width: 280 }}
                    />
                    <Input placeholder="tenant_id" value={tenantId} onChange={(e) => { setTenantId(e.target.value); setPage(1); }} style={{ width: 120 }} />
                    <Input placeholder="user_id" value={userId} onChange={(e) => { setUserId(e.target.value); setPage(1); }} style={{ width: 120 }} />
                    <RangePicker showTime onChange={(v) => { setRange(v as [dayjs.Dayjs, dayjs.Dayjs] | null); setPage(1); }} />
                </Space>

                <Table
                    rowKey="id" size="small"
                    loading={isFetching}
                    columns={columns}
                    dataSource={data?.data ?? []}
                    pagination={{ current: page, pageSize: 50, total: data?.meta.pagination.total ?? 0, showSizeChanger: false, onChange: setPage }}
                />
            </Card>

            <Drawer
                open={openRow != null}
                onClose={() => setOpenRow(null)}
                width={640}
                title={openRow ? openRow.action : 'Chi tiết log'}
            >
                {openRow && (
                    <>
                        <Typography.Paragraph><strong>Tenant:</strong> {openRow.tenant ? `#${openRow.tenant.id} · ${openRow.tenant.name}` : '—'}</Typography.Paragraph>
                        <Typography.Paragraph><strong>User:</strong> {openRow.user ? `${openRow.user.name} (${openRow.user.email})` : '—'}</Typography.Paragraph>
                        <Typography.Paragraph><strong>IP:</strong> {openRow.ip ?? '—'}</Typography.Paragraph>
                        <Typography.Paragraph><strong>Created at:</strong> {openRow.created_at ? dayjs(openRow.created_at).format('DD/MM/YYYY HH:mm:ss') : '—'}</Typography.Paragraph>
                        <Typography.Title level={5}>Changes</Typography.Title>
                        <pre style={{ background: '#0F172A', color: '#E2E8F0', padding: 12, borderRadius: 6, overflow: 'auto', maxHeight: 400 }}>
                            {JSON.stringify(openRow.changes, null, 2)}
                        </pre>
                    </>
                )}
            </Drawer>
        </>
    );
}
