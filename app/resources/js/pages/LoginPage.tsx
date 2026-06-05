import { Button, Form, Input, Alert, Checkbox } from 'antd';
import { Link, useNavigate } from 'react-router-dom';
import {
    MailOutlined,
    LockOutlined,
    ThunderboltOutlined,
    RiseOutlined,
    RobotOutlined,
    AccountBookOutlined,
    ClusterOutlined,
    ArrowRightOutlined,
} from '@ant-design/icons';
import { useLogin } from '@/lib/auth';
import { errorMessage } from '@/lib/api';
import { AuthBackdrop } from '@/components/AuthBackdrop';

export function LoginPage() {
    const login = useLogin();
    const navigate = useNavigate();

    return (
        <div className="auth-shell">
            <div className="auth-brand">
                <div className="auth-scanline" />
                <AuthBackdrop />
                <div className="auth-brand-content">
                    <span className="auth-logo-mark"><img src="/images/logocmb.png" alt="CMB Core" /></span>
                    <h1>Bán nhiều hơn,<br />làm ít việc tay hơn.</h1>
                    <p className="tagline">
                        Một nền tảng lo trọn cho seller đa kênh: bán hàng, quảng cáo AI,
                        chăm sóc khách và kế toán — không còn nhảy giữa chục công cụ.
                    </p>
                    <ul className="auth-feature-list">
                        <li>
                            <span className="feat-icon"><RiseOutlined /></span>
                            <span><b>Giám sát &amp; tối ưu quảng cáo bằng AI</b> — theo dõi Facebook Ads liên tục, cảnh báo và gợi ý để giảm chi phí, tăng đơn.</span>
                        </li>
                        <li>
                            <span className="feat-icon"><RobotOutlined /></span>
                            <span><b>Chốt sale tự động ngay trong khung chat</b> — nhắn tin chăm sóc đa kênh, luồng AI tự trả lời và lên đơn cho khách.</span>
                        </li>
                        <li>
                            <span className="feat-icon"><AccountBookOutlined /></span>
                            <span><b>Kế toán &amp; đối soát tự động</b> — khớp tiền sàn, soi lãi/lỗ từng đơn, sổ sách luôn sạch.</span>
                        </li>
                        <li>
                            <span className="feat-icon"><ThunderboltOutlined /></span>
                            <span><b>Đồng bộ đa sàn thời gian thực</b> — đơn, tồn kho, vận chuyển TikTok Shop · Lazada gom về một nơi.</span>
                        </li>
                        <li>
                            <span className="feat-icon"><ClusterOutlined /></span>
                            <span><b>Tồn kho nhiều kho &amp; dự báo nhập hàng</b> — hết khi nào nhập khi đó, không lo tồn đọng.</span>
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
                            label="Email / Tên đăng nhập"
                            rules={[{ required: true, message: 'Nhập email hoặc tên đăng nhập' }]}
                        >
                            <Input
                                autoFocus
                                size="large"
                                prefix={<MailOutlined style={{ color: '#9ca3af' }} />}
                                placeholder="email@vidu.com hoặc ten@mãshop"
                            />
                        </Form.Item>

                        <Form.Item
                            name="password"
                            label="Mật khẩu"
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
                                <Link to="/forgot-password" style={{ fontWeight: 500 }}>Quên mật khẩu?</Link>
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
