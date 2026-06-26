import { Link } from 'react-router-dom';
import { Button, Card, Col, Row, Space, Typography } from 'antd';
import {
    ApiOutlined, BarChartOutlined, ShopOutlined, SyncOutlined, TruckOutlined, WalletOutlined,
} from '@ant-design/icons';

const FEATURES = [
    { icon: <SyncOutlined />, title: 'Đồng bộ đa sàn', desc: 'Đồng bộ đơn, tồn kho, sản phẩm giữa TikTok Shop, Shopee, Lazada — một nơi quản lý tất cả.' },
    { icon: <TruckOutlined />, title: 'Xử lý & giao vận', desc: 'Chuẩn bị hàng, in tem/phiếu, đẩy đơn vị vận chuyển (GHN, GHTK, ViettelPost, J&T) tự động.' },
    { icon: <ShopOutlined />, title: 'Tồn kho master SKU', desc: 'Một nguồn tồn kho duy nhất cho mọi gian hàng, chống bán âm, đối soát chính xác.' },
    { icon: <WalletOutlined />, title: 'Kế toán & đối soát', desc: 'Ghi nhận doanh thu, giá vốn, đối soát sao kê sàn, ví trả trước của khách.' },
    { icon: <BarChartOutlined />, title: 'Báo cáo & quảng cáo', desc: 'Báo cáo bán hàng, lợi nhuận; quản lý quảng cáo Facebook & TikTok Ads.' },
    { icon: <ApiOutlined />, title: 'API mở', desc: 'Tích hợp hệ thống ngoài qua API key — thao tác như trên web. Xem tài liệu API.' },
];

/** Trang giới thiệu phần mềm (public). SPEC 2026-06-26. */
export function HomePage() {
    return (
        <div>
            <section style={{ background: 'linear-gradient(180deg,#f0f6ff,#fff)', padding: '72px 24px' }}>
                <div style={{ maxWidth: 880, margin: '0 auto', textAlign: 'center' }}>
                    <Typography.Title style={{ fontSize: 40, marginBottom: 12 }}>
                        Quản lý bán hàng đa sàn cho nhà bán Việt Nam
                    </Typography.Title>
                    <Typography.Paragraph style={{ fontSize: 18, color: '#595959' }}>
                        Đồng bộ đơn hàng, tồn kho, giao vận và kế toán giữa TikTok Shop, Shopee, Lazada — trên một nền tảng duy nhất.
                    </Typography.Paragraph>
                    <Space size={12} style={{ marginTop: 16 }}>
                        <Link to="/register"><Button type="primary" size="large">Dùng thử miễn phí</Button></Link>
                        <Link to="/pricing"><Button size="large">Xem bảng giá</Button></Link>
                    </Space>
                </div>
            </section>
            <section style={{ maxWidth: 1080, margin: '0 auto', padding: '48px 24px' }}>
                <Typography.Title level={2} style={{ textAlign: 'center', marginBottom: 32 }}>Tính năng chính</Typography.Title>
                <Row gutter={[24, 24]}>
                    {FEATURES.map((f) => (
                        <Col xs={24} sm={12} lg={8} key={f.title}>
                            <Card variant="borderless" style={{ height: '100%', background: '#fafafa' }}>
                                <div style={{ fontSize: 28, color: '#1677ff', marginBottom: 8 }}>{f.icon}</div>
                                <Typography.Title level={4} style={{ marginTop: 0 }}>{f.title}</Typography.Title>
                                <Typography.Paragraph type="secondary" style={{ marginBottom: 0 }}>{f.desc}</Typography.Paragraph>
                            </Card>
                        </Col>
                    ))}
                </Row>
            </section>
        </div>
    );
}
