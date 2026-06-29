import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { ChromeOutlined, DownOutlined, MobileOutlined } from '@ant-design/icons';
import { useAuth } from '@/lib/auth';
import { CHROME_EXT_URL } from './ToolsPage';

/**
 * Header public frosted (lh-*) — nav + hamburger mobile + sticky shadow.
 * Ported from landing-header.blade.php — JS behaviour dùng React state thay DOM trực tiếp.
 * SPEC 2026-06-26.
 */
export function PublicHeader() {
    const [scrolled, setScrolled] = useState(false);
    const [menuOpen, setMenuOpen] = useState(false);
    const [toolsOpen, setToolsOpen] = useState(false);
    const headerRef = useRef<HTMLElement>(null);

    const { data: user } = useAuth();
    const loggedIn = !!user;

    /* Sticky shadow on scroll */
    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    /* Đóng khi click ngoài header */
    useEffect(() => {
        if (!menuOpen && !toolsOpen) return;
        const onOutside = (e: MouseEvent) => {
            if (headerRef.current && !headerRef.current.contains(e.target as Node)) {
                setMenuOpen(false);
                setToolsOpen(false);
            }
        };
        document.addEventListener('click', onOutside);
        return () => document.removeEventListener('click', onOutside);
    }, [menuOpen, toolsOpen]);

    /* Đóng tools dropdown khi đóng mobile menu */
    useEffect(() => {
        if (!menuOpen) setToolsOpen(false);
    }, [menuOpen]);

    const closeAll = () => { setMenuOpen(false); setToolsOpen(false); };

    const arrowSvg = (
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
            <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
        </svg>
    );

    return (
        <header ref={headerRef} className={`lh-header${scrolled ? ' is-scrolled' : ''}`}>
            <div className="container lh-inner">
                {/* Logo */}
                <Link to="/" className="lh-brand" aria-label="CMBcoreSeller">
                    <span className="lh-mark"><span>C</span></span>
                    <span className="lh-name">CMBcore<strong>Seller</strong></span>
                </Link>

                {/* Hamburger (ẩn trên desktop qua CSS) */}
                <button
                    className={`lh-toggle${menuOpen ? ' is-open' : ''}`}
                    aria-label="Mở menu"
                    aria-expanded={menuOpen ? 'true' : 'false'}
                    onClick={(e) => { e.stopPropagation(); setMenuOpen((o) => !o); }}
                >
                    <span /><span /><span />
                </button>

                {/* Menu (desktop: luôn hiện; mobile: toggle qua is-open) */}
                <div className={`lh-menu${menuOpen ? ' is-open' : ''}`}>
                    <nav className="lh-nav" aria-label="Điều hướng sản phẩm">
                        <Link to="/#features" onClick={closeAll}>Tính năng</Link>
                        <Link to="/#automation" onClick={closeAll}>AI &amp; Quảng cáo</Link>
                        <Link to="/#integrations" onClick={closeAll}>Tích hợp</Link>
                        <Link to="/pricing" onClick={closeAll}>Bảng giá</Link>
                        <Link to="/api-docs" onClick={closeAll}>Tài liệu API</Link>

                        {/* Phần mềm phụ trợ — desktop: dropdown; mobile: link thẳng */}
                        {menuOpen ? (
                            <>
                                <a
                                    href={CHROME_EXT_URL}
                                    target="_blank"
                                    rel="noreferrer"
                                    onClick={closeAll}
                                    style={{ paddingLeft: 16, opacity: 0.85 }}
                                >
                                    <ChromeOutlined style={{ marginRight: 6 }} />Chrome extension
                                </a>
                                <Link to="/download" onClick={closeAll} style={{ paddingLeft: 16, opacity: 0.85 }}>
                                    <MobileOutlined style={{ marginRight: 6 }} />App mobile
                                </Link>
                            </>
                        ) : (
                            <div style={{ position: 'relative' }}>
                                <button
                                    onClick={() => setToolsOpen((o) => !o)}
                                    style={{
                                        background: 'none', border: 'none', cursor: 'pointer',
                                        fontSize: '14.5px', fontWeight: 500,
                                        color: toolsOpen ? '#0B1426' : '#5A6478',
                                        padding: 0, display: 'inline-flex', alignItems: 'center',
                                        gap: 4, whiteSpace: 'nowrap', fontFamily: 'inherit',
                                        transition: 'color .2s',
                                    }}
                                >
                                    Phần mềm phụ trợ
                                    <DownOutlined style={{ fontSize: 11, transition: 'transform .2s', transform: toolsOpen ? 'rotate(180deg)' : 'none' }} />
                                </button>
                                {toolsOpen && (
                                    <div style={{
                                        position: 'absolute', top: 'calc(100% + 12px)', left: 0,
                                        minWidth: 200, background: '#fff',
                                        border: '1px solid #EFF2F7', borderRadius: 10,
                                        boxShadow: '0 10px 28px rgba(11,20,38,.1)',
                                        padding: '8px 0', zIndex: 300,
                                    }}>
                                        <a
                                            href={CHROME_EXT_URL}
                                            target="_blank"
                                            rel="noreferrer"
                                            onClick={closeAll}
                                            style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '10px 16px', fontSize: 14, color: '#0B1426', fontWeight: 500 }}
                                        >
                                            <ChromeOutlined style={{ color: '#1B4DFF' }} />
                                            Chrome extension
                                        </a>
                                        <Link
                                            to="/download"
                                            onClick={closeAll}
                                            style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '10px 16px', fontSize: 14, color: '#0B1426', fontWeight: 500 }}
                                        >
                                            <MobileOutlined style={{ color: '#1B4DFF' }} />
                                            App mobile
                                        </Link>
                                    </div>
                                )}
                            </div>
                        )}
                    </nav>

                    {/* CTA buttons */}
                    <div className="lh-actions">
                        {loggedIn ? (
                            <Link to="/dashboard" className="lh-btn lh-cta" onClick={closeAll}>
                                Truy cập {arrowSvg}
                            </Link>
                        ) : (
                            <>
                                <Link to="/register" className="lh-btn lh-ghost" onClick={closeAll}>
                                    Dùng thử
                                </Link>
                                <Link to="/dashboard" className="lh-btn lh-cta" onClick={closeAll}>
                                    Truy cập {arrowSvg}
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </header>
    );
}
