import { Button, Form, Input, Alert } from 'antd';
import { Link, useNavigate } from 'react-router-dom';
import {
    UserOutlined,
    MailOutlined,
    LockOutlined,
    ShopOutlined,
    ThunderboltOutlined,
    SafetyCertificateOutlined,
    ClusterOutlined,
    ArrowRightOutlined,
    AppstoreOutlined,
} from '@ant-design/icons';
import { useRegister } from '@/lib/auth';
import { errorMessage } from '@/lib/api';

export function RegisterPage() {
    const register = useRegister();
    const navigate = useNavigate();

    return (
        <div className="auth-shell">
            <div className="auth-brand">
                <div className="auth-scanline" />
                <div className="auth-brand-content">
                    <span className="auth-logo-mark"><ShopOutlined /></span>
                    <h1>Bắt đầu hành trình<br />bán hàng đa kênh.</h1>
                    <p className="tagline">
                        Tạo tài khoản miễn phí để hợp nhất đơn hàng, tồn kho và vận chuyển trên cùng một nền tảng.
                    </p>
                    <ul className="auth-feature-list">
                        <li>
                            <span className="feat-icon"><ThunderboltOutlined /></span>
                            <span>Đồng bộ đơn hàng theo thời gian thực từ nhiều sàn TMĐT.</span>
                        </li>
                        <li>
                            <span className="feat-icon"><ClusterOutlined /></span>
                            <span>Quản lý tồn kho nhiều kho, dự báo nhu cầu và lệnh nhập hàng.</span>
                        </li>
                        <li>
                            <span className="feat-icon"><SafetyCertificateOutlined /></span>
                            <span>Bảo mật cấp doanh nghiệp, phân quyền chi tiết theo vai trò.</span>
                        </li>
                    </ul>
                </div>
                <div className="auth-brand-footer">
                    &copy; {new Date().getFullYear()} CMBcoreSeller. All rights reserved.
                </div>
            </div>

            <div className="auth-form-panel">
                <div className="auth-form-card">
                    <h2>Tạo tài khoản mới</h2>
                    <p className="sub">Chỉ mất 1 phút để bắt đầu sử dụng CMBcoreSeller.</p>

                    {register.isError && (
                        <Alert
                            type="error"
                            showIcon
                            style={{ marginBottom: 16 }}
                            message={errorMessage(register.error, 'Đăng ký thất bại.')}
                        />
                    )}

                    <Form
                        layout="vertical"
                        requiredMark={false}
                        onFinish={(v) => register.mutate(v, { onSuccess: () => navigate('/') })}
                    >
                        <Form.Item
                            name="name"
                            label="Tên của bạn"
                            rules={[{ required: true, message: 'Nhập tên' }]}
                        >
                            <Input
                                autoFocus
                                size="large"
                                prefix={<UserOutlined style={{ color: '#9ca3af' }} />}
                                placeholder="Nguyễn Văn A"
                            />
                        </Form.Item>

                        <Form.Item
                            name="tenant_name"
                            label="Tên gian hàng / workspace"
                            tooltip="Có thể đổi sau"
                        >
                            <Input
                                size="large"
                                prefix={<AppstoreOutlined style={{ color: '#9ca3af' }} />}
                                placeholder="Shop của tôi"
                            />
                        </Form.Item>

                        <Form.Item
                            name="email"
                            label="Email"
                            rules={[{ required: true, type: 'email', message: 'Nhập email hợp lệ' }]}
                        >
                            <Input
                                size="large"
                                prefix={<MailOutlined style={{ color: '#9ca3af' }} />}
                                placeholder="email@vidu.com"
                            />
                        </Form.Item>

                        <Form.Item
                            name="password"
                            label="Mật khẩu"
                            rules={[{ required: true, min: 8, message: 'Tối thiểu 8 ký tự' }]}
                            hasFeedback
                        >
                            <Input.Password
                                size="large"
                                prefix={<LockOutlined style={{ color: '#9ca3af' }} />}
                                placeholder="Tối thiểu 8 ký tự"
                            />
                        </Form.Item>

                        <Form.Item
                            name="password_confirmation"
                            label="Nhập lại mật khẩu"
                            dependencies={['password']}
                            hasFeedback
                            rules={[
                                { required: true, message: 'Nhập lại mật khẩu' },
                                ({ getFieldValue }) => ({
                                    validator: (_, value) =>
                                        !value || getFieldValue('password') === value
                                            ? Promise.resolve()
                                            : Promise.reject(new Error('Mật khẩu không khớp')),
                                }),
                            ]}
                        >
                            <Input.Password
                                size="large"
                                prefix={<LockOutlined style={{ color: '#9ca3af' }} />}
                                placeholder="Nhập lại mật khẩu"
                            />
                        </Form.Item>

                        <Button
                            type="primary"
                            htmlType="submit"
                            block
                            loading={register.isPending}
                            icon={!register.isPending ? <ArrowRightOutlined /> : undefined}
                            iconPosition="end"
                        >
                            Đăng ký
                        </Button>
                    </Form>

                    <div className="auth-footer-row">
                        Đã có tài khoản? <Link to="/login">Đăng nhập</Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
