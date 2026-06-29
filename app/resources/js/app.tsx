import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { App as AntApp, ConfigProvider, theme } from 'antd';
import viVN from 'antd/locale/vi_VN';
import 'antd/dist/reset.css';
import '../css/app.css';
import dayjs from 'dayjs';
import 'dayjs/locale/vi';

import { RequireAuth } from '@/components/RequireAuth';
import { AppLayout } from '@/components/AppLayout';
import { DesktopShell } from '@/components/desktop/DesktopShell';
import { useUserPreferences } from '@/lib/preferences';
import { useAuth } from '@/lib/auth';
import { LoginPage } from '@/pages/LoginPage';
import { RegisterPage } from '@/pages/RegisterPage';
import { EmailVerifiedPage } from '@/pages/EmailVerifiedPage';
import { ForgotPasswordPage } from '@/pages/ForgotPasswordPage';
import { ResetPasswordPage } from '@/pages/ResetPasswordPage';
import { PublicTrackingPage } from '@/pages/PublicTrackingPage';
import { DownloadAppPage } from '@/pages/DownloadAppPage';
import { PlansPage } from '@/pages/PlansPage';
import { PublicLayout } from '@/pages/public/PublicLayout';
import SellerLandingPage from '@/pages/public/SellerLandingPage';
import { PricingPage } from '@/pages/public/PricingPage';
import { ToolsPage } from '@/pages/public/ToolsPage';
import { ApiDocsPage } from '@/pages/public/ApiDocsPage';
// Spec 2026-05-17 — admin SPA tách bundle riêng tại `/admin/*` (xem
// `resources/js/admin.tsx`). User SPA không còn route admin nào.
import { NotFoundPage } from '@/pages/NotFoundPage';
import { appRouteElements } from '@/routes/appRoutes';

dayjs.locale('vi');

const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: 1, refetchOnWindowFocus: false, staleTime: 30_000 } },
});

function Root() {
    const { isLoading } = useAuth();
    const prefs = useUserPreferences();
    const shell = prefs.ui_shell;
    return (
        <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
            {/* SPEC 0022 — callback từ link xác thực trong email (BE redirect tới đây). Public. */}
            <Route path="/email-verified" element={<EmailVerifiedPage />} />
            {/* SPEC 0022 — luồng quên / đặt lại mật khẩu. Public (link đặt lại tới từ email). */}
            <Route path="/forgot-password" element={<ForgotPasswordPage />} />
            <Route path="/password-reset" element={<ResetPasswordPage />} />
            {/* SPEC 0030 — trang tra cứu đơn công khai (đơn tự tạo). Public, không cần đăng nhập. */}
            <Route path="/tracking" element={<PublicTrackingPage />} />
            {/* Trang tải ứng dụng di động. Public, không cần đăng nhập. */}
            <Route path="/download" element={<DownloadAppPage />} />
            {/* SPEC 0032 — trang gói full-screen riêng (có nút back), tách khỏi sidebar. */}
            <Route path="/plans" element={<RequireAuth><PlansPage /></RequireAuth>} />
            {/* SPEC 2026-06-26 — site public (marketing). Đặt TRƯỚC catch-all shell. Dashboard dời sang /dashboard. */}
            <Route element={<PublicLayout />}>
                <Route path="/" element={<SellerLandingPage />} />
                <Route path="/pricing" element={<PricingPage />} />
                <Route path="/tools" element={<ToolsPage />} />
                <Route path="/api-docs" element={<ApiDocsPage />} />
            </Route>
            {shell === 'v2' && !isLoading ? (
                <Route path="/*" element={<RequireAuth><DesktopShell /></RequireAuth>} />
            ) : (
                <Route element={<RequireAuth><AppLayout /></RequireAuth>}>
                    {appRouteElements()}
                </Route>
            )}
            <Route path="404" element={<NotFoundPage />} />
            <Route path="*" element={<Navigate to="/404" replace />} />
        </Routes>
    );
}

const el = document.getElementById('app');
if (el) {
    createRoot(el).render(
        <React.StrictMode>
            <QueryClientProvider client={queryClient}>
                <ConfigProvider
                    locale={viVN}
                    theme={{
                        token: {
                            colorPrimary: '#2563EB',
                            colorInfo: '#2563EB',
                            colorSuccess: '#10B981',
                            colorWarning: '#F59E0B',
                            colorError: '#EF4444',
                            colorTextBase: '#0F172A',
                            borderRadius: 8,
                            colorBgLayout: '#F8FAFC',
                            fontFamily: "'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
                        },
                        components: {
                            Layout: { headerHeight: 56, bodyBg: '#F8FAFC', siderBg: '#FFFFFF', headerBg: '#FFFFFF' },
                            Menu: { itemHeight: 38, itemSelectedBg: '#EFF6FF', itemSelectedColor: '#1D4ED8', itemHoverBg: '#EFF6FF' },
                            Button: { primaryShadow: '0 8px 22px rgba(37, 99, 235, 0.22)' },
                        },
                        algorithm: theme.defaultAlgorithm,
                    }}
                >
                    <AntApp>
                        <BrowserRouter>
                            <Root />
                        </BrowserRouter>
                    </AntApp>
                </ConfigProvider>
            </QueryClientProvider>
        </React.StrictMode>,
    );
}
