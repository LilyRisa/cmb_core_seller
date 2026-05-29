import { useEffect, useState } from 'react';
import { Alert, Checkbox, Empty, Image, Modal, Select, Space, Spin, Typography } from 'antd';
import { useMessagingChannels } from '@/lib/messagingConfig';
import { useChannelPosts } from '@/lib/messagingFlows';

/**
 * Chọn 1 hay NHIỀU bài viết Facebook để áp dụng kịch bản (trigger comment_on_post).
 * Lưới bài đăng (ảnh + trích nội dung + ngày) từ GET channels/{id}/posts.
 */
export function PostPicker({ open, value, onClose, onChange }: {
    open: boolean;
    value: string[];
    onClose: () => void;
    onChange: (ids: string[]) => void;
}) {
    const { data: channels } = useMessagingChannels();
    const fbChannels = (channels ?? []).filter((c) => c.provider === 'facebook_page' && c.status === 'active');
    const [channelId, setChannelId] = useState<number | null>(null);
    const effectiveChannel = channelId ?? fbChannels[0]?.id ?? null;
    const { data, isFetching } = useChannelPosts(open ? effectiveChannel : null);
    const [selected, setSelected] = useState<string[]>(value);

    useEffect(() => { if (open) setSelected(value); }, [open]); // eslint-disable-line react-hooks/exhaustive-deps

    const toggle = (id: string) => setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));

    return (
        <Modal
            open={open}
            onCancel={onClose}
            onOk={() => { onChange(selected); onClose(); }}
            okText={`Chọn ${selected.length} bài`}
            okButtonProps={{ disabled: selected.length === 0 }}
            cancelText="Huỷ"
            title="Chọn bài viết áp dụng kịch bản"
            width={680}
        >
            {fbChannels.length === 0 && <Empty description="Chưa kết nối trang Facebook nào." />}

            {fbChannels.length > 1 && (
                <Select
                    style={{ width: '100%', marginBottom: 12 }}
                    value={effectiveChannel ?? undefined}
                    onChange={setChannelId}
                    options={fbChannels.map((c) => ({ value: c.id, label: c.name }))}
                />
            )}

            {isFetching && <div style={{ textAlign: 'center', padding: 24 }}><Spin /></div>}

            {!isFetching && data && data.items.length === 0 && <Empty description="Trang chưa có bài đăng nào." />}

            {!isFetching && data && data.items.length > 0 && (
                <Space direction="vertical" style={{ width: '100%', maxHeight: 440, overflowY: 'auto' }} size={8}>
                    {data.items.map((p) => (
                        <div
                            key={p.id}
                            onClick={() => toggle(p.id)}
                            style={{ display: 'flex', gap: 10, alignItems: 'flex-start', border: '1px solid', borderColor: selected.includes(p.id) ? '#1677ff' : '#f0f0f0', borderRadius: 8, padding: 8, cursor: 'pointer', background: selected.includes(p.id) ? '#e6f4ff' : '#fff' }}
                        >
                            <Checkbox checked={selected.includes(p.id)} />
                            {p.image_url && <Image src={p.image_url} width={56} height={56} style={{ objectFit: 'cover', borderRadius: 4 }} preview={false} />}
                            <div style={{ flex: 1, minWidth: 0 }}>
                                <Typography.Paragraph ellipsis={{ rows: 2 }} style={{ marginBottom: 2 }}>{p.message || <Typography.Text type="secondary">(bài viết không có chữ)</Typography.Text>}</Typography.Paragraph>
                                <Typography.Text type="secondary" style={{ fontSize: 12 }}>{p.created_time ? new Date(p.created_time).toLocaleDateString('vi-VN') : ''}</Typography.Text>
                            </div>
                        </div>
                    ))}
                    {data.has_more && <Alert type="info" showIcon message="Chỉ hiển thị các bài mới nhất. Nếu cần bài cũ hơn, hãy đăng nhập Facebook và dùng nội dung gần đây." />}
                </Space>
            )}
        </Modal>
    );
}
