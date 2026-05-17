// Spec 2026-05-17 — drawer chi tiết tenant user. Sửa name/email, reset password,
// suspend/reactivate. Hiển thị danh sách tenant đang là thành viên.

import { useEffect, useState } from 'react';
import { Drawer, Form, Input, Button, Space, App, Popconfirm, Tag, Typography } from 'antd';
import { errorMessage } from '@/lib/api';
import {
    useTenantUserDetail,
    useUpdateTenantUser,
    useResetTenantUserPassword,
    useSuspendTenantUser,
    useReactivateTenantUser,
} from '../../lib/tenantUsers';

export function TenantUserDrawer({
    userId,
    onClose,
}: {
    userId: number | null;
    onClose: () => void;
}) {
    const [form] = Form.useForm();
    const [newPassword, setNewPassword] = useState('');
    const { data } = useTenantUserDetail(userId);
    const update = useUpdateTenantUser();
    const reset = useResetTenantUserPassword();
    const suspend = useSuspendTenantUser();
    const reactivate = useReactivateTenantUser();
    const { message } = App.useApp();

    useEffect(() => {
        if (data) {
            form.setFieldsValue({ name: data.name, email: data.email });
        }
    }, [data, form]);

    if (userId === null) return null;
    const suspended = !!data?.suspended_at;

    return (
        <Drawer
            open
            width={460}
            title={`Người dùng: ${data?.name ?? '…'}`}
            onClose={onClose}
            destroyOnHidden
        >
            <Form
                layout="vertical"
                form={form}
                onFinish={(v) =>
                    update.mutate(
                        { id: userId, ...v },
                        {
                            onSuccess: () => {
                                message.success('Đã lưu.');
                                onClose();
                            },
                            onError: (e) => message.error(errorMessage(e)),
                        },
                    )
                }
            >
                <Form.Item name="name" label="Tên" rules={[{ required: true }]}>
                    <Input />
                </Form.Item>
                <Form.Item name="email" label="Email">
                    <Input />
                </Form.Item>

                <Typography.Paragraph type="secondary" style={{ marginBottom: 12 }}>
                    Tenant:{' '}
                    {data?.tenants?.length
                        ? data.tenants.map((t) => (
                              <Tag key={t.id}>
                                  {t.name} · {t.role}
                              </Tag>
                          ))
                        : <Typography.Text type="secondary">—</Typography.Text>}
                </Typography.Paragraph>

                {suspended && (
                    <Typography.Paragraph>
                        <Tag color="red">Tạm khoá</Tag> Người dùng này không thể vào tenant nào cho tới khi
                        được kích hoạt lại.
                    </Typography.Paragraph>
                )}

                <Space wrap>
                    <Button type="primary" htmlType="submit" loading={update.isPending}>
                        Lưu
                    </Button>

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
                                { id: userId, password: newPassword },
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

                    {suspended ? (
                        <Button
                            onClick={() =>
                                reactivate.mutate(userId, {
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
                    ) : (
                        <Popconfirm
                            title="Tạm khoá người dùng này?"
                            onConfirm={() =>
                                suspend.mutate(userId, {
                                    onSuccess: () => {
                                        message.success('Đã khoá.');
                                        onClose();
                                    },
                                    onError: (e) => message.error(errorMessage(e)),
                                })
                            }
                        >
                            <Button danger>Suspend</Button>
                        </Popconfirm>
                    )}
                </Space>
            </Form>
        </Drawer>
    );
}
