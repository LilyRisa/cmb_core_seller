import { useEffect, useMemo, useRef, useState } from 'react';
import { App, Avatar, Badge, Button, Dropdown, Empty, Image, Input, List, Popover, Segmented, Space, Spin, Tag, Typography, Upload } from 'antd';
import { FileOutlined, MoreOutlined, PaperClipOutlined, PictureOutlined, RobotOutlined, SendOutlined, ShopOutlined, SmileOutlined, VideoCameraOutlined } from '@ant-design/icons';
import Picker from '@emoji-mart/react';
import emojiData from '@emoji-mart/data';
import { errorMessage } from '@/lib/api';
import {
    type Conversation,
    type ConversationStatus,
    INBOX_GROUP_PROVIDERS,
    type InboxGroup,
    providerLabel,
    useAiSuggestion,
    useBlockConversation,
    useConversations,
    useConversationThread,
    useMarkRead,
    useMarkUnread,
    useSendMedia,
    useSendText,
    useUnblockConversation,
} from '@/lib/messaging';
import { MessagingNav } from '@/components/MessagingNav';

const { Text } = Typography;

/**
 * Hộp thư hợp nhất 3 cột (SPEC-0024 §3.1): danh sách hội thoại | luồng tin +
 * ô soạn | panel thông tin. Realtime = polling fallback (Reverb là follow-up).
 * MVP: text + AI gợi ý. Media/template/auto-rule UI ở các vòng sau.
 */
