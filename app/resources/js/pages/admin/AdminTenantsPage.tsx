import { useMemo, useState } from 'react';
import { Card, Input, Radio, Space, Table, Tag, Tooltip, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { LockOutlined, SearchOutlined, WarningOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { useAdminTenants, type AdminTenantSummary } from '@/lib/admin';
import { AdminTenantDrawer } from './AdminTenantDrawer';
import dayjs from 'dayjs';

type FilterKind = 'all' | 'over_quota' | 'suspended';

const KIND_OPTIONS: Array<{ value: FilterKind; label: string }> = [
    { value: 'all', label: 'Tất cả' },
    { value: 'over_quota', label: 'Đang vượt mức' },
    { value: 'suspended', label: 'Đang tạm khoá' },
];

export function AdminTenantsPage() {
    const [q, setQ] = useState('');
    const [kind, setKind] = useState<FilterKind>('all');
    const [page, setPage] = useState(1);
    const [openTenantId, setOpenTenantId] = useState<number | null>(null);

    const filters = useMemo(() => ({
        q: q.trim() || undefined,
        over_quota: kind === 'over_quota',
        suspended: kind === 'suspended',
        page, per_page: 30,
    }), [q, kind, page]);

    const { data, isLoading, isFetching } = useAdminTenants(filters);

    const columns: ColumnsType<AdminTenantSummary> = [
        {
            title: 'Gian hàng', dataIndex: 'name', key: 'name',
            render: (_v, r) => (
                <Space direction="vertical" size={0}>
                    <Typography.Text strong>{r.name}</Typography.Text>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                        {r.slug} · từ {r.created_at ? dayjs(r.created_at).format('DD/MM/YYYY') : '—'}
                    </Typography.Text>
                </Space>
            ),
        },
        {
            title: 'Chủ sở hữu', dataIndex: ['owner', 'email'], key: 'owner',
            render: (_v, r) => r.owner ? (
                <Space direction="vertical" size={0}>
                    <Typography.Text>{r.owner.name}</Typography.Text>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>{r.owner.email}</Typography.Text>
                </Space>
            ) : <Typography.Text type="secondary">—</Typography.Text>,
        },
        {
            title: 'Gói', key: 'plan', width: 180,
            render: (_v, r) => r.subscription ? (
                <Space direction="vertical" size={0}>
                    <Tag color={planColor(r.subscription.plan_code)}>{(r.subscription.plan_code ?? '—').toUpperCase()}</Tag>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>{r.subscription.status}</Typography.Text>
                </Space>
            ) : <Typography.Text type="secondary">—</Typography.Text>,
        },
        {
            title: 'Gian hàng đã kết nối', key: 'channels', width: 180,
            render: (_v, r) => {
                const { used, limit, over } = r.usage.channel_accounts;
                const limitLabel = limit < 0 ? '∞' : limit;

                return (
                    <Space size={6}>
                        <Typography.Text strong style={{ color: over ? '#cf1322' : undefined }}>
                            {used} / {limitLabel}
                        </Typography.Text>
                        {over && <Tag color="red" icon={<WarningOutlined />}>Vượt mức</Tag>}
                    </Space>
                );
            },
        },
        {
            title: 'Trạng thái', dataIndex: 'status', key: 'status', width: 160,
            render: (_v, r) => (
                <Space size={4} wrap>
                    <Tag color={r.status === 'suspended' ? 'red' : 'green'}>
                        {r.status === 'suspended' ? 'Tạm khoá' : 'Hoạt động'}
                    </Tag>
                    {r.subscription?.over_quota_warned_at && (
                        <Tooltip title={`Cảnh báo từ ${dayjs(r.subscription.over_quota_warned_at).format('DD/MM HH:mm')}`}>
                            <Tag color={r.subscription.over_quota_locked ? 'red' : 'orange'}
                                icon={r.subscription.over_quota_locked ? <LockOutlined /> : <WarningOutlined />}>
                                {r.subscription.over_quota_locked ? 'Đã khoá' : 'Đếm 48h'}
                            </Tag>
                        </Tooltip>
                    )}
                </Space>
            ),
        },
    ];

    return (
        <div>
            <PageHeader
                title="Quản trị hệ thống — Tenant"
                subtitle="Super-admin có thể xem, hỗ trợ và can thiệp dữ liệu mọi tenant. Mọi thao tác đều ghi audit log."
            />

            <Card styles={{ body: { padding: 12 } }}>
                <Space size={12} wrap style={{ marginBottom: 12 }}>
                    <Input prefix={<SearchOutlined />} placeholder="Tìm theo tên / slug" allowClear
                        value={q} onChange={(e) => { setQ(e.target.value); setPage(1); }}
                        style={{ width: 280 }} />
                    <Radio.Group value={kind} optionType="button" buttonStyle="solid"
                        onChange={(e) => { setKind(e.target.value as FilterKind); setPage(1); }}
                        options={KIND_OPTIONS} />
                </Space>

                <Table<AdminTenantSummary>
                    rowKey="id"
                    columns={columns}
                    dataSource={data?.data ?? []}
                    loading={isLoading || isFetching}
                    onRow={(r) => ({ onClick: () => setOpenTenantId(r.id), style: { cursor: 'pointer' } })}
                    pagination={{
                        current: data?.meta.pagination.page ?? 1,
                        pageSize: data?.meta.pagination.per_page ?? 30,
                        total: data?.meta.pagination.total ?? 0,
                        onChange: (p) => setPage(p),
                        showSizeChanger: false,
                    }}
                    size="middle"
                />
            </Card>

            <AdminTenantDrawer tenantId={openTenantId} onClose={() => setOpenTenantId(null)} />
        </div>
    );
}

function planColor(code: string | null | undefined): string {
    return ({ trial: 'default', starter: 'blue', pro: 'purple', business: 'gold' } as Record<string, string>)[code ?? ''] ?? 'default';
}
