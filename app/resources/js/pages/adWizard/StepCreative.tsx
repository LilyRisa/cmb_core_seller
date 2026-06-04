import { useState, useEffect } from 'react';
import { Alert, Button, Card, Form, Input, Space, Typography } from 'antd';
import type { SegmentedProps } from 'antd';
import { Segmented } from 'antd';
import { PictureOutlined, SwapOutlined } from '@ant-design/icons';
import { useDraftStore } from '@/lib/adWizard/draftStore';
import type { AdObjective } from '@/lib/adWizard';
import { PagePostPickerModal } from '@/pages/adWizard/PagePostPickerModal';

const { Text, Paragraph } = Typography;

type ContentMode = 'page_post' | 'new';

const MODE_OPTIONS: SegmentedProps['options'] = [
    { label: 'Dùng bài viết có sẵn', value: 'page_post' },
    { label: 'Tạo nội dung mới', value: 'new' },
];

interface CtaOption {
    label: string;
    value: string;
}

const CTA_BY_OBJECTIVE: Record<AdObjective, CtaOption[]> = {
    messages: [{ label: 'Gửi tin nhắn', value: 'MESSAGE_PAGE' }],
    traffic: [
        { label: 'Tìm hiểu thêm', value: 'LEARN_MORE' },
        { label: 'Mua ngay', value: 'SHOP_NOW' },
    ],
    engagement: [{ label: 'Tìm hiểu thêm', value: 'LEARN_MORE' }],
};

// Local UI state for the picked post (image + message for preview, not stored in draft payload)
interface PickedPostSummary {
    image_url: string | null;
    message: string | null;
}

export function StepCreative() {
    const objective = useDraftStore((s) => s.objective);
    const payload = useDraftStore((s) => s.payload);
    const accountId = useDraftStore((s) => s.accountId);
    const patchCreative = useDraftStore((s) => s.patchCreative);

    const [modalOpen, setModalOpen] = useState(false);
    const [pickedSummary, setPickedSummary] = useState<PickedPostSummary | null>(null);

    const creative = payload.creative;
    const mode: ContentMode = (creative?.mode as ContentMode | undefined) ?? 'page_post';

    // When objective changes, reset CTA to the first valid option for the new objective
    useEffect(() => {
        if (objective == null) return;
        const allowed = CTA_BY_OBJECTIVE[objective];
        if (allowed.length === 0) return;
        const currentCta = creative?.cta;
        const isValid = currentCta != null && allowed.some((o) => o.value === currentCta);
        if (!isValid) {
            patchCreative({ cta: allowed[0].value });
        }
    // Only run when objective changes — eslint-disable is intentional
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [objective]);

    const ctaOptions: SegmentedProps['options'] =
        objective != null
            ? CTA_BY_OBJECTIVE[objective].map((o) => ({ label: o.label, value: o.value }))
            : [];

    const currentCta = creative?.cta ?? (ctaOptions[0] as { value: string } | undefined)?.value;

    function handleModeChange(v: string | number) {
        patchCreative({ mode: v as ContentMode });
    }

    function handleCtaChange(v: string | number) {
        patchCreative({ cta: v as string });
    }

    function handlePick(p: {
        page_id: string;
        page_post_id: string;
        image_url: string | null;
        message: string | null;
    }) {
        patchCreative({ page_id: p.page_id, page_post_id: p.page_post_id });
        setPickedSummary({ image_url: p.image_url, message: p.message });
    }

    return (
        <Form layout="vertical">
            {/* Mode segmented */}
            <Form.Item label="Nội dung">
                <Segmented
                    options={MODE_OPTIONS}
                    value={mode}
                    onChange={handleModeChange}
                />
            </Form.Item>

            {/* page_post mode */}
            {mode === 'page_post' && (
                <>
                    <Form.Item>
                        <Button
                            icon={<PictureOutlined />}
                            onClick={() => setModalOpen(true)}
                        >
                            Chọn bài viết
                        </Button>
                    </Form.Item>

                    {creative?.page_post_id != null && (
                        <Form.Item label="Bài viết đã chọn">
                            <Card
                                size="small"
                                style={{ maxWidth: 400 }}
                                styles={{ body: { display: 'flex', gap: 12, alignItems: 'flex-start' } }}
                            >
                                {pickedSummary?.image_url != null && (
                                    <img
                                        src={pickedSummary.image_url}
                                        alt=""
                                        style={{
                                            width: 72,
                                            height: 72,
                                            objectFit: 'cover',
                                            borderRadius: 4,
                                            flexShrink: 0,
                                        }}
                                    />
                                )}
                                <Space direction="vertical" size={4} style={{ flex: 1, minWidth: 0 }}>
                                    {pickedSummary?.message != null ? (
                                        <Paragraph
                                            ellipsis={{ rows: 2 }}
                                            style={{ marginBottom: 0, fontSize: 13 }}
                                        >
                                            {pickedSummary.message}
                                        </Paragraph>
                                    ) : (
                                        <Text type="secondary" style={{ fontSize: 12 }}>
                                            Post ID: {creative.page_post_id}
                                        </Text>
                                    )}
                                    <Button
                                        size="small"
                                        icon={<SwapOutlined />}
                                        onClick={() => setModalOpen(true)}
                                    >
                                        Đổi bài
                                    </Button>
                                </Space>
                            </Card>
                        </Form.Item>
                    )}
                </>
            )}

            {/* new mode */}
            {mode === 'new' && (
                <>
                    <Form.Item label="Nội dung chính">
                        <Input.TextArea
                            rows={3}
                            value={creative?.primary_text ?? ''}
                            onChange={(e) => patchCreative({ primary_text: e.target.value })}
                            placeholder="Mô tả sản phẩm / dịch vụ..."
                            style={{ maxWidth: 520 }}
                        />
                    </Form.Item>
                    <Form.Item label="Tiêu đề">
                        <Input
                            value={creative?.headline ?? ''}
                            onChange={(e) => patchCreative({ headline: e.target.value })}
                            placeholder="Tiêu đề ngắn gọn"
                            style={{ maxWidth: 400 }}
                        />
                    </Form.Item>
                    <Form.Item label="Đường dẫn (URL)">
                        <Input
                            value={creative?.link_url ?? ''}
                            onChange={(e) => patchCreative({ link_url: e.target.value })}
                            placeholder="https://example.com"
                            style={{ maxWidth: 400 }}
                        />
                    </Form.Item>
                    <Alert
                        type="warning"
                        showIcon
                        message="Tải ảnh/video sẽ có ở bản cập nhật sau — hiện hãy dùng bài viết có sẵn."
                        style={{ maxWidth: 520 }}
                    />
                </>
            )}

            {/* CTA */}
            {ctaOptions.length > 0 && (
                <Form.Item label="Nút kêu gọi hành động (CTA)" style={{ marginTop: 16 }}>
                    <Segmented
                        options={ctaOptions}
                        value={currentCta}
                        onChange={handleCtaChange}
                    />
                </Form.Item>
            )}

            <PagePostPickerModal
                open={modalOpen}
                accountId={accountId}
                onPick={handlePick}
                onClose={() => setModalOpen(false)}
            />
        </Form>
    );
}
