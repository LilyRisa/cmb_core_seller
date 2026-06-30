import { useEffect, useMemo, useState } from 'react';
import { Alert, Avatar, Badge, Card, Col, Empty, Modal, Row, Select, Space, Spin, Typography } from 'antd';
import { CheckCircleFilled, CommentOutlined, FacebookFilled, LikeOutlined, ShareAltOutlined } from '@ant-design/icons';
import { type MessagingChannel, useMessagingChannels } from '@/lib/messagingConfig';
import { type FbPost, useChannelPosts } from '@/lib/messagingFlows';

const pageName = (c: MessagingChannel) => c.name || c.shop_name || c.external_shop_id;
const fmtCount = (n?: number) => (n == null ? 0 : n >= 1000 ? `${(n / 1000).toFixed(1)}k` : n);

/**
 * Chọn 1 hay NHIỀU bài viết Facebook để áp dụng kịch bản (trigger comment_on_post / giới hạn inbox).
 * Lưới bài đăng (ảnh + nội dung + like/comment/share) từ GET channels/{id}/posts.
 *
 * Lọc theo PHẠM VI TRANG của flow: chỉ hiện bài của các trang flow áp dụng
 * (appliesAllPages ⇒ mọi trang FB; ngược lại chỉ pageIds). Khi flow áp NHIỀU trang,
 * người dùng đổi trang ở ô chọn trang (có avatar) để xem bài từng trang.
 */
export function PostPicker({ open, value, onClose, onChange, onChangePosts, appliesAllPages = true, pageIds = [] }: {
    open: boolean;
    value: string[];
    onClose: () => void;
    onChange: (ids: string[]) => void;
    /** Tùy chọn: trả kèm nhãn bài (cho node "Rẽ theo bài viết"). */
    onChangePosts?: (posts: { id: string; label: string }[]) => void;
    appliesAllPages?: boolean;
    pageIds?: number[];
}) {
    const { data: channels } = useMessagingChannels('facebook_page');
    const fbChannels = useMemo(
        () => (channels ?? []).filter((c) => c.status === 'active' && (appliesAllPages || pageIds.includes(c.id))),
        [channels, appliesAllPages, pageIds],
    );
    const [channelId, setChannelId] = useState<number | null>(null);
    const effectiveChannel = channelId ?? fbChannels[0]?.id ?? null;
    const { data, isFetching } = useChannelPosts(open ? effectiveChannel : null);
    const [selected, setSelected] = useState<string[]>(value);
    // Tích luỹ nhãn bài qua các trang đã xem (để giữ nhãn khi đổi trang).
    const [labelById, setLabelById] = useState<Record<string, string>>({});

    useEffect(() => { if (open) setSelected(value); }, [open]); // eslint-disable-line react-hooks/exhaustive-deps
    useEffect(() => {
        if (!data) return;
        setLabelById((m) => {
            const next = { ...m };
            for (const p of data.items) next[p.id] = (p.message?.trim().slice(0, 40)) || 'Bài viết';
            return next;
        });
    }, [data]);

    const toggle = (id: string) => setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));

    const confirm = () => {
        onChange(selected);
        onChangePosts?.(selected.map((id) => ({ id, label: labelById[id] ?? id })));
        onClose();
    };

    return (
        <Modal
            open={open}
            onCancel={onClose}
            onOk={confirm}
            okText={`Chọn ${selected.length} bài`}
            okButtonProps={{ disabled: selected.length === 0 }}
            cancelText="Huỷ"
            title="Chọn bài viết áp dụng kịch bản"
            width={820}
        >
            {fbChannels.length === 0 && (
                <Empty description={appliesAllPages ? 'Chưa kết nối trang Facebook nào.' : 'Hãy chọn trang áp dụng cho kịch bản trước.'} />
            )}

            {fbChannels.length > 1 && (
                <Select
                    style={{ width: '100%', marginBottom: 12 }}
                    value={effectiveChannel ?? undefined}
                    onChange={setChannelId}
                    optionLabelProp="label"
                    options={fbChannels.map((c) => ({ value: c.id, label: pageName(c), page: c }))}
                    optionRender={(opt) => {
                        const c = (opt.data as { page: MessagingChannel }).page;
                        return (
                            <Space>
                                <Avatar size={20} src={c.avatar_url || undefined} icon={<FacebookFilled />}
                                    style={{ background: c.avatar_url ? undefined : '#1877F2' }} />
                                <span>{pageName(c)}</span>
                            </Space>
                        );
                    }}
                />
            )}

            {isFetching && <div style={{ textAlign: 'center', padding: 24 }}><Spin /></div>}

            {!isFetching && data && data.items.length === 0 && <Empty description="Trang chưa có bài đăng nào." />}

            {!isFetching && data && data.items.length > 0 && (
                <div style={{ maxHeight: 480, overflowY: 'auto' }}>
                    <Row gutter={[12, 12]}>
                        {data.items.map((p) => (
                            <Col key={p.id} xs={24} sm={12} md={8}>
                                <PostCard post={p} selected={selected.includes(p.id)} onToggle={() => toggle(p.id)} />
                            </Col>
                        ))}
                    </Row>
                    {data.has_more && (
                        <Alert style={{ marginTop: 12 }} type="info" showIcon
                            message="Chỉ hiển thị các bài mới nhất. Nếu cần bài cũ hơn, hãy đăng bài gần đây hơn." />
                    )}
                </div>
            )}
        </Modal>
    );
}

function PostCard({ post, selected, onToggle }: { post: FbPost; selected: boolean; onToggle: () => void }) {
    return (
        <Badge.Ribbon text={<CheckCircleFilled />} color="#1677ff" style={{ display: selected ? undefined : 'none' }}>
            <Card
                hoverable
                onClick={onToggle}
                styles={{ body: { padding: 10 } }}
                style={{ borderColor: selected ? '#1677ff' : undefined, borderWidth: selected ? 2 : 1, height: '100%' }}
                cover={
                    post.image_url
                        ? <img alt="" src={post.image_url} style={{ height: 120, objectFit: 'cover' }} />
                        : <div style={{ height: 120, background: '#f5f5f5', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#bfbfbf' }}><FacebookFilled style={{ fontSize: 28 }} /></div>
                }
            >
                <Typography.Paragraph ellipsis={{ rows: 2 }} style={{ marginBottom: 6, minHeight: 40, fontSize: 13 }}>
                    {post.message || <Typography.Text type="secondary">(bài viết không có chữ)</Typography.Text>}
                </Typography.Paragraph>
                <Space size={14} style={{ color: '#8c8c8c', fontSize: 12 }}>
                    <span><LikeOutlined /> {fmtCount(post.likes)}</span>
                    <span><CommentOutlined /> {fmtCount(post.comments)}</span>
                    <span><ShareAltOutlined /> {fmtCount(post.shares)}</span>
                </Space>
                <div style={{ marginTop: 4 }}>
                    <Typography.Text type="secondary" style={{ fontSize: 11 }}>
                        {post.created_time ? new Date(post.created_time).toLocaleDateString('vi-VN') : ''}
                    </Typography.Text>
                </div>
            </Card>
        </Badge.Ribbon>
    );
}
