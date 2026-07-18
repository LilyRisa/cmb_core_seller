// Spec 2026-05-17 — router root cho admin SPA.
// `/admin/login` public; mọi route khác bọc trong `<AdminProtected>` (redirect
// login nếu /auth/me trả 401).

import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AdminProtected } from './AdminProtected';
import { AdminLayout } from './AdminLayout';
import { AdminLoginPage } from './pages/AdminLoginPage';
import { AdminDashboardPage } from './pages/AdminDashboardPage';
import { AdminTenantsPage } from './pages/tenants/AdminTenantsPage';
import { AdminTenantDetailPage } from './pages/tenants/AdminTenantDetailPage';
import { AdminVouchersPage } from './pages/tenants/AdminVouchersPage';
import { AdminPlansPage } from './pages/tenants/AdminPlansPage';
import { AdminAuditLogsPage } from './pages/tenants/AdminAuditLogsPage';
import { AdminInvoicesPage } from './pages/tenants/AdminInvoicesPage';
import { AdminBroadcastsPage } from './pages/tenants/AdminBroadcastsPage';
import { AdminAnnouncementsPage } from './pages/AdminAnnouncementsPage';
import { AdminDesktopBackgroundsPage } from './pages/AdminDesktopBackgroundsPage';
import { AdminUsersPage } from './pages/users/AdminUsersPage';
import { SystemSettingsPage } from './pages/settings/SystemSettingsPage';
import { AdminAiProvidersPage } from './pages/settings/AdminAiProvidersPage';
import { AdminMarketingAiProvidersPage } from './pages/settings/AdminMarketingAiProvidersPage';
import { AdminAiSupportPage } from './pages/settings/AdminAiSupportPage';
import { AdminVisualRerankPage } from './pages/settings/AdminVisualRerankPage';
import { AdminTranscriptionPage } from './pages/settings/AdminTranscriptionPage';
import { AdminSupportRequestsPage } from './pages/support/AdminSupportRequestsPage';
import { AdminNotificationEmailsPage } from './pages/AdminNotificationEmailsPage';

export function AdminApp() {
    return (
        <BrowserRouter>
            <Routes>
                <Route path="/admin/login" element={<AdminLoginPage />} />
                <Route
                    path="/admin"
                    element={
                        <AdminProtected>
                            <AdminLayout />
                        </AdminProtected>
                    }
                >
                    <Route index element={<AdminDashboardPage />} />
                    <Route path="tenants/:id" element={<AdminTenantDetailPage />} />
                    <Route path="tenants" element={<AdminTenantsPage />} />
                    <Route path="vouchers" element={<AdminVouchersPage />} />
                    <Route path="plans" element={<AdminPlansPage />} />
                    <Route path="broadcasts" element={<AdminBroadcastsPage />} />
                    <Route path="announcements" element={<AdminAnnouncementsPage />} />
                    <Route path="desktop-backgrounds" element={<AdminDesktopBackgroundsPage />} />
                    <Route path="audit-logs" element={<AdminAuditLogsPage />} />
                    <Route path="invoices" element={<AdminInvoicesPage />} />
                    <Route path="users" element={<AdminUsersPage />} />
                    <Route path="settings" element={<SystemSettingsPage />} />
                    <Route path="notification-emails" element={<AdminNotificationEmailsPage />} />
                    <Route path="ai-providers" element={<AdminAiProvidersPage />} />
                    <Route path="marketing-ai-providers" element={<AdminMarketingAiProvidersPage />} />
                    <Route path="ai-support" element={<AdminAiSupportPage />} />
                    <Route path="ai-visual-rerank" element={<AdminVisualRerankPage />} />
                    <Route path="ai-transcription" element={<AdminTranscriptionPage />} />
                    <Route path="support-requests" element={<AdminSupportRequestsPage />} />
                    <Route path="*" element={<Navigate to="/admin" replace />} />
                </Route>
            </Routes>
        </BrowserRouter>
    );
}
