import { Alert, Card, Form, Input, Radio, Space, Typography } from 'antd';
import { GlobalOutlined, LikeOutlined, MessageOutlined, ShoppingOutlined } from '@ant-design/icons';
import type { RadioChangeEvent } from 'antd';
import type { AdObjective } from '@/lib/adWizard';
import { useDraftStore } from '@/lib/adWizard/draftStore';

const { Text } = Typography;

interface ObjectiveOption {
    value: AdObjective;
    icon: React.ReactNode;
    label: string;
    hint: string;
}

const OBJECTIVE_OPTIONS: ObjectiveOption[] = [
    {
        value: 'messages',
        icon: <MessageOutlined style={{ fontSize: 20 }} />,
        label: 'Tin nhắn',
        hint: 'Nhận hội thoại Messenger',
    },
    {
        value: 'engagement',
        icon: <LikeOutlined style={{ fontSize: 20 }} />,
        label: 'Tương tác',
        hint: 'Like/Comment/Share',
    },
    {
        value: 'traffic',
        icon: <GlobalOutlined style={{ fontSize: 20 }} />,
        label: 'Truy cập web',
        hint: 'Kéo về website',
    },
    {
        value: 'conversions',
        icon: <ShoppingOutlined style={{ fontSize: 20 }} />,
        label: 'Chuyển đổi',
        hint: 'Mua hàng / sự kiện Pixel',
    },
];

const OBJECTIVE_ALERTS: Record<AdObjective, string> = {
    messages:
        "Tối ưu để khách nhắn tin cho Trang. Ở bước Nội dung nên chọn bài viết của Trang với nút 'Gửi tin nhắn'.",
    engagement:
        'Tối ưu để tăng lượt thích, bình luận và chia sẻ cho bài viết hoặc Trang.',
    traffic:
        'Tối ưu để kéo người dùng truy cập website. Hãy điền URL đích ở bước Nội dung.',
    conversions:
        'Tối ưu theo sự kiện chuyển đổi (Pixel) như Mua hàng. Chọn Pixel và sự kiện ở bước Ngân sách, và điền URL đích ở bước Nội dung.',
};

export function StepObjective() {
    const name = useDraftStore((s) => s.name);
    const objective = useDraftStore((s) => s.objective);
    const setName = useDraftStore((s) => s.setName);
    const setObjective = useDraftStore((s) => s.setObjective);

    function handleObjectiveChange(e: RadioChangeEvent) {
        setObjective(e.target.value as AdObjective);
    }

    return (
        <Form layout="vertical">
            <Form.Item label="Tên chiến dịch">
                <Input
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder="Ví dụ: Chiến dịch tháng 6"
                    style={{ maxWidth: 400 }}
                />
            </Form.Item>

            <Form.Item label="Mục tiêu">
                <Radio.Group
                    value={objective}
                    onChange={handleObjectiveChange}
                    style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}
                >
                    {OBJECTIVE_OPTIONS.map((opt) => (
                        <Radio.Button
                            key={opt.value}
                            value={opt.value}
                            style={{ height: 'auto', padding: 0 }}
                        >
                            <Card
                                style={{
                                    width: 160,
                                    border: 'none',
                                    cursor: 'pointer',
                                    background: 'transparent',
                                }}
                                styles={{ body: { padding: '16px 12px', textAlign: 'center' } }}
                            >
                                <Space direction="vertical" size={4} style={{ width: '100%' }}>
                                    {opt.icon}
                                    <Text strong>{opt.label}</Text>
                                    <Text type="secondary" style={{ fontSize: 12 }}>
                                        {opt.hint}
                                    </Text>
                                </Space>
                            </Card>
                        </Radio.Button>
                    ))}
                </Radio.Group>
            </Form.Item>

            {objective != null && (
                <Alert
                    type="info"
                    showIcon
                    message={OBJECTIVE_ALERTS[objective]}
                    style={{ maxWidth: 560 }}
                />
            )}
        </Form>
    );
}
