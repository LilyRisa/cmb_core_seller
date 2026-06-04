import { Alert, Button, Descriptions, Space, Typography } from 'antd';
import { RocketOutlined } from '@ant-design/icons';
import { App } from 'antd';
import { useNavigate } from 'react-router-dom';
import { useDraftStore } from '@/lib/adWizard/draftStore';
import { useAdDraft, useAdPreviews, usePublishDraft } from '@/lib/adWizard';
import type { AdObjective } from '@/lib/adWizard';
import { errorMessage } from '@/lib/api';

const { Text } = Typography;

const OBJECTIVE_LABELS: Record<AdObjective, string> = {
    messages: 'Tin nhắn',
    engagement: 'Tương tác',
    traffic: 'Truy cập web',
};

function formatBudget(daily_major: number | undefined): string {
    if (daily_major == null || daily_major === 0) return '—';
    return new Intl.NumberFormat('vi-VN').format(daily_major) + 'đ';
}

export function StepReview() {
    const { message } = App.useApp();
    const navigate = useNavigate();

    const draftId = useDraftStore((s) => s.draftId);
    const accountId = useDraftStore((s) => s.accountId);
    const name = useDraftStore((s) => s.name);
    const objective = useDraftStore((s) => s.objective);
    const payload = useDraftStore((s) => s.payload);

    const { data: draftData } = useAdDraft(draftId);
    const previewMutation = useAdPreviews();
    const publishMutation = usePublishDraft();

    const creative = payload.creative;
    const mode = creative?.mode ?? 'page_post';

    const contentLabel = mode === 'page_post' ? 'Bài viết có sẵn' : 'Nội dung mới';
    const ctaLabel = creative?.cta ?? '—';

    const canPublish =
        objective != null &&
        (payload.budget?.daily_major ?? 0) > 0 &&
        (mode === 'page_post' ? (creative?.page_post_id ?? '') !== '' : (creative?.primary_text ?? '') !== '');

    function handlePreview() {
        if (accountId == null) return;
        const creativeSpec: Record<string, unknown> = {
            page_id: creative?.page_id,
            link_data: {
                message: creative?.primary_text ?? name ?? 'Xem thêm',
                call_to_action: creative?.cta ? { type: creative.cta } : undefined,
            },
        };
        previewMutation.mutate(
            { accountId, creative: creativeSpec, formats: ['DESKTOP_FEED_STANDARD', 'MOBILE_FEED_STANDARD'] },
        );
    }

    function handlePublish() {
        if (draftId == null) return;
        publishMutation.mutate(draftId, {
            onSuccess: () => {
                void message.success('Đã gửi xuất bản — quảng cáo sẽ ở trạng thái Tạm dừng.');
                navigate('/marketing');
            },
            onError: (err) => {
                void message.error(errorMessage(err, 'Không xuất bản được. Có thể cần quyền ads_management.'));
            },
        });
    }

    return (
        <Space direction="vertical" size={16} style={{ width: '100%' }}>
            <Descriptions column={1} size="small" bordered>
                <Descriptions.Item label="Tên">{name || '—'}</Descriptions.Item>
                <Descriptions.Item label="Mục tiêu">
                    {objective != null ? OBJECTIVE_LABELS[objective] : '—'}
                </Descriptions.Item>
                <Descriptions.Item label="Ngân sách/ngày">
                    {formatBudget(payload.budget?.daily_major)}
                </Descriptions.Item>
                <Descriptions.Item label="Nội dung">{contentLabel}</Descriptions.Item>
                <Descriptions.Item label="CTA">{ctaLabel}</Descriptions.Item>
            </Descriptions>

            {draftData?.last_error != null && (
                <Alert
                    type="error"
                    showIcon
                    message="Lỗi lần xuất bản trước"
                    description={draftData.last_error}
                />
            )}

            <Space wrap>
                <Button
                    onClick={handlePreview}
                    loading={previewMutation.isPending}
                    disabled={accountId == null}
                >
                    Tạo xem trước
                </Button>
                <Button
                    type="primary"
                    icon={<RocketOutlined />}
                    disabled={!canPublish}
                    loading={publishMutation.isPending}
                    onClick={handlePublish}
                >
                    Xuất bản quảng cáo
                </Button>
            </Space>

            {previewMutation.isError && (
                <Alert
                    type="warning"
                    showIcon
                    message="Không tạo được xem trước — bạn vẫn có thể xuất bản."
                />
            )}

            {previewMutation.isSuccess && previewMutation.data.length > 0 && (
                <Space direction="vertical" size={12} style={{ width: '100%' }}>
                    {previewMutation.data.map((preview) => (
                        <div
                            key={preview.format}
                            style={{
                                border: '1px solid #d9d9d9',
                                borderRadius: 6,
                                padding: 8,
                                overflow: 'hidden',
                            }}
                        >
                            <Text type="secondary" style={{ fontSize: 12, display: 'block', marginBottom: 4 }}>
                                {preview.format}
                            </Text>
                            {/* Graph API returns its own iframe HTML — contained in a bordered div */}
                            <div dangerouslySetInnerHTML={{ __html: preview.body }} />
                        </div>
                    ))}
                </Space>
            )}
        </Space>
    );
}
