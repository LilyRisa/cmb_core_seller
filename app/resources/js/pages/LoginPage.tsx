import { Button, Form, Input, Alert, Checkbox } from 'antd';
import { Link, useNavigate } from 'react-router-dom';
import {
    MailOutlined,
    LockOutlined,
    ShopOutlined,
    ThunderboltOutlined,
    SafetyCertificateOutlined,
    ClusterOutlined,
    ArrowRightOutlined,
} from '@ant-design/icons';
import { useLogin } from '@/lib/auth';
import { errorMessage } from '@/lib/api';

export function LoginPage() {
    const login = useLogin();
    const navigate = useNavigate();

    return (
        <div className="auth-shell">
            <div className="auth-brand">
                <div className="auth-scanline" />
                <div className="auth-brand-content">
                    <span className="auth-logo-mark"><ShopOutlined /></span>
                    <h1>Vận hành gian hàng,<br />tối ưu mỗi đơn hàng.</h1>
                    <p className="tagline">
                        Nền tảng quản lý đơn – tồn kho – vận chuyển hợp nhất cho seller đa kênh.
                        Đăng nhập để tiếp tục công việc của bạn.
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
                    <h2>Chào mừng trở lại</h2>
                    <p className="sub">Đăng nhập vào CMBcoreSeller để tiếp tục.</p>

                    {login.isError && (
                        <Alert
                            type="error"
                            showIcon
                            style={{ marginBottom: 16 }}
                            message={errorMessage(login.error, 'Đăng nhập thất bại.')}
                        />
                    )}

                    <Form
                        layout="vertical"
                        requiredMark={false}
                        onFinish={(v) => login.mutate(v, { onSuccess: () => navigate('/') })}
                    >
                        <Form.Item
                            name="email"
                            label="Email"
                            rules={[{ required: true, type: 'email', message: 'Nhập email hợp lệ' }]}
                        >
                            <Input
                                autoFocus
                                size="large"
                                prefix={<MailOutlined style={{ color: '#9ca3af' }} />}
                                placeholder="email@vidu.com"
                            />
                        </Form.Item>

                        <Form.Item
                            name="password"
                            label={
                                <div className="auth-divider-row" style={{ width: '100%' }}>
                                    <span>Mật khẩu</span>
                                </div>
                            }
                            rules={[{ required: true, message: 'Nhập mật khẩu' }]}
                        >
                            <Input.Password
                                size="large"
                                prefix={<LockOutlined style={{ color: '#9ca3af' }} />}
                                placeholder="Nhập mật khẩu"
                            />
                        </Form.Item>

                        <Form.Item style={{ marginBottom: 18 }}>
                            <div className="auth-divider-row">
                                <Form.Item name="remember" valuePropName="checked" noStyle>
                                    <Checkbox>Ghi nhớ đăng nhập</Checkbox>
                                </Form.Item>
                            </div>
                        </Form.Item>

                        <Button
                            type="primary"
                            htmlType="submit"
                            block
                            loading={login.isPending}
                            icon={!login.isPending ? <ArrowRightOutlined /> : undefined}
                            iconPosition="end"
                        >
                            Đăng nhập
                        </Button>
                    </Form>

                    <div className="auth-footer-row">
                        Chưa có tài khoản? <Link to="/register">Tạo tài khoản mới</Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
