// Spec 2026-05-17 — router root cho admin SPA.
// `/admin/login` public; mọi route khác bọc trong `<AdminProtected>` (redirect
// login nếu /auth/me trả 401).

import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AdminProtected } from './AdminProtected';
import { AdminLayout } from './AdminLayout';
import { AdminLoginPage } from './pages/AdminLoginPage';
import { AdminDashboardPage } from './pages/AdminDashboardPage';

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
                    {/* Sub-routes (tenants, users, settings, …) đăng ký ở Task 20-22. */}
                    <Route path="*" element={<Navigate to="/admin" replace />} />
                </Route>
            </Routes>
        </BrowserRouter>
    );
}
