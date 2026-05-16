import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import { Button, Tooltip } from 'antd';
import {
    CheckCircleFilled,
    ClockCircleOutlined,
    CloseCircleFilled,
    InboxOutlined,
    LogoutOutlined,
    ReloadOutlined,
    SearchOutlined,
    SendOutlined,
    WarningFilled,
} from '@ant-design/icons';
import { AuthUser, useLogout, useResendVerification } from '@/lib/auth';
import { errorMessage } from '@/lib/api';

const RESEND_COOLDOWN_SECONDS = 60;

/**
 * SPEC 0022 — màn xác thực email (gate).
 *
 * Editorial / postal aesthetic: nền navy continuity với LoginPage, card giấy
 * kem có họa tiết phong bì SVG + headline serif (Fraunces). User đã login
 * nhưng `email_verified_at = null` ⇒ RequireAuth render trang này.
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
            <div className="verify-email-top">
                <div className="brand">
                    <img src="/images/logocmb.png" alt="CMBcoreSeller" />
                    <span>CMBcoreSeller</span>
                </div>
                <div className="tag">Quản lý bán hàng đa sàn</div>
            </div>

            <div className="verify-email-card">
                <div className="verify-email-hero">
                    <div className="verify-envelope" aria-hidden="true">
                        <EnvelopeMark />
                    </div>

                    <div className="verify-eyebrow">Xác thực tài khoản</div>

                    <h1 className="verify-email-headline">
                        Còn một bước nữa,<br />
                        hãy <em>xác thực email</em> để bắt đầu.
                    </h1>

                    <p className="verify-email-leader">
                        Chúng tôi đã gửi đường dẫn xác thực đến{' '}
                        <span className="email-addr">{user.email}</span>. Bấm vào liên kết trong email để
                        mở khoá đầy đủ tính năng — đơn hàng, kho và đối soát đa sàn.
                    </p>
                </div>

                {justSent && !resend.isError && resend.data?.sent && (
                    <div className="verify-email-alert is-success">
                        <CheckCircleFilled />
                        <div>
                            <strong>Đã gửi lại email xác thực.</strong> Kiểm tra hộp thư{' '}
                            <strong>{user.email}</strong> (kể cả thư mục Spam / Quảng cáo). Email thường
                            tới sau 1–2 phút.
                        </div>
                    </div>
                )}

                {justSent && resend.data?.sent === false && resend.data?.reason === 'already_verified' && (
                    <div className="verify-email-alert is-success">
                        <CheckCircleFilled />
                        <div>
                            <strong>Email của bạn đã được xác thực.</strong> Đang chuyển sang bảng điều khiển…
                        </div>
                    </div>
                )}

                {resend.isError && (
                    <div className="verify-email-alert is-error">
                        <CloseCircleFilled />
                        <div>{errorMessage(resend.error, 'Không gửi lại được. Vui lòng thử sau ít phút.')}</div>
                    </div>
                )}

                <div className="verify-steps">
                    <div className="verify-steps-label">Bốn bước đơn giản</div>

                    <Step
                        n="01"
                        icon={<InboxOutlined />}
                        title="Mở hộp thư email"
                        desc={
                            <>
                                Đăng nhập vào hộp thư <strong>{user.email}</strong> trên trình duyệt hoặc app
                                email bạn đang dùng.
                            </>
                        }
                    />

                    <Step
                        n="02"
                        icon={<SearchOutlined />}
                        title={
                            <>
                                Tìm email tiêu đề <code>[CMBcoreSeller] Xác thực địa chỉ email</code>
                            </>
                        }
                        desc={
                            <>
                                Gửi từ <strong>no-reply@cmbcore.com</strong>. Có thể mất 1–2 phút để tới hộp
                                thư của bạn.
                            </>
                        }
                    />

                    <Step
                        n="03"
                        icon={<WarningFilled />}
                        highlight
                        title={
                            <>
                                Không thấy? <strong>Kiểm tra thư mục Spam / Quảng cáo</strong>
                            </>
                        }
                        desc={
                            <>
                                Email tự động hay bị Gmail/Outlook/Yahoo lọc nhầm vào các mục phụ. Hãy thử
                                xem:
                                <div className="verify-step-callout">
                                    <ul>
                                        <li>
                                            <strong>Gmail</strong> — mục <em>Spam</em>, tab <em>Quảng cáo</em>{' '}
                                            hoặc <em>Cập nhật</em>
                                        </li>
                                        <li>
                                            <strong>Outlook</strong> — mục <em>Junk Email</em> / <em>Other</em>
                                        </li>
                                        <li>
                                            <strong>Yahoo Mail</strong> — mục <em>Spam</em> hoặc <em>Bulk</em>
                                        </li>
                                    </ul>
                                </div>
                                Nếu tìm thấy, đánh dấu <em>"Không phải spam"</em> để email sau vào thẳng hộp
                                thư chính.
                            </>
                        }
                    />

                    <Step
                        n="04"
                        icon={<SendOutlined />}
                        title={
                            <>
                                Bấm nút <strong>"Xác thực email →"</strong> trong email
                            </>
                        }
                        desc={
                            <>
                                Hoặc copy đường dẫn (bắt đầu bằng{' '}
                                <code>{window.location.origin}/api/v1/auth/email/verify/…</code>) rồi dán vào
                                trình duyệt.
                                <div className="verify-step-meta">
                                    <ClockCircleOutlined />
                                    <span>Link có hiệu lực 60 phút kể từ lúc gửi.</span>
                                </div>
                            </>
                        }
                    />
                </div>

                <div className="verify-actions">
                    <Button
                        type="primary"
                        size="large"
                        icon={<SendOutlined />}
                        onClick={handleResend}
                        loading={resend.isPending}
                        disabled={cooldown > 0}
                    >
                        {cooldown > 0 ? `Gửi lại sau ${cooldown}s` : 'Gửi lại email xác thực'}
                    </Button>

                    <Tooltip title="Đã bấm xác thực trong email mà trang chưa đổi? Bấm để kiểm tra lại trạng thái.">
                        <Button size="large" icon={<ReloadOutlined />} onClick={handleRefresh}>
                            Tôi đã xác thực — Tải lại
                        </Button>
                    </Tooltip>
                </div>

                <div className="verify-email-help-bar">
                    <span>
                        Sai địa chỉ email?{' '}
                        <a onClick={handleLogout}>
                            <LogoutOutlined />Đăng xuất và đăng ký lại
                        </a>
                    </span>
                    <span>
                        Cần hỗ trợ?{' '}
                        <a href="mailto:support@cmbcore.com">support@cmbcore.com</a>
                    </span>
                </div>
            </div>

            <div className="verify-email-footer">
                <span>&copy; {new Date().getFullYear()} CMBcoreSeller</span>
                <span className="dot" />
                <span>Quản lý bán hàng đa sàn TikTok · Shopee · Lazada</span>
            </div>
        </div>
    );
}

function Step({
    n,
    icon,
    title,
    desc,
    highlight = false,
}: {
    n: string;
    icon: React.ReactNode;
    title: React.ReactNode;
    desc: React.ReactNode;
    highlight?: boolean;
}) {
    return (
        <div className={`verify-step-row${highlight ? ' is-highlight' : ''}`}>
            <span className="verify-step-num">{n}</span>
            <span className="verify-step-icon-cell">{icon}</span>
            <div>
                <div className="verify-step-title-text">{title}</div>
                <div className="verify-step-desc">{desc}</div>
            </div>
        </div>
    );
}

/**
 * Inline SVG postal envelope — letter peeking out the top with a CMB stamp.
 * Inline keeps it themable + zero extra HTTP request.
 */
