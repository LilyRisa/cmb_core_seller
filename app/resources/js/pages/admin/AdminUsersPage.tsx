import { useMemo, useState } from 'react';
import { Card, Input, Radio, Space, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { SearchOutlined, SafetyCertificateOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { useAdminUsers, type AdminUserRow } from '@/lib/admin';

type RoleFilter = 'all' | 'admin';

export function AdminUsersPage() {
    const [q, setQ] = useState('');
    const [kind, setKind] = useState<RoleFilter>('all');
    const [page, setPage] = useState(1);

    const filters = useMemo(() => ({
        q: q.trim() || undefined,
        is_super_admin: kind === 'admin',
        page, per_page: 30,
    }), [q, kind, page]);

    const { data, isLoading, isFetching } = useAdminUsers(filters);

    const columns: ColumnsType<AdminUserRow> = [
        {
            title: 'Người dùng', dataIndex: 'name', key: 'name',
            render: (_v, r) => (
                <Space direction="vertical" size={0}>
                    <Space size={6}>
                        <Typography.Text strong>{r.name}</Typography.Text>
                        {r.is_super_admin && (
                            <Tag color="purple" icon={<SafetyCertificateOutlined />}>Super admin</Tag>
                        )}
                    </Space>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>{r.email}</Typography.Text>
                </Space>
            ),
        },
        {
            title: 'Tenant đang là thành viên', key: 'tenants',
            render: (_v, r) => r.tenants.length === 0
                ? <Typography.Text type="secondary">—</Typography.Text>
                : (
                    <Space size={4} wrap>
                        {r.tenants.map((t) => (
                            <Tag key={t.id}>{t.name} · {t.role}</Tag>
                        ))}
                    </Space>
                ),
        },
        {
            title: 'Tạo lúc', dataIndex: 'created_at', key: 'created_at', width: 140,
            render: (v: string | null) => v ? dayjs(v).format('DD/MM/YYYY') : '—',
        },
    ];

    return (
        <div>
            <PageHeader
                title="Quản trị hệ thống — Người dùng"
                subtitle="Danh sách user toàn hệ thống. Để promote/demote super admin: dùng Artisan `php artisan admin:promote {email}`."
            />

            <Card styles={{ body: { padding: 12 } }}>
                <Space size={12} wrap style={{ marginBottom: 12 }}>
                    <Input prefix={<SearchOutlined />} placeholder="Tìm theo email / tên" allowClear
                        value={q} onChange={(e) => { setQ(e.target.value); setPage(1); }}
                        style={{ width: 280 }} />
                    <Radio.Group value={kind} optionType="button" buttonStyle="solid"
                        onChange={(e) => { setKind(e.target.value as RoleFilter); setPage(1); }}
                        options={[
                            { value: 'all', label: 'Tất cả' },
                            { value: 'admin', label: 'Chỉ super admin' },
                        ]} />
                </Space>

                <Table<AdminUserRow>
                    rowKey="id"
                    columns={columns}
                    dataSource={data?.data ?? []}
                    loading={isLoading || isFetching}
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
