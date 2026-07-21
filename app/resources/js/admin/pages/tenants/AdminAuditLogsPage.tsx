import { useMemo, useState } from 'react';
import { AutoComplete, Card, DatePicker, Drawer, Empty, Input, Space, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { formatDateTimeSeconds } from '@/lib/format';
import { useAdminAuditLogs, type AdminAuditLogRow } from '@admin/lib/admin';
import { TenantPicker } from '@admin/components/TenantPicker';
import { AUDIT_ACTION_CODES } from '@admin/lib/auditActionCodes';

const ACTION_OPTIONS = AUDIT_ACTION_CODES.map((code) => ({ value: code }));

const { RangePicker } = DatePicker;

export function AdminAuditLogsPage() {
    const [action, setAction] = useState('');
    const [tenantId, setTenantId] = useState<number | undefined>(undefined);
    const [userId, setUserId] = useState<string>('');
    const [range, setRange] = useState<[dayjs.Dayjs, dayjs.Dayjs] | null>(null);
    const [page, setPage] = useState(1);
    const [openRow, setOpenRow] = useState<AdminAuditLogRow | null>(null);

    const filters = useMemo(() => ({
        action: action || undefined,
        tenant_id: tenantId,
        user_id: userId ? Number(userId) : undefined,
        from: range?.[0]?.toISOString(),
        to: range?.[1]?.toISOString(),
        page, per_page: 50,
    }), [action, tenantId, userId, range, page]);

    const { data, isFetching } = useAdminAuditLogs(filters);

    const columns: ColumnsType<AdminAuditLogRow> = [
        {
            title: 'Thời gian', dataIndex: 'created_at', width: 160,
            render: (v: string | null) => formatDateTimeSeconds(v),
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
                    <AutoComplete
                        options={ACTION_OPTIONS}
                        value={action}
                        onChange={(v) => { setAction(v); setPage(1); }}
                        filterOption={(inputValue, option) =>
                            ((option?.value as string) ?? '').toLowerCase().includes(inputValue.toLowerCase())
                        }
                        placeholder="Action (gõ hoặc chọn, vd admin.* hoặc admin.voucher.create)"
                        allowClear
                        style={{ width: 280 }}
                    />
                    <TenantPicker value={tenantId} onChange={(v) => { setTenantId(v); setPage(1); }} placeholder="Tenant (mã/tên/email)" style={{ width: 220 }} />
                    <Input placeholder="user_id" value={userId} onChange={(e) => { setUserId(e.target.value); setPage(1); }} style={{ width: 120 }} />
                    <RangePicker showTime onChange={(v) => { setRange(v as [dayjs.Dayjs, dayjs.Dayjs] | null); setPage(1); }} />
                </Space>

                <Table
                    rowKey="id" size="small"
                    loading={isFetching}
                    columns={columns}
                    dataSource={data?.data ?? []}
                    pagination={{ current: page, pageSize: 50, total: data?.meta.pagination.total ?? 0, showSizeChanger: false, onChange: setPage }}
                    locale={{ emptyText: <Empty description="Chưa có nhật ký nào khớp bộ lọc." /> }}
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
                        <Typography.Paragraph><strong>Created at:</strong> {formatDateTimeSeconds(openRow.created_at)}</Typography.Paragraph>
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
