import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import { Alert, Button, Space, Tooltip, Typography } from 'antd';
import {
    CheckCircleOutlined,
    ClockCircleOutlined,
    InboxOutlined,
    LogoutOutlined,
    MailOutlined,
    QuestionCircleOutlined,
    ReloadOutlined,
    SearchOutlined,
    SendOutlined,
    WarningOutlined,
} from '@ant-design/icons';
import { AuthUser, useLogout, useResendVerification } from '@/lib/auth';
import { errorMessage } from '@/lib/api';

const RESEND_COOLDOWN_SECONDS = 60;

/**
 * SPEC 0022 — màn xác thực email (gate).
 *
 * User đã login nhưng `email_verified_at = null` ⇒ RequireAuth render trang này
 * thay vì AppLayout ⇒ user KHÔNG vào được bất kỳ tính năng nào khác.
 *
 * Cung cấp:
 *  - Hướng dẫn từng bước (mở hộp thư → tìm email → KIỂM TRA SPAM → click link).
 *  - Nút "Gửi lại email" có cooldown 60s.
 *  - Nút "Tôi đã xác thực" — refetch /auth/me để bỏ gate khi BE đã verify.
 *  - Logout — phòng trường hợp đăng ký nhầm email.
 */
export function VerifyEmailPage({ user }: { user: AuthUser }) {
    const qc = useQueryClient();
    const navigate = useNavigate();
    const resend = useResendVerification();
    const logout = useLogout();
    const [cooldown, setCooldown] = useState(0);
    const [justSent, setJustSent] = useState(false);

    useEffect(() => {
        if (cooldown <= 0) return;
        const t = setTimeout(() => setCooldown((s) => s - 1), 1000);
        return () => clearTimeout(t);
    }, [cooldown]);

    const handleResend = () => {
        resend.mutate(undefined, {
            onSuccess: (r) => {
                setJustSent(true);
                setCooldown(RESEND_COOLDOWN_SECONDS);
                // Đã verified rồi ⇒ refetch /me để gate tự bỏ.
                if (r.sent === false && r.reason === 'already_verified') {
                    qc.invalidateQueries({ queryKey: ['me'] });
                }
            },
            onError: () => setJustSent(false),
        });
    };

    const handleRefresh = async () => {
        await qc.invalidateQueries({ queryKey: ['me'] });
    };

    const handleLogout = () => {
        logout.mutate(undefined, {
            onSuccess: () => navigate('/login', { replace: true }),
        });
    };

    return (
        <div className="verify-email-shell">
            <div className="verify-email-card">

                {/* Brand + hero icon */}
                <div className="verify-email-brand">
                    <img src="/images/logocmb.png" alt="CMBcoreSeller" />
                    <span>CMBcoreSeller</span>
                </div>

                <div className="verify-email-hero-icon">
                    <MailOutlined />
                </div>

                <Typography.Title level={2} style={{ marginTop: 0, marginBottom: 8, textAlign: 'center', fontWeight: 700, letterSpacing: '-0.01em' }}>
                    Xác thực địa chỉ email
                </Typography.Title>

                <Typography.Paragraph style={{ textAlign: 'center', color: '#475569', marginBottom: 8 }}>
                    Chúng tôi đã gửi một email xác thực tới
                </Typography.Paragraph>

                <Typography.Paragraph style={{ textAlign: 'center', marginBottom: 24 }}>
                    <strong style={{ fontSize: 16, color: '#0F172A' }}>{user.email}</strong>
                </Typography.Paragraph>

                <Typography.Paragraph style={{ color: '#475569', marginBottom: 20 }}>
                    Vui lòng <strong>xác thực email</strong> để mở khóa toàn bộ tính năng quản lý đơn, kho và đối soát đa sàn. Trong lúc chờ, các tính năng khác đã được tạm khóa để bảo vệ tài khoản của bạn.
                </Typography.Paragraph>

                {/* Inline state alerts */}
                {justSent && !resend.isError && resend.data?.sent && (
                    <Alert
                        type="success"
                        showIcon
                        icon={<CheckCircleOutlined />}
                        message="Đã gửi lại email xác thực"
                        description={<>Vui lòng kiểm tra hộp thư <strong>{user.email}</strong> (đừng quên thư mục Spam / Quảng cáo). Email có thể tới sau 1–2 phút.</>}
                        style={{ marginBottom: 16 }}
                    />
                )}

                {justSent && resend.data?.sent === false && resend.data?.reason === 'already_verified' && (
                    <Alert
                        type="success"
                        showIcon
                        icon={<CheckCircleOutlined />}
                        message="Email của bạn đã được xác thực"
                        description="Đang chuyển sang bảng điều khiển..."
                        style={{ marginBottom: 16 }}
                    />
                )}

                {resend.isError && (
                    <Alert
                        type="error"
                        showIcon
                        message={errorMessage(resend.error, 'Không gửi lại được. Vui lòng thử sau ít phút.')}
                        style={{ marginBottom: 16 }}
                    />
                )}

                {/* Hướng dẫn từng bước */}
                <div className="verify-steps">
                    <div className="verify-step-title">Cách lấy link xác thực:</div>

                    <Step
                        n={1}
                        icon={<InboxOutlined />}
                        title="Mở hộp thư email"
                        desc={<>Đăng nhập vào hộp thư <strong>{user.email}</strong> trên trình duyệt hoặc app email của bạn.</>}
                    />

                    <Step
                        n={2}
                        icon={<SearchOutlined />}
                        title={<>Tìm email có tiêu đề <code>[CMBcoreSeller] Xác thực địa chỉ email</code></>}
                        desc={<>Email được gửi từ <strong>no-reply@cmbcore.com</strong>. Có thể mất 1–2 phút để tới hộp thư.</>}
                    />

                    <Step
                        n={3}
                        icon={<WarningOutlined />}
                        title={<>Không thấy email? <strong>Kiểm tra thư mục Spam / Junk / Quảng cáo</strong></>}
                        desc={
                            <>
                                Email tự động đôi khi bị các nhà cung cấp (Gmail, Outlook, Yahoo...) lọc nhầm vào:
                                <ul style={{ margin: '6px 0 0 18px', padding: 0, color: '#64748B' }}>
                                    <li><strong>Gmail:</strong> mục <em>Spam</em>, tab <em>Quảng cáo</em> hoặc <em>Cập nhật</em></li>
                                    <li><strong>Outlook:</strong> mục <em>Junk Email</em> hoặc <em>Other</em></li>
                                    <li><strong>Yahoo Mail:</strong> mục <em>Spam</em> hoặc <em>Bulk</em></li>
                                </ul>
                                Nếu tìm thấy, hãy đánh dấu <em>"Không phải spam"</em> để email sau vào thẳng hộp thư chính.
                            </>
                        }
                        highlight
                    />

                    <Step
                        n={4}
                        icon={<SendOutlined />}
                        title={<>Bấm nút <strong>"Xác thực email →"</strong> trong email</>}
                        desc={
                            <>
                                Hoặc sao chép đường dẫn dưới nút (bắt đầu bằng <code>{window.location.origin}/api/v1/auth/email/verify/...</code>) dán vào trình duyệt.
                                <Space size={6} style={{ marginTop: 6, color: '#92400E' }}>
                                    <ClockCircleOutlined />
                                    <span><strong>Link hết hạn sau 60 phút</strong> kể từ lúc gửi.</span>
                                </Space>
                            </>
                        }
                    />
                </div>

                {/* Actions */}
                <Space direction="vertical" size={10} style={{ width: '100%', marginTop: 20 }}>
                    <Button
                        type="primary"
                        size="large"
                        block
                        icon={<SendOutlined />}
                        onClick={handleResend}
                        loading={resend.isPending}
                        disabled={cooldown > 0}
                    >
                        {cooldown > 0 ? `Gửi lại email (chờ ${cooldown}s)` : 'Gửi lại email xác thực'}
                    </Button>

                    <Tooltip title="Đã bấm xác thực trong email mà trang chưa đổi? Bấm đây để kiểm tra lại.">
                        <Button
                            size="large"
                            block
                            icon={<ReloadOutlined />}
                            onClick={handleRefresh}
                        >
                            Tôi đã xác thực — Tải lại
                        </Button>
                    </Tooltip>
                </Space>

                {/* Helpdesk */}
                <div className="verify-email-help">
                    <div>
                        <QuestionCircleOutlined /> Sai địa chỉ email?{' '}
                        <Typography.Link onClick={handleLogout}>
                            <LogoutOutlined /> Đăng xuất và đăng ký lại
                        </Typography.Link>
                    </div>
                    <div style={{ marginTop: 6 }}>
                        Cần hỗ trợ? Liên hệ{' '}
                        <a href="mailto:support@cmbcore.com">support@cmbcore.com</a>
                    </div>
                </div>

            </div>

            <div className="verify-email-footer">
                &copy; {new Date().getFullYear()} CMBcoreSeller. All rights reserved.
            </div>
        </div>
    );
}

function Step({
    n, icon, title, desc, highlight = false,
}: {
    n: number;
    icon: React.ReactNode;
    title: React.ReactNode;
    desc: React.ReactNode;
    highlight?: boolean;
}) {
    return (
        <div className={`verify-step${highlight ? ' verify-step-highlight' : ''}`}>
            <div className="verify-step-num">{n}</div>
            <div className="verify-step-body">
                <div className="verify-step-head">
                    <span className="verify-step-icon">{icon}</span>
                    <span className="verify-step-title-text">{title}</span>
                </div>
                <div className="verify-step-desc">{desc}</div>
            </div>
        </div>
    );
}
