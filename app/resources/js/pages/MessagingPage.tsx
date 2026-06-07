import { useEffect, useMemo, useRef, useState, useCallback, type CSSProperties, type ReactNode, type UIEvent } from 'react';
import dayjs from 'dayjs';
import { Link } from 'react-router-dom';
import { App, Avatar, Badge, Button, Checkbox, Divider, Dropdown, Empty, Grid, Image, Input, List, Popconfirm, Popover, Radio, Segmented, Select, Space, Spin, Tag, Tooltip, Typography } from 'antd';
import { BellFilled, BellOutlined, CommentOutlined, DeleteOutlined, EyeInvisibleOutlined, EyeOutlined, FileOutlined, FilterOutlined, LikeFilled, LikeOutlined, MessageOutlined, MoreOutlined, PhoneOutlined, PictureOutlined, RedoOutlined, ShopOutlined, ShoppingOutlined, SoundOutlined, TagOutlined, VideoCameraOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import {
    type Conversation,
    INBOX_GROUP_PROVIDERS,
    MARKETPLACE_CHAT_ENABLED,
    type MessageAttachment,
    type MessageButton,
    type MessagingTag,
    providerLabel,
    useAiSuggestion,
    useBlockConversation,
    useConversations,
    useConversationThread,
    useDeleteComment,
    useHideComment,
    useLikeComment,
    useMarkRead,
    useMarkUnread,
    useMessagingRealtime,
    useMessagingTags,
    useReplyComment,
    useResendMessage,
    useSendMedia,
    useSendText,
    useSaveTag,
    useSetConversationTags,
    useUnblockConversation,
} from '@/lib/messaging';
import { useMessagingChannels, useTemplates } from '@/lib/messagingConfig';
import { usePushNotifications } from '@/lib/usePushNotifications';
import { MessagingNav } from '@/components/MessagingNav';
import { TagManagerModal } from '@/components/TagManagerModal';
import { ConversationOrderPanel } from '@/components/messaging/ConversationOrderPanel';
import { CommentPostCard } from '@/components/messaging/CommentPostCard';
import { CommentAvatarStack } from '@/components/messaging/CommentAvatarStack';
import { CommentPrivateMessageModal } from '@/components/messaging/CommentPrivateMessageModal';
import { MessageComposer, type ComposerSubmit } from '@/components/messaging/MessageComposer';

const { Text } = Typography;

// Bảng màu thẻ preset (tạo thẻ nhanh trong popover gắn thẻ).
const TAG_COLORS = ['#2563EB', '#16A34A', '#DC2626', '#D97706', '#7C3AED', '#0891B2', '#DB2777', '#475569'];

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

/** Nhãn người tham gia comment: 1→"A"; 2→"A, B"; ≥3→"A, B +N người". */
function commentParticipantsLabel(names: string[] | undefined): string | null {
    const list = (names ?? []).filter((n) => n && n.trim() !== '');
    if (list.length === 0) return null;
    if (list.length === 1) return list[0];
    if (list.length === 2) return `${list[0]}, ${list[1]}`;
    return `${list[0]}, ${list[1]} +${list.length - 2} người`;
}

/** Tên hiển thị hội thoại: comment → danh sách người tham gia; còn lại → buyer_name. */
function convDisplayName(c: Conversation): string {
    if (c.thread_type === 'comment') {
        const label = commentParticipantsLabel(c.comment?.participants);
        if (label) return label;
    }
    return c.buyer_name ?? c.buyer_external_id ?? '?';
}

/** Placeholder khi media không có URL render được (relay lỗi & URL nguồn đã chết). */
function AttachmentPlaceholder({ icon, label }: { icon: ReactNode; label: string }) {
    return (
        <Space size={4} style={{ fontStyle: 'italic', opacity: 0.7 }}>
            {icon}
            {label}
        </Space>
    );
}

/**
 * Render 1 attachment theo LOẠI, không phải dạng text/link:
 *  - image (gồm sticker — sàn trả kind=image) → <Image> (bấm phóng to)
 *  - video → <video controls>
 *  - audio (voice — kind=file nhưng mime audio/*) → <audio controls>
 *  - còn lại (file/tài liệu) → link tải thật (không phải href="#")
 *
 * `download_url` đã được BE fallback về URL CDN sàn khi relay chưa xong (xem
 * MessageResource), nên ảnh/video/sticker hiển thị ngay thay vì rơi xuống link.
 */
function MessageAttachmentView({ att }: { att: MessageAttachment }) {
    const url = att.download_url ?? null;
    const isImage = att.kind === 'image';
    const isVideo = att.kind === 'video';
    const isAudio = att.kind === 'audio' || (att.mime?.startsWith('audio/') ?? false);

    if (isImage) {
        return url
            ? <Image src={url} alt={att.filename ?? ''} style={{ maxWidth: 220, borderRadius: 8 }} />
            : <AttachmentPlaceholder icon={<PictureOutlined />} label="Hình ảnh" />;
    }
    if (isVideo) {
        return url
            ? <video src={url} controls style={{ maxWidth: 240, borderRadius: 8 }} />
            : <AttachmentPlaceholder icon={<VideoCameraOutlined />} label="Video" />;
    }
    if (isAudio) {
        return url
            ? <audio src={url} controls style={{ maxWidth: 240 }} />
            : <AttachmentPlaceholder icon={<SoundOutlined />} label="Âm thanh" />;
    }
    return url
        ? (
            <a href={url} target="_blank" rel="noreferrer" style={{ color: 'inherit' }}>
                <Space size={4}><FileOutlined /> {att.filename ?? 'Tệp đính kèm'}</Space>
            </a>
        )
        : <AttachmentPlaceholder icon={<FileOutlined />} label={att.filename ?? 'Tệp đính kèm'} />;
}

/**
 * Hàng nút bấm (CHỈ hiển thị) của tin trả lời tự động Facebook (template/quick-reply).
 * Nút có URL → mở link; nút postback → chip tĩnh (không bấm được từ hộp thư này).
 */
function MessageButtons({ buttons, outbound }: { buttons: MessageButton[]; outbound: boolean }) {
    const chip: CSSProperties = {
        display: 'inline-block',
        padding: '3px 10px',
        borderRadius: 14,
        fontSize: 12,
        lineHeight: '18px',
        border: `1px solid ${outbound ? 'rgba(255,255,255,0.6)' : '#CBD5E1'}`,
        color: outbound ? '#fff' : '#2563EB',
        background: outbound ? 'rgba(255,255,255,0.12)' : '#F8FAFC',
        textDecoration: 'none',
        maxWidth: '100%',
    };
    return (
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6, marginTop: 6 }}>
            {buttons.map((b, i) => b.url
                ? <a key={i} href={b.url} target="_blank" rel="noreferrer" style={chip}>{b.title}</a>
                : <span key={i} style={chip}>{b.title}</span>)}
        </div>
    );
}

