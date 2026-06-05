/**
 * Lớp nền trang trí cho khung thương hiệu (auth). Phần lưới công nghệ + orbs nằm ở
 * CSS (`.auth-brand::before/::after`). Component này thêm lớp "bán hàng":
 *  - Biểu đồ tăng trưởng TỰ VẼ (đường line chạy dần + vùng nền + chấm đỉnh nhấp nháy).
 *  - Hạt "doanh thu" bay lên (CSS, ở `.auth-commerce::after`).
 * Thuần trang trí ⇒ aria-hidden; tự tắt animation khi người dùng bật reduce-motion.
 */
export function AuthBackdrop() {
    return (
        <div className="auth-commerce" aria-hidden="true">
            <svg className="auth-chart" viewBox="0 0 376 220" fill="none">
                <defs>
                    <linearGradient id="authChartFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stopColor="#ffffff" stopOpacity="0.16" />
                        <stop offset="1" stopColor="#ffffff" stopOpacity="0" />
                    </linearGradient>
                </defs>
                <polygon
                    className="auth-chart-area"
                    points="0,182 60,160 120,168 180,120 240,134 300,74 360,40 360,220 0,220"
                    fill="url(#authChartFill)"
                />
                <polyline
                    className="auth-chart-line"
                    points="0,182 60,160 120,168 180,120 240,134 300,74 360,40"
                />
                <circle className="auth-chart-dot" cx="360" cy="40" r="5" />
            </svg>
        </div>
    );
}
