// Spec 2026-05-17 — drawer thêm/sửa super-admin.
//
// `target === 'new'`: form tạo mới (username + name + email? + password).
// `target` là AdminRow: form sửa metadata (name + email) + action buttons
// (reset password, suspend, reactivate). Không cho phép tự suspend / reset
// chính mình — BE chặn (409 CANNOT_SELF_MUTATE). UI show error message.

import { useEffect, useState } from 'react';
import { Drawer, Form, Input, Space, Button, App, Popconfirm, Typography } from 'antd';
import { errorMessage } from '@/lib/api';
import {
    useCreateAdminUser,
    useUpdateAdminUser,
    useSuspendAdminUser,
    useReactivateAdminUser,
    useResetAdminPassword,
    type AdminRow,
} from '../../lib/adminUsers';

type Target = AdminRow | 'new' | null;

export function AdminUserFormDrawer({
    open,
    target,
    onClose,
}: {
    open: boolean;
    target: Target;
    onClose: () => void;
}) {
    const [form] = Form.useForm();
    const [newPassword, setNewPassword] = useState('');
    const create = useCreateAdminUser();
    const update = useUpdateAdminUser();
    const suspend = useSuspendAdminUser();
    const reactivate = useReactivateAdminUser();
    const reset = useResetAdminPassword();
    const { message } = App.useApp();

    useEffect(() => {
        if (!open) return;
        setNewPassword('');
        if (target === 'new') {
            form.resetFields();
        } else if (target) {
            form.setFieldsValue({
                username: target.username,
                name: target.name,
                email: target.email ?? '',
            });
        }
    }, [open, target, form]);

    const isNew = target === 'new';
    const editing = target && typeof target !== 'string' ? target : null;

    return (
        <Drawer
            open={open}
            title={isNew ? 'Thêm super-admin' : `Sửa: ${editing?.username ?? ''}`}
            width={420}
            onClose={onClose}
            destroyOnHidden
        >
            <Form
                layout="vertical"
                form={form}
                onFinish={(v: { username?: string; name: string; email?: string; password?: string }) => {
                    if (isNew) {
                        create.mutate(
                            {
                                username: v.username!,
                                name: v.name,
                                email: v.email || undefined,
                                password: v.password!,
                            },
                            {
                                onSuccess: () => {
                                    message.success('Đã tạo admin.');
                                    onClose();
                                },
                                onError: (e) => message.error(errorMessage(e, 'Tạo thất bại.')),
                            },
                        );
                    } else if (editing) {
                        update.mutate(
                            { id: editing.id, name: v.name, email: v.email || null },
                            {
                                onSuccess: () => {
                                    message.success('Đã lưu.');
                                    onClose();
                                },
                                onError: (e) => message.error(errorMessage(e, 'Lưu thất bại.')),
                            },
                        );
                    }
                }}
            >
                <Form.Item name="username" label="Username" rules={[{ required: true }]}>
                    <Input disabled={!isNew} placeholder="ops_lead" />
                </Form.Item>
                <Form.Item name="name" label="Tên" rules={[{ required: true }]}>
                    <Input />
                </Form.Item>
                <Form.Item name="email" label="Email (không bắt buộc)">
                    <Input placeholder="ops@cmbcore.vn" />
                </Form.Item>
                {isNew && (
                    <Form.Item name="password" label="Mật khẩu" rules={[{ required: true, min: 8 }]}>
                        <Input.Password autoComplete="new-password" />
                    </Form.Item>
                )}

                <Space wrap>
                    <Button type="primary" htmlType="submit" loading={create.isPending || update.isPending}>
                        Lưu
                    </Button>

                    {editing && (
                        <>
                            <Popconfirm
                                title="Đặt mật khẩu mới"
                                description={
                                    <div style={{ width: 220 }}>
                                        <Input.Password
                                            placeholder="Mật khẩu mới (≥ 8)"
                                            value={newPassword}
                                            onChange={(e) => setNewPassword(e.target.value)}
                                        />
                                    </div>
                                }
                                okText="Đổi"
                                cancelText="Huỷ"
                                onConfirm={() => {
                                    if (newPassword.length < 8) {
                                        message.error('Mật khẩu phải ≥ 8 ký tự.');
                                        return;
                                    }
                                    reset.mutate(
                                        { id: editing.id, password: newPassword },
                                        {
                                            onSuccess: () => {
                                                message.success('Đã đổi mật khẩu.');
                                                setNewPassword('');
                                            },
                                            onError: (e) => message.error(errorMessage(e)),
                                        },
                                    );
                                }}
                            >
                                <Button>Reset password</Button>
                            </Popconfirm>

                            {editing.is_active ? (
                                <Popconfirm
                                    title="Vô hiệu hoá admin này?"
                                    onConfirm={() =>
                                        suspend.mutate(editing.id, {
                                            onSuccess: () => {
                                                message.success('Đã vô hiệu hoá.');
                                                onClose();
                                            },
                                            onError: (e) => message.error(errorMessage(e)),
                                        })
                                    }
                                >
                                    <Button danger>Suspend</Button>
                                </Popconfirm>
                            ) : (
                                <Button
                                    onClick={() =>
                                        reactivate.mutate(editing.id, {
                                            onSuccess: () => {
                                                message.success('Đã kích hoạt lại.');
                                                onClose();
                                            },
                                            onError: (e) => message.error(errorMessage(e)),
                                        })
                                    }
                                >
                                    Reactivate
                                </Button>
                            )}
                        </>
                    )}
                </Space>

                {editing && (
                    <Typography.Paragraph type="secondary" style={{ marginTop: 12, fontSize: 12 }}>
                        Lưu ý: không thể tự suspend / reset password chính mình.
                    </Typography.Paragraph>
                )}
            </Form>
        </Drawer>
    );
}
