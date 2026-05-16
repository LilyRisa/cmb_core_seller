import { useEffect, useMemo } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import { Button, Result } from 'antd';
import {
    ArrowRightOutlined,
    CheckCircleFilled,
    CloseCircleFilled,
    InfoCircleFilled,
    ReloadOutlined,
} from '@ant-design/icons';
import { useAuth } from '@/lib/auth';

/**
 * SPEC 0022 — trang callback sau khi user click link xác thực trong email.
 *
 * BE `GET /auth/email/verify/{id}/{hash}` redirect tới đây với
 * `?status=success | already | invalid`. Trang này public — user có thể chưa
 * đăng nhập (click link từ thiết bị khác) hoặc đã đăng nhập.
 */
export function EmailVerifiedPage() {
    const [params] = useSearchParams();
    const navigate = useNavigate();
    const qc = useQueryClient();
    const { data: user } = useAuth();
    const status = useMemo(() => {
        const raw = params.get('status');
        return raw === 'success' || raw === 'already' || raw === 'invalid' ? raw : 'invalid';
    }, [params]);

    // Verify thành công ⇒ refetch /auth/me để gate FE bỏ ngay khi user đang login.
    useEffect(() => {
        if (status === 'success' || status === 'already') {
            qc.invalidateQueries({ queryKey: ['me'] });
        }
    }, [status, qc]);

    const goNext = () => navigate(user ? '/' : '/login', { replace: true });

    if (status === 'success') {
        return (
            <Shell>
                <Result
                    status="success"
                    icon={<CheckCircleFilled style={{ color: '#10B981' }} />}
                    title="Đã xác thực email thành công 🎉"
                    subTitle={
                        <span>
                            Tài khoản <strong>{user?.email ?? 'của bạn'}</strong> đã được kích hoạt. Bạn có thể bắt đầu sử dụng toàn bộ tính năng CMBcoreSeller ngay.
                        </span>
                    }
                    extra={[
                        <Button key="go" type="primary" size="large" icon={<ArrowRightOutlined />} iconPosition="end" onClick={goNext}>
                            {user ? 'Vào bảng điều khiển' : 'Đăng nhập ngay'}
                        </Button>,
                    ]}
                />
            </Shell>
        );
    }

    if (status === 'already') {
        return (
            <Shell>
                <Result
                    status="info"
                    icon={<InfoCircleFilled style={{ color: '#0EA5E9' }} />}
                    title="Email này đã được xác thực trước đó"
                    subTitle="Bạn không cần xác thực lại. Đăng nhập để tiếp tục sử dụng."
                    extra={[
                        <Button key="go" type="primary" size="large" icon={<ArrowRightOutlined />} iconPosition="end" onClick={goNext}>
                            {user ? 'Vào bảng điều khiển' : 'Đăng nhập'}
                        </Button>,
                    ]}
                />
            </Shell>
        );
    }

    return (
        <Shell>
            <Result
                status="error"
                icon={<CloseCircleFilled style={{ color: '#EF4444' }} />}
                title="Link xác thực không hợp lệ hoặc đã hết hạn"
                subTitle={
                    <div style={{ maxWidth: 480, margin: '0 auto', textAlign: 'left' }}>
                        <p style={{ marginBottom: 8 }}>Một số nguyên nhân thường gặp:</p>
                        <ul style={{ marginTop: 0, paddingLeft: 20, color: '#475569' }}>
                            <li>Link <strong>đã quá 60 phút</strong> kể từ lúc gửi.</li>
                            <li>Bạn đã sao chép thiếu ký tự khi paste vào trình duyệt.</li>
                            <li>Đường dẫn bị email-client tự chèn theo dõi (tracking redirect) làm hỏng chữ ký.</li>
                        </ul>
                        <p style={{ marginTop: 12 }}>Giải pháp: đăng nhập rồi bấm <strong>"Gửi lại email xác thực"</strong> để nhận link mới (60 phút hiệu lực).</p>
                    </div>
                }
                extra={[
                    user ? (
                        <Button key="back" type="primary" size="large" icon={<ReloadOutlined />} onClick={() => navigate('/', { replace: true })}>
                            Quay lại để gửi lại email
                        </Button>
                    ) : (
                        <Link key="login" to="/login">
                            <Button type="primary" size="large" icon={<ArrowRightOutlined />} iconPosition="end">
                                Đăng nhập để gửi lại email
                            </Button>
                        </Link>
                    ),
                ]}
            />
        </Shell>
    );
}

function Shell({ children }: { children: React.ReactNode }) {
    return (
        <div className="verify-email-shell">
            <div className="verify-email-top">
                <div className="brand">
                    <img src="/images/logocmb.png" alt="CMBcoreSeller" />
                    <span>CMBcoreSeller</span>
                </div>
                <div className="tag">Quản lý bán hàng đa sàn</div>
            </div>

            <div className="verify-email-card verify-result-card">{children}</div>

            <div className="verify-email-footer">
                <span>&copy; {new Date().getFullYear()} CMBcoreSeller</span>
                <span className="dot" />
                <span>Quản lý bán hàng đa sàn TikTok · Shopee · Lazada</span>
            </div>
        </div>
    );
}
