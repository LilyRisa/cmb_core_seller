import { Link } from 'react-router-dom';
import { Space, Typography } from 'antd';

export function PublicFooter() {
    return (
        <footer style={{ borderTop: '1px solid #f0f0f0', padding: '24px', background: '#fafafa' }}>
            <div style={{ maxWidth: 1080, margin: '0 auto', display: 'flex', flexWrap: 'wrap', gap: 16, justifyContent: 'space-between', alignItems: 'center' }}>
                <Typography.Text strong style={{ color: '#1677ff' }}>CMBcoreSeller</Typography.Text>
                <Space size={20} wrap>
                    <Link to="/pricing">Bảng giá</Link>
                    <Link to="/api-docs">Tài liệu API</Link>
                    <Link to="/tools">Phần mềm phụ trợ</Link>
                    <Link to="/tracking">Tra cứu đơn</Link>
                    <Link to="/login">Đăng nhập</Link>
                </Space>
                <Typography.Text type="secondary" style={{ fontSize: 13 }}>© {new Date().getFullYear()} CMBcoreSeller</Typography.Text>
            </div>
        </footer>
    );
}
