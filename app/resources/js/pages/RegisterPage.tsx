import { Button, Card, Form, Input, Typography, Alert } from 'antd';
import { Link, useNavigate } from 'react-router-dom';
import { useRegister } from '@/lib/auth';
import { errorMessage } from '@/lib/api';

export function RegisterPage() {
    const register = useRegister();
    const navigate = useNavigate();

    return (
        <div style={{ display: 'grid', placeItems: 'center', minHeight: '100vh', background: '#f5f5f5' }}>
            <Card style={{ width: 420 }}>
                <Typography.Title level={3} style={{ textAlign: 'center', marginBottom: 4 }}>CMBcoreSeller</Typography.Title>
                <Typography.Paragraph type="secondary" style={{ textAlign: 'center' }}>Tạo tài khoản</Typography.Paragraph>
                {register.isError && <Alert type="error" showIcon style={{ marginBottom: 16 }} message={errorMessage(register.error, 'Đăng ký thất bại.')} />}
                <Form layout="vertical" onFinish={(v) => register.mutate(v, { onSuccess: () => navigate('/') })}>
                    <Form.Item name="name" label="Tên của bạn" rules={[{ required: true, message: 'Nhập tên' }]}>
                        <Input autoFocus />
                    </Form.Item>
                    <Form.Item name="tenant_name" label="Tên gian hàng / workspace" tooltip="Có thể đổi sau">
                        <Input placeholder="Shop của tôi" />
                    </Form.Item>
                    <Form.Item name="email" label="Email" rules={[{ required: true, type: 'email', message: 'Nhập email hợp lệ' }]}>
                        <Input />
                    </Form.Item>
                    <Form.Item name="password" label="Mật khẩu" rules={[{ required: true, min: 8, message: 'Tối thiểu 8 ký tự' }]} hasFeedback>
                        <Input.Password />
                    </Form.Item>
                    <Form.Item name="password_confirmation" label="Nhập lại mật khẩu" dependencies={['password']} hasFeedback rules={[
                        { required: true, message: 'Nhập lại mật khẩu' },
                        ({ getFieldValue }) => ({
                            validator: (_, value) => (!value || getFieldValue('password') === value ? Promise.resolve() : Promise.reject(new Error('Mật khẩu không khớp'))),
                        }),
                    ]}>
                        <Input.Password />
                    </Form.Item>
                    <Button type="primary" htmlType="submit" block loading={register.isPending}>Đăng ký</Button>
                </Form>
                <Typography.Paragraph style={{ textAlign: 'center', marginTop: 16, marginBottom: 0 }}>
                    Đã có tài khoản? <Link to="/login">Đăng nhập</Link>
                </Typography.Paragraph>
            </Card>
        </div>
    );
}
