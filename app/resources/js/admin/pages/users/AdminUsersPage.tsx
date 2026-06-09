// Spec 2026-05-17 — `/admin/users` quản lý cả super-admin và tenant user.
// Tabs (Radio.Group/Segmented style theo memory `ui-avoid-select-prefer-radio`).

import { useState } from 'react';
import { Card, Tabs, Input, Space, Table, Tag, Button, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { SearchOutlined, PlusOutlined } from '@ant-design/icons';
import { formatDate } from '@/lib/format';
import { useAdminUsersList, type AdminRow } from '../../lib/adminUsers';
import { useTenantUsers, type TenantUserRow } from '../../lib/tenantUsers';
import { AdminUserFormDrawer } from './AdminUserFormDrawer';
import { TenantUserDrawer } from './TenantUserDrawer';

export function AdminUsersPage() {
    const [tab, setTab] = useState<'admin' | 'tenant'>('admin');
    const [q, setQ] = useState('');
    const [page, setPage] = useState(1);
    const [editingAdmin, setEditingAdmin] = useState<AdminRow | 'new' | null>(null);
    const [openTenantUserId, setOpenTenantUserId] = useState<number | null>(null);

    const adminQuery = useAdminUsersList({ q: q || undefined, page, per_page: 30 });
    const tenantQuery = useTenantUsers({ q: q || undefined, page, per_page: 30 });

    const adminCols: ColumnsType<AdminRow> = [
        {
            title: 'Username',
            dataIndex: 'username',
            render: (v: string, r) => (
                <a onClick={() => setEditingAdmin(r)}>{v}</a>
            ),
        },
        { title: 'Tên', dataIndex: 'name' },
        {
            title: 'Email',
            dataIndex: 'email',
            render: (v: string | null) => v || <Typography.Text type="secondary">—</Typography.Text>,
        },
        {
            title: 'Hoạt động',
            dataIndex: 'is_active',
            width: 110,
            render: (v: boolean) => (v ? <Tag color="green">active</Tag> : <Tag color="red">suspended</Tag>),
        },
        {
            title: 'Login gần nhất',
            dataIndex: 'last_login_at',
            width: 160,
            render: (v: string | null) => formatDate(v),
        },
    ];

    const tenantCols: ColumnsType<TenantUserRow> = [
        {
            title: 'Tên',
            dataIndex: 'name',
            render: (v: string, r) => <a onClick={() => setOpenTenantUserId(r.id)}>{v}</a>,
        },
        { title: 'Email', dataIndex: 'email' },
        {
            title: 'Xác minh email',
            dataIndex: 'email_verified_at',
            width: 130,
            render: (v: string | null) => (v
                ? <Tag color="green">Đã xác minh</Tag>
                : <Tag color="red">Chưa xác minh</Tag>),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'suspended_at',
            width: 110,
            render: (v: string | null) => (v
                ? <Tag color="default">Đã khoá</Tag>
                : <Tag color="blue">Hoạt động</Tag>),
        },
        {
            title: 'Tenant',
            dataIndex: 'tenants',
            render: (ts: TenantUserRow['tenants']) => (
                <Space size={4} wrap>
                    {ts.length === 0
                        ? <Typography.Text type="secondary">—</Typography.Text>
                        : ts.map((t) => <Tag key={t.id}>{t.name} · {t.role}</Tag>)}
                </Space>
            ),
        },
        {
            title: 'Tạo lúc',
            dataIndex: 'created_at',
            width: 130,
            render: (v: string | null) => formatDate(v, false),
        },
    ];

    return (
        <Card styles={{ body: { padding: 12 } }}>
            <Space style={{ marginBottom: 12 }} wrap>
                <Input
                    prefix={<SearchOutlined />}
                    placeholder="Tìm theo tên / username / email"
                    allowClear
                    value={q}
                    onChange={(e) => {
                        setQ(e.target.value);
                        setPage(1);
                    }}
                    style={{ width: 320 }}
                />
                {tab === 'admin' && (
                    <Button
                        type="primary"
                        icon={<PlusOutlined />}
                        onClick={() => setEditingAdmin('new')}
                    >
                        Thêm super-admin
                    </Button>
                )}
            </Space>

            <Tabs
                activeKey={tab}
                onChange={(k) => {
                    setTab(k as 'admin' | 'tenant');
                    setPage(1);
                }}
                items={[
                    {
                        key: 'admin',
                        label: `Super-admin (${adminQuery.data?.meta.pagination.total ?? 0})`,
                        children: (
                            <Table<AdminRow>
                                rowKey="id"
                                columns={adminCols}
                                dataSource={adminQuery.data?.data ?? []}
                                loading={adminQuery.isLoading}
                                pagination={{
                                    current: adminQuery.data?.meta.pagination.page ?? page,
                                    pageSize: adminQuery.data?.meta.pagination.per_page ?? 30,
                                    total: adminQuery.data?.meta.pagination.total ?? 0,
                                    onChange: setPage,
                                    showSizeChanger: false,
                                }}
                            />
                        ),
                    },
                    {
                        key: 'tenant',
                        label: `Người dùng tenant (${tenantQuery.data?.meta.pagination.total ?? 0})`,
                        children: (
                            <Table<TenantUserRow>
                                rowKey="id"
                                columns={tenantCols}
                                dataSource={tenantQuery.data?.data ?? []}
                                loading={tenantQuery.isLoading}
                                pagination={{
                                    current: tenantQuery.data?.meta.pagination.page ?? page,
                                    pageSize: tenantQuery.data?.meta.pagination.per_page ?? 30,
                                    total: tenantQuery.data?.meta.pagination.total ?? 0,
                                    onChange: setPage,
                                    showSizeChanger: false,
                                }}
                            />
                        ),
                    },
                ]}
            />

            <AdminUserFormDrawer
                open={editingAdmin !== null}
                target={editingAdmin}
                onClose={() => setEditingAdmin(null)}
            />
            <TenantUserDrawer userId={openTenantUserId} onClose={() => setOpenTenantUserId(null)} />
        </Card>
    );
}
