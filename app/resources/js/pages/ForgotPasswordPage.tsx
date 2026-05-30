import { Button, Form, Input, Alert } from 'antd';
import { Link } from 'react-router-dom';
import {
    MailOutlined,
    ThunderboltOutlined,
    SafetyCertificateOutlined,
    ClusterOutlined,
    ArrowRightOutlined,
    ArrowLeftOutlined,
    CheckCircleOutlined,
} from '@ant-design/icons';
import { useForgotPassword } from '@/lib/auth';
import { errorMessage } from '@/lib/api';

/**
 * SPEC 0022 — bước 1 của luồng quên mật khẩu: nhập email để nhận link đặt lại.
 * Public. BE trả response generic (chống enumerate) ⇒ luôn hiện cùng một thông
 * báo "đã gửi" dù email có tồn tại hay không.
 */
export function ForgotPasswordPage() {
    const forgot = useForgotPassword();

    return (
        <div className="auth-shell">
            <div className="auth-brand">
                <div className="auth-scanline" />
                <div className="auth-brand-content">
                    <span className="auth-logo-mark"><img src="/images/logocmb.png" alt="CMB Core" /></span>
                    <h1>Lấy lại quyền truy cập<br />trong vài bước.</h1>
                    <p className="tagline">
                        Nhập email tài khoản, chúng tôi sẽ gửi liên kết đặt lại mật khẩu để bạn quay lại công việc nhanh nhất.
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
                    <h2>Quên mật khẩu?</h2>
                    <p className="sub">Nhập email tài khoản để nhận liên kết đặt lại mật khẩu.</p>

                    {forgot.isSuccess ? (
                        <Alert
                            type="success"
                            showIcon
                            icon={<CheckCircleOutlined />}
                            style={{ marginBottom: 16 }}
                            message="Đã gửi yêu cầu"
                            description="Nếu email vừa nhập khớp với một tài khoản, chúng tôi đã gửi liên kết đặt lại mật khẩu (hiệu lực 60 phút). Vui lòng kiểm tra hộp thư đến và cả mục spam."
                        />
                    ) : (
                        <>
                            {forgot.isError && (
                                <Alert
                                    type="error"
                                    showIcon
                                    style={{ marginBottom: 16 }}
                                    message={errorMessage(forgot.error, 'Không gửi được yêu cầu. Vui lòng thử lại.')}
                                />
                            )}

                            <Form
                                layout="vertical"
                                requiredMark={false}
                                onFinish={(v) => forgot.mutate(v)}
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

                                <Button
                                    type="primary"
                                    htmlType="submit"
                                    block
                                    loading={forgot.isPending}
                                    icon={!forgot.isPending ? <ArrowRightOutlined /> : undefined}
                                    iconPosition="end"
                                >
                                    Gửi liên kết đặt lại
                                </Button>
                            </Form>
                        </>
                    )}

                    <div className="auth-footer-row">
                        <Link to="/login"><ArrowLeftOutlined /> Quay lại đăng nhập</Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
