// Spec 2026-05-17 — router root cho admin SPA.
// `/admin/login` public; mọi route khác bọc trong `<AdminProtected>` (redirect
// login nếu /auth/me trả 401).

import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AdminProtected } from './AdminProtected';
import { AdminLayout } from './AdminLayout';
import { AdminLoginPage } from './pages/AdminLoginPage';
import { AdminDashboardPage } from './pages/AdminDashboardPage';
import { AdminTenantsPage } from './pages/tenants/AdminTenantsPage';
import { AdminVouchersPage } from './pages/tenants/AdminVouchersPage';
import { AdminPlansPage } from './pages/tenants/AdminPlansPage';
import { AdminAuditLogsPage } from './pages/tenants/AdminAuditLogsPage';
import { AdminBroadcastsPage } from './pages/tenants/AdminBroadcastsPage';
import { AdminUsersPage } from './pages/users/AdminUsersPage';
import { SystemSettingsPage } from './pages/settings/SystemSettingsPage';
import { AdminAiProvidersPage } from './pages/settings/AdminAiProvidersPage';
import { AdminAiSupportPage } from './pages/settings/AdminAiSupportPage';

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
                    <Route path="tenants" element={<AdminTenantsPage />} />
                    <Route path="vouchers" element={<AdminVouchersPage />} />
                    <Route path="plans" element={<AdminPlansPage />} />
                    <Route path="broadcasts" element={<AdminBroadcastsPage />} />
                    <Route path="audit-logs" element={<AdminAuditLogsPage />} />
                    <Route path="users" element={<AdminUsersPage />} />
                    <Route path="settings" element={<SystemSettingsPage />} />
                    <Route path="ai-providers" element={<AdminAiProvidersPage />} />
                    <Route path="ai-support" element={<AdminAiSupportPage />} />
                    <Route path="*" element={<Navigate to="/admin" replace />} />
                </Route>
            </Routes>
        </BrowserRouter>
    );
}