function EnvelopeMark() {
    return (
        <svg viewBox="0 0 220 160" xmlns="http://www.w3.org/2000/svg">
            <ellipse cx="110" cy="150" rx="86" ry="3" fill="#0B1437" opacity="0.16" />

            {/* Letter (paper sheet) behind/inside the envelope */}
            <rect x="38" y="6" width="144" height="104" rx="2" fill="#FFFEF7" stroke="#0B1437" strokeWidth="1.4" />
            <line x1="52" y1="22" x2="170" y2="22" stroke="#0B1437" strokeWidth="1.6" opacity="0.78" strokeLinecap="round" />
            <line x1="52" y1="36" x2="152" y2="36" stroke="#0B1437" strokeWidth="0.7" opacity="0.30" strokeLinecap="round" />
            <line x1="52" y1="46" x2="162" y2="46" stroke="#0B1437" strokeWidth="0.7" opacity="0.30" strokeLinecap="round" />
            <line x1="52" y1="56" x2="138" y2="56" stroke="#0B1437" strokeWidth="0.7" opacity="0.30" strokeLinecap="round" />
            <line x1="52" y1="66" x2="160" y2="66" stroke="#0B1437" strokeWidth="0.7" opacity="0.30" strokeLinecap="round" />
            <line x1="52" y1="76" x2="120" y2="76" stroke="#0B1437" strokeWidth="0.7" opacity="0.30" strokeLinecap="round" />

            {/* Stamp on letter — gold, perforated edges, slight rotation */}
            <g transform="translate(154 24) rotate(7)">
                <rect
                    x="-14"
                    y="-14"
                    width="28"
                    height="32"
                    fill="#C8923D"
                    stroke="#FAF7F0"
                    strokeWidth="2"
                    strokeDasharray="1.6 1.4"
                />
                <rect x="-14" y="-14" width="28" height="32" fill="none" stroke="#0B1437" strokeWidth="0.8" />
                <text
                    x="0"
                    y="-1"
                    fontFamily="Fraunces, Georgia, serif"
                    fontSize="9.5"
                    fontWeight="700"
                    fill="#0B1437"
                    textAnchor="middle"
                >
                    CMB
                </text>
                <text x="0" y="10" fontFamily="Fraunces, Georgia, serif" fontSize="5.5" fill="#0B1437" textAnchor="middle">
                    VERIFY
                </text>
            </g>

            {/* Envelope front — cover bottom half of letter, with subtle bottom-shade stripe */}
            <rect x="20" y="80" width="180" height="68" rx="3" fill="#1668dc" />
            {/* Open-flap V crease behind the letter — visible at the envelope opening */}
            <path d="M20 80 L110 130 L200 80" fill="none" stroke="#0B1437" strokeWidth="1.2" opacity="0.55" />
            {/* Bottom shadow band on envelope */}
            <rect x="20" y="130" width="180" height="18" rx="3" fill="#0B1437" opacity="0.14" />
            {/* Subtle top edge highlight on envelope front */}
            <path d="M20 80 L200 80" stroke="#FFFFFF" strokeWidth="1" opacity="0.20" />
        </svg>
    );
}
