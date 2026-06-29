import { useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import type { ReactNode } from 'react';

/* ── shared SVG helpers ─────────────────────────────────────────── */

function ChkIcon({ size = 16, sw = 3 }: { size?: number; sw?: number }) {
    return (
        <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={sw} strokeLinecap="round" strokeLinejoin="round">
            <polyline points="20 6 9 17 4 12" />
        </svg>
    );
}
function ArrowRight({ size = 16 }: { size?: number }) {
    return (
        <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
            <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
        </svg>
    );
}
function SaveBadge({ children }: { children: ReactNode }) {
    return (
        <div className="feature-savings">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
                <polyline points="20 6 9 17 4 12" strokeLinecap="round" />
            </svg>
            {children}
        </div>
    );
}

/* ── data ───────────────────────────────────────────────────────── */

const TRUST = [
    { p: 'M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5.8 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1.84-.1z', n: 'TikTok Shop' },
    { p: 'M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.6 0 12 0zM8 6h8v2H8V6zm10 12H6V9h12v9z', n: 'Shopee' },
    { p: 'M12 1.5 2 6v6c0 5.5 4 10.7 10 12 6-1.3 10-6.5 10-12V6L12 1.5zM10 17l-4-4 1.4-1.4L10 14.2l6.6-6.6L18 9l-8 8z', n: 'Lazada' },
    { p: 'M9.04 21.54c.96.29 1.93.46 2.96.46a10 10 0 0 0 10-10A10 10 0 0 0 12 2 10 10 0 0 0 2 12c0 2.5.92 4.78 2.43 6.53l-1.42 1.42a.5.5 0 0 0 .35.85h6.18z', n: 'Facebook' },
    { p: 'M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5A1.5 1.5 0 0 1 4.5 17 1.5 1.5 0 0 1 6 15.5 1.5 1.5 0 0 1 7.5 17 1.5 1.5 0 0 1 6 18.5zm12 0a1.5 1.5 0 0 1-1.5-1.5 1.5 1.5 0 0 1 1.5-1.5 1.5 1.5 0 0 1 1.5 1.5 1.5 1.5 0 0 1-1.5 1.5z', n: 'GHN' },
    { p: 'M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4z', n: 'GHTK' },
    { p: 'M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4z', n: 'Viettel Post' },
    { p: 'M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.2 6 3-6 3-6-3 6-3z', n: 'J&T Express' },
];

const PAINS = [
    { icon: 'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z', quote: '"Hết hàng rồi mà sao đơn vẫn về?"', desc: 'Cùng mẫu áo bán cả TikTok lẫn Shopee, kho còn 5 nhưng 2 sàn vẫn hiện "còn hàng". Khách đặt rồi hủy, shop tụt sao.' },
    { icon: 'M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z M12 6v6l4 2', quote: '"Sáng nào cũng mất 30 phút check đơn"', desc: 'Phải đăng nhập từng sàn, copy đơn ra Excel, xem đơn nào mới, đơn nào hủy. Lặp đi lặp lại mỗi ngày.' },
    { icon: 'M6 9V2h12v7 M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2 M6 14h12v8H6z', quote: '"In tem 100 đơn mất nửa buổi sáng"', desc: 'Mở từng đơn, bấm in, đóng lại, mở đơn tiếp theo. Đến trưa vẫn chưa giao xong cho shipper.' },
    { icon: 'M12 1v22 M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6', quote: '"Cuối tháng không biết lãi hay lỗ"', desc: 'Doanh thu thấy ngay, nhưng sàn trừ phí, voucher, phí ship… tính ra chẳng còn bao nhiêu. Không biết SKU nào đang lỗ.' },
    { icon: 'M3 3v18h18 M18 17V9 M13 17V5 M8 17v-3', quote: '"Quảng cáo đốt tiền mà không biết"', desc: 'Chạy ads Facebook nhiều tài khoản, cuối ngày mới biết chiến dịch nào lỗ. Không ai ngồi canh ads cả ngày được.' },
    { icon: 'M2 3h20v14H2z M8 21h8 M12 17v4', quote: '"Sàn trả tiền có đúng không?"', desc: 'Cuối tháng nhận sao kê, có khi thiếu vài trăm nghìn đến vài triệu, mà ngồi dò Excel thì không nổi.' },
    { icon: 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z', quote: '"Tin nhắn khách trả lời không xuể"', desc: 'Inbox Facebook, Shopee, TikTok dồn về cả trăm tin. Trả lời chậm là mất đơn, mà thuê người thì tốn kém.' },
];

type AutoItem = { color: string; tag: string; tagLabel: string; svgD: string; title: ReactNode; desc: ReactNode; points: ReactNode[] };
const AUTOS: AutoItem[] = [
    {
        color: 'blue', tag: 'live', tagLabel: 'Đang chạy',
        svgD: 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z',
        title: 'AI nhắn tin Facebook & đa sàn — chốt đơn tự động',
        desc: <>Hộp thư hợp nhất Facebook · Shopee · TikTok · Lazada. AI đọc hiểu tin khách, tư vấn và <strong>tự lên đơn ngay trong khung chat</strong>.</>,
        points: ['Auto-reply 4 kịch bản: chào, theo trạng thái đơn, ngoài giờ, tin đầu', 'Trợ lý AI trả lời theo kho tri thức (RAG) riêng của shop', 'Chế độ AI tự động (opt-in) — chốt đơn ngay trong inbox Facebook'],
    },
    {
        color: 'pink', tag: 'live', tagLabel: 'Đang chạy',
        svgD: 'M2 3h20v14H2z M8 21h8 M12 17v4 M7 13l3-3 2 2 4-4',
        title: 'Giám sát & quản lý tài khoản quảng cáo',
        desc: 'Kết nối nhiều tài khoản quảng cáo Facebook về một bảng điều khiển. Theo dõi chi tiêu, ngân sách và hiệu suất real-time, cảnh báo khi bất thường.',
        points: ['Tổng hợp nhiều tài khoản & chiến dịch quảng cáo về một nơi', 'Theo dõi chi tiêu, ngân sách, CPM/CPC/ROAS theo thời gian thực', 'Cảnh báo sớm khi chi phí mỗi đơn tăng hoặc tài khoản bị hạn chế'],
    },
    {
        color: 'violet', tag: 'live', tagLabel: 'Đang chạy',
        svgD: 'M3 3v18h18 M18 17V9 M13 17V5 M8 17v-3',
        title: 'AI phân tích hiệu suất & tạo chiến dịch quảng cáo',
        desc: <>AI đọc dữ liệu hiệu suất, chỉ ra chiến dịch đang đốt tiền và <strong>gợi ý / tạo chiến dịch mới</strong> theo mục tiêu — tăng đơn, giảm chi phí.</>,
        points: ['Phân tích sâu từng chiến dịch: nhóm quảng cáo, đối tượng, nội dung', 'Đề xuất hành động cụ thể: tăng/giảm ngân sách, tắt, nhân bản', 'Tạo nhanh chiến dịch mới từ gợi ý AI theo mục tiêu doanh thu'],
    },
    {
        color: 'teal', tag: 'live', tagLabel: 'Đang chạy',
        svgD: 'M3 3h18v4H3z M3 7v14h18V7 M8 11h8 M8 15h8 M8 19h5',
        title: 'Kế toán đúng chuẩn VAS (TT133)',
        desc: 'Sổ sách kế toán đầy đủ, tự động hạch toán từ đơn & kho. Cuối kỳ in ra cho kế toán, không phải làm tay.',
        points: ['Sổ cái kép bất biến, công nợ phải thu/phải trả (AR/AP)', 'Quỹ tiền mặt – ngân hàng, đối chiếu sao kê tự động', 'Báo cáo tài chính, tờ khai VAT, xuất MISA AMIS'],
    },
    {
        color: 'green', tag: 'live', tagLabel: 'Đang chạy',
        svgD: 'M20 7h-9 M14 17H5 M17 17a3 3 0 1 0 0-6 3 3 0 0 0 0 6z M7 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6z',
        title: 'Dự báo & đề xuất nhập hàng',
        desc: 'Nhìn tốc độ bán 30 ngày so với tồn và hàng đang về, tự đề xuất nhập đúng lượng — không hết hàng, không tồn ế.',
        points: ['Cảnh báo SKU sắp hết: "còn 8 ngày là hết, nhập 200 cái"', 'Tạo đơn mua hàng (PO) tự chia theo nhà cung cấp', 'Giá vốn theo đúng đợt nhập — lãi/lỗ chính xác'],
    },
    {
        color: 'orange', tag: 'live', tagLabel: 'Đang chạy',
        svgD: 'M3 7v6h6 M21 17a9 9 0 0 0-15-6.7L3 13 M21 17v-6h-6 M3 17a9 9 0 0 0 15 6.7L21 11',
        title: 'Quản lý đơn Hoàn & Hủy',
        desc: 'Gom toàn bộ đơn hoàn/hủy đa sàn về một nơi, dịch lý do sang tiếng Việt, xử lý gọn và đối soát đúng tiền.',
        points: ['Tập hợp đơn hoàn/hủy TikTok · Shopee · Lazada', 'Map mã lý do hoàn/hủy sang tiếng Việt dễ hiểu', 'Khớp lại tồn kho & tài chính khi hàng quay về'],
    },
];

const COMPARES: [string, string][] = [
    ['Sàn "trục trặc" → mất đơn không biết', 'Quét đơn mỗi 5-15 phút kể cả khi sàn không báo webhook — không bao giờ mất đơn.'],
    ['Trạng thái đơn cập nhật chậm hoặc sai', 'Cập nhật ngay khi sàn gửi tín hiệu, kể cả khi hệ thống sàn đang lỗi tạm thời.'],
    ['Bán cùng SKU 2 sàn → oversell', 'Tồn kho dùng chung — sàn nào bán trước trừ trước, sàn còn lại tự cập nhật trong vài giây.'],
    ['Đăng cùng sản phẩm lên 3 sàn rất mất công', 'Sao chép sản phẩm một lần — tự map thuộc tính & đăng lên TikTok, Shopee, Lazada.'],
    ['Set khuyến mãi/flash sale phải vào từng sàn làm tay', 'Tạo một lần — đẩy chương trình & flash sale đồng loạt lên cả 3 sàn, kèm lịch tự bật/tắt và chặn bán dưới giá vốn.'],
    ['Quảng cáo nhiều tài khoản, không ai canh nổi', 'Giám sát tập trung + AI phân tích, cảnh báo và đề xuất/tạo chiến dịch tự động.'],
    ['In tem mà không có hàng → shipper phạt', 'Chặn in tem khi đơn hết hàng + cảnh báo đỏ trước khi tạo vận đơn.'],
    ['Settlement nhân đôi vì lỗi đồng bộ', 'Tự khử trùng (idempotent) — chạy lại bao nhiêu lần cũng không nhân đôi tiền.'],
];

const ROI_CARDS: [string, string, string, string][] = [
    ['80', '%', 'Việc văn phòng được tự động', 'Từ check đơn đến đối soát'],
    ['4', 'x', 'Tăng năng suất nhân viên', '50 đơn/ngày → 200+ đơn/ngày'],
    ['80', 'h', 'Tiết kiệm mỗi tháng', 'Tương đương 0.5 FTE'],
    ['30', '%', 'Giảm vốn đọng kho', 'Đề xuất nhập hàng AI-driven'],
];
const ROI_ROWS: [string, string, string, string][] = [
    ['Thời gian check đơn buổi sáng', '30 phút', '5 phút', '25 phút/ngày'],
    ['Thời gian in tem 100 đơn', '60 phút', '5 phút', '55 phút/ngày'],
    ['Đăng sản phẩm mới lên 3 sàn', '45 phút', '5 phút', '40 phút/sản phẩm'],
    ['Set up flash sale 100 SKU (3 sàn)', '2-3 giờ', '5 phút', '~2.5 giờ/đợt'],
    ['Tỷ lệ đơn hủy vì oversell', '3-5%', '~0%', '150-250 đơn/tháng'],
    ['Đối soát settlement mỗi tháng', '1 ngày', '10 phút', '~8 giờ/tháng'],
    ['Vốn đọng kho do nhập sai', '(cao)', '(giảm 20-40%)', 'vài chục → vài trăm triệu'],
];

type IntItem = { bg: string; abbr: string; name: string; status: string; cls: string; label: string };
const PLATFORMS: IntItem[] = [
    { bg: '#000', abbr: 'TT', name: 'TikTok Shop', status: 'Đồng bộ đơn, tồn kho, sao chép & đăng sản phẩm', cls: 'live', label: 'Live' },
    { bg: '#EE4D2D', abbr: 'SP', name: 'Shopee', status: 'Đầy đủ - đồng bộ đơn, tồn kho, đối soát, copy SP', cls: 'live', label: 'Live' },
    { bg: '#0F156D', abbr: 'LZ', name: 'Lazada', status: 'Đồng bộ đơn + tồn kho + đối soát + copy SP', cls: 'live', label: 'Live' },
    { bg: '#1877F2', abbr: 'f', name: 'Facebook', status: 'Hộp thư hợp nhất, AI chốt đơn & tối ưu quảng cáo', cls: 'live', label: 'Live' },
    { bg: '#FF3B5C', abbr: 'LV', name: 'Đơn ngoài sàn', status: 'Livestream, Zalo, gọi điện - chung quy trình', cls: 'live', label: 'Live' },
];
const COURIERS: IntItem[] = [
    { bg: '#F26522', abbr: 'GHN', name: 'Giao Hàng Nhanh (GHN)', status: 'Tạo vận đơn, in tem, tracking, hủy đầy đủ', cls: 'live', label: 'Live' },
    { bg: '#2EB67D', abbr: 'GHT', name: 'Giao Hàng Tiết Kiệm (GHTK)', status: 'Tạo vận đơn, in tem, tracking, hủy đầy đủ', cls: 'live', label: 'Live' },
    { bg: '#EE0033', abbr: 'VTP', name: 'Viettel Post', status: 'Tạo vận đơn, in tem, tracking toàn quốc', cls: 'live', label: 'Live' },
    { bg: '#D71921', abbr: 'J&T', name: 'J&T Express', status: 'Đang hoàn thiện kết nối API', cls: 'beta', label: 'Sắp ra mắt' },
    { bg: '#5A6478', abbr: '+6', name: 'Ninja Van, SPX, Ahamove...', status: 'Theo lộ trình - đợt sau', cls: 'soon', label: 'Roadmap' },
];
const AUDIENCES = [
    { icon: 'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z M3.27 6.96 12 12.01 20.73 6.96 M12 22.08V12', title: 'Nhà bán đa sàn', desc: 'Đang vận hành từ 2 sàn trở lên: TikTok + Shopee, hoặc TikTok + Lazada, hoặc cả 3.' },
    { icon: 'M3 3v18h18 M7 12l4-4 4 4 6-6', title: 'Quy mô 1k-10k+ đơn/tháng', desc: 'Đã vượt khả năng quản lý bằng Excel hoặc các tool đơn lẻ. Cần hệ thống chuyên nghiệp.' },
    { icon: 'M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M8.5 3a4 4 0 1 1 0 8 4 4 0 0 1 0-8z M20 8v6 M23 11h-6', title: 'Shop 2-10 nhân viên', desc: 'Chủ + xử lý đơn + kho + kế toán — cần phân quyền role-based và phối hợp công việc.' },
    { icon: 'M2 3h20v14H2z M8 21h8 M12 17v4 M7 13l3-3 2 2 4-4', title: 'Đang chạy quảng cáo Facebook', desc: 'Nhiều tài khoản ads, cần giám sát tập trung và AI tối ưu để không đốt tiền.' },
    { icon: 'M12 1v22 M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6', title: 'Muốn biết lãi/lỗ thật', desc: 'Không hài lòng với chỉ xem doanh thu — cần biết SKU nào lãi, SKU nào lỗ.' },
    { icon: 'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z M3.27 6.96 12 12.01 20.73 6.96 M12 22.08V12', title: 'Có kho riêng', desc: 'Cần quy trình nhập / xuất / kiểm kê chuẩn, giao kho cho nhân viên không lo thất thoát.' },
];

const LABEL_CODES = ['TT291847', 'SP110293', 'LZ887122', 'TT291922', 'SP110301', 'LZ887188'];
const LABEL_WEIGHTS = ['500g', '800g', '300g', '700g', '400g', '600g'];

/* ── sections ───────────────────────────────────────────────────── */

function HeroSection() {
    return (
        <section className="hero">
            <div className="hero-grid-bg" />
            <div className="hero-aurora">
                <span className="a1" /><span className="a2" /><span className="a3" />
            </div>
            <div className="hero-noise" />
            <div className="container">
                <div className="hero-grid">
                    {/* copy */}
                    <div className="hero-copy">
                        <span className="eyebrow">
                            <span className="eyebrow-dot">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                            </span>
                            Phần mềm bán hàng đa kênh #1 cho nhà bán Việt Nam
                        </span>
                        <h1>Bán <span className="grad">nhiều sàn</span>,<br />quản <span className="grad">một nơi</span>.</h1>
                        <p className="hero-sub">
                            Hợp nhất TikTok Shop, Shopee, Lazada và đơn ngoài sàn về một trung tâm điều hành.
                            Đồng bộ real-time đơn &amp; tồn kho, sao chép sản phẩm hàng loạt, in tem một chạm,
                            AI chốt đơn trong chat Facebook, giám sát &amp; tối ưu quảng cáo, đối soát đến từng SKU.
                        </p>
                        <div className="hero-ctas">
                            <Link to="/register" className="btn btn-primary btn-lg">
                                Dùng thử miễn phí mãi mãi <ArrowRight />
                            </Link>
                            <a href="#features" className="btn btn-ghost btn-lg">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <polygon points="5 3 19 12 5 21 5 3" fill="currentColor" />
                                </svg>
                                Xem demo tính năng
                            </a>
                        </div>
                        <div className="hero-meta">
                            {['Miễn phí mãi mãi', 'Không cần thẻ tín dụng', 'Kích hoạt trong 5 phút'].map((t) => (
                                <span key={t} className="hero-meta-item">
                                    <ChkIcon size={14} />
                                    {t}
                                </span>
                            ))}
                        </div>
                    </div>

                    {/* stage */}
                    <div className="hero-stage">
                        <svg className="hero-links" viewBox="0 0 600 520" preserveAspectRatio="none">
                            <path id="lk1" className="link" d="M60 44 Q 210 130 300 260" />
                            <path id="lk2" className="link" d="M540 44 Q 390 130 300 260" />
                            <path id="lk3" className="link" d="M30 240 Q 150 252 300 260" />
                            <path id="lk4" className="link" d="M570 240 Q 450 252 300 260" />
                            <path id="lk5" className="link" d="M300 492 Q 300 380 300 260" />
                            {[
                                { id: 'lk1', dur: '2.4s', begin: undefined },
                                { id: 'lk2', dur: '2.8s', begin: '0.5s' },
                                { id: 'lk3', dur: '2.2s', begin: '1s' },
                                { id: 'lk4', dur: '2.6s', begin: '1.4s' },
                                { id: 'lk5', dur: '2.5s', begin: '0.8s' },
                            ].map((lk) => (
                                <circle key={lk.id} className="packet" r="3.4">
                                    <animateMotion dur={lk.dur} begin={lk.begin} repeatCount="indefinite" rotate="auto">
                                        <mpath href={`#${lk.id}`} />
                                    </animateMotion>
                                </circle>
                            ))}
                        </svg>

                        <div className="hero-node n1"><span className="nd tk">TT</span>TikTok Shop</div>
                        <div className="hero-node n2"><span className="nd sp">SP</span>Shopee</div>
                        <div className="hero-node n3"><span className="nd lz">LZ</span>Lazada</div>
                        <div className="hero-node n4"><span className="nd fb">f</span>Facebook</div>
                        <div className="hero-node n5"><span className="nd lv">LV</span>Livestream &amp; Zalo</div>

                        <div className="hub-card">
                            <div className="hub-bar">
                                <span className="d r" /><span className="d y" /><span className="d g" />
                                <span className="u">app.cmbcore.com</span>
                            </div>
                            <div className="hub-body">
                                <div className="hub-head">
                                    <h4>Tổng quan hôm nay</h4>
                                    <span className="hub-sync"><span className="dot" />Real-time</span>
                                </div>
                                <div className="hub-stats">
                                    <div className="hub-stat">
                                        <div className="l">Đơn hôm nay</div>
                                        <div className="v" data-count="487">0</div>
                                        <div className="dl">+24% vs hôm qua</div>
                                    </div>
                                    <div className="hub-stat">
                                        <div className="l">Lợi nhuận</div>
                                        <div className="v">₫38.4M</div>
                                        <div className="dl">Tỷ suất 27%</div>
                                    </div>
                                </div>
                                <div className="hub-rows">
                                    <div className="hub-row"><span className="hub-pill tt">TikTok</span><span className="nm">#TT-291847 · Áo thun unisex</span><span className="pf">+₫84K</span></div>
                                    <div className="hub-row"><span className="hub-pill sp">Shopee</span><span className="nm">#SP-110293 · Combo 3 món</span><span className="pf">+₫112K</span></div>
                                    <div className="hub-row"><span className="hub-pill lz">Lazada</span><span className="nm">#LZ-887122 · Phụ kiện ĐT</span><span className="pf">+₫42K</span></div>
                                </div>
                            </div>
                        </div>

                        <div className="hero-chip h1">
                            <span className="ci green">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                </svg>
                            </span>
                            <div><div className="ct">Tồn kho đồng bộ</div><div className="cs">3 sàn &lt; 5 giây</div></div>
                        </div>
                        <div className="hero-chip h2">
                            <span className="ci blue">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M6 9V2h12v7" /><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" /><rect x="6" y="14" width="12" height="8" rx="1" />
                                </svg>
                            </span>
                            <div><div className="ct">In tem hàng loạt</div><div className="cs">200 đơn / 10 phút</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

function StatsBand() {
    return (
        <div className="stats-band">
            <div className="container">
                <div className="stats-grid">
                    <div className="stat-cell reveal">
                        <div className="stat-num">&lt;5<span className="u">s</span></div>
                        <div className="stat-cap">Đồng bộ tồn kho real-time trên cả 3 sàn</div>
                    </div>
                    <div className="stat-cell reveal reveal-delay-1">
                        <div className="stat-num"><span data-count="200">0</span></div>
                        <div className="stat-cap">Đơn in tem chỉ trong 10 phút / 1 nhân viên</div>
                    </div>
                    <div className="stat-cell reveal reveal-delay-2">
                        <div className="stat-num"><span data-count="80">0</span><span className="u">h</span></div>
                        <div className="stat-cap">Tiết kiệm vận hành mỗi tháng (~0.5 FTE)</div>
                    </div>
                    <div className="stat-cell reveal reveal-delay-3">
                        <div className="stat-num"><span data-count="4">0</span><span className="u">×</span></div>
                        <div className="stat-cap">Tăng năng suất xử lý đơn / nhân viên</div>
                    </div>
                    <div className="stat-cell reveal reveal-delay-3">
                        <div className="stat-num">∞</div>
                        <div className="stat-cap">Miễn phí dùng thử mãi mãi, không cần thẻ tín dụng</div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function TrustMarquee() {
    return (
        <div className="trust-strip">
            <div className="container">
                <p className="trust-label">Tích hợp chính thức qua API với các nền tảng &amp; đơn vị vận chuyển</p>
            </div>
            <div className="marquee">
                {[0, 1].map((dup) => (
                    <div key={dup} className="marquee-track" aria-hidden={dup === 1 ? 'true' : 'false'}>
                        {TRUST.map((t) => (
                            <span key={t.n} className="trust-logo">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d={t.p} /></svg>
                                {t.n}
                            </span>
                        ))}
                    </div>
                ))}
            </div>
        </div>
    );
}

function PainSection() {
    return (
        <section className="pain-section" id="pain">
            <div className="container">
                <div className="section-header reveal">
                    <span className="section-tag">Nỗi đau quen thuộc</span>
                    <h2>Bạn có đang gặp những vấn đề này không?</h2>
                    <p className="section-sub">Nếu bạn gật đầu với 3 trong 7 ý dưới đây — CMBcoreSeller được sinh ra để dành cho bạn.</p>
                </div>
                <div className="pain-grid">
                    {PAINS.map((p, i) => (
                        <div key={p.quote} className={`pain-card reveal${i > 0 ? ` reveal-delay-${Math.min(i, 3)}` : ''}`}>
                            <div className="pain-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <path d={p.icon} />
                                </svg>
                            </div>
                            <div className="pain-quote">{p.quote}</div>
                            <p className="pain-desc">{p.desc}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

function FeaturesSection() {
    return (
        <section id="features">
            <div className="container">
                <div className="section-header reveal">
                    <span className="section-tag">Tính năng cốt lõi</span>
                    <h2>Một nền tảng — toàn bộ vận hành đa kênh</h2>
                    <p className="section-sub">CMBcoreSeller làm thay bạn 80% việc văn phòng, để bạn tập trung vào chốt đơn và xây dựng nội dung.</p>
                </div>

                {/* Feature 1 – Unified Order Hub */}
                <div className="feature-block">
                    <div className="feature-text reveal">
                        <span className="feature-tag"><span className="feature-tag-num">1</span>Unified Order Hub</span>
                        <h3>Mọi đơn — mọi sàn — một danh sách duy nhất</h3>
                        <p>Khách đặt hàng trên TikTok, Shopee hay Lazada — chỉ trong vài phút, đơn xuất hiện ngay trên CMBcoreSeller. Nhập thêm đơn ngoài sàn (livestream, Zalo, gọi điện) — chạy chung quy trình.</p>
                        <ul className="feature-list">
                            <li><ChkIcon /><span><strong>Zero order leakage</strong> — quét đơn mỗi 5-15 phút kể cả khi sàn trục trặc.</span></li>
                            <li><ChkIcon /><span><strong>Status mapping</strong> — không phải học thuộc trạng thái riêng của từng sàn.</span></li>
                            <li><ChkIcon /><span><strong>Advanced filtering</strong> — lọc, tìm, gắn thẻ, ghi chú đơn cực nhanh.</span></li>
                            <li><ChkIcon /><span><strong>Audit trail đầy đủ</strong> — ai đổi trạng thái, đổi lúc nào, từ gì sang gì.</span></li>
                        </ul>
                        <div className="feature-savings">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round">
                                <circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />
                            </svg>
                            Tiết kiệm 25 phút/ngày · ~12 giờ/tháng
                        </div>
                    </div>
                    <div className="feature-visual reveal reveal-delay-1">
                        <div className="multichannel-viz">
                            <svg className="connection-svg" viewBox="0 0 400 360" preserveAspectRatio="none">
                                <path d="M 70 30 Q 200 90 200 180" /><path d="M 330 30 Q 200 90 200 180" />
                                <path d="M 30 150 Q 120 170 200 180" /><path d="M 370 150 Q 280 170 200 180" />
                                <path d="M 200 280 Q 200 230 200 180" />
                            </svg>
                            <div className="channel-card c1"><div className="ch-icon tk">TT</div>TikTok Shop</div>
                            <div className="channel-card c2"><div className="ch-icon sp">SP</div>Shopee</div>
                            <div className="channel-card c3"><div className="ch-icon lz">LZ</div>Lazada</div>
                            <div className="channel-card c4"><div className="ch-icon zl">ZL</div>Zalo OA</div>
                            <div className="channel-card c5"><div className="ch-icon lv">LV</div>Livestream</div>
                            <div className="central-hub">CMB<br />Core</div>
                        </div>
                    </div>
                </div>

                {/* Feature 2 – Inventory Sync */}
                <div className="feature-block reverse">
                    <div className="feature-text reveal">
                        <span className="feature-tag"><span className="feature-tag-num">2</span>Real-time Inventory Sync</span>
                        <h3>Tồn kho luôn đúng — chấm dứt vĩnh viễn oversell</h3>
                        <p>Bạn nhập kho <strong>một lần</strong> — phần mềm tự đẩy số lượng lên TikTok, Shopee, Lazada. Bán ra ở sàn nào cũng trừ chung một kho. Tồn về 0 — cả 3 sàn cùng hiện hết hàng trong vài giây.</p>
                        <ul className="feature-list">
                            <li><ChkIcon /><span><strong>Safety stock</strong> — SKU bán chạy luôn giữ tối thiểu, không bao giờ rơi xuống 0 đột ngột.</span></li>
                            <li><ChkIcon /><span><strong>Multi-warehouse</strong> — quản lý đồng thời kho Hà Nội, TP.HCM, kho CTV.</span></li>
                            <li><ChkIcon /><span><strong>Bundle &amp; combo</strong> — mua áo tặng quà — phần mềm trừ đúng từng thành phần.</span></li>
                            <li><ChkIcon /><span><strong>Stock movement log</strong> — mọi thay đổi tồn đều ghi lại, minh bạch.</span></li>
                        </ul>
                        <SaveBadge>Giảm 80-90% case xin lỗi &amp; hoàn tiền vì hết hàng</SaveBadge>
                    </div>
                    <div className="feature-visual reveal reveal-delay-1">
                        <div className="stock-viz">
                            <div className="stock-bar">
                                <div className="stock-bar-head"><span className="stock-bar-name">Áo thun unisex / M / Trắng</span><span className="stock-bar-count">142 / 200</span></div>
                                <div className="stock-bar-track"><div className="stock-bar-fill" style={{ width: '71%' }} /></div>
                            </div>
                            <div className="stock-bar">
                                <div className="stock-bar-head"><span className="stock-bar-name">Phụ kiện điện thoại - Combo 3</span><span className="stock-bar-count">48 / 100</span></div>
                                <div className="stock-bar-track"><div className="stock-bar-fill" style={{ width: '48%', background: '#F59E0B' }} /></div>
                            </div>
                            <div className="stock-bar">
                                <div className="stock-bar-head"><span className="stock-bar-name">Set quà tặng cao cấp</span><span className="stock-bar-count">12 / 80</span></div>
                                <div className="stock-bar-track"><div className="stock-bar-fill" style={{ width: '15%', background: 'var(--danger)' }} /></div>
                            </div>
                            <div className="stock-bar">
                                <div className="stock-bar-head"><span className="stock-bar-name">Túi vải canvas custom</span><span className="stock-bar-count">96 / 120</span></div>
                                <div className="stock-bar-track"><div className="stock-bar-fill" style={{ width: '80%' }} /></div>
                            </div>
                            <div style={{ display: 'flex', gap: 8, justifyContent: 'center', marginTop: 6 }}>
                                {['TikTok', 'Shopee', 'Lazada'].map((s) => (
                                    <span key={s} className="sync-badge"><span className="pulse-dot" />{s}</span>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Feature 3 – Product Copy */}
                <div className="feature-block">
                    <div className="feature-text reveal">
                        <span className="feature-tag"><span className="feature-tag-num">3</span>Product Copy &amp; Listing</span>
                        <h3>Sao chép sản phẩm — đăng một lần, lên cả 3 sàn</h3>
                        <p>Tạo sản phẩm <strong>một lần</strong> trong CMBcoreSeller, rồi <strong>sao chép sang TikTok, Shopee, Lazada</strong> chỉ với vài cú nhấp. Tự map thuộc tính, phân loại, giá và ảnh theo chuẩn từng sàn — không phải gõ lại từng nơi.</p>
                        <ul className="feature-list">
                            <li><ChkIcon /><span><strong>Đăng hàng loạt</strong> — copy nhiều SKU, biến thể, ảnh lên nhiều sàn cùng lúc.</span></li>
                            <li><ChkIcon /><span><strong>Mapping thông minh</strong> — tự khớp danh mục &amp; thuộc tính theo từng sàn.</span></li>
                            <li><ChkIcon /><span><strong>Giá &amp; biến thể riêng</strong> — đặt giá khác nhau cho mỗi sàn ngay khi copy.</span></li>
                            <li><ChkIcon /><span><strong>Đồng bộ 2 chiều</strong> — sửa sản phẩm gốc, các sàn cập nhật theo.</span></li>
                        </ul>
                        <SaveBadge>Mở rộng sang sàn mới chỉ trong vài phút thay vì cả ngày</SaveBadge>
                    </div>
                    <div className="feature-visual reveal reveal-delay-1">
                        <div className="copy-viz">
                            <div className="copy-source">
                                <div className="copy-thumb">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path d="m7.5 4.27 9 5.15" /><path d="M21 8 12 13 3 8" /><path d="M3 8v8l9 5 9-5V8" />
                                    </svg>
                                </div>
                                <div className="copy-meta"><div className="t">Áo thun unisex Premium</div><div className="s">SKU-AT-001 · 4 biến thể</div></div>
                                <span className="sync-badge">Gốc</span>
                            </div>
                            <div className="copy-flow"><span className="ln" /></div>
                            <div className="copy-targets">
                                {[
                                    { bg: '#000', abbr: 'TT', name: 'TikTok' },
                                    { bg: '#EE4D2D', abbr: 'SP', name: 'Shopee' },
                                    { bg: '#0F156D', abbr: 'LZ', name: 'Lazada' },
                                ].map((t) => (
                                    <div key={t.name} className="copy-target">
                                        <div className="ic" style={{ background: t.bg }}>{t.abbr}</div>
                                        <div className="nm">{t.name}</div>
                                        <div className="ok">
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3.5" strokeLinecap="round"><polyline points="20 6 9 17 4 12" /></svg>
                                            Đã đăng
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Feature 4 – Promotions */}
                <div className="feature-block reverse">
                    <div className="feature-text reveal">
                        <span className="feature-tag"><span className="feature-tag-num">4</span>Promotions &amp; Flash Sale</span>
                        <h3>Khuyến mãi &amp; Flash sale hàng loạt — đẩy một lần, lên cả 3 sàn</h3>
                        <p>Tạo chương trình giảm giá, voucher và <strong>flash sale cho hàng trăm SKU một lần</strong>, đẩy đồng loạt lên TikTok Shop, Shopee, Lazada — không phải vào từng sàn set tay từng sản phẩm.</p>
                        <ul className="feature-list">
                            <li><ChkIcon /><span><strong>Khuyến mãi đa sàn</strong> — tạo &amp; quản lý chương trình giảm giá, voucher tập trung, đẩy lên 3 sàn từ một nơi.</span></li>
                            <li><ChkIcon /><span><strong>Flash sale hàng loạt</strong> — chọn nhiều SKU, đặt giá sốc &amp; khung giờ, đẩy đồng loạt cùng lúc.</span></li>
                            <li><ChkIcon /><span><strong>Lên lịch tự động</strong> — hẹn giờ bật/tắt chương trình, tự kết thúc đúng giờ, không cần canh thủ công.</span></li>
                            <li><ChkIcon /><span><strong>Bảo vệ lợi nhuận</strong> — cảnh báo/chặn khi giá khuyến mãi xuống dưới giá vốn, tránh bán lỗ hàng loạt.</span></li>
                        </ul>
                        <SaveBadge>Set up flash sale 100+ SKU trong 5 phút thay vì cả buổi</SaveBadge>
                    </div>
                    <div className="feature-visual reveal reveal-delay-1">
                        <div className="promo-viz">
                            <div className="promo-card">
                                <div className="promo-head">
                                    <span className="pt">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 3 14h7l-1 8 10-12h-7z" /></svg>
                                        Flash Sale 12.12
                                    </span>
                                    <span className="promo-timer"><span className="dot" />01:59:48</span>
                                </div>
                                <div className="promo-body">
                                    {[
                                        { name: 'Áo thun unisex Premium', old: '₫289K', nw: '₫199K', disc: '−31%' },
                                        { name: 'Set combo 3 món', old: '₫520K', nw: '₫364K', disc: '−30%' },
                                        { name: 'Túi canvas custom', old: '₫150K', nw: '₫99K', disc: '−34%' },
                                    ].map((r) => (
                                        <div key={r.name} className="promo-row">
                                            <div className="promo-thumb" />
                                            <div className="promo-info">
                                                <div className="nm">{r.name}</div>
                                                <div className="pr"><span className="old">{r.old}</span><span className="new">{r.nw}</span></div>
                                            </div>
                                            <span className="promo-disc">{r.disc}</span>
                                        </div>
                                    ))}
                                </div>
                                <div className="promo-push">
                                    <span className="lbl">Đẩy 128 SKU →</span>
                                    {['TikTok', 'Shopee', 'Lazada'].map((s) => (
                                        <span key={s} className="promo-chan">
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3.5" strokeLinecap="round"><polyline points="20 6 9 17 4 12" /></svg>
                                            {s}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Feature 5 – Bulk Fulfillment */}
                <div className="feature-block">
                    <div className="feature-text reveal">
                        <span className="feature-tag"><span className="feature-tag-num">5</span>Bulk Fulfillment</span>
                        <h3>In tem hàng loạt — 200 đơn trong 10 phút</h3>
                        <p>Chọn hết đơn mới, bấm <strong>một nút</strong> — phần mềm tự gọi đơn vị vận chuyển, lấy mã vận đơn, tải tem PDF về <strong>gộp thành một file</strong>. In một lần, dán vào kiện hàng, mang ra shipper.</p>
                        <ul className="feature-list">
                            <li><ChkIcon /><span><strong>Picklist gộp theo SKU</strong> — đi một vòng nhặt hết hàng, không chạy đi chạy lại.</span></li>
                            <li><ChkIcon /><span><strong>Barcode scan đóng gói</strong> — quét là biết đơn nào đã đóng, đã giao shipper.</span></li>
                            <li><ChkIcon /><span><strong>Auto-tracking</strong> — đơn đã giao, đã nhận, đã hoàn — tự cập nhật.</span></li>
                            <li><ChkIcon /><span><strong>Out-of-stock block</strong> — chặn in tem khi đơn hết hàng, tránh bị shipper phạt.</span></li>
                        </ul>
                        <SaveBadge>Năng suất tăng 4x · 1 nhân viên xử lý 200+ đơn/ngày</SaveBadge>
                    </div>
                    <div className="feature-visual reveal reveal-delay-1">
                        <div className="label-viz">
                            {LABEL_CODES.map((code, i) => (
                                <div key={code} className="label-paper">
                                    <div>GHN · {LABEL_WEIGHTS[i]}</div>
                                    <div className="label-barcode" />
                                    <div className="label-code">{code}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Feature 6 – Customer Intelligence */}
                <div className="feature-block reverse">
                    <div className="feature-text reveal">
                        <span className="feature-tag"><span className="feature-tag-num">6</span>Customer Intelligence</span>
                        <h3>Sổ khách thông minh — biết khách "xịn" hay khách "bom"</h3>
                        <p>Phần mềm tự nhận diện khách qua <strong>số điện thoại</strong>. Cùng một số đặt 3 đơn ở TikTok và 2 đơn ở Shopee — vẫn gom chung một hồ sơ. Nhìn vào là biết ngay đặt bao nhiêu, hủy mấy.</p>
                        <ul className="feature-list">
                            <li><ChkIcon /><span><strong>Trust scoring</strong> — Tốt / Cần chú ý / Rủi ro / Đã chặn — hiện ngay khi đơn về.</span></li>
                            <li><ChkIcon /><span><strong>Auto blacklist</strong> — không nhận đơn từ khách bom hàng nhiều lần.</span></li>
                            <li><ChkIcon /><span><strong>Cross-channel matching</strong> — gộp khách trùng dù 2 số điện thoại khác nhau.</span></li>
                            <li><ChkIcon /><span><strong>GDPR-compliant</strong> — tuân thủ quy định bảo vệ dữ liệu cá nhân buyer.</span></li>
                        </ul>
                        <SaveBadge>Giảm 30-50% tỷ lệ hủy đơn / hoàn hàng</SaveBadge>
                    </div>
                    <div className="feature-visual reveal reveal-delay-1">
                        <div className="customer-viz">
                            {[
                                { init: 'NT', name: 'Nguyễn Thị Trang', meta: '42 đơn · 0 hủy · LTV ₫12.8M', badgeCls: 'good', badge: 'Tốt', avatarCls: '' },
                                { init: 'LH', name: 'Lê Hoàng Nam', meta: '8 đơn · 1 hủy · LTV ₫2.4M', badgeCls: 'good', badge: 'Tốt', avatarCls: '' },
                                { init: 'PV', name: 'Phạm Văn Hùng', meta: '6 đơn · 2 hủy liên tiếp', badgeCls: 'attention', badge: 'Cần chú ý', avatarCls: 'warn' },
                                { init: 'TQ', name: 'Trần Quốc B.', meta: '5 đơn · 5 bom liên tiếp', badgeCls: 'risk', badge: 'Rủi ro', avatarCls: 'danger' },
                            ].map((c) => (
                                <div key={c.name} className="customer-card">
                                    <div className={`customer-avatar${c.avatarCls ? ' ' + c.avatarCls : ''}`}>{c.init}</div>
                                    <div className="customer-info">
                                        <div className="customer-name">{c.name}</div>
                                        <div className="customer-meta">{c.meta}</div>
                                    </div>
                                    <span className={`customer-badge ${c.badgeCls}`}>{c.badge}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Feature 7 – Settlement */}
                <div className="feature-block">
                    <div className="feature-text reveal">
                        <span className="feature-tag"><span className="feature-tag-num">7</span>Settlement Reconciliation</span>
                        <h3>Đối soát tiền sàn — biết sàn có tính đúng không</h3>
                        <p>CMBcoreSeller <strong>tự kéo bảng kê về</strong>, đối chiếu với đơn của bạn. Phát hiện sai phí, thu hồi tiền. Đối soát 1 tháng làm trong 10 phút thay vì 1 ngày.</p>
                        <ul className="feature-list">
                            <li><ChkIcon /><span><strong>Variance detection</strong> — đơn nào đã thanh toán, đơn nào chưa.</span></li>
                            <li><ChkIcon /><span><strong>Ghost orders alert</strong> — sàn tính phí nhưng không thấy đơn trong hệ thống.</span></li>
                            <li><ChkIcon /><span><strong>Actual P&L per order</strong> — dùng phí THỰC sàn đã trừ, không phải ước tính.</span></li>
                            <li><ChkIcon /><span><strong>Idempotent sync</strong> — chạy lại bao nhiêu lần cũng không nhân đôi tiền.</span></li>
                        </ul>
                        <SaveBadge>Đối soát 1 tháng làm trong 10 phút thay vì 1 ngày</SaveBadge>
                    </div>
                    <div className="feature-visual reveal reveal-delay-1">
                        <div className="settlement-viz">
                            <div style={{ fontWeight: 700, fontSize: 14, marginBottom: 6 }}>Sao kê T05/2026 · TikTok Shop</div>
                            {[
                                { label: 'Doanh thu gộp (487 đơn)', val: '₫142,480,000', cls: '' },
                                { label: 'Phí sàn 5%', val: '−₫7,124,000', cls: 'neg' },
                                { label: 'Phí thanh toán 2.5%', val: '−₫3,562,000', cls: 'neg' },
                                { label: 'Voucher người bán', val: '−₫4,200,000', cls: 'neg' },
                                { label: 'Phí ship trợ giá', val: '−₫2,180,000', cls: 'neg' },
                                { label: 'Tổng thực nhận', val: '₫125,414,000', cls: 'total' },
                            ].map((row) => (
                                <div key={row.label} className="settle-line">
                                    <span>{row.label}</span>
                                    <span className={row.cls || undefined} style={!row.cls ? { fontFamily: 'var(--mono)' } : undefined}>{row.val}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Feature 8 – Profitability */}
                <div className="feature-block reverse">
                    <div className="feature-text reveal">
                        <span className="feature-tag"><span className="feature-tag-num">8</span>Profitability Analytics</span>
                        <h3>Lãi/Lỗ từng đơn — hiện ngay trên danh sách</h3>
                        <p>Mở danh sách đơn ra, bạn thấy ngay cột "Lợi nhuận ước tính" cho mỗi đơn. <strong>Doanh thu − Giá vốn − Phí sàn − Phí ship</strong>. Đơn lãi mạnh, đơn lỗ vốn — hiển thị rõ từng dòng.</p>
                        <ul className="feature-list">
                            <li><ChkIcon /><span><strong>SKU-level P&L</strong> — biết SKU nào đang lỗ → quyết định bỏ hoặc tăng giá.</span></li>
                            <li><ChkIcon /><span><strong>Channel ROAS</strong> — sàn nào lãi nhất → đầu tư quảng cáo đúng chỗ.</span></li>
                            <li><ChkIcon /><span><strong>Campaign attribution</strong> — chiến dịch giảm giá thực sự kiếm được bao nhiêu.</span></li>
                            <li><ChkIcon /><span><strong>COGS alert</strong> — cảnh báo khi đơn thiếu giá vốn SKU.</span></li>
                        </ul>
                        <SaveBadge>Quyết định dựa trên lợi nhuận thật, không phải doanh thu ảo</SaveBadge>
                    </div>
                    <div className="feature-visual reveal reveal-delay-1">
                        <div className="profit-viz">
                            {[
                                { name: 'Áo thun unisex / M / Trắng', rev: '₫289,000', result: '+29%', pos: true },
                                { name: 'Set combo 3 món', rev: '₫520,000', result: '+22%', pos: true },
                                { name: 'Phụ kiện điện thoại', rev: '₫149,000', result: '+28%', pos: true },
                                { name: 'Túi canvas (flash sale)', rev: '₫89,000', result: '−14%', pos: false },
                                { name: 'Quần short summer', rev: '₫220,000', result: '+18%', pos: true },
                            ].map((r) => (
                                <div key={r.name} className="profit-row">
                                    <div className="profit-name">{r.name}</div>
                                    <div className="profit-rev">{r.rev}</div>
                                    <div className={`profit-result ${r.pos ? 'pos' : 'neg'}`}>{r.result}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Feature 9 – Mobile App */}
                <div className="feature-block">
                    <div className="feature-text reveal">
                        <span className="feature-tag"><span className="feature-tag-num">9</span>Mobile App</span>
                        <h3>App điện thoại — vận hành cả shop ngay trong túi bạn</h3>
                        <p>Đi café, đi nhập hàng hay đang livestream — vẫn nắm trọn tình hình. <strong>App CMBcoreSeller</strong> báo đơn mới tức thì, cho bạn duyệt đơn, xem tồn kho và trả lời khách ngay trên điện thoại.</p>
                        <ul className="feature-list">
                            <li><ChkIcon /><span><strong>Thông báo real-time</strong> — đơn mới, đơn hủy, hết hàng đẩy ngay về máy.</span></li>
                            <li><ChkIcon /><span><strong>Duyệt &amp; xử lý đơn</strong> — xác nhận, tạo vận đơn, theo dõi giao hàng mọi lúc.</span></li>
                            <li><ChkIcon /><span><strong>Chat &amp; chốt đơn</strong> — trả lời inbox đa sàn ngay trên app, không lỡ khách.</span></li>
                            <li><ChkIcon /><span><strong>Báo cáo nhanh</strong> — doanh thu, lợi nhuận, tồn kho xem được mọi nơi.</span></li>
                        </ul>
                        <SaveBadge>Không còn dán mắt vào máy tính — quản shop từ bất cứ đâu</SaveBadge>
                    </div>
                    <div className="feature-visual reveal reveal-delay-1">
                        <div className="phone-viz">
                            <div className="phone">
                                <div className="phone-screen">
                                    <div className="phone-notch" />
                                    <div className="phone-top"><div className="h">CMBcoreSeller</div><div className="s">Tổng quan · 12 đơn mới</div></div>
                                    <div className="phone-feed">
                                        <div className="phone-item">
                                            <span className="pi green"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M9 11H1v10h8z" /><path d="M22 11h-8v10h8z" /><circle cx="12" cy="6" r="3" /></svg></span>
                                            <div className="pt"><div className="a">Đơn mới TikTok</div><div className="b">#TT-291847 · ₫289K</div></div>
                                        </div>
                                        <div className="phone-item">
                                            <span className="pi blue"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" /></svg></span>
                                            <div className="pt"><div className="a">Khách nhắn tin</div><div className="b">"Còn size M không shop?"</div></div>
                                        </div>
                                        <div className="phone-item">
                                            <span className="pi orange"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg></span>
                                            <div className="pt"><div className="a">SKU sắp hết</div><div className="b">Set quà tặng · còn 12</div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="phone-ping pg1">
                                <span className="pp"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><polyline points="20 6 9 17 4 12" /></svg></span>
                                <div><b>Đã chốt đơn</b><br /><span>qua AI chat</span></div>
                            </div>
                            <div className="phone-ping pg2">
                                <span className="pp"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M12 2v20" /><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" /></svg></span>
                                <div><b>+₫1.2M</b><br /><span>doanh thu hôm nay</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

function AutomationSection() {
    return (
        <section className="automation-section" id="automation">
            <div className="container">
                <div className="section-header reveal">
                    <span className="section-tag">AI · Quảng cáo &amp; Tự động hoá</span>
                    <h2>Bộ não tự động — chat, quảng cáo &amp; kế toán</h2>
                    <p className="section-sub">Không chỉ quản đơn — CMBcoreSeller tự chốt sale trong chat Facebook, giám sát &amp; tối ưu quảng cáo bằng AI và lo sổ sách kế toán cho bạn.</p>
                </div>
                <div className="automation-grid">
                    {AUTOS.map((a, i) => (
                        <div key={i} className={`auto-card reveal reveal-delay-${i % 3}`}>
                            <div className="auto-head">
                                <div className={`auto-icon ${a.color}`}>
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                        <path d={a.svgD} />
                                    </svg>
                                </div>
                                <div>
                                    <div className="auto-title">{a.title}</div>
                                    <span className={`auto-tag ${a.tag}`}>{a.tagLabel}</span>
                                </div>
                            </div>
                            <p className="auto-desc">{a.desc}</p>
                            <ul className="auto-points">
                                {a.points.map((pt, j) => (
                                    <li key={j}>
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round">
                                            <polyline points="20 6 9 17 4 12" />
                                        </svg>
                                        <span>{pt}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

function CompareSection() {
    return (
        <section className="compare-section" id="compare">
            <div className="container">
                <div className="section-header reveal">
                    <span className="section-tag">Vì sao chọn CMBcoreSeller</span>
                    <h2>Khác biệt so với các tool đa sàn khác</h2>
                    <p className="section-sub">Kiến trúc hệ thống thiết kế cho thị trường Việt Nam — xử lý các edge case mà tool generic không xử lý được.</p>
                </div>
                <div className="compare-wrap reveal">
                    <div className="compare-row head">
                        <div>Vấn đề bạn hay gặp với tool khác</div>
                        <div>CMBcoreSeller xử lý thế nào</div>
                    </div>
                    {COMPARES.map(([prob, sol]) => (
                        <div key={prob} className="compare-row">
                            <div>{prob}</div>
                            <div>{sol}</div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

function RoiSection() {
    return (
        <section id="roi">
            <div className="container">
                <div className="section-header reveal">
                    <span className="section-tag">Hiệu quả tài chính</span>
                    <h2>Lợi ích cụ thể tính bằng tiền</h2>
                    <p className="section-sub">Một shop mẫu (5.000 đơn/tháng, 3 sàn, 4 nhân viên) sau khi triển khai CMBcoreSeller.</p>
                </div>
                <div className="roi-cards">
                    {ROI_CARDS.map(([num, unit, label, desc], i) => (
                        <div key={label} className={`roi-card reveal reveal-delay-${i}`}>
                            <div className="roi-number"><span data-count={num}>0</span>{unit}</div>
                            <div className="roi-label">{label}</div>
                            <div className="roi-desc">{desc}</div>
                        </div>
                    ))}
                </div>
                <div className="roi-table reveal">
                    <div className="roi-row head"><div>Hạng mục</div><div>Trước</div><div>Sau</div><div>Tiết kiệm</div></div>
                    {ROI_ROWS.map(([item, before, after, save]) => (
                        <div key={item} className="roi-row">
                            <div>{item}</div><div>{before}</div><div>{after}</div><div>{save}</div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

function IntegrationBlock({ title, icon, items }: { title: ReactNode; icon: ReactNode; items: IntItem[] }) {
    return (
        <div className="integration-block reveal">
            <div className="integration-title">{icon}{title}</div>
            <div className="integration-items">
                {items.map((it) => (
                    <div key={it.name} className="integration-item">
                        <div className="integration-icon" style={{ background: it.bg }}>{it.abbr}</div>
                        <div className="integration-info">
                            <div className="integration-name">{it.name}</div>
                            <div className="integration-status">{it.status}</div>
                        </div>
                        <span className={`status-tag ${it.cls}`}>{it.label}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

function IntegrationsSection() {
    return (
        <section id="integrations" className="compare-section">
            <div className="container">
                <div className="section-header reveal">
                    <span className="section-tag">Tích hợp</span>
                    <h2>Sàn TMĐT &amp; đơn vị vận chuyển</h2>
                    <p className="section-sub">Kết nối chính thức qua API. Mở rộng liên tục theo nhu cầu thị trường.</p>
                </div>
                <div className="integration-grid">
                    <IntegrationBlock
                        title="Sàn thương mại điện tử &amp; kênh chat"
                        icon={
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9z" /><path d="m3 9 2-5h14l2 5" /><line x1="12" y1="13" x2="12" y2="17" />
                            </svg>
                        }
                        items={PLATFORMS}
                    />
                    <IntegrationBlock
                        title="Đơn vị vận chuyển"
                        icon={
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M14 9h4l4 4v5h-2" /><circle cx="6" cy="18" r="2" /><circle cx="18" cy="18" r="2" /><path d="M16 18H8" /><path d="M4 18H2v-5h12v5h-2" /><path d="M14 9V5a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v8" />
                            </svg>
                        }
                        items={COURIERS}
                    />
                </div>
            </div>
        </section>
    );
}

function AudienceSection() {
    return (
        <section className="audience-section" id="audience">
            <div className="container">
                <div className="section-header reveal">
                    <span className="section-tag">Đối tượng phù hợp</span>
                    <h2>Ai nên dùng CMBcoreSeller?</h2>
                    <p className="section-sub">Thiết kế cho nhà bán đa sàn quy mô vừa đến lớn, đã vượt khả năng quản lý bằng Excel.</p>
                </div>
                <div className="audience-grid">
                    {AUDIENCES.map((a, i) => (
                        <div key={a.title} className={`audience-card reveal${i > 0 ? ` reveal-delay-${(i % 3) + 1}` : ''}`}>
                            <div className="audience-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                                    <path d={a.icon} />
                                </svg>
                            </div>
                            <div className="audience-title">{a.title}</div>
                            <p className="audience-desc">{a.desc}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

function FinalCta() {
    return (
        <section className="final-cta">
            <div className="container">
                <div className="cta-card reveal">
                    <span className="glow g1" /><span className="glow g2" />
                    <h2>Sẵn sàng tăng tốc vận hành đa kênh?</h2>
                    <p className="section-sub">Đăng ký dùng thử miễn phí mãi mãi. Không cần thẻ tín dụng. Kích hoạt trong 5 phút.</p>
                    <Link to="/register" className="btn btn-primary btn-lg">
                        Bắt đầu miễn phí ngay hôm nay <ArrowRight />
                    </Link>
                    <div className="cta-meta">
                        {['Hỗ trợ 24/7 qua Zalo', 'Migrate dữ liệu miễn phí', 'Hủy bất cứ lúc nào'].map((t) => (
                            <span key={t} className="cta-meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                {t}
                            </span>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}

/* ── main export ─────────────────────────────────────────────────── */

/** Landing page body — rendered inside PublicLayout (provides .cmb-public wrapper + header + footer). SPEC 2026-06-26 Task L3. */
export default function SellerLandingPage() {
    const location = useLocation();

    /* Hash-scroll: when navigating from another page with /#anchor */
    useEffect(() => {
        if (!location.hash) return;
        const el = document.querySelector(location.hash);
        if (el) el.scrollIntoView({ behavior: 'smooth' });
    }, [location.hash]);

    /* Scroll-reveal + count-up observers */
    useEffect(() => {
        const revealObs = new IntersectionObserver(
            (entries) => {
                entries.forEach((e) => {
                    if (e.isIntersecting) {
                        e.target.classList.add('in');
                        revealObs.unobserve(e.target);
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -60px 0px' },
        );
        document.querySelectorAll('.reveal').forEach((el) => revealObs.observe(el));

        const countObs = new IntersectionObserver(
            (entries) => {
                entries.forEach((e) => {
                    if (!e.isIntersecting) return;
                    const el = e.target as HTMLElement;
                    const target = parseInt(el.dataset.count ?? '0', 10);
                    const start = performance.now();
                    const tick = (now: number) => {
                        const p = Math.min((now - start) / 1400, 1);
                        const eased = 1 - Math.pow(1 - p, 3);
                        el.textContent = String(Math.floor(target * eased));
                        if (p < 1) requestAnimationFrame(tick);
                        else el.textContent = String(target);
                    };
                    requestAnimationFrame(tick);
                    countObs.unobserve(el);
                });
            },
            { threshold: 0.5 },
        );
        document.querySelectorAll('[data-count]').forEach((c) => countObs.observe(c));

        /* Smooth-scroll for in-page anchor clicks */
        const handleAnchorClick = (e: MouseEvent) => {
            const a = (e.target as Element).closest('a[href^="#"]') as HTMLAnchorElement | null;
            if (!a) return;
            const href = a.getAttribute('href');
            if (!href || href === '#') return;
            const target = document.querySelector(href);
            if (!target) return;
            e.preventDefault();
            window.scrollTo({ top: target.getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth' });
        };
        document.addEventListener('click', handleAnchorClick);

        return () => {
            revealObs.disconnect();
            countObs.disconnect();
            document.removeEventListener('click', handleAnchorClick);
        };
    }, []);

    return (
        <>
            <HeroSection />
            <StatsBand />
            <TrustMarquee />
            <PainSection />
            <FeaturesSection />
            <AutomationSection />
            <CompareSection />
            <RoiSection />
            <IntegrationsSection />
            <AudienceSection />
            <FinalCta />
        </>
    );
}
