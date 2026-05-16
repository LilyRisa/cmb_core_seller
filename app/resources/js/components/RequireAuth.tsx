import { ReactNode } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { Spin } from 'antd';
import { useAuth } from '@/lib/auth';
import { VerifyEmailPage } from '@/pages/VerifyEmailPage';

/**
 * Redirects to /login when not authenticated; shows a spinner while loading.
 *
 * SPEC 0022 — nếu đã đăng nhập nhưng `email_verified_at = null` ⇒ render
 * VerifyEmailPage thay cho children. Toàn bộ app khác bị chặn cứng tại đây,
 * khớp với hard gating ở BE (middleware `verified` → 403 EMAIL_NOT_VERIFIED).
 */
export function RequireAuth({ children }: { children: ReactNode }) {
    const { data: user, isLoading } = useAuth();
    const location = useLocation();

    if (isLoading) {
        return (
            <div style={{ display: 'grid', placeItems: 'center', height: '100vh' }}>
                <Spin size="large" />
            </div>
        );
    }

    if (!user) {
        return <Navigate to="/login" replace state={{ from: location.pathname }} />;
    }

    if (!user.email_verified_at) {
        return <VerifyEmailPage user={user} />;
    }

    return <>{children}</>;
}
