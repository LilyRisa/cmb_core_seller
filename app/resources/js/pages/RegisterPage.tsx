import { Button, Form, Input, Alert } from 'antd';
import { Link, useNavigate } from 'react-router-dom';
import {
    UserOutlined,
    MailOutlined,
    LockOutlined,
    ThunderboltOutlined,
    SafetyCertificateOutlined,
    ClusterOutlined,
    ArrowRightOutlined,
    AppstoreOutlined,
} from '@ant-design/icons';
import { useEffect, useState } from 'react';
import { useRegister } from '@/lib/auth';
import { errorMessage } from '@/lib/api';
import { AuthBackdrop } from '@/components/AuthBackdrop';
import { Captcha, useCaptchaConfig } from '@/lib/captcha';
import { captureExtRedirect } from '@/lib/extRedirect';
import { readAcquisition, readFacebookCookies, clearAcquisition } from '@/lib/acquisition';

export function RegisterPage() {
    const register = useRegister();
    const navigate = useNavigate();
    const { data: captcha } = useCaptchaConfig();
    const [captchaToken, setCaptchaToken] = useState('');

    // Giữ luồng: đăng ký xong phải verify email rồi mới chuyển hướng về extension.
    // Lưu đường quay lại ở đây; RequireAuth chỉ tiêu thụ khi user đã verify.
    useEffect(() => captureExtRedirect(window.location.search), []);

    return (
        <div className="auth-shell">
            <div className="auth-brand">
                <div className="auth-scanline" />
                <AuthBackdrop />
                <div className="auth-brand-content">
                    <span className="auth-logo-mark"><img src="/images/logocmb.png" alt="CMB Core" /></span>
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
                        onFinish={(v) => {
                            const eventId = crypto.randomUUID();
                            const acquisition = { ...readAcquisition(), ...readFacebookCookies() };
                            register.mutate(
                                { ...v, captcha_token: captchaToken, event_id: eventId, acquisition },
                                {
                                    onSuccess: () => {
                                        if (typeof window.fbq === 'function') {
                                            window.fbq('track', 'CompleteRegistration', {}, { eventID: eventId });
                                        }
                                        clearAcquisition();
                                        navigate('/dashboard');
                                    },
                                },
                            );
                        }}
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
                            extra="Tối thiểu 8 ký tự, gồm chữ hoa, chữ thường, chữ số và ký tự đặc biệt."
                            rules={[
                                { required: true, message: 'Nhập mật khẩu' },
                                () => ({
                                    validator(_, value) {
                                        if (!value) return Promise.resolve();
                                        if (value.length < 8) return Promise.reject(new Error('Mật khẩu phải có ít nhất 8 ký tự'));
                                        if (!/[A-Z]/.test(value)) return Promise.reject(new Error('Mật khẩu phải có ít nhất 1 chữ in hoa'));
                                        if (!/[a-z]/.test(value)) return Promise.reject(new Error('Mật khẩu phải có ít nhất 1 chữ thường'));
                                        if (!/[0-9]/.test(value)) return Promise.reject(new Error('Mật khẩu phải có ít nhất 1 chữ số'));
                                        if (!/[^A-Za-z0-9]/.test(value)) return Promise.reject(new Error('Mật khẩu phải có ít nhất 1 ký tự đặc biệt'));
                                        return Promise.resolve();
                                    },
                                }),
                            ]}
                            hasFeedback
                        >
                            <Input.Password
                                size="large"
                                prefix={<LockOutlined style={{ color: '#9ca3af' }} />}
                                placeholder="≥8 ký tự, gồm chữ hoa, chữ thường, số & ký tự đặc biệt"
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

                        {captcha?.enabled && captcha.site_key
                            ? <Captcha siteKey={captcha.site_key} onVerify={setCaptchaToken} />
                            : null}

                        <Button
                            type="primary"
                            htmlType="submit"
                            block
                            loading={register.isPending}
                            disabled={!!captcha?.enabled && captchaToken === ''}
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
