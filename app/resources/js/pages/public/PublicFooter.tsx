import { Link } from 'react-router-dom';
import { CHROME_EXT_URL } from './ToolsPage';

/**
 * Footer public 4 cột — port từ .site-footer trong seller.blade.php.
 * Cột: 1) brand + CTA  2) Sản phẩm  3) Tích hợp  4) Công ty
 * SPEC 2026-06-26.
 */
export function PublicFooter() {
    const arrowSvg = (
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round">
            <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
        </svg>
    );

    return (
        <footer className="site-footer">
            <div className="container">
                <div className="footer-grid">
                    {/* Col 1 — Brand + tagline + CTA */}
                    <div className="footer-brand">
                        <Link to="/" className="logo">
                            <span className="logo-mark"><span>C</span></span>
                            <span>CMBcore<strong>Seller</strong></span>
                        </Link>
                        <p>Phần mềm bán hàng đa kênh dành cho nhà bán Việt Nam. Bán nhiều sàn, quản một nơi.</p>
                        <Link to="/register" className="btn btn-primary" style={{ fontSize: '13.5px', padding: '10px 18px' }}>
                            Đăng ký dùng thử {arrowSvg}
                        </Link>
                    </div>

                    {/* Col 2 — Sản phẩm */}
                    <div className="footer-col">
                        <h4>Sản phẩm</h4>
                        <ul>
                            <li><Link to="/#features">Tính năng</Link></li>
                            <li><Link to="/#automation">AI &amp; Quảng cáo</Link></li>
                            <li><Link to="/#integrations">Tích hợp</Link></li>
                            <li><Link to="/pricing">Bảng giá</Link></li>
                            <li><Link to="/api-docs">Tài liệu API</Link></li>
                        </ul>
                    </div>

                    {/* Col 3 — Tích hợp (sàn) */}
                    <div className="footer-col">
                        <h4>Tích hợp</h4>
                        <ul>
                            <li><Link to="/#integrations">TikTok Shop</Link></li>
                            <li><Link to="/#integrations">Shopee</Link></li>
                            <li><Link to="/#integrations">Lazada</Link></li>
                            <li><Link to="/#integrations">Facebook</Link></li>
                            <li><Link to="/#integrations">Vận chuyển</Link></li>
                        </ul>
                    </div>

                    {/* Col 4 — Công ty / tiện ích */}
                    <div className="footer-col">
                        <h4>CMB Solutions</h4>
                        <ul>
                            <li><a href={CHROME_EXT_URL} target="_blank" rel="noreferrer">Chrome extension</a></li>
                            <li><Link to="/download">App mobile</Link></li>
                            <li><Link to="/tracking">Tra cứu đơn</Link></li>
                            <li><Link to="/login">Đăng nhập</Link></li>
                            <li><Link to="/register">Đăng ký dùng thử</Link></li>
                        </ul>
                    </div>
                </div>

                <div className="footer-bottom">
                    <div>
                        &copy; {new Date().getFullYear()} CMB Core Solutions. CMBcoreSeller - Bản quyền thuộc CMB Core Solutions.
                    </div>
                    <div style={{ fontStyle: 'italic' }}>Bán nhiều sàn, quản một nơi.</div>
                </div>
            </div>
        </footer>
    );
}
