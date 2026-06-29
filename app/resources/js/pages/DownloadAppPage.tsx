import type { CSSProperties } from 'react';
import {
    AndroidFilled,
    AppleFilled,
    BarChartOutlined,
    MessageOutlined,
    ScanOutlined,
} from '@ant-design/icons';
import '../../css/download-app.css';

/**
 * Trang tải ứng dụng di động — công khai, không cần đăng nhập. Reached at `/download`.
 * Ứng dụng cho phép: xem thống kê, trả lời tin nhắn, quét giao hàng.
 * Android tải trực tiếp APK; iOS/Google Play đang "sắp ra mắt".
 * Hiển thị bên trong PublicLayout (header + footer dùng chung, không tự quản).
 */

// Link tải bản test hiện tại. Khi lên store/CDN chính thức, đổi tại đây.
const APK_URL =
    'https://expo.dev/artifacts/eas/YCvIzulXP72tPMTEhGCS__WhoY6RYlcfaJvTUujFCxI.apk';

// CSS custom properties định nghĩa bởi .dl-shell — khai lại ở đây vì không còn wrapper dl-shell
const DL_VARS = {
    '--dl-blue': '#2563eb',
    '--dl-blue-deep': '#1e40af',
    '--dl-navy': '#0b1222',
    '--dl-cyan': '#38bdf8',
    '--dl-lime': '#34d399',
    '--dl-ink': '#0f172a',
    '--dl-muted': '#64748b',
    '--dl-line': '#e2e8f0',
} as CSSProperties;

const FEATURE_ITEMS = [
    { Icon: BarChartOutlined, text: 'Xem thống kê doanh thu & đơn hàng theo thời gian thực' },
    { Icon: MessageOutlined,  text: 'Trả lời tin nhắn khách hàng đa kênh trong một hộp thư' },
    { Icon: ScanOutlined,     text: 'Quét mã đóng gói & bàn giao vận chuyển siêu nhanh' },
];

const FEATURE_CARDS = [
    {
        Icon: BarChartOutlined,
        title: 'Xem thống kê',
        desc: 'Doanh thu, lợi nhuận, số đơn theo ngày và theo sàn — cập nhật liên tục. Nắm tình hình kinh doanh chỉ trong vài giây, ở bất cứ đâu.',
        grad: 'linear-gradient(135deg, #2563eb, #1e40af)',
    },
    {
        Icon: MessageOutlined,
        title: 'Trả lời tin nhắn',
        desc: 'Gộp tin nhắn từ nhiều kênh về một hộp thư. Trả lời khách, chốt đơn và chăm sóc ngay trên điện thoại, không bỏ lỡ khách nào.',
        grad: 'linear-gradient(135deg, #7c3aed, #4f46e5)',
    },
    {
        Icon: ScanOutlined,
        title: 'Quét giao hàng',
        desc: 'Dùng camera quét mã để đóng gói và bàn giao đơn cho đơn vị vận chuyển. Hạn chế nhầm đơn, tăng tốc khâu xử lý kho.',
        grad: 'linear-gradient(135deg, #0f766e, #34d399)',
    },
];

