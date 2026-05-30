import { useState } from 'react';
import dayjs from 'dayjs';
import { Avatar, Button, Image, Space, Tag, Typography } from 'antd';
import { EyeInvisibleOutlined, FacebookFilled, LinkOutlined, MessageOutlined } from '@ant-design/icons';
import type { Conversation } from '@/lib/messaging';

const { Text, Paragraph } = Typography;

/** "x phút/giờ trước" · "Hôm qua" · "DD/MM" · "DD/MM/YY" — không phụ thuộc plugin relativeTime. */
function fmtPostTime(iso: string | null): string {
    if (!iso) return '';
    const d = dayjs(iso);
    if (!d.isValid()) return '';
    const now = dayjs();
    const diffMin = now.diff(d, 'minute');
    if (diffMin < 1) return 'vừa xong';
    if (diffMin < 60) return `${diffMin} phút trước`;
    const diffHour = now.diff(d, 'hour');
    if (diffHour < 24 && d.isSame(now, 'day')) return `${diffHour} giờ trước`;
    if (d.isSame(now.subtract(1, 'day'), 'day')) return 'Hôm qua';
    if (d.isSame(now, 'year')) return d.format('DD/MM');
    return d.format('DD/MM/YY');
}

// Bài viết dài → thu gọn còn 4 dòng, có nút "Xem thêm".
const COLLAPSE_ROWS = 4;

/**
 * Post card cho hội thoại bình luận Facebook (SPEC-0024): hiển thị bài viết gốc
 * NỔI BẬT như một bài Facebook thật (avatar page + giờ đăng + nội dung đầy đủ +
 * ảnh + link), tách hẳn khỏi luồng bình luận bên dưới — tránh nhầm bài viết với
 * một tin nhắn. Ghim đầu vùng cuộn, cuộn cùng thread.
 */
export function CommentPostCard({ conversation }: { conversation: Conversation }) {
    const c = conversation.comment;
    const [expanded, setExpanded] = useState(false);
    const [imgError, setImgError] = useState(false);
    if (!c) return null;

    const pageName = conversation.channel_account_name ?? 'Trang Facebook';
    const postTime = fmtPostTime(c.post_created_time);
    const hasImage = !!c.post_picture && !imgError;

    return (
        <div
            style={{
                background: '#fff',
                border: '1px solid #E2E8F0',
                borderRadius: 12,
                boxShadow: '0 1px 2px rgba(15,23,42,0.04)',
                overflow: 'hidden',
                marginBottom: 12,
            }}
        >
            {/* Header: page + giờ đăng */}
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '12px 14px 8px' }}>
                <Avatar
                    size={40}
                    src={conversation.channel_account_avatar_url ?? undefined}
                    style={{ background: '#1877F2', flexShrink: 0 }}
                    icon={<FacebookFilled />}
                />
                <div style={{ minWidth: 0, flex: 1 }}>
                    <Text strong ellipsis style={{ display: 'block' }}>{pageName}</Text>
                    <Space size={4} style={{ fontSize: 12, color: '#64748B' }}>
                        <FacebookFilled style={{ color: '#1877F2' }} />
                        <span>Bài viết{postTime ? ` · ${postTime}` : ''}</span>
                    </Space>
                </div>
                {c.hidden && <Tag icon={<EyeInvisibleOutlined />} color="orange" style={{ marginInlineEnd: 0 }}>Đã ẩn</Tag>}
                {c.private_replied && <Tag icon={<MessageOutlined />} color="green" style={{ marginInlineEnd: 0 }}>Đã nhắn riêng</Tag>}
            </div>

            {/* Nội dung bài viết — thu gọn nếu dài */}
            {c.post_message && (
                <div style={{ padding: '0 14px 10px' }}>
                    <Paragraph
                        style={{ marginBottom: expanded ? 4 : 0, whiteSpace: 'pre-wrap' }}
                        ellipsis={expanded ? false : { rows: COLLAPSE_ROWS, expandable: false }}
                    >
                        {c.post_message}
                    </Paragraph>
                    {c.post_message.length > 180 && (
                        <Button type="link" size="small" style={{ padding: 0, height: 'auto' }} onClick={() => setExpanded((v) => !v)}>
                            {expanded ? 'Thu gọn' : 'Xem thêm'}
                        </Button>
                    )}
                </div>
            )}

            {/* Ảnh bài viết (ẩn nếu CDN hết hạn / lỗi tải) */}
            {hasImage && (
                <Image
                    src={c.post_picture!}
                    alt="Ảnh bài viết"
                    width="100%"
                    style={{ maxHeight: 320, objectFit: 'cover', display: 'block' }}
                    onError={() => setImgError(true)}
                    preview={{ mask: 'Xem ảnh' }}
                />
            )}

            {/* Footer: link bài viết */}
            {c.post_permalink && (
                <div style={{ padding: '8px 14px', borderTop: '1px solid #F1F5F9' }}>
                    <a href={c.post_permalink} target="_blank" rel="noreferrer">
                        <Space size={6} style={{ fontSize: 13, fontWeight: 500 }}>
                            <LinkOutlined />
                            Xem bài viết trên Facebook
                        </Space>
                    </a>
                </div>
            )}
        </div>
    );
}