export function MessagingPage() {
    const { message } = App.useApp();
    const [group, setGroup] = useState<InboxGroup>('all');   // tách: sàn TMĐT vs Facebook
    const [status, setStatus] = useState<ConversationStatus | 'all'>('open');
    const [activeId, setActiveId] = useState<number | null>(null);
    const [draft, setDraft] = useState('');
    const [unreadOnly, setUnreadOnly] = useState(false);
    const [view, setView] = useState<'inbox' | 'blocked'>('inbox');
    const [emojiOpen, setEmojiOpen] = useState(false);

    const list = useConversations({
        status: status === 'all' ? undefined : status,
        provider: INBOX_GROUP_PROVIDERS[group],
        unread: unreadOnly || undefined,
        blocked: view === 'blocked' || undefined,
    });
    const thread = useConversationThread(activeId);
    const sendText = useSendText(activeId);
    const sendMedia = useSendMedia(activeId);
    const markRead = useMarkRead();
    const markUnread = useMarkUnread();
    const block = useBlockConversation();
    const unblock = useUnblockConversation();
    const aiSuggest = useAiSuggestion(activeId);

    const conversations = list.data?.data ?? [];
    const active = useMemo(() => conversations.find((c) => c.id === activeId) ?? thread.data?.conversation, [conversations, activeId, thread.data]);

    const bottomRef = useRef<HTMLDivElement>(null);
    useEffect(() => { bottomRef.current?.scrollIntoView({ behavior: 'smooth' }); }, [thread.data?.messages.length]);

    // auto mark-read khi mở hội thoại có tin chưa đọc
    useEffect(() => {
        if (activeId && active && active.unread_count > 0) {
            markRead.mutate(activeId);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [activeId]);

    const handleSend = () => {
        const body = draft.trim();
        if (!body || !activeId) return;
        sendText.mutate(body, {
            onSuccess: () => setDraft(''),
            onError: (e) => message.error(errorMessage(e, 'Không gửi được tin.')),
        });
    };

    const handleAi = () => {
        if (!activeId) return;
        aiSuggest.mutate(undefined, {
            onSuccess: (d) => setDraft(d.draft_text),
            onError: (e) => message.error(errorMessage(e, 'AI không phản hồi.')),
        });
    };

    const handleMedia = (file: File, kind: 'image' | 'video' | 'file') => {
        if (!activeId) return false;
        sendMedia.mutate({ file, kind }, { onError: (e) => message.error(errorMessage(e, 'Không gửi được tệp.')) });
        return false; // chặn antd Upload tự upload
    };

    const handleEmoji = (emoji: { native?: string }) => {
        if (emoji.native) setDraft((d) => d + emoji.native);
    };

    const convMenuItems = (c: Conversation) => [
        { key: 'unread', label: 'Đánh dấu chưa đọc' },
        c.blocked_at
            ? { key: 'unblock', label: 'Bỏ chặn người dùng' }
            : { key: 'block', label: 'Chặn người dùng', danger: true },
    ];

    const onConvAction = (key: string, c: Conversation) => {
        if (key === 'unread') {
            markUnread.mutate(c.id, { onSuccess: () => message.success('Đã đánh dấu chưa đọc.') });
        } else if (key === 'block') {
            block.mutate(c.id, {
                onSuccess: () => { message.success('Đã chặn người dùng.'); if (activeId === c.id) setActiveId(null); },
                onError: (e) => message.error(errorMessage(e)),
            });
        } else if (key === 'unblock') {
            unblock.mutate(c.id, { onSuccess: () => message.success('Đã bỏ chặn.') });
        }
    };

    return (
        <div>
            <MessagingNav />
            <div style={{ display: 'flex', height: 'calc(100vh - 150px)', gap: 12 }}>
            {/* Cột trái — danh sách hội thoại */}
            <div style={{ width: 320, background: '#fff', borderRadius: 12, display: 'flex', flexDirection: 'column', overflow: 'hidden' }}>
                <div style={{ padding: 12, borderBottom: '1px solid #F1F5F9', display: 'flex', flexDirection: 'column', gap: 8 }}>
                    {/* Toggle inbox / đã chặn */}
                    <Segmented
                        block
                        value={view}
                        onChange={(v) => { setView(v as 'inbox' | 'blocked'); setActiveId(null); }}
                        options={[{ label: 'Hộp thư', value: 'inbox' }, { label: 'Đã chặn', value: 'blocked' }]}
                    />
                    {/* Tách nguồn: Tin nhắn sàn (Shopee/TikTok/Lazada) vs Facebook Page */}
                    <Segmented
                        block
                        value={group}
                        onChange={(v) => { setGroup(v as InboxGroup); setActiveId(null); }}
                        options={[
                            { label: 'Tất cả', value: 'all' },
                            { label: 'Tin nhắn sàn', value: 'marketplace' },
                            { label: 'Facebook', value: 'facebook' },
                        ]}
                    />
                    <Segmented
                        block
                        value={status}
                        onChange={(v) => setStatus(v as ConversationStatus | 'all')}
                        options={[
                            { label: 'Đang mở', value: 'open' },
                            { label: 'Đã xong', value: 'resolved' },
                            { label: 'Tất cả', value: 'all' },
                        ]}
                    />
                    <Segmented
                        block
                        value={unreadOnly ? 'unread' : 'all_msgs'}
                        onChange={(v) => setUnreadOnly(v === 'unread')}
                        options={[{ label: 'Tất cả tin', value: 'all_msgs' }, { label: 'Chưa đọc', value: 'unread' }]}
                    />
                </div>
                <div style={{ flex: 1, overflowY: 'auto' }}>
                    {list.isLoading ? (
                        <div style={{ padding: 24, textAlign: 'center' }}><Spin /></div>
                    ) : conversations.length === 0 ? (
                        <Empty description="Chưa có hội thoại" style={{ marginTop: 48 }} />
                    ) : (
                        <List
                            dataSource={conversations}
                            renderItem={(c: Conversation) => (
                                <List.Item
                                    onClick={() => setActiveId(c.id)}
                                    style={{ cursor: 'pointer', padding: '10px 14px', background: c.id === activeId ? '#EFF6FF' : undefined }}
                                >
                                    <List.Item.Meta
                                        avatar={<Avatar src={c.buyer_avatar_url ?? undefined}>{(c.buyer_name ?? c.buyer_external_id ?? '?').slice(0, 1).toUpperCase()}</Avatar>}
                                        title={(
                                            <Space size={6} style={{ width: '100%', justifyContent: 'space-between' }}>
                                                <Space size={6}>
                                                    <Badge count={c.unread_count} size="small" />
                                                    <Text strong ellipsis style={{ maxWidth: 120 }}>{c.buyer_name ?? c.buyer_external_id}</Text>
                                                    <Tag color="blue" style={{ marginInlineEnd: 0 }}>{providerLabel(c.provider)}</Tag>
                                                </Space>
                                                <Dropdown trigger={['click']} menu={{ items: convMenuItems(c), onClick: ({ key, domEvent }) => { domEvent.stopPropagation(); onConvAction(key, c); } }}>
                                                    <Button type="text" size="small" icon={<MoreOutlined />} onClick={(e) => e.stopPropagation()} />
                                                </Dropdown>
                                            </Space>
                                        )}
                                        description={(
                                            <Space direction="vertical" size={0} style={{ width: '100%' }}>
                                                {c.channel_account_name && (
                                                    <Text type="secondary" style={{ fontSize: 11 }} ellipsis><ShopOutlined /> {c.channel_account_name}</Text>
                                                )}
                                                <Text type="secondary" ellipsis style={{ fontSize: 12 }}>{c.last_message_preview ?? '—'}</Text>
                                            </Space>
                                        )}
                                    />
                                </List.Item>
                            )}
                        />
                    )}
                </div>
            </div>

            {/* Cột giữa — luồng tin + ô soạn */}
            <div style={{ flex: 1, background: '#fff', borderRadius: 12, display: 'flex', flexDirection: 'column', overflow: 'hidden' }}>
                {!activeId ? (
                    <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                        <Empty description="Chọn một hội thoại để xem" />
                    </div>
                ) : (
                    <>
                        <div style={{ padding: 12, borderBottom: '1px solid #F1F5F9', display: 'flex', alignItems: 'center', gap: 8 }}>
                            <Avatar src={active?.buyer_avatar_url ?? undefined} size={32}>{(active?.buyer_name ?? active?.buyer_external_id ?? '?').slice(0, 1).toUpperCase()}</Avatar>
                            <div style={{ flex: 1 }}>
                                <Text strong>{active?.buyer_name ?? active?.buyer_external_id}</Text>{' '}
                                <Tag color="blue">{providerLabel(active?.provider ?? '')}</Tag>
                                {active?.channel_account_name && (
                                    <Text type="secondary" style={{ marginInlineStart: 4 }}>· {active.channel_account_name}</Text>
                                )}
                            </div>
                        </div>
                        <div style={{ flex: 1, overflowY: 'auto', padding: 16, background: '#F8FAFC' }}>
                            {thread.isLoading ? (
                                <div style={{ textAlign: 'center', marginTop: 48 }}><Spin /></div>
                            ) : (
                                (thread.data?.messages ?? []).map((m) => (
                                    <div key={m.id} style={{ display: 'flex', justifyContent: m.direction === 'outbound' ? 'flex-end' : 'flex-start', marginBottom: 8 }}>
                                        <div style={{
                                            maxWidth: '70%', padding: '8px 12px', borderRadius: 12,
                                            background: m.direction === 'outbound' ? '#2563EB' : '#fff',
                                            color: m.direction === 'outbound' ? '#fff' : '#0F172A',
                                            border: m.direction === 'outbound' ? 'none' : '1px solid #E2E8F0',
                                        }}>
                                            {m.sent_by_ai && <Tag color="purple" style={{ marginBottom: 4 }}>AI</Tag>}
                                            {(m.attachments ?? []).map((a) => (
                                                <div key={a.id} style={{ marginBottom: m.body ? 6 : 0 }}>
                                                    {a.kind === 'image' && a.download_url ? (
                                                        <Image src={a.download_url} alt={a.filename ?? ''} style={{ maxWidth: 220, borderRadius: 8 }} />
                                                    ) : a.kind === 'video' && a.download_url ? (
                                                        <video src={a.download_url} controls style={{ maxWidth: 240, borderRadius: 8 }} />
                                                    ) : (
                                                        <a href={a.download_url ?? '#'} target="_blank" rel="noreferrer" style={{ color: 'inherit' }}>
                                                            <Space size={4}><FileOutlined /> {a.filename ?? 'Tệp đính kèm'}</Space>
                                                        </a>
                                                    )}
                                                </div>
                                            ))}
                                            {m.body != null && <div style={{ whiteSpace: 'pre-wrap' }}>{m.body}</div>}
                                            {m.body == null && (m.attachments ?? []).length === 0 && <div>{`[${m.kind}]`}</div>}
                                            {m.direction === 'outbound' && (
                                                <div style={{ fontSize: 10, opacity: 0.8, textAlign: 'right' }}>
                                                    {m.delivery_status === 'failed' ? 'Gửi lỗi' : m.delivery_status}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))
                            )}
                            <div ref={bottomRef} />
                        </div>
                        {active?.blocked_at ? (
                            <div style={{ padding: 16, borderTop: '1px solid #F1F5F9', textAlign: 'center' }}>
                                <Space direction="vertical" size={8}>
                                    <Text type="secondary">Đã chặn người dùng này — không thể gửi tin.</Text>
                                    <Button onClick={() => active && onConvAction('unblock', active)}>Bỏ chặn để nhắn lại</Button>
                                </Space>
                            </div>
                        ) : (
                        <div style={{ padding: 12, borderTop: '1px solid #F1F5F9' }}>
                            <Input.TextArea
                                value={draft}
                                onChange={(e) => setDraft(e.target.value)}
                                placeholder="Nhập tin nhắn… (Enter để gửi, Shift+Enter xuống dòng)"
                                autoSize={{ minRows: 1, maxRows: 4 }}
                                onPressEnter={(e) => { if (!e.shiftKey) { e.preventDefault(); handleSend(); } }}
                            />
                            <Space style={{ marginTop: 8, justifyContent: 'space-between', width: '100%' }}>
                                <Space size={4}>
                                    <Upload showUploadList={false} accept="image/*" beforeUpload={(f) => handleMedia(f as File, 'image')}>
                                        <Button icon={<PictureOutlined />} title="Gửi ảnh" />
                                    </Upload>
                                    <Upload showUploadList={false} accept="video/*" beforeUpload={(f) => handleMedia(f as File, 'video')}>
                                        <Button icon={<VideoCameraOutlined />} title="Gửi video" />
                                    </Upload>
                                    <Upload showUploadList={false} beforeUpload={(f) => handleMedia(f as File, 'file')}>
                                        <Button icon={<PaperClipOutlined />} title="Gửi tài liệu" />
                                    </Upload>
                                    <Popover
                                        open={emojiOpen}
                                        onOpenChange={setEmojiOpen}
                                        trigger="click"
                                        content={<Picker data={emojiData} onEmojiSelect={(e: { native?: string }) => handleEmoji(e)} previewPosition="none" locale="vi" />}
                                    >
                                        <Button icon={<SmileOutlined />} title="Chèn emoji" />
                                    </Popover>
                                    <Button icon={<RobotOutlined />} loading={aiSuggest.isPending} onClick={handleAi}>AI gợi ý</Button>
                                </Space>
                                <Button type="primary" icon={<SendOutlined />} loading={sendText.isPending} onClick={handleSend} disabled={!draft.trim()}>Gửi</Button>
                            </Space>
                        </div>
                        )}
                    </>
                )}
            </div>

            {/* Cột phải — panel thông tin (MVP) */}
            <div style={{ width: 280, background: '#fff', borderRadius: 12, padding: 16 }}>
                <Text strong>Thông tin</Text>
                {active ? (
                    <div style={{ marginTop: 12, display: 'flex', flexDirection: 'column', gap: 8 }}>
                        <div><Text type="secondary">Khách: </Text>{active.buyer_name ?? active.buyer_external_id}</div>
                        <div><Text type="secondary">Nguồn: </Text>{providerLabel(active.provider)}{active.channel_account_name ? ` · ${active.channel_account_name}` : ''}</div>
                        <div><Text type="secondary">Trạng thái: </Text>{active.status}</div>
                        {active.order_id && <div><Text type="secondary">Đơn liên quan: </Text><a href={`/orders/${active.order_id}`}>#{active.order_id}</a></div>}
                        {active.customer_id && <div><Text type="secondary">Khách hàng: </Text><a href={`/customers/${active.customer_id}`}>Hồ sơ</a></div>}
                    </div>
                ) : (
                    <div style={{ marginTop: 12 }}><Text type="secondary">Chọn hội thoại để xem chi tiết.</Text></div>
                )}
            </div>
            </div>
        </div>
    );
}