export function DownloadAppPage() {
    return (
        <div style={DL_VARS}>
            {/* ---- Hero ---- */}
            <section className="hero">
                <div className="hero-grid-bg" />
                <div className="hero-aurora">
                    <span className="a1" />
                    <span className="a2" />
                    <span className="a3" />
                </div>
                <div className="hero-noise" />
                <div className="container">
                    <div className="hero-grid">
                        {/* Left copy */}
                        <div className="hero-copy">
                            <div className="eyebrow">
                                <span className="eyebrow-dot">
                                    <AndroidFilled style={{ fontSize: 10 }} />
                                </span>
                                Quản lý shop ngay trên điện thoại
                            </div>
                            <h1>
                                Cả cửa hàng của bạn,<br />
                                <span className="grad">gọn trong túi.</span>
                            </h1>
                            <p className="hero-sub">
                                Ứng dụng CMBcoreSeller giúp bạn theo dõi doanh số, trả lời khách
                                và quét giao hàng mọi lúc mọi nơi — không cần mở máy tính.
                            </p>
                            <ul style={{ listStyle: 'none', padding: 0, margin: '0 0 0', display: 'flex', flexDirection: 'column', gap: 13 }}>
                                {FEATURE_ITEMS.map(({ Icon, text }) => (
                                    <li key={text} style={{ display: 'flex', alignItems: 'center', gap: 12, fontSize: 14.5, fontWeight: 500, color: 'rgba(255,255,255,.85)' }}>
                                        <span style={{
                                            width: 34, height: 34, borderRadius: 10, flexShrink: 0,
                                            display: 'grid', placeItems: 'center', fontSize: 16,
                                            background: 'rgba(255,255,255,.09)', border: '1px solid rgba(255,255,255,.14)',
                                        }}>
                                            <Icon />
                                        </span>
                                        {text}
                                    </li>
                                ))}
                            </ul>
                            <div className="hero-ctas">
                                <a className="dl-apk" href={APK_URL} download>
                                    <AndroidFilled className="ico" />
                                    <span>
                                        Tải cho Android
                                        <small>File .APK · cài trực tiếp</small>
                                    </span>
                                </a>
                                <p style={{ width: '100%', fontSize: 12.5, color: 'rgba(255,255,255,.48)', margin: '4px 0 0', flexBasis: '100%' }}>
                                    Android 8.0 trở lên · Khi cài cần cho phép &quot;Cài từ nguồn không xác định&quot;.
                                </p>
                            </div>
                        </div>

                        {/* Right: phone mockup art */}
                        <div className="hero-stage" style={{ display: 'flex', justifyContent: 'center', alignItems: 'flex-end' }}>
                            <div className="dl-phone" style={{ transform: 'rotate(-3deg)' }}>
                                <div className="dl-phone-screen">
                                    <div className="dl-ph-top">
                                        <div className="hi">Doanh thu hôm nay</div>
                                        <div className="big">42.8 triệu ₫</div>
                                        <div className="delta">▲ 18% so với hôm qua</div>
                                    </div>
                                    <div className="dl-ph-stats">
                                        <div className="dl-ph-stat"><div className="k">Đơn mới</div><div className="v">127</div></div>
                                        <div className="dl-ph-stat"><div className="k">Chờ giao</div><div className="v">34</div></div>
                                    </div>
                                    <div className="dl-ph-bars">
                                        <span style={{ height: '40%' }} />
                                        <span style={{ height: '62%' }} />
                                        <span style={{ height: '48%' }} />
                                        <span style={{ height: '80%' }} />
                                        <span style={{ height: '66%' }} />
                                        <span style={{ height: '95%' }} />
                                        <span style={{ height: '72%' }} />
                                    </div>
                                    <div className="dl-ph-chat">
                                        <div className="dl-ph-bubble in">Shop ơi còn hàng size M không ạ?</div>
                                        <div className="dl-ph-bubble out">Dạ còn ạ, em lên đơn cho mình nhé! 🧡</div>
                                    </div>
                                    <div className="dl-ph-scan">
                                        <div className="frame"><div className="line" /></div>
                                        <div className="lbl">Quét mã bàn giao</div>
                                        <div className="sub">Đưa camera vào mã vận đơn</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* ---- Features ---- */}
            <section style={{ background: 'var(--bg-soft)', borderTop: '1px solid var(--border-soft)' }}>
                <div className="container">
                    <div style={{ textAlign: 'center', maxWidth: 620, margin: '0 auto 48px' }}>
                        <span className="section-tag">Tính năng</span>
                        <h2 style={{ marginTop: 16 }}>Ba việc quan trọng nhất, làm ngay trên tay</h2>
                        <p className="section-sub">Thiết kế cho chủ shop và nhân viên bận rộn — mở app là làm được việc.</p>
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: 24 }}>
                        {FEATURE_CARDS.map(({ Icon, title, desc, grad }) => (
                            <div
                                key={title}
                                style={{
                                    background: '#fff',
                                    border: '1px solid var(--border-soft)',
                                    borderRadius: 'var(--radius-lg)',
                                    padding: '28px 24px',
                                    boxShadow: 'var(--shadow-sm)',
                                }}
                            >
                                <div style={{
                                    width: 52, height: 52, borderRadius: 15,
                                    display: 'grid', placeItems: 'center',
                                    fontSize: 24, color: '#fff',
                                    marginBottom: 18, background: grad,
                                }}>
                                    <Icon />
                                </div>
                                <h3 style={{ marginBottom: 8 }}>{title}</h3>
                                <p style={{ color: 'var(--text-muted)', fontSize: 14, lineHeight: 1.6, margin: 0 }}>{desc}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ---- Coming Soon stores ---- */}
            <section>
                <div className="container">
                    <div style={{
                        background: 'linear-gradient(135deg, #0f1c3a, #0b1222)',
                        borderRadius: 26,
                        padding: '44px',
                        textAlign: 'center',
                        color: '#fff',
                        position: 'relative',
                        overflow: 'hidden',
                    }}>
                        <div style={{
                            position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, pointerEvents: 'none',
                            background: 'radial-gradient(560px 220px at 50% -30%, rgba(56,189,248,.28), transparent 60%)',
                        }} />
                        {/* color:'#fff' bắt buộc vì .cmb-public h2{color:var(--text)} ghi đè màu kế thừa từ thẻ cha */}
                        <h2 style={{ fontSize: 'clamp(22px, 3vw, 28px)', fontWeight: 800, marginBottom: 8, position: 'relative', color: '#fff' }}>
                            Sắp có mặt trên App Store &amp; Google Play
                        </h2>
                        <p style={{ color: '#aab8d6', fontSize: 14.5, margin: '0 0 26px', position: 'relative' }}>
                            Chúng tôi đang hoàn tất đưa ứng dụng lên các kho ứng dụng chính thức.
                        </p>
                        <div className="dl-badges">
                            <div className="dl-badge">
                                <span className="soon">Sắp ra mắt</span>
                                <AppleFilled className="ico" />
                                <span>
                                    <span className="t1">Tải về từ</span>
                                    <span className="t2">App Store</span>
                                </span>
                            </div>
                            <div className="dl-badge">
                                <span className="soon">Sắp ra mắt</span>
                                <AndroidFilled className="ico" />
                                <span>
                                    <span className="t1">Tải về từ</span>
                                    <span className="t2">Google Play</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    );
}
