// Spec 2026-05-17 — màn login admin tại `/admin/login`.
// Form đơn giản username + password. Banner cảnh báo "audit log" để răn đe.

import { useState } from 'react';
import { Card, Form, Input, Button, Alert, Typography, Space } from 'antd';
import { SafetyCertificateOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import { useAdminLogin } from '../lib/adminAuth';

export function AdminLoginPage() {
    const nav = useNavigate();
    const login = useAdminLogin();
    const [err, setErr] = useState<string | null>(null);

    return (
        <div style={{
            minHeight: '100vh',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            background: '#0F172A',
        }}>
            <Card style={{ width: 380 }}>
                <Space direction="vertical" size={4} style={{ marginBottom: 16, width: '100%' }}>
                    <Space>
                        <SafetyCertificateOutlined />
                        <Typography.Title level={4} style={{ margin: 0 }}>Admin hệ thống</Typography.Title>
                    </Space>
                    <Typography.Text type="secondary">
                        Khu vực quản trị nội bộ — mọi thao tác được ghi nhật ký.
                    </Typography.Text>
                </Space>

                {err && <Alert type="error" message={err} style={{ marginBottom: 12 }} showIcon />}

                <Form
                    layout="vertical"
                    onFinish={(v: { username: string; password: string }) => {
                        setErr(null);
                        login.mutate(v, {
                            onSuccess: () => nav('/admin', { replace: true }),
                            onError: (e: any) => setErr(
                                e?.response?.data?.error?.message ?? 'Đăng nhập thất bại.'
                            ),
                        });
                    }}
                >
                    <Form.Item name="username" label="Tên đăng nhập" rules={[{ required: true, message: 'Bắt buộc' }]}>
                        <Input autoFocus autoComplete="username" />
                    </Form.Item>
                    <Form.Item name="password" label="Mật khẩu" rules={[{ required: true, message: 'Bắt buộc' }]}>
                        <Input.Password autoComplete="current-password" />
                    </Form.Item>
                    <Button type="primary" htmlType="submit" block loading={login.isPending}>
                        Đăng nhập
                    </Button>
                </Form>
            </Card>
        </div>
    );
}
