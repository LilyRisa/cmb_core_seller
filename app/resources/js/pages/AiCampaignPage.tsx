import { useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Alert, Button, Card, DatePicker, Empty, Input, Modal, Radio, Space, Tag, Typography, message } from 'antd';
import {
    LikeOutlined,
    LinkOutlined,
    MessageOutlined,
    RobotOutlined,
    ShareAltOutlined,
} from '@ant-design/icons';
import dayjs, { type Dayjs } from 'dayjs';
import { PagePostPickerModal, type PickResult } from '@/pages/adWizard/PagePostPickerModal';
import { useGenerateAiCampaign, type AdObjective } from '@/lib/adWizard';

const { Title, Paragraph, Text } = Typography;
const { TextArea } = Input;

const OBJECTIVES: { value: AdObjective; label: string }[] = [
    { value: 'messages', label: 'Tin nhắn' },
    { value: 'engagement', label: 'Tương tác' },
    { value: 'traffic', label: 'Truy cập web' },
    { value: 'conversions', label: 'Chuyển đổi (đăng ký form)' },
];

function nf(n: number): string {
    return n.toLocaleString('vi-VN');
}

export function AiCampaignPage() {
    const [params] = useSearchParams();
    const navigate = useNavigate();
    const accountId = params.get('accountId') != null ? Number(params.get('accountId')) : null;

    const [pickerOpen, setPickerOpen] = useState(false);
    const [post, setPost] = useState<PickResult | null>(null);
    const [objective, setObjective] = useState<AdObjective>('messages');
    const [mode, setMode] = useState<'test' | 'scale'>('test');
    const [placement, setPlacement] = useState<'advantage_plus' | 'manual'>('advantage_plus');
    const [start, setStart] = useState<Dayjs | null>(null);
    const [prompt, setPrompt] = useState('');
    const [pixelId, setPixelId] = useState('');
    const [conversionEvent, setConversionEvent] = useState('COMPLETE_REGISTRATION');
    const [linkUrl, setLinkUrl] = useState('');

    const gen = useGenerateAiCampaign();
    const isConversions = objective === 'conversions';
    const postLink = post?.link_url ?? null;
    const needsLinkInput = isConversions && postLink == null;

    function submit() {
        if (accountId == null) return message.error('Thiếu tài khoản quảng cáo.');
        if (post == null) return message.error('Hãy chọn một bài viết.');
        if (isConversions && (pixelId.trim() === '' || conversionEvent.trim() === '')) {
            return message.error('Mục tiêu chuyển đổi cần Pixel ID và sự kiện chuyển đổi.');
        }
        if (needsLinkInput && linkUrl.trim() === '') {
            return message.error('Bài viết chưa có link — hãy nhập link đích (landing) cho mục tiêu chuyển đổi.');
        }

        gen.mutate(
            {
                accountId,
                page_id: post.page_id,
                page_post_id: post.page_post_id,
                objective,
                mode,
                placement_mode: placement,
                prompt: prompt.trim() === '' ? undefined : prompt,
                caption: post.message,
                likes: post.likes,
                comments: post.comments,
                shares: post.shares,
                link_url: postLink ?? (linkUrl.trim() === '' ? null : linkUrl),
                landing_url: (linkUrl.trim() === '' ? postLink : linkUrl),
                cta_type: post.cta_type,
                pixel_id: isConversions ? pixelId : null,
                conversion_event: isConversions ? conversionEvent : null,
                start_time: start != null ? start.toISOString() : null,
            },
            {
                onSuccess: (res) => {
                    Modal.success({
                        title: 'Đã tạo bản nháp chiến dịch bằng AI',
                        content: (
                            <div>
                                <Paragraph type="secondary">AI đề xuất thêm:</Paragraph>
                                {res.recommendations.length > 0 ? (
                                    <ul style={{ paddingLeft: 18, margin: 0 }}>
                                        {res.recommendations.map((r, i) => (
                                            <li key={i}>{r}</li>
                                        ))}
                                    </ul>
                                ) : (
                                    <Text type="secondary">Không có đề xuất bổ sung.</Text>
                                )}
                            </div>
                        ),
                        okText: 'Mở để chỉnh & xuất bản',
                        onOk: () => navigate(`/marketing/ads/${res.draft.id}/edit`),
                    });
                },
                onError: () => message.error('Không tạo được chiến dịch — kiểm tra lại thông tin.'),
            },
        );
    }

    if (accountId == null) {
        return <Empty description="Thiếu tài khoản quảng cáo. Hãy mở từ trang Quảng cáo." style={{ marginTop: 64 }} />;
    }

    return (
        <Card
            title={
                <Space>
                    <RobotOutlined />
                    <span>Tạo chiến dịch bằng AI</span>
                </Space>
            }
            style={{ maxWidth: 760, margin: '16px auto' }}
        >
            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                <div>
                    <Title level={5} style={{ marginTop: 0 }}>1. Chọn bài viết trên Page</Title>
                    {post == null ? (
                        <Button onClick={() => setPickerOpen(true)}>Chọn bài viết…</Button>
                    ) : (
                        <Card size="small">
                            <Space align="start">
                                {post.image_url != null && (
                                    <img src={post.image_url} alt="" style={{ width: 96, height: 96, objectFit: 'cover', borderRadius: 6 }} />
                                )}
                                <div style={{ flex: 1 }}>
                                    <Paragraph ellipsis={{ rows: 2 }} style={{ marginBottom: 4 }}>
                                        {post.message ?? <Text type="secondary">(không có caption)</Text>}
                                    </Paragraph>
                                    <Space size="middle" style={{ color: '#888' }}>
                                        <span><LikeOutlined /> {nf(post.likes)}</span>
                                        <span><MessageOutlined /> {nf(post.comments)}</span>
                                        <span><ShareAltOutlined /> {nf(post.shares)}</span>
                                    </Space>
                                    <div style={{ marginTop: 6 }}>
                                        {postLink != null ? (
                                            <Tag icon={<LinkOutlined />} color="blue">{post.cta_type ?? 'Có link'}</Tag>
                                        ) : (
                                            <Tag>Chưa có nút/link</Tag>
                                        )}
                                        <Button type="link" size="small" onClick={() => setPickerOpen(true)}>Đổi bài</Button>
                                    </div>
                                </div>
                            </Space>
                        </Card>
                    )}
                </div>

                <div>
                    <Title level={5}>2. Mục tiêu</Title>
                    <Radio.Group
                        optionType="button"
                        buttonStyle="solid"
                        options={OBJECTIVES}
                        value={objective}
                        onChange={(e) => setObjective(e.target.value as AdObjective)}
                    />
                </div>

                {isConversions && (
                    <div>
                        <Title level={5}>Cấu hình chuyển đổi</Title>
                        <Space direction="vertical" style={{ width: '100%' }}>
                            <Input addonBefore="Pixel ID" value={pixelId} onChange={(e) => setPixelId(e.target.value)} placeholder="VD 9955679..." />
                            <Input addonBefore="Sự kiện" value={conversionEvent} onChange={(e) => setConversionEvent(e.target.value)} placeholder="COMPLETE_REGISTRATION" />
                            {needsLinkInput && (
                                <Input addonBefore="Link đích" value={linkUrl} onChange={(e) => setLinkUrl(e.target.value)} placeholder="https://… (form đăng ký)" />
                            )}
                        </Space>
                    </div>
                )}

                <div>
                    <Title level={5}>3. Loại chiến dịch</Title>
                    <Radio.Group optionType="button" buttonStyle="solid" value={mode} onChange={(e) => setMode(e.target.value)}>
                        <Radio.Button value="test">Test (đo lường)</Radio.Button>
                        <Radio.Button value="scale">Scale (nhân ngân sách)</Radio.Button>
                    </Radio.Group>
                </div>

                <div>
                    <Title level={5}>4. Vị trí quảng cáo</Title>
                    <Radio.Group optionType="button" buttonStyle="solid" value={placement} onChange={(e) => setPlacement(e.target.value)}>
                        <Radio.Button value="advantage_plus">Advantage+ (tự động)</Radio.Button>
                        <Radio.Button value="manual">Thủ công</Radio.Button>
                    </Radio.Group>
                </div>

                <div>
                    <Title level={5}>5. Lịch bắt đầu</Title>
                    <DatePicker
                        showTime
                        value={start}
                        onChange={setStart}
                        format="DD/MM/YYYY HH:mm"
                        disabledDate={(d) => d.isBefore(dayjs().startOf('day'))}
                        placeholder="Để trống = AI tự chọn giờ an toàn"
                        style={{ width: '100%' }}
                    />
                    <Text type="secondary">Gần nửa đêm dễ tiêu hết ngân sách ngày — để trống để AI đề xuất giờ an toàn.</Text>
                </div>

                <div>
                    <Title level={5}>6. Yêu cầu cho AI</Title>
                    <TextArea
                        rows={3}
                        value={prompt}
                        onChange={(e) => setPrompt(e.target.value)}
                        placeholder="VD: nhắm nữ 25–40 ở Hà Nội & HCM, ngân sách tiết kiệm, ưu tiên tin nhắn hỏi giá…"
                    />
                </div>

                {gen.isError && <Alert type="error" message="Tạo chiến dịch thất bại. Kiểm tra lại thông tin." />}

                <Button type="primary" icon={<RobotOutlined />} loading={gen.isPending} disabled={post == null} onClick={submit} block>
                    AI tạo chiến dịch
                </Button>
            </Space>

            <PagePostPickerModal
                open={pickerOpen}
                accountId={accountId}
                onPick={(p) => setPost(p)}
                onClose={() => setPickerOpen(false)}
            />
        </Card>
    );
}
