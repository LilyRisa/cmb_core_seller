// Spec 2026-05-17 (redesign 2026-07-21) — drawer thêm/sửa super-admin.
//
// `target === 'new'`: form tạo mới (username + name + email? + password).
// `target` là AdminRow: form sửa metadata (name + email) + action buttons
// (đặt lại mật khẩu, tạm khoá, mở lại). Không cho phép tự tạm khoá / đặt lại mật khẩu
// chính mình — BE chặn (409 CANNOT_SELF_MUTATE). UI show error message.
// Tạm khoá/Mở lại dùng useReasonConfirm — tier "high-impact" (khoá tài khoản khỏi hệ
// thống, cả 2 chiều) theo docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §5.1.
// Đặt lại mật khẩu vẫn dùng Popconfirm (spec không xếp tier high-impact cho hành động này).

import { useEffect, useState } from 'react';
import { Drawer, Form, Input, Space, Button, App, Popconfirm, Typography } from 'antd';
import { errorMessage } from '@/lib/api';
import { useReasonConfirm } from '@admin/components/ReasonConfirmModal';
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
    const confirmWithReason = useReasonConfirm();

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

    const onSuspend = () => {
        if (!editing) return;
        confirmWithReason({
            title: `Tạm khoá admin «${editing.username}»?`,
            danger: true,
            okText: 'Tạm khoá',
            onConfirm: async (reason) => {
                await suspend.mutateAsync({ id: editing.id, reason });
                message.success('Đã tạm khoá.');
                onClose();
            },
        });
    };

    const onReactivate = () => {
        if (!editing) return;
        confirmWithReason({
            title: `Mở lại admin «${editing.username}»?`,
            okText: 'Mở lại',
            onConfirm: async (reason) => {
                await reactivate.mutateAsync({ id: editing.id, reason });
                message.success('Đã mở lại.');
                onClose();
            },
        });
    };

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
                                <Button>Đặt lại mật khẩu</Button>
                            </Popconfirm>

                            {editing.is_active ? (
                                <Button danger onClick={onSuspend} loading={suspend.isPending}>
                                    Tạm khoá
                                </Button>
                            ) : (
                                <Button onClick={onReactivate} loading={reactivate.isPending}>
                                    Mở lại
                                </Button>
                            )}
                        </>
                    )}
                </Space>

                {editing && (
                    <Typography.Paragraph type="secondary" style={{ marginTop: 12, fontSize: 12 }}>
                        Lưu ý: không thể tự tạm khoá / đặt lại mật khẩu chính mình.
                    </Typography.Paragraph>
                )}
            </Form>
        </Drawer>
    );
}
