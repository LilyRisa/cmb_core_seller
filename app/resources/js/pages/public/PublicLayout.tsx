import { Outlet } from 'react-router-dom';
import { PublicHeader } from './PublicHeader';
import { PublicFooter } from './PublicFooter';
import '../../../css/cmb-public.css';

/** Khung trang public (marketing) — bọc .cmb-public + header + main + footer. SPEC 2026-06-26. */
export function PublicLayout() {
    return (
        <div className="cmb-public" style={{ minHeight: '100vh', display: 'flex', flexDirection: 'column', overflowX: 'hidden' }}>
            <PublicHeader />
            <main style={{ flex: 1 }}><Outlet /></main>
            <PublicFooter />
        </div>
    );
}
