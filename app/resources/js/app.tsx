import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { App as AntApp, ConfigProvider, theme } from 'antd';
import { Result, Typography } from 'antd';
import { ToolOutlined } from '@ant-design/icons';
import viVN from 'antd/locale/vi_VN';
import 'antd/dist/reset.css';
import '../css/app.css';
import dayjs from 'dayjs';
import 'dayjs/locale/vi';

import { RequireAuth } from '@/components/RequireAuth';
import { AppLayout } from '@/components/AppLayout';
import { LoginPage } from '@/pages/LoginPage';
import { RegisterPage } from '@/pages/RegisterPage';
import { EmailVerifiedPage } from '@/pages/EmailVerifiedPage';
import { DashboardPage } from '@/pages/DashboardPage';
import { OrdersPage } from '@/pages/OrdersPage';
import { OrderDetailPage } from '@/pages/OrderDetailPage';
import { ReturnsPage } from '@/pages/ReturnsPage';
import { ChannelsPage } from '@/pages/ChannelsPage';
import { SyncLogsPage } from '@/pages/SyncLogsPage';
import { CustomersPage } from '@/pages/CustomersPage';
import { CustomerDetailPage } from '@/pages/CustomerDetailPage';
import { MessagingPage } from '@/pages/MessagingPage';
import { MessagingTemplatesPage } from '@/pages/MessagingTemplatesPage';
import { MessagingAutoRulesPage } from '@/pages/MessagingAutoRulesPage';
import { MessagingKnowledgePage } from '@/pages/MessagingKnowledgePage';
import { MessagingSettingsPage } from '@/pages/MessagingSettingsPage';
import { MessagingChannelsPage } from '@/pages/MessagingChannelsPage';
import { InventoryPage } from '@/pages/InventoryPage';
import { CreateSkuPage } from '@/pages/CreateSkuPage';
import { CreateOrderPage } from '@/pages/CreateOrderPage';
import { CarrierAccountsPage } from '@/pages/CarrierAccountsPage';
import { SettingsLayout } from '@/components/SettingsLayout';
import { SettingsMembersPage } from '@/pages/SettingsMembersPage';
import { SettingsProfilePage } from '@/pages/SettingsProfilePage';
import { SettingsWorkspacePage } from '@/pages/SettingsWorkspacePage';
import { SettingsOrdersPage } from '@/pages/SettingsOrdersPage';
import { SettingsPlanPage } from '@/pages/SettingsPlanPage';
import { SettingsPrintPage } from '@/pages/SettingsPrintPage';
import { SuppliersPage } from '@/pages/SuppliersPage';
import { PurchaseOrdersPage } from '@/pages/PurchaseOrdersPage';
import { DemandPlanningPage } from '@/pages/DemandPlanningPage';
import { ReportsPage } from '@/pages/ReportsPage';
import { SettlementsPage } from '@/pages/SettlementsPage';
import { JournalsPage } from '@/pages/accounting/JournalsPage';
import { ChartOfAccountsPage } from '@/pages/accounting/ChartOfAccountsPage';
import { PeriodsPage } from '@/pages/accounting/PeriodsPage';
import { BalancesPage } from '@/pages/accounting/BalancesPage';
import { ArPage } from '@/pages/accounting/ArPage';
import { ApPage } from '@/pages/accounting/ApPage';
import { CashPage } from '@/pages/accounting/CashPage';
import { AccountingReportsPage } from '@/pages/accounting/ReportsPage';
import { AccountingPostRulesPage } from '@/pages/settings/AccountingPostRulesPage';
import { SettingsShippingLabelsPage } from '@/pages/SettingsShippingLabelsPage';
import { ShippingLabelEditorPage } from '@/pages/ShippingLabelEditorPage';
// Spec 2026-05-17 — admin SPA tách bundle riêng tại `/admin/*` (xem
// `resources/js/admin.tsx`). User SPA không còn route admin nào.
import { NotFoundPage } from '@/pages/NotFoundPage';

dayjs.locale('vi');

const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: 1, refetchOnWindowFocus: false, staleTime: 30_000 } },
});

