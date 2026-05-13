import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { App as AntApp, ConfigProvider, theme } from 'antd';
import { Result, Typography } from 'antd';
import viVN from 'antd/locale/vi_VN';
import 'antd/dist/reset.css';
import '../css/app.css';
import dayjs from 'dayjs';
import 'dayjs/locale/vi';

import { RequireAuth } from '@/components/RequireAuth';
import { AppLayout } from '@/components/AppLayout';
import { LoginPage } from '@/pages/LoginPage';
import { RegisterPage } from '@/pages/RegisterPage';
import { DashboardPage } from '@/pages/DashboardPage';
import { OrdersPage } from '@/pages/OrdersPage';
import { OrderDetailPage } from '@/pages/OrderDetailPage';
import { ChannelsPage } from '@/pages/ChannelsPage';
import { SyncLogsPage } from '@/pages/SyncLogsPage';
import { CustomersPage } from '@/pages/CustomersPage';
import { CustomerDetailPage } from '@/pages/CustomerDetailPage';
import { InventoryPage } from '@/pages/InventoryPage';
import { CreateSkuPage } from '@/pages/CreateSkuPage';
import { CreateOrderPage } from '@/pages/CreateOrderPage';
import { CarrierAccountsPage } from '@/pages/CarrierAccountsPage';
import { SettingsLayout } from '@/components/SettingsLayout';
import { SettingsMembersPage } from '@/pages/SettingsMembersPage';
import { SettingsProfilePage } from '@/pages/SettingsProfilePage';
import { SettingsWorkspacePage } from '@/pages/SettingsWorkspacePage';
import { SettingsOrdersPage } from '@/pages/SettingsOrdersPage';
import { SettingsPrintPage } from '@/pages/SettingsPrintPage';
import { SuppliersPage } from '@/pages/SuppliersPage';
import { PurchaseOrdersPage } from '@/pages/PurchaseOrdersPage';
import { ReportsPage } from '@/pages/ReportsPage';
import { SettlementsPage } from '@/pages/SettlementsPage';
import { NotFoundPage } from '@/pages/NotFoundPage';

dayjs.locale('vi');

const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: 1, refetchOnWindowFocus: false, staleTime: 30_000 } },
});

/** Placeholder for module pages not built yet (Phase 2+). */
function ComingSoon({ title, phase }: { title: string; phase?: string }) {
    return (
        <Result
            icon={<span style={{ fontSize: 48 }}>🚧</span>}
            title={title}
            subTitle={<Typography.Text type="secondary">Tính năng này sẽ được xây dựng theo roadmap{phase ? ` (${phase})` : ''} — xem <code>docs/00-overview/roadmap.md</code>.</Typography.Text>}
        />
    );
}

function Root() {
    return (
        <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
            <Route element={<RequireAuth><AppLayout /></RequireAuth>}>
                <Route index element={<DashboardPage />} />
                <Route path="orders" element={<OrdersPage />} />
                <Route path="orders/new" element={<CreateOrderPage />} />
                <Route path="orders/:id" element={<OrderDetailPage />} />
                <Route path="channels" element={<ChannelsPage />} />
                <Route path="customers" element={<CustomersPage />} />
                <Route path="customers/:id" element={<CustomerDetailPage />} />
                <Route path="products" element={<Navigate to="/inventory?tab=skus" replace />} />
                <Route path="inventory" element={<InventoryPage />} />
                <Route path="inventory/skus/new" element={<CreateSkuPage />} />
                <Route path="inventory/skus/:id/edit" element={<CreateSkuPage />} />
                <Route path="fulfillment" element={<Navigate to="/orders?tab=prepare" replace />} />   {/* xử lý đơn nay là các tab trong /orders */}
                <Route path="procurement" element={<Navigate to="/procurement/suppliers" replace />} />
                <Route path="procurement/suppliers" element={<SuppliersPage />} />
                <Route path="procurement/purchase-orders" element={<PurchaseOrdersPage />} />
                <Route path="reports" element={<ReportsPage />} />
                <Route path="finance" element={<Navigate to="/finance/settlements" replace />} />
                <Route path="finance/settlements" element={<SettlementsPage />} />
                <Route path="sync-logs" element={<SyncLogsPage />} />
                <Route path="settings" element={<SettingsLayout />}>
                    <Route index element={<Navigate to="/settings/profile" replace />} />
                    <Route path="profile" element={<SettingsProfilePage />} />
                    <Route path="workspace" element={<SettingsWorkspacePage />} />
                    <Route path="members" element={<SettingsMembersPage />} />
                    <Route path="carriers" element={<CarrierAccountsPage />} />
                    <Route path="orders" element={<SettingsOrdersPage />} />
                    <Route path="print" element={<SettingsPrintPage />} />
                    <Route path="*" element={<ComingSoon title="Phần này đang được xây dựng" phase="SPEC 0007 / 0011" />} />
                </Route>
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
                <ConfigProvider
                    locale={viVN}
                    theme={{
                        token: { colorPrimary: '#1668dc', borderRadius: 6, colorBgLayout: '#f5f6fa' },
                        components: { Layout: { headerHeight: 56, bodyBg: '#f5f6fa' }, Menu: { itemHeight: 38 } },
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
