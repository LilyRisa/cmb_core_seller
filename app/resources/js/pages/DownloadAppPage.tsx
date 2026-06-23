import { Link } from 'react-router-dom';
import {
    AndroidFilled,
    AppleFilled,
    BarChartOutlined,
    MessageOutlined,
    ScanOutlined,
    ArrowLeftOutlined,
} from '@ant-design/icons';
import '../../css/download-app.css';

/**
 * Trang tải ứng dụng di động — công khai, không cần đăng nhập. Reached at `/download`.
 * Ứng dụng cho phép: xem thống kê, trả lời tin nhắn, quét giao hàng.
 * Android tải trực tiếp APK; iOS/Google Play đang "sắp ra mắt".
 */

// Link tải bản test hiện tại. Khi lên store/CDN chính thức, đổi tại đây.
const APK_URL =
    'https://expo.dev/artifacts/eas/Apuoger3lIHWJh9uq6am-V7x-27nNZ6oFzMAPlGSYlA.apk';

export function DownloadAppPage() {
    return (
        <div className="dl-shell">
            <nav className="dl-nav">
                <div className="dl-nav-brand">
                    <img src="/images/logocmb.png" alt="CMBcoreSeller" />
                    <span>
                        CMBcoreSeller
                        <small>Ứng dụng di động</small>
                    </span>
                </div>
                <Link to="/" className="dl-nav-back">
                    <ArrowLeftOutlined /> Về trang chính
                </Link>
            </nav>

            {/* ---------------- HERO ---------------- */}
            <header className="dl-hero">
                <div className="dl-hero-copy">
                    <span className="dl-pill dl-rise dl-d1">
                        <span className="dot" /> Quản lý shop ngay trên điện thoại
                    </span>
                    <h1 className="dl-rise dl-d2">
                        Cả cửa hàng của bạn,<br />
                        <span className="grad">gọn trong túi.</span>
                    </h1>
                    <p className="dl-hero-sub dl-rise dl-d2">
                        Ứng dụng CMBcoreSeller giúp bạn theo dõi doanh số, trả lời khách
                        và quét giao hàng mọi lúc mọi nơi — không cần mở máy tính.
                    </p>

                    <ul className="dl-hero-feats dl-rise dl-d3">
                        <li><span className="fi"><BarChartOutlined /></span> Xem thống kê doanh thu &amp; đơn hàng theo thời gian thực</li>
                        <li><span className="fi"><MessageOutlined /></span> Trả lời tin nhắn khách hàng đa kênh trong một hộp thư</li>
                        <li><span className="fi"><ScanOutlined /></span> Quét mã đóng gói &amp; bàn giao vận chuyển siêu nhanh</li>
                    </ul>

                    <div className="dl-cta-row dl-rise dl-d4">
                        <a className="dl-apk" href={APK_URL} download>
                            <AndroidFilled className="ico" />
                            <span>
                                Tải cho Android
                                <small>File .APK · cài trực tiếp</small>
                            </span>
                        </a>
                    </div>
                    <p className="dl-apk-note dl-rise dl-d4">
                        Android 8.0 trở lên · Khi cài cần cho phép “Cài từ nguồn không xác định”.
                    </p>
                </div>

                {/* mockup điện thoại dựng bằng CSS */}
                <div className="dl-hero-art dl-rise dl-d3">
                    <div className="dl-phone">
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
            </header>

            {/* ---------------- FEATURES ---------------- */}
            <section className="dl-section">
                <div className="dl-section-head">
                    <h2>Ba việc quan trọng nhất, làm ngay trên tay</h2>
                    <p>Thiết kế cho chủ shop và nhân viên bận rộn — mở app là làm được việc.</p>
                </div>
                <div className="dl-cards">
                    <div className="dl-card c1 dl-rise dl-d1">
                        <div className="ic"><BarChartOutlined /></div>
                        <h3>Xem thống kê</h3>
                        <p>Doanh thu, lợi nhuận, số đơn theo ngày và theo sàn — cập nhật liên tục.
                            Nắm tình hình kinh doanh chỉ trong vài giây, ở bất cứ đâu.</p>
                    </div>
                    <div className="dl-card c2 dl-rise dl-d2">
                        <div className="ic"><MessageOutlined /></div>
                        <h3>Trả lời tin nhắn</h3>
                        <p>Gộp tin nhắn từ nhiều kênh về một hộp thư. Trả lời khách, chốt đơn và
                            chăm sóc ngay trên điện thoại, không bỏ lỡ khách nào.</p>
                    </div>
                    <div className="dl-card c3 dl-rise dl-d3">
                        <div className="ic"><ScanOutlined /></div>
                        <h3>Quét giao hàng</h3>
                        <p>Dùng camera quét mã để đóng gói và bàn giao đơn cho đơn vị vận chuyển.
                            Hạn chế nhầm đơn, tăng tốc khâu xử lý kho.</p>
                    </div>
                </div>
            </section>

            {/* ---------------- COMING SOON STORES ---------------- */}
            <section className="dl-stores">
                <div className="dl-stores-inner dl-rise dl-d1">
                    <h2>Sắp có mặt trên App Store &amp; Google Play</h2>
                    <p>Chúng tôi đang hoàn tất đưa ứng dụng lên các kho ứng dụng chính thức.</p>
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
            </section>

            <footer className="dl-footer">
                <span>&copy; {new Date().getFullYear()} CMBcoreSeller — Quản lý bán hàng đa sàn</span>
                <Link to="/login" style={{ color: 'var(--dl-blue)', fontWeight: 600 }}>Đăng nhập trên web →</Link>
            </footer>
        </div>
    );
}
