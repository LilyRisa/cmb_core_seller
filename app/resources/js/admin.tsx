// Spec 2026-05-17 — admin SPA entry. Bundle thứ 2 của Vite (xem vite.config.ts).
// Tách hoàn toàn khỏi `app.tsx` (user SPA) — không import gì từ `resources/js/pages/*`
// hay `resources/js/lib/*` ngoại trừ shared utility cực thuần.
import React from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { App as AntApp, ConfigProvider, theme } from 'antd';
import viVN from 'antd/locale/vi_VN';
import 'antd/dist/reset.css';
import dayjs from 'dayjs';
import 'dayjs/locale/vi';
import { AdminApp } from './admin/AdminApp';

dayjs.locale('vi');

const qc = new QueryClient({
    defaultOptions: {
        queries: { retry: 1, refetchOnWindowFocus: false, staleTime: 30_000 },
    },
});

const el = document.getElementById('admin-root');
if (el) {
    createRoot(el).render(
        <React.StrictMode>
            <QueryClientProvider client={qc}>
                <ConfigProvider
                    locale={viVN}
                    theme={{
                        token: {
                            colorPrimary: '#1F2937',
                            colorError: '#DC2626',
                            borderRadius: 6,
                            colorBgLayout: '#F1F5F9',
                        },
                        components: {
                            Layout: { headerHeight: 56 },
                            Menu: { itemHeight: 38 },
                        },
                        algorithm: theme.defaultAlgorithm,
                    }}
                >
                    <AntApp>
                        <AdminApp />
                    </AntApp>
                </ConfigProvider>
            </QueryClientProvider>
        </React.StrictMode>,
    );
}
