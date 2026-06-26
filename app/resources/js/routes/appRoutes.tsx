import React, { Suspense } from 'react';
import { Navigate, Route } from 'react-router-dom';
import { Spin } from 'antd';

import { DashboardPage } from '@/pages/DashboardPage';
import { OrdersPage } from '@/pages/OrdersPage';
import { OrderDetailPage } from '@/pages/OrderDetailPage';
import { ReturnsPage } from '@/pages/ReturnsPage';
import { ChannelsPage } from '@/pages/ChannelsPage';
import { CopiedProductsPage } from '@/pages/marketplace/CopiedProductsPage';
import { OnChannelPage } from '@/pages/marketplace/OnChannelPage';
import { MarketplaceEditPage } from '@/pages/marketplace/MarketplaceEditPage';
import { ListingDraftEditorPage } from '@/pages/marketplace/ListingDraftEditorPage';
import { PublishablePage } from '@/pages/marketplace/PublishablePage';
import { PromotionsPage } from '@/pages/marketplace/PromotionsPage';
import { PromotionEditPage } from '@/pages/marketplace/PromotionEditPage';
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
import { MessagingAiTrainingPage } from '@/pages/MessagingAiTrainingPage';
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
import { SettingsApiKeysPage } from '@/pages/settings/SettingsApiKeysPage';
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
import { SettingsAppearancePage } from '@/pages/SettingsAppearancePage';
import { ComingSoon } from '@/components/ComingSoon';

const AdvancedImageEditorPage = React.lazy(() => import('@/pages/marketplace/AdvancedImageEditorPage'));
const MarketplaceImageEditorPage = React.lazy(() => import('@/pages/marketplace/MarketplaceImageEditorPage'));

const lazy = (node: React.ReactNode) => <Suspense fallback={<Spin style={{ margin: 48 }} />}>{node}</Suspense>;

/** Toàn bộ route page (không gồm public, không gồm layout). Dùng chung vỏ v1 & v2. */
export function appRouteElements(): React.ReactNode {
    return (
        <>
            <Route index element={<DashboardPage />} />
            <Route path="orders" element={<OrdersPage />} />
            <Route path="orders/new" element={<CreateOrderPage />} />
            <Route path="orders/:id/edit" element={<CreateOrderPage />} />
            <Route path="orders/:id" element={<OrderDetailPage />} />
            <Route path="returns" element={<ReturnsPage />} />
            <Route path="channels" element={<ChannelsPage />} />
            <Route path="listings" element={<Navigate to="/marketplace/products" replace />} />
            <Route path="marketplace" element={<Navigate to="/marketplace/products" replace />} />
            <Route path="marketplace/products" element={<CopiedProductsPage />} />
            <Route path="marketplace/on-channel" element={<OnChannelPage />} />
            <Route path="marketplace/on-channel/:id/edit" element={<MarketplaceEditPage />} />
            <Route path="marketplace/on-channel/:id/images/edit" element={lazy(<MarketplaceImageEditorPage />)} />
            <Route path="marketplace/to-push" element={<PublishablePage />} />
            <Route path="marketplace/promotions" element={<PromotionsPage />} />
            <Route path="marketplace/promotions/:id/edit" element={<PromotionEditPage />} />
            <Route path="marketplace/listings/:id/edit" element={<ListingDraftEditorPage />} />
            <Route path="marketplace/listings/:id/images/edit" element={lazy(<AdvancedImageEditorPage />)} />
            <Route path="customers" element={<CustomersPage />} />
            <Route path="customers/:id" element={<CustomerDetailPage />} />
            <Route path="messaging" element={<MessagingPage />} />
            <Route path="messaging/channels" element={<MessagingChannelsPage />} />
            <Route path="messaging/templates" element={<MessagingTemplatesPage />} />
            <Route path="messaging/utility-templates" element={<MessagingUtilityTemplatesPage />} />
            <Route path="messaging/auto-rules" element={<MessagingAutoRulesPage />} />
            <Route path="messaging/flows" element={<MessagingFlowsPage />} />
            <Route path="messaging/flows/:id/edit" element={<MessagingFlowEditorPage />} />
            <Route path="messaging/knowledge" element={<MessagingAiTrainingPage />} />
            <Route path="marketing" element={<MarketingDashboardPage />} />
            <Route path="marketing/tiktok" element={<TikTokAdsDashboardPage />} />
            <Route path="marketing/ads/new" element={<AdWizardPage />} />
            <Route path="marketing/ads/ai" element={<AiCampaignPage />} />
            <Route path="marketing/ads/:draftId/edit" element={<AdWizardPage />} />
            <Route path="products" element={<Navigate to="/inventory?tab=skus" replace />} />
            <Route path="inventory" element={<InventoryPage />} />
            <Route path="inventory/skus/new" element={<CreateSkuPage />} />
            <Route path="inventory/skus/:id/edit" element={<CreateSkuPage />} />
            <Route path="fulfillment" element={<Navigate to="/orders?tab=pending" replace />} />
            <Route path="procurement" element={<Navigate to="/procurement/suppliers" replace />} />
            <Route path="procurement/suppliers" element={<SuppliersPage />} />
            <Route path="procurement/purchase-orders" element={<PurchaseOrdersPage />} />
            <Route path="procurement/demand-planning" element={<DemandPlanningPage />} />
            <Route path="reports/overview" element={<OverviewReportPage />} />
            <Route path="reports" element={<ReportsPage />} />
            <Route path="shop-report" element={<ShopReportPage />} />
            <Route path="finance" element={<Navigate to="/finance/settlements" replace />} />
            <Route path="finance/settlements" element={<SettlementsPage />} />
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
            <Route path="settings" element={<SettingsLayout />}>
                <Route index element={<Navigate to="/settings/profile" replace />} />
                <Route path="profile" element={<SettingsProfilePage />} />
                <Route path="appearance" element={<SettingsAppearancePage />} />
                <Route path="workspace" element={<SettingsWorkspacePage />} />
                <Route path="members" element={<SettingsMembersPage />} />
                <Route path="carriers" element={<CarrierAccountsPage />} />
                <Route path="api-keys" element={<SettingsApiKeysPage />} />
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
        </>
    );
}
