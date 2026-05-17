// Spec 2026-05-17 — gate component. Wrap mọi route admin trừ `/admin/login`.
// 401 từ /auth/me ⇒ redirect /admin/login. Loading ⇒ spinner full-page.

import { ReactNode } from 'react';
import { Navigate } from 'react-router-dom';
import { Spin } from 'antd';
import { useAdminMe } from './lib/adminAuth';

export function AdminProtected({ children }: { children: ReactNode }) {
    const { data, isLoading } = useAdminMe();

    if (isLoading) {
        return (
            <div style={{ padding: 64, textAlign: 'center' }}>
                <Spin size="large" />
            </div>
        );
    }
    if (!data) {
        return <Navigate to="/admin/login" replace />;
    }

    return <>{children}</>;
}