/** Placeholder for module pages not built yet (Phase 2+). */
function ComingSoon({ title, phase }: { title: string; phase?: string }) {
    return (
        <Result
            icon={<ToolOutlined style={{ fontSize: 48, color: '#2563EB' }} />}
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
            {/* SPEC 0022 — callback từ link xác thực trong email (BE redirect tới đây). Public. */}
            <Route path="/email-verified" element={<EmailVerifiedPage />} />
            <Route element={<RequireAuth><AppLayout /></RequireAuth>}>
                <Route index element={<DashboardPage />} />
                <Route path="orders" element={<OrdersPage />} />
                <Route path="orders/new" element={<CreateOrderPage />} />
                <Route path="orders/:id/edit" element={<CreateOrderPage />} />
                <Route path="orders/:id" element={<OrderDetailPage />} />
                <Route path="returns" element={<ReturnsPage />} />               {/* Đơn Hoàn & Hủy — SPEC 0025 */}
                <Route path="channels" element={<ChannelsPage />} />
                <Route path="customers" element={<CustomersPage />} />
                <Route path="customers/:id" element={<CustomerDetailPage />} />
                {/* SPEC-0024 — Hộp thư hợp nhất + trang quản lý. */}
                <Route path="messaging" element={<MessagingPage />} />
                <Route path="messaging/channels" element={<MessagingChannelsPage />} />
                <Route path="messaging/templates" element={<MessagingTemplatesPage />} />
                <Route path="messaging/auto-rules" element={<MessagingAutoRulesPage />} />
                <Route path="messaging/knowledge" element={<MessagingKnowledgePage />} />
                <Route path="products" element={<Navigate to="/inventory?tab=skus" replace />} />
                <Route path="inventory" element={<InventoryPage />} />
                <Route path="inventory/skus/new" element={<CreateSkuPage />} />
                <Route path="inventory/skus/:id/edit" element={<CreateSkuPage />} />
                <Route path="fulfillment" element={<Navigate to="/orders?tab=prepare" replace />} />   {/* xử lý đơn nay là các tab trong /orders */}
                <Route path="procurement" element={<Navigate to="/procurement/suppliers" replace />} />
                <Route path="procurement/suppliers" element={<SuppliersPage />} />
                <Route path="procurement/purchase-orders" element={<PurchaseOrdersPage />} />
                <Route path="procurement/demand-planning" element={<DemandPlanningPage />} />
                <Route path="reports" element={<ReportsPage />} />
                <Route path="finance" element={<Navigate to="/finance/settlements" replace />} />
                <Route path="finance/settlements" element={<SettlementsPage />} />
                {/* Phase 7 — Module Kế toán đầy đủ (gated by plan.feature:accounting_basic ở BE). */}
                <Route path="accounting" element={<Navigate to="/accounting/journals" replace />} />
                <Route path="accounting/journals" element={<JournalsPage />} />
                <Route path="accounting/chart-of-accounts" element={<ChartOfAccountsPage />} />
                <Route path="accounting/periods" element={<PeriodsPage />} />
                <Route path="accounting/balances" element={<BalancesPage />} />
                <Route path="accounting/ar" element={<ArPage />} />
                <Route path="accounting/ap" element={<ApPage />} />
                <Route path="accounting/cash" element={<CashPage />} />
                <Route path="accounting/reports" element={<AccountingReportsPage />} />
                <Route path="sync-logs" element={<SyncLogsPage />} />
                {/* Spec 2026-05-17 — admin SPA tách bundle riêng tại `/admin/*` (server-side route). */}
                <Route path="settings" element={<SettingsLayout />}>
                    <Route index element={<Navigate to="/settings/profile" replace />} />
                    <Route path="profile" element={<SettingsProfilePage />} />
                    <Route path="workspace" element={<SettingsWorkspacePage />} />
                    <Route path="members" element={<SettingsMembersPage />} />
                    <Route path="carriers" element={<CarrierAccountsPage />} />
                    <Route path="orders" element={<SettingsOrdersPage />} />
                    <Route path="messaging" element={<MessagingSettingsPage />} />
                    <Route path="plan" element={<SettingsPlanPage />} />
                    <Route path="print" element={<SettingsPrintPage />} />
                    <Route path="shipping-labels" element={<SettingsShippingLabelsPage />} />
                    <Route path="shipping-labels/new" element={<ShippingLabelEditorPage />} />
                    <Route path="shipping-labels/:id" element={<ShippingLabelEditorPage />} />
                    <Route path="accounting/post-rules" element={<AccountingPostRulesPage />} />
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
