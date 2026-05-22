import { useEffect, useMemo, useRef, useState, useCallback } from 'react';
import dayjs from 'dayjs';
import { Link } from 'react-router-dom';
import { App, Avatar, Badge, Button, Checkbox, Dropdown, Empty, Grid, Image, Input, List, Modal, Popconfirm, Popover, Radio, Segmented, Select, Space, Spin, Tag, Tooltip, Typography, Upload } from 'antd';
import { CommentOutlined, DeleteOutlined, EyeInvisibleOutlined, EyeOutlined, FileOutlined, FilterOutlined, MessageFilled, MessageOutlined, MoreOutlined, PaperClipOutlined, PhoneOutlined, PictureOutlined, RobotOutlined, SendOutlined, ShopOutlined, SmileOutlined, TagOutlined, VideoCameraOutlined } from '@ant-design/icons';
import Picker from '@emoji-mart/react';
import emojiData from '@emoji-mart/data';
import { errorMessage } from '@/lib/api';
import {
    type Conversation,
    INBOX_GROUP_PROVIDERS,
    type MessagingTag,
    providerLabel,
    useAiSuggestion,
    useBlockConversation,
    useConversations,
    useConversationThread,
    useDeleteComment,
    useHideComment,
    useMarkRead,
    useMarkUnread,
    useMessagingTags,
    usePrivateReplyComment,
    useReplyComment,
    useSendMedia,
    useSendText,
    useSetConversationTags,
    useUnblockConversation,
} from '@/lib/messaging';
import { useMessagingChannels, useTemplates, type MessageTemplate } from '@/lib/messagingConfig';
import { MessagingNav } from '@/components/MessagingNav';
import { TagManagerModal } from '@/components/TagManagerModal';

const { Text } = Typography;

const URL_SPLIT_RE = /(https?:\/\/[^\s]+)/g;
const URL_TEST_RE = /^https?:\/\/[^\s]+$/;
// SĐT VN: tiền tố +84 hoặc 0, cho phép . - và khoảng trắng giữa các nhóm số.
const PHONE_SPLIT_RE = /((?:\+84|0)\d[\d .-]{7,12}\d)/g;
const PHONE_TEST_RE = /^(?:\+84|0)\d[\d .-]{7,12}\d$/;

/** Chip sđt: bấm để copy (đã bỏ ký tự ngăn cách). */
function PhoneChip({ value }: { value: string }) {
    const { message } = App.useApp();
    const normalized = value.replace(/[ .-]/g, '');
    return (
        <Tag
            color="green"
            icon={<PhoneOutlined />}
            style={{ cursor: 'pointer', marginInline: 2 }}
            onClick={(e) => {
                e.stopPropagation();
                void navigator.clipboard?.writeText(normalized);
                message.success('Đã copy số điện thoại');
            }}
        >
            {value.trim()}
        </Tag>
    );
}

/** Render 1 đoạn text: tách sđt thành chip. */
function renderPhones(text: string, keyPrefix: string) {
    return text.split(PHONE_SPLIT_RE).map((part, i) =>
        PHONE_TEST_RE.test(part.trim())
            ? <PhoneChip key={`${keyPrefix}-p${i}`} value={part} />
            : <span key={`${keyPrefix}-t${i}`}>{part}</span>,
    );
}

/** Render nội dung tin: URL → link; sđt → chip màu (bấm copy); còn lại giữ nguyên. */
function MessageBody({ text }: { text: string }) {
    const parts = text.split(URL_SPLIT_RE);
    return (
        <>
            {parts.map((part, i) =>
                URL_TEST_RE.test(part) ? (
                    <a key={`u${i}`} href={part} target="_blank" rel="noreferrer" style={{ color: 'inherit', textDecoration: 'underline' }}>
                        {part}
                    </a>
                ) : (
                    <span key={`s${i}`}>{renderPhones(part, `s${i}`)}</span>
                ),
            )}
        </>
    );
}

/** Giờ hiển thị cho 1 tin trong thread: cùng ngày → HH:mm; khác ngày → DD/MM HH:mm. */
function fmtMsgTime(iso: string | null): string {
    if (!iso) return '';
    const d = dayjs(iso);
    return d.isSame(dayjs(), 'day') ? d.format('HH:mm') : d.format('DD/MM HH:mm');
}

/** Giờ gọn cho danh sách hội thoại: <60' → "x phút"; hôm nay → HH:mm; hôm qua → "Hôm qua"; còn lại → DD/MM. */
function fmtListTime(iso: string | null): string {
    if (!iso) return '';
    const d = dayjs(iso);
    const now = dayjs();
    const diffMin = now.diff(d, 'minute');
    if (diffMin < 1) return 'vừa xong';
    if (diffMin < 60) return `${diffMin} phút`;
    if (d.isSame(now, 'day')) return d.format('HH:mm');
    if (d.isSame(now.subtract(1, 'day'), 'day')) return 'Hôm qua';
    if (d.isSame(now, 'year')) return d.format('DD/MM');
    return d.format('DD/MM/YY');
}

