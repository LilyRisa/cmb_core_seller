import { useMemo } from 'react';
import { Button, Form, Input, Alert, Result } from 'antd';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import {
    LockOutlined,
    ThunderboltOutlined,
    SafetyCertificateOutlined,
    ClusterOutlined,
    ArrowRightOutlined,
    ArrowLeftOutlined,
    CheckCircleFilled,
    CloseCircleFilled,
} from '@ant-design/icons';
import { useResetPassword } from '@/lib/auth';
import { errorMessage, errorCode } from '@/lib/api';

/**
 * SPEC 0022 — bước 2 của luồng quên mật khẩu: đặt mật khẩu mới.
 *
 * BE gửi email với link `${FRONTEND_URL}/password-reset?token=…&email=…`.
 * Trang public này đọc `token` + `email` từ query, cho nhập mật khẩu mới
 * (policy: ≥8 ký tự, có chữ hoa + chữ thường + ký tự đặc biệt — khớp validate BE),
 * rồi gọi `POST /auth/password/reset`.
 */
export function ResetPasswordPage() {
    const [params] = useSearchParams();
    const navigate = useNavigate();
    const reset = useResetPassword();

    const token = params.get('token') ?? '';
    const email = params.get('email') ?? '';
    const linkValid = useMemo(() => token !== '' && email !== '', [token, email]);

    // Link thiếu token/email ⇒ không thể đặt lại; hướng dẫn yêu cầu link mới.
    if (!linkValid) {
        return (
            <div className="auth-shell">
                <BrandPanel />
                <div className="auth-form-panel">
                    <div className="auth-form-card">
                        <Result
                            status="error"
                            icon={<CloseCircleFilled style={{ color: '#EF4444' }} />}
                            title="Liên kết không hợp lệ"
                            subTitle="Liên kết đặt lại mật khẩu bị thiếu thông tin hoặc đã bị thay đổi. Hãy yêu cầu một liên kết mới."
                            extra={
                                <Link to="/forgot-password">
                                    <Button type="primary" size="large" icon={<ArrowRightOutlined />} iconPosition="end">
                                        Yêu cầu liên kết mới
                                    </Button>
                                </Link>
                            }
                        />
                    </div>
                </div>
            </div>
        );
    }

    // Đặt lại thành công ⇒ màn hình xác nhận + điều hướng đăng nhập.
    if (reset.isSuccess) {
        return (
            <div className="auth-shell">
                <BrandPanel />
                <div className="auth-form-panel">
                    <div className="auth-form-card">
                        <Result
                            status="success"
                            icon={<CheckCircleFilled style={{ color: '#10B981' }} />}
                            title="Đã đặt lại mật khẩu"
                            subTitle="Mật khẩu mới đã được lưu. Bạn có thể đăng nhập bằng mật khẩu vừa tạo."
                            extra={
                                <Button
                                    type="primary"
                                    size="large"
                                    icon={<ArrowRightOutlined />}
                                    iconPosition="end"
                                    onClick={() => navigate('/login', { replace: true })}
                                >
                                    Đăng nhập ngay
                                </Button>
                            }
                        />
                    </div>
                </div>
            </div>
        );
    }

    const tokenExpired = reset.isError && errorCode(reset.error) === 'INVALID_RESET_TOKEN';

    return (
        <div className="auth-shell">
            <BrandPanel />
            <div className="auth-form-panel">
                <div className="auth-form-card">
                    <h2>Đặt mật khẩu mới</h2>
                    <p className="sub">Đặt lại mật khẩu cho <strong>{email}</strong>.</p>

                    {reset.isError && (
                        <Alert
                            type="error"
                            showIcon
                            style={{ marginBottom: 16 }}
                            message={
                                tokenExpired
                                    ? 'Liên kết đặt lại đã hết hạn hoặc đã được dùng. Vui lòng yêu cầu liên kết mới.'
                                    : errorMessage(reset.error, 'Đặt lại mật khẩu thất bại.')
                            }
                            action={
                                tokenExpired ? (
                                    <Link to="/forgot-password">
                                        <Button size="small" type="link">Gửi lại</Button>
                                    </Link>
                                ) : undefined
                            }
                        />
                    )}

                    <Form
                        layout="vertical"
                        requiredMark={false}
                        onFinish={(v) =>
                            reset.mutate({
                                email,
                                token,
                                password: v.password,
                                password_confirmation: v.password_confirmation,
                            })
                        }
                    >
                        <Form.Item
                            name="password"
                            label="Mật khẩu mới"
                            extra="Tối thiểu 8 ký tự, gồm chữ hoa, chữ thường và ký tự đặc biệt."
                            rules={[
                                { required: true, message: 'Nhập mật khẩu mới' },
                                () => ({
                                    validator(_, value) {
                                        if (!value) return Promise.resolve();
                                        if (value.length < 8) return Promise.reject(new Error('Mật khẩu phải có ít nhất 8 ký tự'));
                                        if (!/[A-Z]/.test(value)) return Promise.reject(new Error('Mật khẩu phải có ít nhất 1 chữ in hoa'));
                                        if (!/[a-z]/.test(value)) return Promise.reject(new Error('Mật khẩu phải có ít nhất 1 chữ thường'));
                                        if (!/[^A-Za-z0-9]/.test(value)) return Promise.reject(new Error('Mật khẩu phải có ít nhất 1 ký tự đặc biệt'));
                                        return Promise.resolve();
                                    },
                                }),
                            ]}
                            hasFeedback
                        >
                            <Input.Password
                                autoFocus
                                size="large"
                                prefix={<LockOutlined style={{ color: '#9ca3af' }} />}
                                placeholder="≥8 ký tự, gồm chữ hoa, chữ thường & ký tự đặc biệt"
                            />
                        </Form.Item>

                        <Form.Item
                            name="password_confirmation"
                            label="Nhập lại mật khẩu mới"
                            dependencies={['password']}
                            hasFeedback
                            rules={[
                                { required: true, message: 'Nhập lại mật khẩu mới' },
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
                                placeholder="Nhập lại mật khẩu mới"
                            />
                        </Form.Item>

                        <Button
                            type="primary"
                            htmlType="submit"
                            block
                            loading={reset.isPending}
                            icon={!reset.isPending ? <ArrowRightOutlined /> : undefined}
                            iconPosition="end"
                        >
                            Đặt lại mật khẩu
                        </Button>
                    </Form>

                    <div className="auth-footer-row">
                        <Link to="/login"><ArrowLeftOutlined /> Quay lại đăng nhập</Link>
                    </div>
                </div>
            </div>
        </div>
    );
}

function BrandPanel() {
    return (
        <div className="auth-brand">
            <div className="auth-scanline" />
            <div className="auth-brand-content">
                <span className="auth-logo-mark"><img src="/images/logocmb.png" alt="CMB Core" /></span>
                <h1>Đặt lại mật khẩu,<br />quay lại công việc.</h1>
                <p className="tagline">
                    Chọn một mật khẩu mạnh để bảo vệ gian hàng và dữ liệu vận hành của bạn.
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
    );
}
