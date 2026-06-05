import { MessageOutlined, RobotOutlined, SoundOutlined } from '@ant-design/icons';

/**
 * Lớp nền trang trí cho khung thương hiệu (auth). Phần lưới công nghệ + orbs nằm ở
 * CSS (`.auth-brand::before/::after`). Component này thêm:
 *  - Lớp "bán hàng": biểu đồ tăng trưởng TỰ VẼ + hạt doanh thu bay lên.
 *  - Lớp "tự động hoá tác vụ": pipeline 3 node (Tin nhắn → AI chat → Quảng cáo) nối
 *    bằng đường ống có dòng chạy + xung dữ liệu di chuyển + vòng ping sáng lần lượt.
 * Thuần trang trí ⇒ aria-hidden; tự tắt animation khi người dùng bật reduce-motion.
 */
export function AuthBackdrop() {
    return (
        <div className="auth-commerce" aria-hidden="true">
            {/* Pipeline tự động hoá: message → AI → ads */}
            <div className="auth-flow">
                <svg className="auth-flow-svg" viewBox="0 0 200 240" fill="none">
                    <path id="authFlowPath" className="auth-flow-path" d="M150 28 C 96 64 50 84 50 120 C 50 156 104 176 150 212" />
                    <circle className="auth-flow-pulse" r="3.5">
                        <animateMotion dur="3.2s" repeatCount="indefinite">
                            <mpath href="#authFlowPath" />
                        </animateMotion>
                    </circle>
                </svg>
                <span className="auth-node auth-node-1"><span className="auth-node-ping" /><MessageOutlined /></span>
                <span className="auth-node auth-node-2"><span className="auth-node-ping" /><RobotOutlined /></span>
                <span className="auth-node auth-node-3"><span className="auth-node-ping" /><SoundOutlined /></span>
            </div>

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
