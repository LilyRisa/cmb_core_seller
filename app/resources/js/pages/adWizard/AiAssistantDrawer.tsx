import { Alert, Drawer, Space, Typography } from 'antd';
import { RobotOutlined } from '@ant-design/icons';
import type { AdDraftPayload } from '@/lib/adWizard';

const { Paragraph } = Typography;

interface AiAssistantDrawerProps {
    open: boolean;
    onClose: () => void;
    step: number;
    payload: AdDraftPayload;
}

interface Suggestion {
    key: string;
    message: string;
}

function getSuggestions(step: number, payload: AdDraftPayload): Suggestion[] {
    switch (step) {
        case 0:
            return [
                {
                    key: 'objective',
                    message: 'Shop bán qua chat nên chọn Tin nhắn; bán hàng web chọn Truy cập web.',
                },
            ];
        case 1: {
            const daily = payload.budget?.daily_major ?? 0;
            if (daily < 50000) {
                return [
                    {
                        key: 'budget-low',
                        message: 'Ngân sách hơi thấp, thử 100.000đ/ngày để đủ dữ liệu.',
                    },
                ];
            }
            return [
                {
                    key: 'budget-ok',
                    message: 'Ngân sách ổn. Theo dõi 3 ngày trước khi tăng.',
                },
            ];
        }
        case 2:
            return [
                {
                    key: 'audience',
                    message: 'Tệp quá rộng sẽ tốn tiền — thu hẹp tuổi và thêm 2–3 sở thích liên quan.',
                },
            ];
        case 4:
            return [
                {
                    key: 'creative',
                    message: 'Quảng cáo từ bài viết có sẵn giữ lại tương tác → tăng độ tin cậy.',
                },
            ];
        default:
            return [
                {
                    key: 'general',
                    message: 'Hoàn tất từng bước, kiểm tra xem trước trước khi xuất bản.',
                },
            ];
    }
}

export function AiAssistantDrawer({ open, onClose, step, payload }: AiAssistantDrawerProps) {
    const suggestions = getSuggestions(step, payload);

    return (
        <Drawer
            title={
                <Space>
                    <RobotOutlined />
                    Trợ lý quảng cáo
                </Space>
            }
            placement="right"
            width={360}
            open={open}
            onClose={onClose}
        >
            <Space direction="vertical" size={12} style={{ width: '100%' }}>
                {suggestions.map((s) => (
                    <Alert key={s.key} type="info" showIcon message={s.message} />
                ))}
                <Paragraph type="secondary" style={{ fontSize: 12, marginTop: 8 }}>
                    Đây là gợi ý tự động dựa trên bước hiện tại — không phải tư vấn chuyên sâu.
                </Paragraph>
            </Space>
        </Drawer>
    );
}
