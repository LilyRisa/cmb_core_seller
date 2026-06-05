import { useEffect, useMemo, useState } from 'react';
import {
    Alert, App as AntApp, Button, Card, Checkbox, Empty, Form, Input, Modal, Popconfirm,
    Radio, Space, Table, Tabs, Tag, Typography,
} from 'antd';
import {
    DeleteOutlined, EditOutlined, KeyOutlined, PlusOutlined, SafetyOutlined, UserAddOutlined,
} from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { errorMessage } from '@/lib/api';
import {
    useAddExistingMember, useCreateRole, useCreateSubAccount, useDeleteRole, usePermissionCatalog,
    useRemoveMember, useResetMemberPassword, useRoles, useTenant, useTenantMembers, useUpdateMemberRole,
    useUpdateRole, type PermissionGroup, type TenantMember, type TenantRole,
} from '@/lib/tenant';

const { Text, Title, Paragraph } = Typography;

export function SettingsMembersPage() {
    const { data: tenant } = useTenant();
    const canManage = tenant?.can_manage_team ?? false;

    return (
        <div>
            <Title level={3}>Thành viên & phân quyền</Title>
            <Paragraph type="secondary">
                Workspace: <b>{tenant?.name ?? '—'}</b>
                {tenant?.code && <> · Mã shop: <Tag style={{ fontFamily: 'monospace' }}>{tenant.code}</Tag></>}
            </Paragraph>

            {!canManage ? (
                <Alert type="warning" showIcon message="Bạn không có quyền quản lý thành viên & vai trò." />
            ) : (
                <Tabs
                    items={[
                        { key: 'members', label: 'Thành viên', children: <MembersTab shopCode={tenant?.code ?? ''} /> },
                        { key: 'roles', label: 'Vai trò & quyền', children: <RolesTab /> },
                    ]}
                />
            )}
        </div>
    );
}

// --- Members ---------------------------------------------------------------

