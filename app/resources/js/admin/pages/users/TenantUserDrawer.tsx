// Spec 2026-05-17 (redesign 2026-07-21) — drawer chi tiết tenant user. Sửa name/email, đặt lại
// mật khẩu, tạm khoá/mở lại. Hiển thị danh sách tenant đang là thành viên.
// Tạm khoá/Mở lại dùng useReasonConfirm — tier "high-impact" theo
// docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §5.1.
// Loading: Spin trong lúc useTenantUserDetail() chưa trả dữ liệu — trước đây thiếu (spec §5.5),
// drawer mở ra thấy form trống trong vài trăm ms rồi mới nạp dữ liệu.

import { useEffect, useState } from 'react';
import { Drawer, Form, Input, Button, Space, App, Popconfirm, Spin, Tag, Typography, Descriptions } from 'antd';
import { errorMessage } from '@/lib/api';
import { useReasonConfirm } from '@admin/components/ReasonConfirmModal';
import {
    useTenantUserDetail,
    useUpdateTenantUser,
    useResetTenantUserPassword,
    useSuspendTenantUser,
    useReactivateTenantUser,
    useTenantUserAiUsage,
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
    const { data, isLoading } = useTenantUserDetail(userId);
    const aiUsage = useTenantUserAiUsage(userId);
    const update = useUpdateTenantUser();
    const reset = useResetTenantUserPassword();
    const suspend = useSuspendTenantUser();
    const reactivate = useReactivateTenantUser();
    const { message } = App.useApp();
    const confirmWithReason = useReasonConfirm();

    useEffect(() => {
        if (data) {
            form.setFieldsValue({ name: data.name, email: data.email });
        }
    }, [data, form]);

    if (userId === null) return null;

    if (isLoading || !data) {
        return (
            <Drawer open width={460} title="Người dùng" onClose={onClose} destroyOnHidden>
                <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>
            </Drawer>
        );
    }

    const suspended = !!data.suspended_at;

    const onSuspend = () => {
        confirmWithReason({
            title: 'Tạm khoá người dùng này?',
            danger: true,
            okText: 'Tạm khoá',
            warningText: 'Người dùng sẽ không thể đăng nhập vào bất kỳ tenant nào cho tới khi được mở lại.',
            onConfirm: async (reason) => {
                await suspend.mutateAsync({ id: userId, reason });
                message.success('Đã khoá.');
                onClose();
            },
        });
    };

    const onReactivate = () => {
        confirmWithReason({
            title: 'Mở lại người dùng này?',
            okText: 'Mở lại',
            onConfirm: async (reason) => {
                await reactivate.mutateAsync({ id: userId, reason });
                message.success('Đã kích hoạt lại.');
                onClose();
            },
        });
    };

    return (
        <Drawer
            open
            width={460}
            title={`Người dùng: ${data.name}`}
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
                    {data.tenants.length
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

                <Descriptions title="Lượt gọi AI" column={1} size="small" style={{ marginTop: 16, marginBottom: 16 }}>
                    <Descriptions.Item label="Tổng">{aiUsage.data?.all_time ?? 0}</Descriptions.Item>
                    {(aiUsage.data?.by_feature ?? []).map((f) => (
                        <Descriptions.Item key={f.feature} label={f.feature}>{f.count}</Descriptions.Item>
                    ))}
                </Descriptions>

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
                        <Button>Đặt lại mật khẩu</Button>
                    </Popconfirm>

                    {suspended ? (
                        <Button onClick={onReactivate} loading={reactivate.isPending}>
                            Mở lại
                        </Button>
                    ) : (
                        <Button danger onClick={onSuspend} loading={suspend.isPending}>
                            Tạm khoá
                        </Button>
                    )}
                </Space>
            </Form>
        </Drawer>
    );
}
