import { Outlet } from 'react-router-dom';
import { PublicHeader } from './PublicHeader';
import { PublicFooter } from './PublicFooter';

/** Khung trang public (marketing) — header menu + nội dung + footer. SPEC 2026-06-26. */
export function PublicLayout() {
    return (
        <div style={{ minHeight: '100vh', display: 'flex', flexDirection: 'column', background: '#fff' }}>
            <PublicHeader />
            <main style={{ flex: 1 }}><Outlet /></main>
            <PublicFooter />
        </div>
    );
}
