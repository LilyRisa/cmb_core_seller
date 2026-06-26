import { Link } from 'react-router-dom';
import { Button, Card, Col, Row, Space, Typography } from 'antd';
import { ApiOutlined, ChromeOutlined, MobileOutlined } from '@ant-design/icons';

/** Phần mềm phụ trợ: Chrome extension + App mobile (gộp 1 trang). SPEC 2026-06-26. */
export function ToolsPage() {
    return (
        <div style={{ maxWidth: 1080, margin: '0 auto', padding: '48px 24px' }}>
            <Typography.Title level={2} style={{ textAlign: 'center' }}>Phần mềm phụ trợ</Typography.Title>
            <Typography.Paragraph type="secondary" style={{ textAlign: 'center', marginBottom: 32 }}>
                Mở rộng CMBcoreSeller với tiện ích trình duyệt, ứng dụng di động và API.
            </Typography.Paragraph>
            <Row gutter={[24, 24]}>
                <Col xs={24} md={8}>
                    <Card id="extension" style={{ height: '100%' }}>
                        <div style={{ fontSize: 32, color: '#1677ff' }}><ChromeOutlined /></div>
                        <Typography.Title level={4}>Chrome Extension</Typography.Title>
                        <Typography.Paragraph type="secondary">Sao chép sản phẩm từ sàn về kho, thao tác nhanh ngay trên trình duyệt khi duyệt trang sàn.</Typography.Paragraph>
                        <Button type="primary" disabled>Sắp ra mắt</Button>
                    </Card>
                </Col>
                <Col xs={24} md={8}>
                    <Card style={{ height: '100%' }}>
                        <div style={{ fontSize: 32, color: '#1677ff' }}><MobileOutlined /></div>
                        <Typography.Title level={4}>App Mobile</Typography.Title>
                        <Typography.Paragraph type="secondary">Quản lý đơn, quét đóng gói, nhận thông báo realtime trên điện thoại.</Typography.Paragraph>
                        <Link to="/download"><Button type="primary">Tải ứng dụng</Button></Link>
                    </Card>
                </Col>
                <Col xs={24} md={8}>
                    <Card style={{ height: '100%' }}>
                        <div style={{ fontSize: 32, color: '#1677ff' }}><ApiOutlined /></div>
                        <Typography.Title level={4}>API mở</Typography.Title>
                        <Typography.Paragraph type="secondary">Tích hợp hệ thống ngoài (ERP, Zapier...) qua API key — thao tác như trên web.</Typography.Paragraph>
                        <Space>
                            <Link to="/api-docs"><Button type="primary">Tài liệu API</Button></Link>
                        </Space>
                    </Card>
                </Col>
            </Row>
        </div>
    );
}
