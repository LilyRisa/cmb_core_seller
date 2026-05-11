import { Button, Card, Form, Input, Typography, Alert } from 'antd';
import { Link, useNavigate } from 'react-router-dom';
import { useLogin } from '@/lib/auth';
import { errorMessage } from '@/lib/api';

export function LoginPage() {
    const login = useLogin();
    const navigate = useNavigate();

    return (
        <div style={{ display: 'grid', placeItems: 'center', minHeight: '100vh', background: '#f5f5f5' }}>
            <Card style={{ width: 400 }}>
                <Typography.Title level={3} style={{ textAlign: 'center', marginBottom: 4 }}>CMBcoreSeller</Typography.Title>
                <Typography.Paragraph type="secondary" style={{ textAlign: 'center' }}>Đăng nhập</Typography.Paragraph>
                {login.isError && <Alert type="error" showIcon style={{ marginBottom: 16 }} message={errorMessage(login.error, 'Đăng nhập thất bại.')} />}
                <Form layout="vertical" onFinish={(v) => login.mutate(v, { onSuccess: () => navigate('/') })}>
                    <Form.Item name="email" label="Email" rules={[{ required: true, type: 'email', message: 'Nhập email hợp lệ' }]}>
                        <Input autoFocus placeholder="email@vidu.com" />
                    </Form.Item>
                    <Form.Item name="password" label="Mật khẩu" rules={[{ required: true, message: 'Nhập mật khẩu' }]}>
                        <Input.Password />
                    </Form.Item>
                    <Button type="primary" htmlType="submit" block loading={login.isPending}>Đăng nhập</Button>
                </Form>
                <Typography.Paragraph style={{ textAlign: 'center', marginTop: 16, marginBottom: 0 }}>
                    Chưa có tài khoản? <Link to="/register">Đăng ký</Link>
                </Typography.Paragraph>
            </Card>
        </div>
    );
}
