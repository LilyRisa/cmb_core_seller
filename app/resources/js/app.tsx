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
import { ForgotPasswordPage } from '@/pages/ForgotPasswordPage';
import { ResetPasswordPage } from '@/pages/ResetPasswordPage';
import { PublicTrackingPage } from '@/pages/PublicTrackingPage';
import { DownloadAppPage } from '@/pages/DownloadAppPage';
import { DashboardPage } from '@/pages/DashboardPage';
import { OrdersPage } from '@/pages/OrdersPage';
import { OrderDetailPage } from '@/pages/OrderDetailPage';
import { ReturnsPage } from '@/pages/ReturnsPage';
import { ChannelsPage } from '@/pages/ChannelsPage';
import { CopiedProductsPage } from '@/pages/marketplace/CopiedProductsPage';
import { OnChannelPage } from '@/pages/marketplace/OnChannelPage';
import { PublishablePage } from '@/pages/marketplace/PublishablePage';
import { SyncLogsPage } from '@/pages/SyncLogsPage';
import { SupportCenterPage } from '@/pages/SupportCenterPage';
import { CustomersPage } from '@/pages/CustomersPage';
import { CustomerDetailPage } from '@/pages/CustomerDetailPage';
import { MessagingPage } from '@/pages/MessagingPage';
import { MessagingTemplatesPage } from '@/pages/MessagingTemplatesPage';
import { MessagingUtilityTemplatesPage } from '@/pages/MessagingUtilityTemplatesPage';
import { MessagingAutoRulesPage } from '@/pages/MessagingAutoRulesPage';
import { MessagingFlowsPage } from '@/pages/MessagingFlowsPage';
import { MessagingFlowEditorPage } from '@/pages/MessagingFlowEditorPage';
import { MessagingKnowledgePage } from '@/pages/MessagingKnowledgePage';
import { MessagingSettingsPage } from '@/pages/MessagingSettingsPage';
import { MessagingChannelsPage } from '@/pages/MessagingChannelsPage';
import { MarketingDashboardPage } from '@/pages/MarketingDashboardPage';
import { TikTokAdsDashboardPage } from '@/pages/TikTokAdsDashboardPage';
import { AdWizardPage } from '@/pages/AdWizardPage';
import { AiCampaignPage } from '@/pages/AiCampaignPage';
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
import { PlansPage } from '@/pages/PlansPage';
import { SettingsPrintPage } from '@/pages/SettingsPrintPage';
import { SuppliersPage } from '@/pages/SuppliersPage';
import { PurchaseOrdersPage } from '@/pages/PurchaseOrdersPage';
import { DemandPlanningPage } from '@/pages/DemandPlanningPage';
import { ReportsPage } from '@/pages/ReportsPage';
import { OverviewReportPage } from '@/pages/OverviewReportPage';
import { ShopReportPage } from '@/pages/ShopReportPage';
import { SettlementsPage } from '@/pages/SettlementsPage';
import { AccountingDashboardPage } from '@/pages/accounting/AccountingDashboardPage';
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
            subTitle={<Typography.Text type="secondary">Tính năng này sẽ được xây dựng theo roadmap{phase ? ` (${phase})` : ''}.</Typography.Text>}
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
            {/* SPEC 0022 — luồng quên / đặt lại mật khẩu. Public (link đặt lại tới từ email). */}
            <Route path="/forgot-password" element={<ForgotPasswordPage />} />
            <Route path="/password-reset" element={<ResetPasswordPage />} />
            {/* SPEC 0030 — trang tra cứu đơn công khai (đơn tự tạo). Public, không cần đăng nhập. */}
            <Route path="/tracking" element={<PublicTrackingPage />} />
            {/* Trang tải ứng dụng di động. Public, không cần đăng nhập. */}
            <Route path="/download" element={<DownloadAppPage />} />
            {/* SPEC 0032 — trang gói full-screen riêng (có nút back), tách khỏi sidebar. */}
            <Route path="/plans" element={<RequireAuth><PlansPage /></RequireAuth>} />
            <Route element={<RequireAuth><AppLayout /></RequireAuth>}>
                <Route index element={<DashboardPage />} />
                <Route path="orders" element={<OrdersPage />} />
                <Route path="orders/new" element={<CreateOrderPage />} />
                <Route path="orders/:id/edit" element={<CreateOrderPage />} />
                <Route path="orders/:id" element={<OrderDetailPage />} />
                <Route path="returns" element={<ReturnsPage />} />               {/* Đơn Hoàn & Hủy — SPEC 0025 */}
                <Route path="channels" element={<ChannelsPage />} />
                {/* Đăng & sao chép sản phẩm lên sàn — nhóm riêng (3 bước: copy → chờ đẩy → đã trên sàn) */}
                <Route path="listings" element={<Navigate to="/marketplace/products" replace />} />
                <Route path="marketplace" element={<Navigate to="/marketplace/products" replace />} />
                <Route path="marketplace/products" element={<CopiedProductsPage />} />
                <Route path="marketplace/on-channel" element={<OnChannelPage />} />
                <Route path="marketplace/to-push" element={<PublishablePage />} />
                <Route path="customers" element={<CustomersPage />} />
                <Route path="customers/:id" element={<CustomerDetailPage />} />
                {/* SPEC-0024 — Hộp thư hợp nhất + trang quản lý. */}
                <Route path="messaging" element={<MessagingPage />} />
                <Route path="messaging/channels" element={<MessagingChannelsPage />} />
                <Route path="messaging/templates" element={<MessagingTemplatesPage />} />
                <Route path="messaging/utility-templates" element={<MessagingUtilityTemplatesPage />} />
                <Route path="messaging/auto-rules" element={<MessagingAutoRulesPage />} />
                <Route path="messaging/flows" element={<MessagingFlowsPage />} />
                <Route path="messaging/flows/:id/edit" element={<MessagingFlowEditorPage />} />
                <Route path="messaging/knowledge" element={<MessagingKnowledgePage />} />
                <Route path="marketing" element={<MarketingDashboardPage />} />
                <Route path="marketing/tiktok" element={<TikTokAdsDashboardPage />} />
                <Route path="marketing/ads/new" element={<AdWizardPage />} />
                <Route path="marketing/ads/ai" element={<AiCampaignPage />} />
                <Route path="marketing/ads/:draftId/edit" element={<AdWizardPage />} />
                <Route path="products" element={<Navigate to="/inventory?tab=skus" replace />} />
                <Route path="inventory" element={<InventoryPage />} />
                <Route path="inventory/skus/new" element={<CreateSkuPage />} />
                <Route path="inventory/skus/:id/edit" element={<CreateSkuPage />} />
                <Route path="fulfillment" element={<Navigate to="/orders?tab=pending" replace />} />   {/* xử lý đơn nay là các tab trong /orders (tab "Chờ xử lý") */}
                <Route path="procurement" element={<Navigate to="/procurement/suppliers" replace />} />
                <Route path="procurement/suppliers" element={<SuppliersPage />} />
                <Route path="procurement/purchase-orders" element={<PurchaseOrdersPage />} />
                <Route path="procurement/demand-planning" element={<DemandPlanningPage />} />
                <Route path="reports/overview" element={<OverviewReportPage />} />
                <Route path="reports" element={<ReportsPage />} />
                <Route path="shop-report" element={<ShopReportPage />} />
                <Route path="finance" element={<Navigate to="/finance/settlements" replace />} />
                <Route path="finance/settlements" element={<SettlementsPage />} />
                {/* Phase 7 — Module Kế toán đầy đủ (gated by plan.feature:accounting_basic ở BE). */}
                <Route path="accounting" element={<Navigate to="/accounting/dashboard" replace />} />
                <Route path="accounting/dashboard" element={<AccountingDashboardPage />} />
                <Route path="accounting/journals" element={<JournalsPage />} />
                <Route path="accounting/chart-of-accounts" element={<ChartOfAccountsPage />} />
                <Route path="accounting/periods" element={<PeriodsPage />} />
                <Route path="accounting/balances" element={<BalancesPage />} />
                <Route path="accounting/ar" element={<ArPage />} />
                <Route path="accounting/ap" element={<ApPage />} />
                <Route path="accounting/cash" element={<CashPage />} />
                <Route path="accounting/reports" element={<AccountingReportsPage />} />
                <Route path="sync-logs" element={<SyncLogsPage />} />
                <Route path="support" element={<SupportCenterPage />} />
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
