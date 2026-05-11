import { Alert, Button, Card, Form, Input, Select, Space, Table, Tag, Typography } from 'antd';
import { UserAddOutlined } from '@ant-design/icons';
import { App as AntApp } from 'antd';
import { errorMessage } from '@/lib/api';
import { ROLES, roleLabel, useAddMember, useCan, useTenant, useTenantMembers, type RoleValue, type TenantMember } from '@/lib/tenant';

const ROLE_COLOR: Record<string, string> = {
    owner: 'gold',
    admin: 'geekblue',
    staff_order: 'blue',
    staff_warehouse: 'cyan',
    accountant: 'green',
    viewer: 'default',
};

export function SettingsMembersPage() {
    const { message } = AntApp.useApp();
    const { data: tenant } = useTenant();
    const members = useTenantMembers();
    const addMember = useAddMember();
    const canManage = useCan('tenant.member.add') || tenant?.current_role === 'owner' || tenant?.current_role === 'admin';
    const [form] = Form.useForm<{ email: string; role: RoleValue }>();

    const columns = [
        { title: 'Tên', dataIndex: 'name', key: 'name' },
        { title: 'Email', dataIndex: 'email', key: 'email' },
        {
            title: 'Vai trò',
            dataIndex: 'role',
            key: 'role',
            render: (role: string) => <Tag color={ROLE_COLOR[role] ?? 'default'}>{roleLabel(role)}</Tag>,
        },
    ];

    return (
        <div>
            <Typography.Title level={3}>Thành viên & phân quyền</Typography.Title>
            <Typography.Paragraph type="secondary">
                Workspace hiện tại: <b>{tenant?.name ?? '—'}</b>. Quy ước vai trò: xem <code>docs/01-architecture/multi-tenancy-and-rbac.md</code>.
            </Typography.Paragraph>

            {canManage && (
                <Card title="Thêm thành viên" style={{ marginBottom: 16 }}>
                    <Alert
                        type="info"
                        showIcon
                        style={{ marginBottom: 16 }}
                        message="Người được thêm phải đã có tài khoản. Luồng mời qua email (gửi link cho người chưa có tài khoản) sẽ bổ sung sau."
                    />
                    {addMember.isError && (
                        <Alert type="error" showIcon style={{ marginBottom: 16 }} message={errorMessage(addMember.error, 'Không thêm được thành viên.')} />
                    )}
                    <Form
                        layout="inline"
                        form={form}
                        onFinish={(v) =>
                            addMember.mutate(v, {
                                onSuccess: () => {
                                    message.success('Đã thêm thành viên.');
                                    form.resetFields();
                                },
                            })
                        }
                    >
                        <Form.Item name="email" rules={[{ required: true, type: 'email', message: 'Nhập email hợp lệ' }]}>
                            <Input placeholder="email@vidu.com" style={{ width: 240 }} />
                        </Form.Item>
                        <Form.Item name="role" initialValue="viewer" rules={[{ required: true }]}>
                            <Select style={{ width: 180 }} options={ROLES.map((r) => ({ value: r.value, label: r.label }))} />
                        </Form.Item>
                        <Form.Item>
                            <Button type="primary" htmlType="submit" icon={<UserAddOutlined />} loading={addMember.isPending}>
                                Thêm
                            </Button>
                        </Form.Item>
                    </Form>
                </Card>
            )}

            <Card title="Danh sách thành viên">
                {members.isError ? (
                    <Alert type="error" showIcon message={errorMessage(members.error, 'Không tải được danh sách thành viên.')} />
                ) : (
                    <Table<TenantMember>
                        rowKey="id"
                        loading={members.isLoading}
                        dataSource={members.data ?? []}
                        columns={columns}
                        pagination={false}
                    />
                )}
            </Card>

            <Space style={{ marginTop: 16 }} />
        </div>
    );
}