function MembersTab({ shopCode }: { shopCode: string }) {
    const { message } = AntApp.useApp();
    const members = useTenantMembers();
    const roles = useRoles();
    const removeMember = useRemoveMember();
    const [subOpen, setSubOpen] = useState(false);
    const [emailOpen, setEmailOpen] = useState(false);
    const [editing, setEditing] = useState<TenantMember | null>(null);
    const [resetting, setResetting] = useState<TenantMember | null>(null);

    const assignableRoles = (roles.data ?? []).filter((r) => !r.is_owner);

    const columns: ColumnsType<TenantMember> = [
        { title: 'Tên', dataIndex: 'name', key: 'name' },
        {
            title: 'Tài khoản', key: 'login',
            render: (_, m) => m.username
                ? <Text style={{ fontFamily: 'monospace' }}>{m.username}</Text>
                : <Text type="secondary">{m.email}</Text>,
        },
        { title: 'Vai trò', key: 'role', render: (_, m) => m.role_name ? <Tag color="blue">{m.role_name}</Tag> : <Tag>—</Tag> },
        { title: 'Loại', key: 'type', render: (_, m) => m.is_sub_account ? <Tag color="purple">Tài khoản phụ</Tag> : <Tag>Chính</Tag> },
        {
            title: '', key: 'actions', align: 'right',
            render: (_, m) => (
                <Space>
                    <Button size="small" icon={<EditOutlined />} onClick={() => setEditing(m)}>Vai trò</Button>
                    {m.is_sub_account && <Button size="small" icon={<KeyOutlined />} onClick={() => setResetting(m)}>Mật khẩu</Button>}
                    <Popconfirm title="Gỡ thành viên này?" okText="Gỡ" cancelText="Huỷ" okButtonProps={{ danger: true }}
                        onConfirm={() => removeMember.mutate(m.id, { onSuccess: () => message.success('Đã gỡ thành viên.') })}>
                        <Button size="small" danger icon={<DeleteOutlined />} />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <div>
            <Space style={{ marginBottom: 12 }} wrap>
                <Button type="primary" icon={<UserAddOutlined />} onClick={() => setSubOpen(true)}>Tạo tài khoản phụ</Button>
                <Button icon={<PlusOutlined />} onClick={() => setEmailOpen(true)}>Thêm bằng email</Button>
            </Space>
            <Alert type="info" showIcon style={{ marginBottom: 12 }}
                message={<>Tài khoản phụ không cần email — đăng nhập bằng <b>{'{tên}'}@{shopCode || 'mãshop'}</b> và mật khẩu do bạn đặt.</>} />

            {members.isError ? (
                <Alert type="error" showIcon message={errorMessage(members.error, 'Không tải được danh sách.')} />
            ) : (
                <Table<TenantMember> rowKey="id" loading={members.isLoading} dataSource={members.data ?? []} columns={columns} pagination={false} />
            )}

            <SubAccountModal open={subOpen} shopCode={shopCode} roles={assignableRoles} onClose={() => setSubOpen(false)} />
            <AddEmailModal open={emailOpen} roles={assignableRoles} onClose={() => setEmailOpen(false)} />
            <EditRoleModal member={editing} roles={assignableRoles} onClose={() => setEditing(null)} />
            <ResetPasswordModal member={resetting} onClose={() => setResetting(null)} />
        </div>
    );
}

function SubAccountModal({ open, shopCode, roles, onClose }: { open: boolean; shopCode: string; roles: TenantRole[]; onClose: () => void }) {
    const { message } = AntApp.useApp();
    const create = useCreateSubAccount();
    const [form] = Form.useForm<{ name: string; password: string; role_id: number }>();
    useEffect(() => { if (open) form.resetFields(); }, [open, form]);

    return (
        <Modal title="Tạo tài khoản phụ" open={open} onCancel={onClose} okText="Tạo" confirmLoading={create.isPending}
            onOk={() => form.submit()}>
            {create.isError && <Alert type="error" showIcon style={{ marginBottom: 12 }} message={errorMessage(create.error, 'Không tạo được.')} />}
            <Form form={form} layout="vertical" onFinish={(v) => create.mutate(v, {
                onSuccess: (m) => { message.success(`Đã tạo ${m.username}`); onClose(); },
            })}>
                <Form.Item name="name" label="Tên nhân viên" rules={[{ required: true, message: 'Nhập tên' }]}>
                    <Input placeholder="Nguyễn Văn A" addonAfter={shopCode ? `@${shopCode}` : undefined} />
                </Form.Item>
                <Form.Item name="password" label="Mật khẩu đăng nhập" rules={[{ required: true, min: 6, message: 'Tối thiểu 6 ký tự' }]}>
                    <Input.Password placeholder="Mật khẩu cho nhân viên" />
                </Form.Item>
                <Form.Item name="role_id" label="Vai trò" rules={[{ required: true, message: 'Chọn vai trò' }]}>
                    <Radio.Group options={roles.map((r) => ({ value: r.id, label: r.name }))} />
                </Form.Item>
            </Form>
        </Modal>
    );
}

function AddEmailModal({ open, roles, onClose }: { open: boolean; roles: TenantRole[]; onClose: () => void }) {
    const { message } = AntApp.useApp();
    const add = useAddExistingMember();
    const [form] = Form.useForm<{ email: string; role_id: number }>();
    useEffect(() => { if (open) form.resetFields(); }, [open, form]);

    return (
        <Modal title="Thêm thành viên bằng email" open={open} onCancel={onClose} okText="Thêm" confirmLoading={add.isPending}
            onOk={() => form.submit()}>
            {add.isError && <Alert type="error" showIcon style={{ marginBottom: 12 }} message={errorMessage(add.error, 'Không thêm được.')} />}
            <Alert type="info" showIcon style={{ marginBottom: 12 }} message="Người được thêm phải đã có tài khoản (đăng ký bằng email)." />
            <Form form={form} layout="vertical" onFinish={(v) => add.mutate(v, {
                onSuccess: () => { message.success('Đã thêm thành viên.'); onClose(); },
            })}>
                <Form.Item name="email" label="Email" rules={[{ required: true, type: 'email', message: 'Nhập email hợp lệ' }]}>
                    <Input placeholder="email@vidu.com" />
                </Form.Item>
                <Form.Item name="role_id" label="Vai trò" rules={[{ required: true, message: 'Chọn vai trò' }]}>
                    <Radio.Group options={roles.map((r) => ({ value: r.id, label: r.name }))} />
                </Form.Item>
            </Form>
        </Modal>
    );
}

function EditRoleModal({ member, roles, onClose }: { member: TenantMember | null; roles: TenantRole[]; onClose: () => void }) {
    const { message } = AntApp.useApp();
    const update = useUpdateMemberRole();
    const [roleId, setRoleId] = useState<number | undefined>(undefined);
    useEffect(() => { setRoleId(member?.role_id ?? undefined); }, [member]);

    return (
        <Modal title={`Đổi vai trò — ${member?.name ?? ''}`} open={member !== null} onCancel={onClose} okText="Lưu"
            confirmLoading={update.isPending} okButtonProps={{ disabled: roleId == null }}
            onOk={() => member && roleId != null && update.mutate({ userId: member.id, role_id: roleId }, {
                onSuccess: () => { message.success('Đã đổi vai trò.'); onClose(); },
                onError: (e) => message.error(errorMessage(e, 'Không đổi được vai trò.')),
            })}>
            <Radio.Group value={roleId} onChange={(e) => setRoleId(e.target.value)}
                options={roles.map((r) => ({ value: r.id, label: r.name }))} />
        </Modal>
    );
}

function ResetPasswordModal({ member, onClose }: { member: TenantMember | null; onClose: () => void }) {
    const { message } = AntApp.useApp();
    const reset = useResetMemberPassword();
    const [form] = Form.useForm<{ password: string }>();
    useEffect(() => { form.resetFields(); }, [member, form]);

    return (
        <Modal title={`Đặt lại mật khẩu — ${member?.name ?? ''}`} open={member !== null} onCancel={onClose} okText="Đặt lại"
            confirmLoading={reset.isPending} onOk={() => form.submit()}>
            <Form form={form} layout="vertical" onFinish={(v) => member && reset.mutate({ userId: member.id, password: v.password }, {
                onSuccess: () => { message.success('Đã đặt lại mật khẩu.'); onClose(); },
                onError: (e) => message.error(errorMessage(e, 'Không đặt lại được.')),
            })}>
                <Form.Item name="password" label="Mật khẩu mới" rules={[{ required: true, min: 6, message: 'Tối thiểu 6 ký tự' }]}>
                    <Input.Password />
                </Form.Item>
            </Form>
        </Modal>
    );
}

// --- Roles -----------------------------------------------------------------

function RolesTab() {
    const { message } = AntApp.useApp();
    const roles = useRoles();
    const catalog = usePermissionCatalog();
    const deleteRole = useDeleteRole();
    const [editing, setEditing] = useState<TenantRole | null>(null);
    const [creating, setCreating] = useState(false);

    const columns: ColumnsType<TenantRole> = [
        {
            title: 'Vai trò', key: 'name',
            render: (_, r) => (
                <Space>
                    <Text strong>{r.name}</Text>
                    {r.is_owner && <Tag color="gold" icon={<SafetyOutlined />}>Toàn quyền</Tag>}
                    {r.is_system && !r.is_owner && <Tag>Mặc định</Tag>}
                </Space>
            ),
        },
        { title: 'Số quyền', key: 'perms', render: (_, r) => r.is_owner ? <Tag color="gold">Tất cả</Tag> : r.permissions.length },
        { title: 'Thành viên', dataIndex: 'members_count', key: 'members_count' },
        {
            title: '', key: 'actions', align: 'right',
            render: (_, r) => r.is_owner ? null : (
                <Space>
                    <Button size="small" icon={<EditOutlined />} onClick={() => setEditing(r)}>Sửa</Button>
                    <Popconfirm title="Xoá vai trò này?" okText="Xoá" cancelText="Huỷ" okButtonProps={{ danger: true }}
                        disabled={r.members_count > 0}
                        onConfirm={() => deleteRole.mutate(r.id, {
                            onSuccess: () => message.success('Đã xoá vai trò.'),
                            onError: (e) => message.error(errorMessage(e, 'Không xoá được.')),
                        })}>
                        <Button size="small" danger icon={<DeleteOutlined />} disabled={r.members_count > 0}
                            title={r.members_count > 0 ? 'Còn thành viên đang dùng vai trò này' : undefined} />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <div>
            <Button type="primary" icon={<PlusOutlined />} style={{ marginBottom: 12 }} onClick={() => setCreating(true)}>Tạo vai trò</Button>
            {roles.isError ? (
                <Alert type="error" showIcon message={errorMessage(roles.error, 'Không tải được vai trò.')} />
            ) : (
                <Table<TenantRole> rowKey="id" loading={roles.isLoading} dataSource={roles.data ?? []} columns={columns} pagination={false} />
            )}

            <RoleFormModal open={creating} role={null} catalog={catalog.data ?? []} onClose={() => setCreating(false)} />
            <RoleFormModal open={editing !== null} role={editing} catalog={catalog.data ?? []} onClose={() => setEditing(null)} />
        </div>
    );
}

function RoleFormModal({ open, role, catalog, onClose }: { open: boolean; role: TenantRole | null; catalog: PermissionGroup[]; onClose: () => void }) {
    const { message } = AntApp.useApp();
    const create = useCreateRole();
    const update = useUpdateRole();
    const [name, setName] = useState('');
    const [selected, setSelected] = useState<string[]>([]);

    useEffect(() => {
        if (open) {
            setName(role?.name ?? '');
            setSelected(role?.permissions ?? []);
        }
    }, [open, role]);

    const allKeys = useMemo(() => catalog.flatMap((g) => g.permissions.map((p) => p.key)), [catalog]);
    const toggle = (key: string, on: boolean) =>
        setSelected((prev) => on ? [...new Set([...prev, key])] : prev.filter((k) => k !== key));
    const pending = create.isPending || update.isPending;
    const err = create.error ?? update.error;

    const submit = () => {
        const vars = { name: name.trim(), permissions: selected };
        const opts = { onSuccess: () => { message.success(role ? 'Đã lưu vai trò.' : 'Đã tạo vai trò.'); onClose(); } };
        if (role) update.mutate({ id: role.id, ...vars }, opts);
        else create.mutate(vars, opts);
    };

    return (
        <Modal title={role ? `Sửa vai trò — ${role.name}` : 'Tạo vai trò'} open={open} onCancel={onClose} width={640}
            okText={role ? 'Lưu' : 'Tạo'} confirmLoading={pending} onOk={submit}
            okButtonProps={{ disabled: name.trim() === '' }} destroyOnClose>
            {(create.isError || update.isError) && (
                <Alert type="error" showIcon style={{ marginBottom: 12 }} message={errorMessage(err, 'Không lưu được vai trò.')} />
            )}
            <Form layout="vertical">
                <Form.Item label="Tên vai trò" required>
                    <Input value={name} onChange={(e) => setName(e.target.value)} placeholder="VD: Thu ngân, Quản lý kho…" maxLength={60} />
                </Form.Item>
            </Form>

            <Space style={{ marginBottom: 8 }}>
                <Button size="small" onClick={() => setSelected(allKeys)}>Chọn tất cả</Button>
                <Button size="small" onClick={() => setSelected([])}>Bỏ chọn</Button>
            </Space>

            <div style={{ maxHeight: 380, overflow: 'auto', paddingRight: 8 }}>
                {catalog.length === 0 ? <Empty /> : catalog.map((group) => (
                    <Card key={group.key} size="small" title={group.label} style={{ marginBottom: 10 }}>
                        <Space direction="vertical" size={6} style={{ display: 'flex' }}>
                            {group.permissions.map((p) => (
                                <Checkbox key={p.key} checked={selected.includes(p.key)}
                                    onChange={(e) => toggle(p.key, e.target.checked)}>
                                    {p.label}{' '}
                                    <Tag color={p.type === 'view' ? 'default' : 'blue'}>{p.type === 'view' ? 'Xem' : 'Thao tác'}</Tag>
                                </Checkbox>
                            ))}
                        </Space>
                    </Card>
                ))}
            </div>
        </Modal>
    );
}
