import { useMemo, useState } from 'react';
import type { Key } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button, Card, Input, Radio, Space, Table, Tag, Tooltip, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import {
    CheckCircleOutlined, ExclamationCircleOutlined, LockOutlined, SearchOutlined, SendOutlined, WarningOutlined,
} from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { useAdminTenants, type AdminTenantSummary } from '@admin/lib/admin';
import { formatDate, formatDateShort } from '@/lib/format';

type FilterKind = 'all' | 'over_quota' | 'suspended';

const KIND_OPTIONS: Array<{ value: FilterKind; label: string }> = [
    { value: 'all', label: 'Tất cả' },
    { value: 'over_quota', label: 'Đang vượt mức' },
    { value: 'suspended', label: 'Đang tạm khoá' },
];

// Tổng chiều rộng cột thực tế cho scroll={{x}}: 48 (checkbox) + 240 (Gian hàng) + 220
// (Chủ sở hữu) + 150 (Xác minh email) + 180 (Gói) + 180 (Gian hàng đã kết nối) + 160 (Trạng thái)
// = 1178, làm tròn lên 1180 cho phần đệm border/padding của ô.
const TABLE_SCROLL_X = 1180;

export function AdminTenantsPage() {
    const navigate = useNavigate();
    const [q, setQ] = useState('');
    const [kind, setKind] = useState<FilterKind>('all');
    const [page, setPage] = useState(1);
    const [selectedRowKeys, setSelectedRowKeys] = useState<Key[]>([]);
    const [selectedRows, setSelectedRows] = useState<AdminTenantSummary[]>([]);

    const filters = useMemo(() => ({
        q: q.trim() || undefined,
        over_quota: kind === 'over_quota',
        suspended: kind === 'suspended',
        page, per_page: 30,
    }), [q, kind, page]);

    const { data, isLoading, isFetching } = useAdminTenants(filters);

    const columns: ColumnsType<AdminTenantSummary> = [
        {
            title: 'Gian hàng', dataIndex: 'name', key: 'name', width: 240,
            render: (_v, r) => (
                <Space direction="vertical" size={0}>
                    <Typography.Text strong>{r.name}</Typography.Text>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                        {r.slug} · từ {formatDate(r.created_at, false)}
                    </Typography.Text>
                </Space>
            ),
        },
        {
            title: 'Chủ sở hữu', dataIndex: ['owner', 'email'], key: 'owner', width: 220,
            render: (_v, r) => r.owner ? (
                <Space direction="vertical" size={0}>
                    <Typography.Text>{r.owner.name}</Typography.Text>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>{r.owner.email}</Typography.Text>
                </Space>
            ) : <Typography.Text type="secondary">—</Typography.Text>,
        },
        {
            title: 'Xác minh email', key: 'email_verified', width: 150,
            render: (_v, r) => {
                if (!r.owner) return <Typography.Text type="secondary">—</Typography.Text>;
                return r.owner.email_verified_at ? (
                    <Tooltip title={`Xác minh ${formatDateShort(r.owner.email_verified_at)}`}>
                        <Tag color="green" icon={<CheckCircleOutlined />}>Đã xác minh</Tag>
                    </Tooltip>
                ) : (
                    <Tag color="orange" icon={<ExclamationCircleOutlined />}>Chưa xác minh</Tag>
                );
            },
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
                        <Tooltip title={`Cảnh báo từ ${formatDateShort(r.subscription.over_quota_warned_at)}`}>
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

                <Space size={12} wrap style={{ marginBottom: 12 }}>
                    <Button
                        icon={<SendOutlined />}
                        disabled={selectedRows.length === 0}
                        onClick={() => navigate('/admin/broadcasts', { state: { presetTenants: selectedRows } })}
                    >
                        {selectedRows.length > 0
                            ? `Gửi broadcast cho ${selectedRows.length} tenant đã chọn`
                            : 'Gửi broadcast cho tenant đã chọn'}
                    </Button>
                </Space>

                <Table<AdminTenantSummary>
                    rowKey="id"
                    columns={columns}
                    dataSource={data?.data ?? []}
                    loading={isLoading || isFetching}
                    onRow={(r) => ({ onClick: () => navigate(`/admin/tenants/${r.id}`), style: { cursor: 'pointer' } })}
                    rowSelection={{
                        selectedRowKeys,
                        columnWidth: 48,
                        onChange: (keys, rows) => { setSelectedRowKeys(keys); setSelectedRows(rows); },
                    }}
                    scroll={{ x: TABLE_SCROLL_X }}
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
        </div>
    );
}

function planColor(code: string | null | undefined): string {
    return ({ trial: 'default', starter: 'blue', pro: 'purple', business: 'gold' } as Record<string, string>)[code ?? ''] ?? 'default';
}
