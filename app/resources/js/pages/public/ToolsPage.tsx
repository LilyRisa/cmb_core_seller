import { Link } from 'react-router-dom';
import { ApiOutlined, ChromeOutlined, MobileOutlined } from '@ant-design/icons';

export const CHROME_EXT_URL = 'https://chromewebstore.google.com/detail/cmbcoreseller-h%E1%BB%87-th%E1%BB%91ng-er/ffhpgodhnajdcbccdijgfclcbfjncick';

const TOOLS = [
    {
        id: 'extension',
        Icon: ChromeOutlined,
        title: 'Chrome Extension',
        desc: 'Sao chép sản phẩm từ sàn về kho, thao tác nhanh ngay trên trình duyệt khi duyệt trang sàn.',
        cta: { href: CHROME_EXT_URL, external: true, label: 'Cài đặt từ Chrome Web Store' } as { href?: string; to?: string; external: boolean; label: string },
        iconColor: 'var(--primary)',
        iconBg: 'var(--primary-soft)',
    },
    {
        id: 'mobile',
        Icon: MobileOutlined,
        title: 'App Mobile',
        desc: 'Quản lý đơn, quét đóng gói, nhận thông báo realtime trên điện thoại.',
        cta: { to: '/download', external: false, label: 'Tải ứng dụng' } as { href?: string; to?: string; external: boolean; label: string },
        iconColor: 'var(--accent)',
        iconBg: 'var(--accent-soft)',
    },
    {
        id: 'api',
        Icon: ApiOutlined,
        title: 'API mở',
        desc: 'Tích hợp hệ thống ngoài (ERP, Zapier...) qua API key — thao tác như trên web.',
        cta: { to: '/api-docs', external: false, label: 'Tài liệu API' } as { href?: string; to?: string; external: boolean; label: string },
        iconColor: 'var(--violet)',
        iconBg: 'rgba(124,92,255,.12)',
    },
];

/** Phần mềm phụ trợ: Chrome extension + App mobile (gộp 1 trang). SPEC 2026-06-26. */
export function ToolsPage() {
    return (
        <section style={{ background: 'var(--bg-soft)', borderTop: '1px solid var(--border-soft)' }}>
            <div className="container">
                <div style={{ marginBottom: 48 }}>
                    <span className="section-tag">Phần mềm phụ trợ</span>
                    <h2 style={{ marginTop: 16, marginBottom: 12 }}>Mở rộng CMBcoreSeller</h2>
                    <p className="section-sub" style={{ maxWidth: 580, margin: 0 }}>
                        Tiện ích trình duyệt, ứng dụng di động và API — kết nối mọi nơi bạn làm việc.
                    </p>
                </div>

                <div style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))',
                    gap: 24,
                }}>
                    {TOOLS.map(({ id, Icon, title, desc, cta, iconColor, iconBg }) => (
                        <div
                            key={id}
                            id={id}
                            style={{
                                background: '#fff',
                                border: '1px solid var(--border-soft)',
                                borderRadius: 'var(--radius-lg)',
                                padding: '28px 24px',
                                boxShadow: 'var(--shadow-sm)',
                                display: 'flex',
                                flexDirection: 'column',
                            }}
                        >
                            <div style={{
                                width: 52,
                                height: 52,
                                borderRadius: 14,
                                background: iconBg,
                                display: 'grid',
                                placeItems: 'center',
                                fontSize: 24,
                                color: iconColor,
                                marginBottom: 18,
                                flexShrink: 0,
                            }}>
                                <Icon />
                            </div>
                            <h3 style={{ marginBottom: 10 }}>{title}</h3>
                            <p style={{ fontSize: 14.5, color: 'var(--text-muted)', lineHeight: 1.6, flex: 1, marginBottom: 24 }}>
                                {desc}
                            </p>
                            {cta.external ? (
                                <a href={cta.href} target="_blank" rel="noreferrer">
                                    <button className="btn btn-blue">{cta.label}</button>
                                </a>
                            ) : (
                                <Link to={cta.to!}>
                                    <button className="btn btn-blue">{cta.label}</button>
                                </Link>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
