import { useState, useEffect } from 'react';
import { Avatar, Card, Col, Empty, Modal, Row, Select, Spin, Space, Typography } from 'antd';
import {
    FileTextOutlined,
    LikeOutlined,
    MessageOutlined,
    PlayCircleOutlined,
    ShareAltOutlined,
} from '@ant-design/icons';
import { useAdPages, usePagePosts, type AdPagePost } from '@/lib/adWizard';

const { Text, Paragraph } = Typography;

export interface PickResult {
    page_id: string;
    page_post_id: string;
    image_url: string | null;
    message: string | null;
    link_url: string | null;
    cta_type: string | null;
    likes: number;
    comments: number;
    shares: number;
}

interface Props {
    open: boolean;
    accountId: number | null;
    onPick: (p: PickResult) => void;
    onClose: () => void;
}

function formatNumber(n: number): string {
    return n.toLocaleString('vi-VN');
}

function PostMediaCover({ post }: { post: AdPagePost }) {
    if (post.image_url != null) {
        return (
            <img
                src={post.image_url}
                alt={post.message ?? ''}
                style={{ height: 120, objectFit: 'cover', width: '100%', display: 'block' }}
            />
        );
    }
    if (post.media_type === 'video') {
        return (
            <div
                style={{
                    height: 120,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    background: '#1a1a2e',
                    width: '100%',
                }}
            >
                <PlayCircleOutlined style={{ fontSize: 36, color: '#fff' }} />
            </div>
        );
    }
    return (
        <div
            style={{
                height: 120,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                background: '#f0f2f5',
                width: '100%',
            }}
        >
            <FileTextOutlined style={{ fontSize: 36, color: '#8c8c8c' }} />
        </div>
    );
}

function EngagementRow({ post }: { post: AdPagePost }) {
    return (
        <Space
            size={12}
            style={{
                marginTop: 8,
                padding: '6px 8px',
                background: '#f5f5f5',
                borderRadius: 6,
                width: '100%',
                justifyContent: 'space-between',
                display: 'flex',
            }}
        >
            <Space size={4}>
                <LikeOutlined style={{ color: '#1677ff', fontSize: 14 }} />
                <Text strong style={{ fontSize: 13, color: '#1677ff' }}>
                    {formatNumber(post.likes)}
                </Text>
            </Space>
            <Space size={4}>
                <MessageOutlined style={{ color: '#52c41a', fontSize: 14 }} />
                <Text strong style={{ fontSize: 13, color: '#52c41a' }}>
                    {formatNumber(post.comments)}
                </Text>
            </Space>
            <Space size={4}>
                <ShareAltOutlined style={{ color: '#fa8c16', fontSize: 14 }} />
                <Text strong style={{ fontSize: 13, color: '#fa8c16' }}>
                    {formatNumber(post.shares)}
                </Text>
            </Space>
        </Space>
    );
}

export function PagePostPickerModal({ open, accountId, onPick, onClose }: Props) {
    const { data: pages, isLoading: pagesLoading } = useAdPages(accountId);
    const [pageId, setPageId] = useState<string | null>(null);

    // Default to first page once pages load
    useEffect(() => {
        if (pages != null && pages.length > 0 && pageId == null) {
            setPageId(pages[0].id);
        }
    }, [pages, pageId]);

    const { data: posts, isLoading: postsLoading } = usePagePosts(accountId, pageId);

    const pageOptions =
        pages?.map((p) => ({
            value: p.id,
            label: (
                <Space size={8}>
                    <Avatar size={20} src={p.picture_url ?? undefined}>{p.name.charAt(0)}</Avatar>
                    <span>{p.name}</span>
                    <Text type="secondary" style={{ fontSize: 12 }}>#{p.id}</Text>
                </Space>
            ),
        })) ?? [];

    function handlePick(post: AdPagePost) {
        if (pageId == null) return;
        onPick({
            page_id: pageId,
            page_post_id: post.id,
            image_url: post.image_url,
            message: post.message,
            link_url: post.link_url ?? null,
            cta_type: post.cta_type ?? null,
            likes: post.likes,
            comments: post.comments,
            shares: post.shares,
        });
        onClose();
    }

    return (
        <Modal
            open={open}
            onCancel={onClose}
            title="Chọn bài viết của Trang"
            footer={null}
            width={720}
            styles={{ body: { maxHeight: '70vh', overflowY: 'auto' } }}
        >
            <Space direction="vertical" size={16} style={{ width: '100%' }}>
                {/* Page chooser */}
                <Select
                    loading={pagesLoading}
                    options={pageOptions}
                    value={pageId}
                    onChange={(v) => setPageId(v)}
                    placeholder="Chọn Trang Facebook"
                    style={{ width: '100%', maxWidth: 360 }}
                />

                {/* Posts grid */}
                {postsLoading ? (
                    <div style={{ textAlign: 'center', padding: 32 }}>
                        <Spin size="large" />
                    </div>
                ) : posts == null || posts.length === 0 ? (
                    <Empty description="Trang chưa có bài viết phù hợp" />
                ) : (
                    <Row gutter={[12, 12]}>
                        {posts.map((post) => (
                            <Col span={8} key={post.id}>
                                <Card
                                    hoverable
                                    size="small"
                                    cover={<PostMediaCover post={post} />}
                                    onClick={() => handlePick(post)}
                                    styles={{
                                        body: { padding: '8px 10px' },
                                        cover: { overflow: 'hidden' },
                                    }}
                                >
                                    <Paragraph
                                        ellipsis={{ rows: 2 }}
                                        style={{ marginBottom: 4, fontSize: 13 }}
                                    >
                                        {post.message ?? <Text type="secondary">(Không có nội dung)</Text>}
                                    </Paragraph>
                                    <Text type="secondary" style={{ fontSize: 11 }}>
                                        {new Date(post.created_time).toLocaleDateString('vi-VN')}
                                    </Text>
                                    <EngagementRow post={post} />
                                </Card>
                            </Col>
                        ))}
                    </Row>
                )}
            </Space>
        </Modal>
    );
}
