import { Button, Card, Empty, Space, Typography, Tag } from 'antd';
import { ShopOutlined } from '@ant-design/icons';

const PROVIDERS = [
    { code: 'tiktok', name: 'TikTok Shop' },
    { code: 'shopee', name: 'Shopee' },
    { code: 'lazada', name: 'Lazada' },
];

export function ChannelsPage() {
    return (
        <div>
            <Typography.Title level={3}>Gian hàng</Typography.Title>
            <Card title="Kết nối gian hàng mới" style={{ marginBottom: 16 }}>
                <Space wrap>
                    {PROVIDERS.map((p) => (
                        <Button key={p.code} icon={<ShopOutlined />} disabled>
                            Kết nối {p.name} <Tag color="default" style={{ marginLeft: 8 }}>Phase 1+</Tag>
                        </Button>
                    ))}
                </Space>
            </Card>
            <Card title="Gian hàng đã kết nối">
                <Empty description="Chưa có gian hàng nào. Luồng OAuth sẽ được bật ở Phase 1 (TikTok Shop trước)." />
            </Card>
        </div>
    );
}
