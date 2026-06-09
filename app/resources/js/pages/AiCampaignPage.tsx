import { useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Alert, Button, Card, DatePicker, Empty, Input, Modal, Radio, Select, Space, Tag, Typography, message } from 'antd';
import {
    LikeOutlined,
    LinkOutlined,
    MessageOutlined,
    RobotOutlined,
    ShareAltOutlined,
} from '@ant-design/icons';
import dayjs, { type Dayjs } from 'dayjs';
import { PagePostPickerModal, type PickResult } from '@/pages/adWizard/PagePostPickerModal';
import { useAdPixels, useGenerateAiCampaign, type AdObjective } from '@/lib/adWizard';
import { CONVERSION_EVENTS, ctaLabel } from '@/lib/adLabels';

const { Title, Paragraph, Text } = Typography;
const { TextArea } = Input;

const OBJECTIVES: { value: AdObjective; label: string }[] = [
    { value: 'messages', label: 'Tin nhắn' },
    { value: 'engagement', label: 'Tương tác' },
    { value: 'traffic', label: 'Truy cập web' },
    { value: 'conversions', label: 'Chuyển đổi' },
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
    const [optimization, setOptimization] = useState<'advantage_plus' | 'manual'>('advantage_plus');
    const [start, setStart] = useState<Dayjs | null>(null);
    const [prompt, setPrompt] = useState('');
    const [pixelId, setPixelId] = useState<string | undefined>(undefined);
    const [conversionEvent, setConversionEvent] = useState('COMPLETE_REGISTRATION');
    const [linkUrl, setLinkUrl] = useState('');

    const gen = useGenerateAiCampaign();
    const isConversions = objective === 'conversions';
    const pixels = useAdPixels(accountId, isConversions);
    const postLink = post?.link_url ?? null;
    const needsLinkInput = isConversions && postLink == null;

    function submit() {
        if (accountId == null) return message.error('Thiếu tài khoản quảng cáo.');
        if (post == null) return message.error('Hãy chọn một bài viết.');
        if (isConversions && (pixelId == null || pixelId === '' || conversionEvent === '')) {
            return message.error('Mục tiêu chuyển đổi cần chọn Pixel và sự kiện chuyển đổi.');
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
                optimization_mode: optimization,
                prompt: prompt.trim() === '' ? undefined : prompt,
                caption: post.message,
                likes: post.likes,
                comments: post.comments,
                shares: post.shares,
                link_url: postLink ?? (linkUrl.trim() === '' ? null : linkUrl),
                landing_url: (linkUrl.trim() === '' ? postLink : linkUrl),
                cta_type: post.cta_type,
                pixel_id: isConversions ? (pixelId ?? null) : null,
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
                                        <Space size={6} wrap>
                                            {post.cta_type != null && <Tag color="blue">{ctaLabel(post.cta_type)}</Tag>}
                                            {postLink != null ? (
                                                <a href={postLink} target="_blank" rel="noreferrer">
                                                    <LinkOutlined /> {postLink}
                                                </a>
                                            ) : (
                                                <Tag>Chưa có nút/link</Tag>
                                            )}
                                            <Button type="link" size="small" onClick={() => setPickerOpen(true)}>Đổi bài</Button>
                                        </Space>
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
                            <Select
                                style={{ width: '100%' }}
                                placeholder="Chọn Pixel chuyển đổi"
                                loading={pixels.isLoading}
                                value={pixelId}
                                onChange={setPixelId}
                                options={(pixels.data ?? []).map((p) => ({ value: p.id, label: `${p.name} (#${p.id})` }))}
                                notFoundContent={pixels.isLoading ? 'Đang tải…' : 'Tài khoản chưa có Pixel'}
                            />
                            <Select
                                style={{ width: '100%' }}
                                placeholder="Sự kiện chuyển đổi"
                                value={conversionEvent}
                                onChange={setConversionEvent}
                                options={CONVERSION_EVENTS}
                            />
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
                    <Title level={5}>4. Cách tối ưu</Title>
                    <Radio.Group optionType="button" buttonStyle="solid" value={optimization} onChange={(e) => setOptimization(e.target.value)}>
                        <Radio.Button value="advantage_plus">Advantage+ (Meta tự tối ưu toàn bộ)</Radio.Button>
                        <Radio.Button value="manual">Thủ công</Radio.Button>
                    </Radio.Group>
                    <div><Text type="secondary">Advantage+ = Meta tự chọn vị trí, mở rộng đối tượng & tăng cường sáng tạo.</Text></div>
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