/**
 * Hộp thư hợp nhất 3 cột (SPEC-0024 §3.1): danh sách hội thoại | luồng tin +
 * ô soạn | panel thông tin. Realtime = polling fallback (Reverb là follow-up).
 * MVP: text + AI gợi ý. Media/template/auto-rule UI ở các vòng sau.
 */
export function MessagingPage() {
    const { message } = App.useApp();
    const screens = Grid.useBreakpoint();

    // ── Delivery status localisation ──────────────────────────────────────────
    const DELIVERY_STATUS_LABEL: Record<string, string> = {
        pending: 'Đang gửi',
        sent: 'Đã gửi',
        delivered: 'Đã nhận',
        read: 'Đã xem',
        failed: 'Gửi lỗi',
    };

    // ── Kind label fallback (khi body=null và không có attachment) ────────────
    const KIND_LABEL: Record<string, string> = {
        text: 'Tin không có nội dung',
        image: 'Hình ảnh',
        video: 'Video',
        file: 'Tệp đính kèm',
        template: 'Mẫu tin',
        system: 'Tin hệ thống',
    };

    // ── Filter state ──────────────────────────────────────────────────────────
    const [board, setBoard] = useState<'marketplace' | 'facebook'>('marketplace');
    const [readState, setReadState] = useState<'all' | 'read' | 'unread'>('all');
    const [hasPhone, setHasPhone] = useState(false);
    const [tagFilter, setTagFilter] = useState<number[]>([]);
    const [statusState, setStatusState] = useState<'open' | 'resolved' | 'blocked' | 'all'>('open');
    const [channelAccountId, setChannelAccountId] = useState<number | undefined>(undefined);
    const [filterOpen, setFilterOpen] = useState(false);
    const [tagModalOpen, setTagModalOpen] = useState(false);

    // ── Other state ───────────────────────────────────────────────────────────
    const [activeId, setActiveId] = useState<number | null>(null);
    const [draft, setDraft] = useState('');
    const [emojiOpen, setEmojiOpen] = useState(false);
    const [tagPopoverConvId, setTagPopoverConvId] = useState<number | null>(null);

    // ── Comment-specific state ────────────────────────────────────────────────
    const [privateReplyOpen, setPrivateReplyOpen] = useState(false);
    const [privateReplyDraft, setPrivateReplyDraft] = useState('');
    const [openingLink, setOpeningLink] = useState(false);

    // ── Templates (for slash-command) ─────────────────────────────────────────
    const templatesQuery = useTemplates();

    /**
     * Slash-command: draft bắt đầu bằng `/` (và chỉ `/...` — không có khoảng trắng ở đầu,
     * không có ký tự xuống dòng) → hiện danh sách mẫu khớp với query.
     * Regex: /^\/(\S*)$/ — neo đầu, phần query không chứa khoảng trắng.
     */
    const SLASH_RE = /^\/(\S*)$/;
    const slashMatch = SLASH_RE.exec(draft);
    const slashQuery = slashMatch ? slashMatch[1].toLowerCase() : null;
    const slashOpen = slashQuery !== null;

    const slashMatches = useMemo<MessageTemplate[]>(() => {
        const templates = templatesQuery.data?.data ?? [];
        if (slashQuery === null) return [];
        const q = slashQuery;
        if (q === '') return templates.filter((t) => t.enabled);
        return templates.filter((t) => {
            if (!t.enabled) return false;
            // shortcut_key là ưu tiên (startsWith), sau đó name (includes, case-insensitive)
            if (t.shortcut_key && t.shortcut_key.toLowerCase().startsWith(q)) return true;
            return t.name.toLowerCase().includes(q);
        });
    }, [slashQuery, templatesQuery.data]);

    const [slashHighlight, setSlashHighlight] = useState(0);
    // Reset highlight mỗi khi danh sách thay đổi
    useEffect(() => { setSlashHighlight(0); }, [slashMatches]);

    const applySlashTemplate = useCallback((t: MessageTemplate) => {
        setDraft(t.body);
    }, []);

    // ── Tags ──────────────────────────────────────────────────────────────────
    const tagsQuery = useMessagingTags();
    const tags: MessagingTag[] = tagsQuery.data ?? [];
    const setConvTags = useSetConversationTags();

    // ── Channels (for Facebook page filter) ──────────────────────────────────
    const channelsQuery = useMessagingChannels();
    const facebookPages = (channelsQuery.data ?? []).filter((c) => c.provider === 'facebook_page');

    // ── Conversations ─────────────────────────────────────────────────────────
    const list = useConversations({
        provider: INBOX_GROUP_PROVIDERS[board === 'facebook' ? 'facebook' : 'marketplace'],
        status: statusState === 'all' || statusState === 'blocked' ? undefined : statusState,
        blocked: statusState === 'blocked' || undefined,
        read: readState === 'read' || undefined,
        unread: readState === 'unread' || undefined,
        has_phone: hasPhone || undefined,
        tags: tagFilter.length ? tagFilter.join(',') : undefined,
        channel_account_id: board === 'facebook' ? channelAccountId : undefined,
    });
    const thread = useConversationThread(activeId);
    const sendText = useSendText(activeId);
    const sendMedia = useSendMedia(activeId);
    const markRead = useMarkRead();
    const markUnread = useMarkUnread();
    const block = useBlockConversation();
    const unblock = useUnblockConversation();
    const aiSuggest = useAiSuggestion(activeId);
    const hideComment = useHideComment();
    const deleteComment = useDeleteComment();
    const replyComment = useReplyComment(activeId);
    const privateReply = usePrivateReplyComment(activeId);

    const conversations = list.data?.data ?? [];
    const active = useMemo(
        () => conversations.find((c) => c.id === activeId) ?? thread.data?.conversation,
        [conversations, activeId, thread.data],
    );

    const bottomRef = useRef<HTMLDivElement>(null);
    useEffect(() => { bottomRef.current?.scrollIntoView({ behavior: 'smooth' }); }, [thread.data?.messages.length]);

    // auto mark-read khi mở hội thoại có tin chưa đọc
    useEffect(() => {
        if (activeId && active && active.unread_count > 0) {
            markRead.mutate(activeId);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [activeId]);

    // ── Helpers ───────────────────────────────────────────────────────────────
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
        if (emoji.native) {
            setDraft((d) => d + emoji.native);
            setEmojiOpen(false);
        }
    };

    const handleCommentReply = () => {
        const body = draft.trim();
        if (!body || !activeId) return;
        replyComment.mutate(body, {
            onSuccess: () => setDraft(''),
            onError: (e) => message.error(errorMessage(e, 'Không gửi được trả lời.')),
        });
    };

    const handlePrivateReplySend = () => {
        const body = privateReplyDraft.trim();
        if (!body || !activeId) return;
        privateReply.mutate(body, {
            onSuccess: () => {
                setPrivateReplyDraft('');
                setPrivateReplyOpen(false);
                message.success('Đã gửi tin nhắn riêng.');
            },
            onError: (e) => message.error(errorMessage(e, 'Không gửi được tin nhắn riêng.')),
        });
    };

    const handleAvatarClick = () => {
        if (!active) return;
        if (active.thread_type === 'comment' && active.comment?.post_permalink) {
            setOpeningLink(true);
            setTimeout(() => setOpeningLink(false), 600);
            window.open(active.comment.post_permalink, '_blank', 'noopener');
        } else {
            message.info('Không có link công khai cho hội thoại chat.');
        }
    };

    // ── Active-filter badge count ─────────────────────────────────────────────
    const activeFilterCount = [
        readState !== 'all',
        statusState !== 'open',
        hasPhone,
        tagFilter.length > 0,
        board === 'facebook' && channelAccountId != null,
    ].filter(Boolean).length;

    // ── Filter popover content ────────────────────────────────────────────────
    const filterPopoverContent = (
        <div style={{ width: 240, display: 'flex', flexDirection: 'column', gap: 16 }}>
            {/* Trạng thái đọc */}
            <div>
                <Text strong style={{ fontSize: 12, display: 'block', marginBottom: 6 }}>Trạng thái đọc</Text>
                <Radio.Group
                    value={readState}
                    onChange={(e) => setReadState(e.target.value as 'all' | 'read' | 'unread')}
                >
                    <Space direction="vertical" size={4}>
                        <Radio value="all">Tất cả</Radio>
                        <Radio value="read">Đã xem</Radio>
                        <Radio value="unread">Chưa đọc</Radio>
                    </Space>
                </Radio.Group>
            </div>

            {/* Trạng thái xử lý */}
            <div>
                <Text strong style={{ fontSize: 12, display: 'block', marginBottom: 6 }}>Trạng thái</Text>
                <Radio.Group
                    value={statusState}
                    onChange={(e) => setStatusState(e.target.value as 'open' | 'resolved' | 'blocked' | 'all')}
                >
                    <Space direction="vertical" size={4}>
                        <Radio value="open">Đang mở</Radio>
                        <Radio value="resolved">Đã xong</Radio>
                        <Radio value="blocked">Đã chặn</Radio>
                        <Radio value="all">Tất cả</Radio>
                    </Space>
                </Radio.Group>
            </div>

            {/* SĐT */}
            <Checkbox
                checked={hasPhone}
                onChange={(e) => setHasPhone(e.target.checked)}
            >
                Chỉ hội thoại có SĐT
            </Checkbox>

            {/* Trang Facebook (chỉ hiện khi đang ở tab Facebook) */}
            {board === 'facebook' && (
                <div>
                    <Text strong style={{ fontSize: 12, display: 'block', marginBottom: 6 }}>Trang</Text>
                    <Select
                        allowClear
                        placeholder="Tất cả trang"
                        style={{ width: '100%' }}
                        value={channelAccountId}
                        onChange={(v) => setChannelAccountId(v as number | undefined)}
                        options={facebookPages.map((p) => ({ value: p.id, label: p.name }))}
                    />
                </div>
            )}

            {/* Thẻ */}
            <div>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 6 }}>
                    <Text strong style={{ fontSize: 12 }}>
                        <TagOutlined style={{ marginInlineEnd: 4 }} />Thẻ
                    </Text>
                    <Button type="link" size="small" style={{ padding: 0, height: 'auto' }} onClick={() => setTagModalOpen(true)}>Quản lý thẻ</Button>
                </div>
                {tags.length > 0 && (
                    <Space direction="vertical" size={4}>
                        {tags.map((t) => (
                            <Checkbox
                                key={t.id}
                                checked={tagFilter.includes(t.id)}
                                onChange={(e) => {
                                    setTagFilter((prev) =>
                                        e.target.checked
                                            ? [...prev, t.id]
                                            : prev.filter((id) => id !== t.id),
                                    );
                                }}
                            >
                                <span
                                    style={{
                                        display: 'inline-block',
                                        width: 8,
                                        height: 8,
                                        borderRadius: '50%',
                                        background: t.color,
                                        marginInlineEnd: 6,
                                    }}
                                />
                                {t.name}
                            </Checkbox>
                        ))}
                    </Space>
                )}
            </div>
        </div>
    );

    // ── Conversation menu ─────────────────────────────────────────────────────
    const convMenuItems = (c: Conversation) => [
        { key: 'unread', label: 'Đánh dấu chưa đọc' },
        c.blocked_at
            ? { key: 'unblock', label: 'Bỏ chặn người dùng' }
            : { key: 'block', label: 'Chặn người dùng', danger: true },
        { key: 'tags', label: 'Gắn thẻ', icon: <TagOutlined /> },
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
        } else if (key === 'tags') {
            setTagPopoverConvId((prev) => (prev === c.id ? null : c.id));
        }
    };

    // ── Tag-attach popover content ────────────────────────────────────────────
    const tagAttachContent = (c: Conversation) => (
        <div style={{ width: 200 }}>
            <Text strong style={{ fontSize: 12, display: 'block', marginBottom: 8 }}>Gắn thẻ hội thoại</Text>
            {tags.length === 0 ? (
                <Text type="secondary" style={{ fontSize: 12 }}>Chưa có thẻ nào.</Text>
            ) : (
                <Space direction="vertical" size={4}>
                    {tags.map((t) => (
                        <Checkbox
                            key={t.id}
                            checked={(c.tags ?? []).includes(t.id)}
                            onChange={(e) => {
                                const next = e.target.checked
                                    ? [...(c.tags ?? []), t.id]
                                    : (c.tags ?? []).filter((id) => id !== t.id);
                                setConvTags.mutate({ conversationId: c.id, tags: next });
                            }}
                        >
                            <span
                                style={{
                                    display: 'inline-block',
                                    width: 8,
                                    height: 8,
                                    borderRadius: '50%',
                                    background: t.color,
                                    marginInlineEnd: 6,
                                }}
                            />
                            {t.name}
                        </Checkbox>
                    ))}
                </Space>
            )}
        </div>
    );

    return (
        <div>
            <MessagingNav />
            <TagManagerModal open={tagModalOpen} onClose={() => setTagModalOpen(false)} />
            {/* Private reply modal */}
            <Modal
                title="Nhắn riêng"
                open={privateReplyOpen}
                onCancel={() => { setPrivateReplyOpen(false); setPrivateReplyDraft(''); }}
                footer={[
                    <Button key="cancel" onClick={() => { setPrivateReplyOpen(false); setPrivateReplyDraft(''); }}>Huỷ</Button>,
                    <Button key="send" type="primary" icon={<SendOutlined />} loading={privateReply.isPending} onClick={handlePrivateReplySend} disabled={!privateReplyDraft.trim()}>Gửi</Button>,
                ]}
            >
                <Input.TextArea
                    value={privateReplyDraft}
                    onChange={(e) => setPrivateReplyDraft(e.target.value)}
                    placeholder="Nội dung tin nhắn riêng…"
                    autoSize={{ minRows: 3, maxRows: 8 }}
                />
            </Modal>
            <div style={{ display: 'flex', height: 'calc(100vh - 150px)', gap: 12, minWidth: 0 }}>
            {/* Cột trái — danh sách hội thoại */}
            <div style={{ flex: `0 0 ${screens.md ? 360 : 320}px`, minWidth: 320, maxWidth: 420, background: '#fff', borderRadius: 12, display: 'flex', flexDirection: 'column', overflow: 'hidden', minHeight: 0 }}>
                <div style={{ padding: 12, borderBottom: '1px solid #F1F5F9', display: 'flex', flexDirection: 'column', gap: 8 }}>
                    {/* 2 tab chính: Sàn / Facebook */}
                    <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                        <Segmented
                            block
                            style={{ flex: 1 }}
                            value={board}
                            onChange={(v) => { setBoard(v as 'marketplace' | 'facebook'); setActiveId(null); setChannelAccountId(undefined); }}
                            options={[
                                { label: 'Sàn', value: 'marketplace' },
                                { label: 'Facebook', value: 'facebook' },
                            ]}
                        />
                        <Popover
                            open={filterOpen}
                            onOpenChange={setFilterOpen}
                            trigger="click"
                            placement="bottomRight"
                            content={filterPopoverContent}
                        >
                            <Badge count={activeFilterCount} size="small">
                                <Button icon={<FilterOutlined />} title="Bộ lọc">Bộ lọc</Button>
                            </Badge>
                        </Popover>
                    </div>
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
                                <Popover
                                    key={c.id}
                                    open={tagPopoverConvId === c.id}
                                    onOpenChange={(open) => { if (!open) setTagPopoverConvId(null); }}
                                    trigger={[]}
                                    placement="right"
                                    content={tagAttachContent(c)}
                                >
                                    <List.Item
                                        onClick={() => setActiveId(c.id)}
                                        style={{
                                            cursor: 'pointer',
                                            padding: '10px 14px',
                                            background: c.id === activeId
                                                ? '#EFF6FF'
                                                : c.unread_count > 0 ? '#F0FDF4' : undefined,
                                        }}
                                    >
                                        <List.Item.Meta
                                            avatar={(
                                                <Badge
                                                    count={c.thread_type === 'comment'
                                                        ? <CommentOutlined style={{ fontSize: 15, color: '#1677ff', background: '#fff', borderRadius: '50%', padding: 2, boxShadow: '0 0 0 1px #fff' }} />
                                                        : <MessageOutlined style={{ fontSize: 15, color: '#52c41a', background: '#fff', borderRadius: '50%', padding: 2, boxShadow: '0 0 0 1px #fff' }} />
                                                    }
                                                    offset={[-4, 30]}
                                                >
                                                    <Avatar size={40} src={c.buyer_avatar_url ?? undefined} style={{ background: '#2563EB', flexShrink: 0 }}>{(c.buyer_name ?? c.buyer_external_id ?? '?').slice(0, 1).toUpperCase()}</Avatar>
                                                </Badge>
                                            )}
                                            title={(
                                                <div style={{ minWidth: 0 }}>
                                                    {/* Row 1: unread badge + name + action menu */}
                                                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 4, minWidth: 0 }}>
                                                        <Space size={4} style={{ minWidth: 0, flex: 1, overflow: 'hidden' }}>
                                                            <Badge count={c.unread_count} size="small" />
                                                            <Text strong={c.unread_count > 0} ellipsis style={{ maxWidth: 160 }}>{c.buyer_name ?? c.buyer_external_id}</Text>
                                                        </Space>
                                                        {c.last_message_at && (
                                                            <Text type="secondary" style={{ fontSize: 11, whiteSpace: 'nowrap', flexShrink: 0 }}>
                                                                {fmtListTime(c.last_message_at)}
                                                            </Text>
                                                        )}
                                                        <Dropdown
                                                            trigger={['click']}
                                                            menu={{
                                                                items: convMenuItems(c),
                                                                onClick: ({ key, domEvent }) => { domEvent.stopPropagation(); onConvAction(key, c); },
                                                            }}
                                                        >
                                                            <Button type="text" size="small" icon={<MoreOutlined />} onClick={(e) => e.stopPropagation()} />
                                                        </Dropdown>
                                                    </div>
                                                    {/* Row 2: page/provider chip — wraps below name */}
                                                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 2, marginTop: 2 }}>
                                                        {c.provider === 'facebook_page'
                                                            ? <Tag color="blue" style={{ marginInlineEnd: 0, fontSize: 11 }}>{c.channel_account_name ?? 'Facebook'}</Tag>
                                                            : <Tag color="blue" style={{ marginInlineEnd: 0, fontSize: 11 }}>{providerLabel(c.provider)}</Tag>
                                                        }
                                                    </div>
                                                </div>
                                            )}
                                            description={(
                                                <Space direction="vertical" size={0} style={{ width: '100%' }}>
                                                    {c.channel_account_name && c.provider !== 'facebook_page' && (
                                                        <Text type="secondary" style={{ fontSize: 11 }} ellipsis><ShopOutlined /> {c.channel_account_name}</Text>
                                                    )}
                                                    <Text type="secondary" ellipsis style={{ fontSize: 12 }}>{c.last_message_preview ?? '—'}</Text>
                                                    {/* SĐT chip + tag chips */}
                                                    {(c.has_phone && c.detected_phone || (c.tags ?? []).length > 0) && (
                                                        <div style={{ marginTop: 4, display: 'flex', flexWrap: 'wrap', gap: 2 }}>
                                                            {c.has_phone && c.detected_phone && (
                                                                <Tag icon={<PhoneOutlined />} color="green" style={{ marginInlineEnd: 0, fontSize: 11 }}>{c.detected_phone}</Tag>
                                                            )}
                                                            {(c.tags ?? []).map((tid) => {
                                                                const t = tags.find((x) => x.id === tid);
                                                                return t ? (
                                                                    <Tag key={tid} color={t.color} style={{ marginInlineEnd: 0, fontSize: 11 }}>{t.name}</Tag>
                                                                ) : null;
                                                            })}
                                                        </div>
                                                    )}
                                                </Space>
                                            )}
                                        />
                                    </List.Item>
                                </Popover>
                            )}
                        />
                    )}
                </div>
            </div>

            {/* Cột giữa — luồng tin + ô soạn */}
            <div style={{ flex: 1, minWidth: 0, minHeight: 0, background: '#fff', borderRadius: 12, display: 'flex', flexDirection: 'column', overflow: 'hidden' }}>
                {!activeId ? (
                    <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                        <Empty description="Chọn một hội thoại để xem" />
                    </div>
                ) : (
                    <>
                        <div style={{ padding: 12, borderBottom: '1px solid #F1F5F9' }}>
                            {/* Row 1: avatar + name + provider chips + comment action buttons */}
                            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                <Tooltip title={active?.thread_type === 'comment' && active?.comment?.post_permalink ? 'Xem bài viết Facebook' : 'Không có link công khai'}>
                                    <Spin spinning={openingLink} size="small">
                                        <Avatar
                                            src={active?.buyer_avatar_url ?? undefined}
                                            size={36}
                                            style={{ cursor: 'pointer', background: '#2563EB', flexShrink: 0 }}
                                            onClick={handleAvatarClick}
                                        >
                                            {(active?.buyer_name ?? active?.buyer_external_id ?? '?').slice(0, 1).toUpperCase()}
                                        </Avatar>
                                    </Spin>
                                </Tooltip>
                                <div style={{ flex: 1 }}>
                                    <Text strong>{active?.buyer_name ?? active?.buyer_external_id}</Text>{' '}
                                    <Tag color="blue">{providerLabel(active?.provider ?? '')}</Tag>
                                    {active?.has_phone && active?.detected_phone && (
                                        <Tag icon={<PhoneOutlined />} color="green" style={{ marginInlineStart: 4 }}>{active.detected_phone}</Tag>
                                    )}
                                    {active?.channel_account_name && (
                                        <Text type="secondary" style={{ marginInlineStart: 4 }}>· {active.channel_account_name}</Text>
                                    )}
                                </div>
                                {/* Comment action buttons — only for comment threads */}
                                {active?.thread_type === 'comment' && active.comment && (
                                    <Space size={4}>
                                        <Tooltip title={active.comment.hidden ? 'Hiện bình luận' : 'Ẩn bình luận'}>
                                            <Button
                                                type="text"
                                                size="small"
                                                icon={active.comment.hidden ? <EyeOutlined /> : <EyeInvisibleOutlined />}
                                                loading={hideComment.isPending}
                                                onClick={() => {
                                                    if (!active?.comment) return;
                                                    hideComment.mutate(
                                                        { conversationId: active.id, hidden: !active.comment.hidden },
                                                        { onError: (e) => message.error(errorMessage(e, 'Không thực hiện được.')) },
                                                    );
                                                }}
                                            />
                                        </Tooltip>
                                        <Popconfirm
                                            title="Xoá bình luận này?"
                                            description="Hành động này không thể hoàn tác."
                                            okText="Xoá"
                                            cancelText="Huỷ"
                                            okButtonProps={{ danger: true }}
                                            onConfirm={() => {
                                                deleteComment.mutate(active.id, {
                                                    onSuccess: () => { setActiveId(null); message.success('Đã xoá bình luận.'); },
                                                    onError: (e) => message.error(errorMessage(e, 'Không xoá được.')),
                                                });
                                            }}
                                        >
                                            <Tooltip title="Xoá bình luận">
                                                <Button
                                                    type="text"
                                                    size="small"
                                                    danger
                                                    icon={<DeleteOutlined />}
                                                    loading={deleteComment.isPending}
                                                />
                                            </Tooltip>
                                        </Popconfirm>
                                        <Tooltip title={active.comment.private_replied ? 'Đã nhắn riêng — nhắn thêm' : 'Nhắn riêng (tin nhắn cá nhân)'}>
                                            <Button
                                                type="text"
                                                size="small"
                                                icon={active.comment.private_replied ? <MessageFilled style={{ color: '#16A34A' }} /> : <MessageOutlined />}
                                                style={active.comment.private_replied ? { color: '#16A34A', fontWeight: 700 } : undefined}
                                                onClick={() => setPrivateReplyOpen(true)}
                                            />
                                        </Tooltip>
                                    </Space>
                                )}
                            </div>
                            {/* Row 2: post preview banner (comment threads only) */}
                            {active?.thread_type === 'comment' && active.comment && (
                                <div style={{
                                    marginTop: 8,
                                    padding: '6px 10px',
                                    background: '#EFF6FF',
                                    borderRadius: 6,
                                    borderLeft: '3px solid #2563EB',
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 8,
                                    flexWrap: 'wrap',
                                }}>
                                    <Tag color="blue" style={{ marginInlineEnd: 0 }}>Bình luận</Tag>
                                    {active.comment.post_message && (
                                        <Text type="secondary" ellipsis style={{ flex: 1, minWidth: 0, fontSize: 12 }}>
                                            {active.comment.post_message.length > 80
                                                ? `${active.comment.post_message.slice(0, 80)}…`
                                                : active.comment.post_message}
                                        </Text>
                                    )}
                                    {active.comment.post_permalink && (
                                        <a href={active.comment.post_permalink} target="_blank" rel="noreferrer" style={{ fontSize: 12, whiteSpace: 'nowrap' }}>
                                            Xem bài viết
                                        </a>
                                    )}
                                    {active.comment.hidden && <Tag color="orange" style={{ marginInlineEnd: 0 }}>Đã ẩn</Tag>}
                                    {active.comment.private_replied && <Tag color="green" style={{ marginInlineEnd: 0 }}>Đã nhắn riêng</Tag>}
                                </div>
                            )}
                        </div>
                        <div style={{ flex: 1, overflowY: 'auto', padding: 16, background: '#F8FAFC' }}>
                            {thread.isLoading ? (
                                <div style={{ textAlign: 'center', marginTop: 48 }}><Spin /></div>
                            ) : (
                                (thread.data?.messages ?? []).map((m) => (
                                    <div key={m.id} style={{ display: 'flex', justifyContent: m.direction === 'outbound' ? 'flex-end' : 'flex-start', marginBottom: m.reaction ? 18 : 8 }}>
                                        <div style={{ position: 'relative', maxWidth: '70%' }}>
                                            <div style={{
                                                padding: '8px 12px', borderRadius: 12,
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
                                                {m.body != null && <div style={{ whiteSpace: 'pre-wrap' }}><MessageBody text={m.body} /></div>}
                                                {m.body == null && (m.attachments ?? []).length === 0 && (
                                                    <div style={{ fontStyle: 'italic', opacity: 0.7 }}>{KIND_LABEL[m.kind] ?? m.kind}</div>
                                                )}
                                                <div style={{ fontSize: 10, opacity: 0.6, textAlign: m.direction === 'outbound' ? 'right' : 'left', marginTop: 2 }}>
                                                    {fmtMsgTime(m.sent_at ?? m.created_at)}
                                                    {m.direction === 'outbound' && (
                                                        <> · {DELIVERY_STATUS_LABEL[m.delivery_status ?? ''] ?? m.delivery_status}</>
                                                    )}
                                                </div>
                                            </div>
                                            {m.reaction && (
                                                <div style={{
                                                    position: 'absolute',
                                                    bottom: -14,
                                                    [m.direction === 'outbound' ? 'right' : 'left']: 8,
                                                    background: '#fff',
                                                    border: '1px solid #E2E8F0',
                                                    borderRadius: 12,
                                                    padding: '1px 5px',
                                                    fontSize: 13,
                                                    lineHeight: '18px',
                                                    boxShadow: '0 1px 3px rgba(0,0,0,0.08)',
                                                    userSelect: 'none',
                                                }}>
                                                    {m.reaction}
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
                        ) : active?.thread_type === 'comment' ? (
                        /* ── Comment composer: public reply only, no media ── */
                        <div style={{ padding: 12, borderTop: '1px solid #F1F5F9' }}>
                            <Input.TextArea
                                value={draft}
                                onChange={(e) => setDraft(e.target.value)}
                                placeholder="Trả lời bình luận… (Enter để gửi, Shift+Enter xuống dòng)"
                                autoSize={{ minRows: 3, maxRows: 10 }}
                                onPressEnter={(e) => { if (!e.shiftKey) { e.preventDefault(); handleCommentReply(); } }}
                            />
                            <Space style={{ marginTop: 8, justifyContent: 'space-between', width: '100%' }}>
                                <Space size={4}>
                                    <Popover
                                        open={emojiOpen}
                                        onOpenChange={setEmojiOpen}
                                        trigger="click"
                                        content={<Picker data={emojiData} onEmojiSelect={(e: { native?: string }) => handleEmoji(e)} previewPosition="none" locale="vi" />}
                                    >
                                        <Button icon={<SmileOutlined />} title="Chèn emoji" />
                                    </Popover>
                                </Space>
                                <Button type="primary" icon={<SendOutlined />} loading={replyComment.isPending} onClick={handleCommentReply} disabled={!draft.trim()}>Trả lời</Button>
                            </Space>
                        </div>
                        ) : (
                        /* ── Message composer (original) ── */
                        <div style={{ padding: 12, borderTop: '1px solid #F1F5F9' }}>
                            <Popover
                                open={slashOpen && (slashMatches.length > 0 || slashQuery !== null)}
                                placement="topLeft"
                                trigger={[]}
                                arrow={false}
                                overlayInnerStyle={{ padding: 4, minWidth: 280, maxWidth: 360 }}
                                content={(
                                    <div>
                                        {slashMatches.length === 0 ? (
                                            <div style={{ padding: '6px 10px', color: '#94A3B8', fontSize: 13 }}>Không có mẫu tin khớp</div>
                                        ) : (
                                            slashMatches.map((t, idx) => (
                                                <div
                                                    key={t.id}
                                                    onClick={() => applySlashTemplate(t)}
                                                    style={{
                                                        padding: '6px 10px',
                                                        borderRadius: 6,
                                                        cursor: 'pointer',
                                                        background: idx === slashHighlight ? '#EFF6FF' : undefined,
                                                        display: 'flex',
                                                        alignItems: 'baseline',
                                                        gap: 8,
                                                    }}
                                                    onMouseEnter={() => setSlashHighlight(idx)}
                                                >
                                                    <span style={{ fontWeight: 600, fontSize: 13, flex: '0 0 auto' }}>{t.name}</span>
                                                    {t.shortcut_key && (
                                                        <Tag style={{ marginInlineEnd: 0, fontFamily: 'monospace', fontSize: 11 }}>/{t.shortcut_key}</Tag>
                                                    )}
                                                    <span style={{ color: '#64748B', fontSize: 12, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', flex: 1 }}>
                                                        {t.body.length > 50 ? t.body.slice(0, 50) + '…' : t.body}
                                                    </span>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                )}
                            >
                            <Input.TextArea
                                value={draft}
                                onChange={(e) => setDraft(e.target.value)}
                                placeholder="Nhập tin nhắn… (Enter để gửi, Shift+Enter xuống dòng; /slug để chèn mẫu)"
                                autoSize={{ minRows: 3, maxRows: 10 }}
                                onPressEnter={(e) => {
                                    if (e.shiftKey) return; // xuống dòng bình thường
                                    if (slashOpen && slashMatches.length > 0) {
                                        e.preventDefault();
                                        applySlashTemplate(slashMatches[slashHighlight] ?? slashMatches[0]);
                                        return;
                                    }
                                    e.preventDefault();
                                    handleSend();
                                }}
                                onKeyDown={(e) => {
                                    if (!slashOpen || slashMatches.length === 0) return;
                                    if (e.key === 'ArrowDown') {
                                        e.preventDefault();
                                        setSlashHighlight((h) => Math.min(h + 1, slashMatches.length - 1));
                                    } else if (e.key === 'ArrowUp') {
                                        e.preventDefault();
                                        setSlashHighlight((h) => Math.max(h - 1, 0));
                                    } else if (e.key === 'Escape') {
                                        e.preventDefault();
                                        setDraft('');
                                    }
                                }}
                            />
                            </Popover>
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

            {/* Cột phải — panel thông tin (MVP, chỉ ≥1200px) */}
            {screens.xl && (
            <div style={{ width: 280, background: '#fff', borderRadius: 12, padding: 16, minHeight: 0, flexShrink: 0 }}>
                <Text strong>Thông tin</Text>
                {active ? (
                    <div style={{ marginTop: 12, display: 'flex', flexDirection: 'column', gap: 8 }}>
                        <div><Text type="secondary">Khách: </Text>{active.buyer_name ?? active.buyer_external_id}</div>
                        <div><Text type="secondary">Nguồn: </Text>{providerLabel(active.provider)}{active.channel_account_name ? ` · ${active.channel_account_name}` : ''}</div>
                        <div><Text type="secondary">Trạng thái: </Text>{active.status}</div>
                        {active.order_id && <div><Text type="secondary">Đơn liên quan: </Text><Link to={`/orders/${active.order_id}`}>#{active.order_id}</Link></div>}
                        {active.customer_id && <div><Text type="secondary">Khách hàng: </Text><Link to={`/customers/${active.customer_id}`}>Hồ sơ</Link></div>}
                    </div>
                ) : (
                    <div style={{ marginTop: 12 }}><Text type="secondary">Chọn hội thoại để xem chi tiết.</Text></div>
                )}
            </div>
            )}
            </div>
        </div>
    );
}