/** Avatar nhỏ cạnh bong bóng tin: buyer cho inbound, page cho outbound (như app nhắn tin chuẩn). */
function MsgAvatar({ src, name, page }: { src?: string | null; name?: string | null; page?: boolean }) {
    return (
        <Avatar size={28} src={src ?? undefined} style={{ flexShrink: 0, background: page ? '#2563EB' : '#94A3B8' }}>
            {(name ?? '?').slice(0, 1).toUpperCase()}
        </Avatar>
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

/** Hội thoại Facebook đã quá cửa sổ 24h kể từ tin buyer gần nhất? */
function isOutsideWindow(lastInboundAt: string | null): boolean {
    if (!lastInboundAt) return true;
    return dayjs().diff(dayjs(lastInboundAt), 'hour') >= 24;
}

/**
 * Hộp thư hợp nhất 3 cột (SPEC-0024 §3.1): danh sách hội thoại | luồng tin +
 * ô soạn | panel thông tin. Realtime = polling fallback (Reverb là follow-up).
 * MVP: text + AI gợi ý. Media/template/auto-rule UI ở các vòng sau.
 */
export function MessagingPage() {
    const { message } = App.useApp();
    const screens = Grid.useBreakpoint();

    // Realtime inbox (Reverb) — đẩy tin/hội thoại mới tức thời; no-op khi Reverb tắt (polling fallback).
    useMessagingRealtime();

    // ── Delivery status localisation ──────────────────────────────────────────
    const DELIVERY_STATUS_LABEL: Record<string, string> = {
        pending: 'Đang gửi',
        sent: 'Đã gửi',
        delivered: 'Đã nhận',
        read: 'Đã xem',
        failed: 'Gửi lỗi',
    };

    // Lý do gửi lỗi cụ thể (failure_code) — hiển thị rõ thay vì "Gửi lỗi" chung chung.
    const FAILURE_LABEL: Record<string, string> = {
        outbound_window_closed: 'Ngoài cửa sổ 24h — khách cần nhắn lại',
        conversation_closed: 'Khách đã chặn / đóng hội thoại',
        interactive_unsupported: 'Kênh không hỗ trợ nút bấm',
        channel_account_inactive: 'Kênh chưa kết nối/đã ngắt',
    };

    // ── Kind label fallback (khi body=null và không có attachment) ────────────
    const KIND_LABEL: Record<string, string> = {
        text: 'Tin nhắn tự động trên Facebook',
        image: 'Hình ảnh',
        video: 'Video',
        file: 'Tệp đính kèm',
        sticker: 'Sticker',
        template: 'Mẫu tin',
        system: 'Tin hệ thống',
    };

    // ── Filter state ──────────────────────────────────────────────────────────
    const [board, setBoard] = useState<'marketplace' | 'facebook'>(MARKETPLACE_CHAT_ENABLED ? 'marketplace' : 'facebook');
    // Lọc loại hội thoại Facebook: tất cả / tin nhắn (DM) / bình luận.
    const [fbThreadType, setFbThreadType] = useState<'all' | 'message' | 'comment'>('all');
    const [readState, setReadState] = useState<'all' | 'read' | 'unread'>('all');
    const [hasPhone, setHasPhone] = useState(false);
    const [tagFilter, setTagFilter] = useState<number[]>([]);
    const [statusState, setStatusState] = useState<'open' | 'resolved' | 'blocked' | 'all'>('open');
    const [channelAccountId, setChannelAccountId] = useState<number[]>([]);
    const [filterOpen, setFilterOpen] = useState(false);
    const [tagModalOpen, setTagModalOpen] = useState(false);

    // ── Other state ───────────────────────────────────────────────────────────
    const [activeId, setActiveId] = useState<number | null>(null);
    const [tagPopoverConvId, setTagPopoverConvId] = useState<number | null>(null);
    const [openingLink, setOpeningLink] = useState(false);

    // ── Templates (mẫu nhanh — truyền vào composer dùng chung) ────────────────
    const templatesQuery = useTemplates();
    const templates = templatesQuery.data?.data ?? [];

    // ── Tags ──────────────────────────────────────────────────────────────────
    const tagsQuery = useMessagingTags();
    const tags: MessagingTag[] = tagsQuery.data ?? [];
    const setConvTags = useSetConversationTags();
    const saveTag = useSaveTag();
    const [newTagName, setNewTagName] = useState('');
    const [newTagColor, setNewTagColor] = useState(TAG_COLORS[0]);

    // Tạo thẻ mới rồi gắn ngay vào hội thoại (cho phép tạo thẻ từ chính chỗ gắn thẻ).
    const createAndAttachTag = (c: Conversation) => {
        const name = newTagName.trim();
        if (name === '') return;
        saveTag.mutate({ name, color: newTagColor }, {
            onSuccess: (tag) => {
                setConvTags.mutate({ conversationId: c.id, tags: [...(c.tags ?? []), tag.id] });
                setNewTagName('');
                message.success('Đã tạo & gắn thẻ.');
            },
            onError: (e) => message.error(errorMessage(e, 'Không tạo được thẻ.')),
        });
    };

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
        channel_account_id: board === 'facebook' && channelAccountId.length ? channelAccountId.join(',') : undefined,
        thread_type: board === 'facebook' && fbThreadType !== 'all' ? fbThreadType : undefined,
    });
    const thread = useConversationThread(activeId);
    const sendText = useSendText(activeId);
    const sendMedia = useSendMedia(activeId);
    const resend = useResendMessage(activeId);
    const markRead = useMarkRead();
    const markUnread = useMarkUnread();
    const block = useBlockConversation();
    const unblock = useUnblockConversation();
    const aiSuggest = useAiSuggestion(activeId);
    const hideComment = useHideComment();
    const deleteComment = useDeleteComment();
    const replyComment = useReplyComment(activeId);
    const likeComment = useLikeComment();

    // Comment đang mở modal "Nhắn riêng" (id comment cụ thể được bấm).
    const [privateMsgCommentId, setPrivateMsgCommentId] = useState<string | null>(null);
    // Trạng thái "đã thích" optimistic theo phiên (Facebook không trả sẵn page-like per comment).
    const [likedComments, setLikedComments] = useState<Set<string>>(new Set());

    const conversations = useMemo(() => list.data?.pages.flatMap((p) => p.data) ?? [], [list.data]);
    // Thông báo tin nhắn mới đã chuyển sang hook toàn cục ở AppLayout (mọi trang, không bắn lặp).
    const push = usePushNotifications();
    const handleConvScroll = useCallback((e: UIEvent<HTMLDivElement>) => {
        const el = e.currentTarget;
        // Gần đáy (<240px) ⇒ tải trang kế (infinite scroll danh sách hội thoại).
        if (el.scrollHeight - el.scrollTop - el.clientHeight < 240 && list.hasNextPage && !list.isFetchingNextPage) {
            void list.fetchNextPage();
        }
    }, [list]);
    const active = useMemo(
        () => conversations.find((c) => c.id === activeId) ?? thread.data?.conversation,
        [conversations, activeId, thread.data],
    );

    const needsTag = active?.provider === 'facebook_page'
        && active?.thread_type === 'message'
        && !active?.blocked_at
        && isOutsideWindow(active?.last_inbound_at ?? null);

    const bottomRef = useRef<HTMLDivElement>(null);
    useEffect(() => { bottomRef.current?.scrollIntoView({ behavior: 'smooth' }); }, [thread.data?.messages.length]);

    // Popover gắn thẻ dùng trigger={[]} (điều khiển hoàn toàn) nên antd KHÔNG tự đóng khi
    // click ra ngoài. Tự đóng khi mousedown ngoài nội dung popover (.tag-attach-popover).
    useEffect(() => {
        if (tagPopoverConvId === null) return;
        const onDocMouseDown = (e: MouseEvent) => {
            const t = e.target as HTMLElement | null;
            if (t && t.closest('.tag-attach-popover')) return; // click trong popover → giữ
            setTagPopoverConvId(null);
        };
        document.addEventListener('mousedown', onDocMouseDown);
        return () => document.removeEventListener('mousedown', onDocMouseDown);
    }, [tagPopoverConvId]);

    // auto mark-read khi mở hội thoại có tin chưa đọc
    useEffect(() => {
        if (activeId && active && active.unread_count > 0) {
            markRead.mutate(activeId);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [activeId]);

    // ── Helpers ───────────────────────────────────────────────────────────────
    // Gửi DM: có ảnh/tệp → gửi media (kèm caption); chỉ text → gửi text. Ném lỗi
    // lên composer để giữ ô soạn khi thất bại.
    const handleDmSubmit = async (p: ComposerSubmit) => {
        if (!activeId) return;
        try {
            if (p.file && p.kind) {
                await sendMedia.mutateAsync({ file: p.file, kind: p.kind, caption: p.text || undefined });
            } else {
                await sendText.mutateAsync({ body: p.text, message_tag: p.messageTag });
            }
        } catch (e) {
            message.error(errorMessage(e, 'Không gửi được tin.'));
            throw e;
        }
    };

    // Gửi trả lời CÔNG KHAI dưới comment, kèm ảnh tuỳ chọn. Nhắn riêng đã chuyển sang
    // nút + modal trên từng bình luận (không dùng tab trong ô soạn).
    const handleCommentSubmit = async (p: ComposerSubmit) => {
        if (!activeId) return;
        const image = p.kind === 'image' ? p.file : undefined;
        try {
            await replyComment.mutateAsync({ body: p.text, image });
        } catch (e) {
            message.error(errorMessage(e, 'Không gửi được trả lời.'));
            throw e;
        }
    };

    // Thích / bỏ thích 1 comment (optimistic, revert nếu lỗi).
    const handleCommentLike = (commentId: string) => {
        if (!activeId) return;
        const liked = likedComments.has(commentId);
        setLikedComments((prev) => {
            const n = new Set(prev);
            if (liked) n.delete(commentId); else n.add(commentId);
            return n;
        });
        likeComment.mutate(
            { conversationId: activeId, commentId, like: !liked },
            {
                onError: (e) => {
                    setLikedComments((prev) => {
                        const n = new Set(prev);
                        if (liked) n.add(commentId); else n.delete(commentId);
                        return n;
                    });
                    message.error(errorMessage(e, 'Không thực hiện được thao tác thích.'));
                },
            },
        );
    };

    // Xoá 1 comment. Xoá comment gốc (== external_conversation_id) ⇒ đóng hội thoại.
    const handleCommentDelete = (commentId: string) => {
        if (!activeId) return;
        const isRoot = commentId === active?.external_conversation_id;
        deleteComment.mutate(
            { conversationId: activeId, commentId },
            {
                onSuccess: () => {
                    message.success('Đã xoá bình luận.');
                    if (isRoot) setActiveId(null);
                },
                onError: (e) => message.error(errorMessage(e, 'Không xoá được bình luận.')),
            },
        );
    };

    // AI gợi ý → trả text điền vào composer (lỗi báo + trả rỗng để không ghi đè).
    const handleAiSuggest = async (): Promise<string> => {
        try {
            return (await aiSuggest.mutateAsync()).draft_text;
        } catch (e) {
            message.error(errorMessage(e, 'AI không phản hồi.'));
            return '';
        }
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
        board === 'facebook' && channelAccountId.length > 0,
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
                        mode="multiple"
                        allowClear
                        placeholder="Tất cả trang"
                        style={{ width: '100%' }}
                        value={channelAccountId}
                        onChange={(v) => setChannelAccountId((v as number[]) ?? [])}
                        maxTagCount="responsive"
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
        <div className="tag-attach-popover" style={{ width: 200 }}>
            <Text strong style={{ fontSize: 12, display: 'block', marginBottom: 8 }}>Gắn thẻ hội thoại</Text>
            {tags.length === 0 ? (
                <Text type="secondary" style={{ fontSize: 12 }}>Chưa có thẻ nào — tạo thẻ mới bên dưới.</Text>
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
            {/* Tạo thẻ mới + gắn ngay vào hội thoại */}
            <div style={{ marginTop: tags.length ? 8 : 4, paddingTop: tags.length ? 8 : 0, borderTop: tags.length ? '1px solid #F1F5F9' : 'none' }}>
                <Input
                    size="small"
                    placeholder="Tên thẻ mới…"
                    value={newTagName}
                    maxLength={50}
                    onChange={(e) => setNewTagName(e.target.value)}
                    onPressEnter={() => createAndAttachTag(c)}
                />
                <div style={{ display: 'flex', gap: 6, margin: '8px 0' }}>
                    {TAG_COLORS.map((col) => (
                        <span
                            key={col}
                            onClick={() => setNewTagColor(col)}
                            title={col}
                            style={{
                                width: 16, height: 16, borderRadius: '50%', background: col, cursor: 'pointer',
                                outline: newTagColor === col ? '2px solid #0F172A' : '1px solid #E2E8F0', outlineOffset: 1,
                            }}
                        />
                    ))}
                </div>
                <Button
                    size="small"
                    type="primary"
                    block
                    icon={<TagOutlined />}
                    loading={saveTag.isPending}
                    disabled={newTagName.trim() === ''}
                    onClick={() => createAndAttachTag(c)}
                >
                    Tạo &amp; gắn
                </Button>
            </div>
        </div>
    );

    return (
        <div>
            <MessagingNav />
            <TagManagerModal open={tagModalOpen} onClose={() => setTagModalOpen(false)} />
            {active?.thread_type === 'comment' && (
                <CommentPrivateMessageModal
                    open={privateMsgCommentId !== null}
                    onClose={() => setPrivateMsgCommentId(null)}
                    conversation={active}
                    commentId={privateMsgCommentId ?? undefined}
                    templates={templates}
                    onDmCreated={(dmId) => { setBoard('facebook'); setFbThreadType('all'); setActiveId(dmId); }}
                />
            )}
            <div style={{ display: 'flex', height: 'calc(100vh - 150px)', gap: 12, minWidth: 0 }}>
            {/* Cột trái — danh sách hội thoại */}
            <div style={{ flex: `0 0 ${screens.md ? 360 : 320}px`, minWidth: 320, maxWidth: 420, background: '#fff', borderRadius: 12, display: 'flex', flexDirection: 'column', overflow: 'hidden', minHeight: 0 }}>
                <div style={{ padding: 12, borderBottom: '1px solid #F1F5F9', display: 'flex', flexDirection: 'column', gap: 8 }}>
                    {/* 2 tab chính: Sàn / Facebook */}
                    <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                        {MARKETPLACE_CHAT_ENABLED ? (
                            <Segmented
                                block
                                style={{ flex: 1 }}
                                value={board}
                                onChange={(v) => { setBoard(v as 'marketplace' | 'facebook'); setActiveId(null); setChannelAccountId([]); setFbThreadType('all'); }}
                                options={[
                                    { label: 'Sàn', value: 'marketplace' },
                                    { label: 'Facebook', value: 'facebook' },
                                ]}
                            />
                        ) : (
                            // Tin nhắn sàn tắt tạm — chỉ còn Facebook.
                            <div style={{ flex: 1, fontWeight: 600, padding: '4px 8px' }}>Tin nhắn Facebook</div>
                        )}
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
                        {push.supported && (
                            <Tooltip title={push.enabled ? 'Đã bật thông báo trình duyệt' : 'Bật thông báo trình duyệt (khi đóng tab)'}>
                                <Button
                                    icon={push.enabled ? <BellFilled style={{ color: '#16A34A' }} /> : <BellOutlined />}
                                    onClick={() => { if (!push.enabled) void push.enable(); }}
                                />
                            </Tooltip>
                        )}
                    </div>
                    {/* Lọc loại hội thoại Facebook: tất cả / tin nhắn / bình luận */}
                    {board === 'facebook' && (
                        <Segmented
                            block
                            size="small"
                            value={fbThreadType}
                            onChange={(v) => { setFbThreadType(v as 'all' | 'message' | 'comment'); setActiveId(null); }}
                            options={[
                                { label: 'Tất cả', value: 'all' },
                                { label: <Space size={4}><MessageOutlined />Tin nhắn</Space>, value: 'message' },
                                { label: <Space size={4}><CommentOutlined />Bình luận</Space>, value: 'comment' },
                            ]}
                        />
                    )}
                </div>
                <div style={{ flex: 1, overflowY: 'auto' }} onScroll={handleConvScroll}>
                    {list.isLoading ? (
                        <div style={{ padding: 24, textAlign: 'center' }}><Spin /></div>
                    ) : conversations.length === 0 ? (
                        <Empty description="Chưa có hội thoại" style={{ marginTop: 48 }} />
                    ) : (
                        <>
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
                                        onClick={() => { setActiveId(c.id); setTagPopoverConvId(null); }}
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
                                                    {c.thread_type === 'comment'
                                                        ? <CommentAvatarStack avatars={c.comment?.participant_avatars} names={c.comment?.participants} size={40} />
                                                        : <Avatar size={40} src={c.buyer_avatar_url ?? undefined} style={{ background: '#2563EB', flexShrink: 0 }}>{convDisplayName(c).slice(0, 1).toUpperCase()}</Avatar>}
                                                </Badge>
                                            )}
                                            title={(
                                                <div style={{ minWidth: 0 }}>
                                                    {/* Row 1: unread badge + name + action menu */}
                                                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 4, minWidth: 0 }}>
                                                        <Space size={4} style={{ minWidth: 0, flex: 1, overflow: 'hidden' }}>
                                                            <Badge count={c.unread_count} size="small" />
                                                            <Text strong={c.unread_count > 0} ellipsis style={{ maxWidth: 160 }}>{convDisplayName(c)}</Text>
                                                            {c.order_id != null && (
                                                                <Tooltip title="Đã có đơn hàng">
                                                                    <ShoppingOutlined style={{ color: '#2563EB', flexShrink: 0 }} />
                                                                </Tooltip>
                                                            )}
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
                        {list.isFetchingNextPage && (
                            <div style={{ textAlign: 'center', padding: 12 }}><Spin size="small" /></div>
                        )}
                        </>
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
                                        {active?.thread_type === 'comment' ? (
                                            <span style={{ cursor: 'pointer', display: 'inline-flex' }} onClick={handleAvatarClick}>
                                                <CommentAvatarStack avatars={active.comment?.participant_avatars} names={active.comment?.participants} size={36} />
                                            </span>
                                        ) : (
                                            <Avatar
                                                src={active?.buyer_avatar_url ?? undefined}
                                                size={36}
                                                style={{ cursor: 'pointer', background: '#2563EB', flexShrink: 0 }}
                                                onClick={handleAvatarClick}
                                            >
                                                {(active ? convDisplayName(active) : '?').slice(0, 1).toUpperCase()}
                                            </Avatar>
                                        )}
                                    </Spin>
                                </Tooltip>
                                <div style={{ flex: 1 }}>
                                    <Text strong>{active ? convDisplayName(active) : ''}</Text>{' '}
                                    <Tag color="blue">{providerLabel(active?.provider ?? '')}</Tag>
                                    {active?.has_phone && active?.detected_phone && (
                                        <Tag icon={<PhoneOutlined />} color="green" style={{ marginInlineStart: 4 }}>{active.detected_phone}</Tag>
                                    )}
                                    {active?.channel_account_name && (
                                        <Text type="secondary" style={{ marginInlineStart: 4 }}>· {active.channel_account_name}</Text>
                                    )}
                                </div>
                                {/* Ẩn/hiện cả luồng bình luận — thao tác like/nhắn riêng/xoá nằm trên TỪNG comment bên dưới */}
                                {active?.thread_type === 'comment' && active.comment && (
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
                                )}
                            </div>
                        </div>
                        <div style={{ flex: 1, overflowY: 'auto', padding: 16, background: '#F8FAFC' }}>
                            {/* Post card bài viết (comment thread) — ghim đầu, cuộn cùng thread */}
                            {active?.thread_type === 'comment' && active.comment && (
                                <>
                                    <CommentPostCard conversation={active} />
                                    <Divider style={{ margin: '4px 0 12px', fontSize: 12, color: '#94A3B8' }}>Bình luận</Divider>
                                </>
                            )}
                            {thread.isLoading ? (
                                <div style={{ textAlign: 'center', marginTop: 48 }}><Spin /></div>
                            ) : (
                                (thread.data?.messages ?? []).map((m, i, arr) => {
                                    const isOut = m.direction === 'outbound';
                                    const showAvatar = arr[i + 1]?.direction !== m.direction; // tin cuối 1 cụm cùng chiều
                                    return (
                                    <div key={m.id} style={{ display: 'flex', gap: 8, alignItems: 'flex-end', justifyContent: isOut ? 'flex-end' : 'flex-start', marginBottom: m.reaction ? 18 : 8 }}>
                                        {!isOut && (showAvatar
                                            ? <MsgAvatar src={active?.buyer_avatar_url} name={active?.buyer_name ?? active?.buyer_external_id} />
                                            : <span style={{ width: 28, flexShrink: 0 }} />)}
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
                                                        <MessageAttachmentView att={a} />
                                                    </div>
                                                ))}
                                                {m.body != null && <div style={{ whiteSpace: 'pre-wrap' }}><MessageBody text={m.body} /></div>}
                                                {(m.buttons ?? []).length > 0 && (
                                                    <MessageButtons buttons={m.buttons!} outbound={m.direction === 'outbound'} />
                                                )}
                                                {m.body == null && (m.attachments ?? []).length === 0 && (m.buttons ?? []).length === 0 && (
                                                    <div style={{ fontStyle: 'italic', opacity: 0.7 }}>{KIND_LABEL[m.kind] ?? m.kind}</div>
                                                )}
                                                <div style={{ fontSize: 10, opacity: 0.6, textAlign: m.direction === 'outbound' ? 'right' : 'left', marginTop: 2 }}>
                                                    {fmtMsgTime(m.sent_at ?? m.created_at)}
                                                    {m.direction === 'outbound' && (
                                                        <> · {m.delivery_status === 'failed' && m.failure_code
                                                            ? (FAILURE_LABEL[m.failure_code] ?? DELIVERY_STATUS_LABEL.failed)
                                                            : (DELIVERY_STATUS_LABEL[m.delivery_status ?? ''] ?? m.delivery_status)}</>
                                                    )}
                                                </div>
                                                {/* Icon gửi lại — TÁCH khỏi div mờ (opacity 0.6) để hiện ĐỎ rõ. */}
                                                {m.direction === 'outbound' && m.delivery_status === 'failed' && (
                                                    <div style={{ textAlign: 'right', marginTop: 2 }}>
                                                        <RedoOutlined
                                                            role="button"
                                                            aria-label="Gửi lại"
                                                            title="Gửi lại"
                                                            spin={resend.isPending && resend.variables === m.id}
                                                            onClick={() => resend.mutate(m.id)}
                                                            style={{ cursor: 'pointer', color: '#ff4d4f', fontSize: 14 }}
                                                        />
                                                    </div>
                                                )}
                                            </div>
                                            {/* Hàng thao tác trên bình luận của khách: Thích · Nhắn riêng · Xoá */}
                                            {active?.thread_type === 'comment' && !isOut && m.external_message_id && (
                                                <Space size={2} style={{ marginTop: 2 }}>
                                                    <Tooltip title={likedComments.has(m.external_message_id) ? 'Bỏ thích' : 'Thích bình luận'}>
                                                        <Button
                                                            type="text"
                                                            size="small"
                                                            icon={likedComments.has(m.external_message_id)
                                                                ? <LikeFilled style={{ color: '#1677ff' }} />
                                                                : <LikeOutlined />}
                                                            onClick={() => handleCommentLike(m.external_message_id!)}
                                                        />
                                                    </Tooltip>
                                                    <Tooltip title="Nhắn tin riêng">
                                                        <Button
                                                            type="text"
                                                            size="small"
                                                            icon={<MessageOutlined />}
                                                            onClick={() => setPrivateMsgCommentId(m.external_message_id!)}
                                                        />
                                                    </Tooltip>
                                                    <Popconfirm
                                                        title="Xoá bình luận này?"
                                                        description="Hành động này không thể hoàn tác."
                                                        okText="Xoá"
                                                        cancelText="Huỷ"
                                                        okButtonProps={{ danger: true }}
                                                        onConfirm={() => handleCommentDelete(m.external_message_id!)}
                                                    >
                                                        <Tooltip title="Xoá bình luận">
                                                            <Button type="text" size="small" danger icon={<DeleteOutlined />} loading={deleteComment.isPending} />
                                                        </Tooltip>
                                                    </Popconfirm>
                                                </Space>
                                            )}
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
                                        {isOut && (showAvatar
                                            ? <MsgAvatar src={active?.channel_account_avatar_url} name={active?.channel_account_name} page />
                                            : <span style={{ width: 28, flexShrink: 0 }} />)}
                                    </div>
                                    );
                                })
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
                        <MessageComposer
                            key={`cmt-${activeId}`}
                            mode="comment"
                            provider={active.provider}
                            templates={templates}
                            aiAvailable
                            onAiSuggest={handleAiSuggest}
                            onSubmit={handleCommentSubmit}
                        />
                        ) : (
                        <MessageComposer
                            key={`dm-${activeId}`}
                            mode="dm"
                            provider={active?.provider ?? ''}
                            templates={templates}
                            needsTag={needsTag}
                            aiAvailable
                            onAiSuggest={handleAiSuggest}
                            onSubmit={handleDmSubmit}
                        />
                        )}
                    </>
                )}
            </div>

            {/* Cột phải — panel thông tin + đơn hàng (chỉ ≥1200px) */}
            {screens.xl && (
            <div style={{ width: 280, background: '#fff', borderRadius: 12, padding: 16, minHeight: 0, flexShrink: 0, display: 'flex', flexDirection: 'column', overflowY: 'auto' }}>
                <Text strong>Thông tin</Text>
                {active ? (
                    <>
                        <div style={{ marginTop: 12, display: 'flex', flexDirection: 'column', gap: 8 }}>
                            <div><Text type="secondary">Khách: </Text>{convDisplayName(active)}</div>
                            <div><Text type="secondary">Nguồn: </Text>{providerLabel(active.provider)}{active.channel_account_name ? ` · ${active.channel_account_name}` : ''}</div>
                            <div><Text type="secondary">Trạng thái: </Text>{active.status}</div>
                            {active.customer_id && <div><Text type="secondary">Khách hàng: </Text><Link to={`/customers/${active.customer_id}`}>Hồ sơ</Link></div>}
                        </div>
                        <ConversationOrderPanel conversation={active} />
                    </>
                ) : (
                    <div style={{ marginTop: 12 }}><Text type="secondary">Chọn hội thoại để xem chi tiết.</Text></div>
                )}
            </div>
            )}
            </div>
        </div>
    );
}
