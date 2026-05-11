import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { App as AntApp, ConfigProvider } from 'antd';
import viVN from 'antd/locale/vi_VN';
import 'antd/dist/reset.css';
import dayjs from 'dayjs';
import 'dayjs/locale/vi';

import { RequireAuth } from '@/components/RequireAuth';
import { AppLayout } from '@/components/AppLayout';
import { LoginPage } from '@/pages/LoginPage';
import { RegisterPage } from '@/pages/RegisterPage';
import { DashboardPage } from '@/pages/DashboardPage';
import { ChannelsPage } from '@/pages/ChannelsPage';
import { NotFoundPage } from '@/pages/NotFoundPage';

dayjs.locale('vi');

const queryClient = new QueryClient({
    defaultOptions: {
        queries: { retry: 1, refetchOnWindowFocus: false, staleTime: 30_000 },
    },
});

/** Placeholder for module pages not built yet. */
function ComingSoon({ title }: { title: string }) {
    return (
        <div>
            <h2>{title}</h2>
            <p>Tính năng này sẽ được xây dựng theo roadmap (xem <code>docs/00-overview/roadmap.md</code>).</p>
        </div>
    );
}

function Root() {
    return (
        <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
            <Route
                element={
                    <RequireAuth>
                        <AppLayout />
                    </RequireAuth>
                }
            >
                <Route index element={<DashboardPage />} />
                <Route path="orders" element={<ComingSoon title="Đơn hàng" />} />
                <Route path="channels" element={<ChannelsPage />} />
                <Route path="products" element={<ComingSoon title="Sản phẩm & SKU" />} />
                <Route path="inventory" element={<ComingSoon title="Tồn kho" />} />
                <Route path="fulfillment" element={<ComingSoon title="Giao hàng & in" />} />
                <Route path="settings" element={<ComingSoon title="Cài đặt" />} />
            </Route>
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
                <ConfigProvider locale={viVN}>
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
