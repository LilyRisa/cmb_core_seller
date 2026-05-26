import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Button, Image, Input, Popover, Radio, Segmented, Space, Tag } from 'antd';
import { CloseOutlined, FileOutlined, PaperClipOutlined, PictureOutlined, RobotOutlined, SendOutlined, SmileOutlined, VideoCameraOutlined } from '@ant-design/icons';
import Picker from '@emoji-mart/react';
import emojiData from '@emoji-mart/data';
import type { MessageTemplate } from '@/lib/messagingConfig';

/**
 * Composer gửi tin DÙNG CHUNG cho mọi nơi (tin nhắn DM + bình luận + nhắn riêng).
 *
 * Vì sao tách: trước đây MessagingPage có 3 ô soạn rời, chỉ ô DM mới gửi được
 * ảnh / chèn mẫu / AI gợi ý — không đồng bộ. Component này gom 1 mối: mọi nơi
 * đều có ảnh (theo capability provider) + mẫu nhanh (slash `/`) + emoji + AI gợi ý.
 *
 * Tự quản state (text, ảnh đính kèm, emoji, slash, đích comment) — không rò rỉ
 * sang nơi khác. Gửi qua `onSubmit` (parent route tới đúng API theo mode).
 */

export type ComposerMode = 'dm' | 'comment';

export interface CommentTarget {
    public: boolean;
    private: boolean;
}

export interface ComposerSubmit {
    text: string;
    file?: File;
    kind?: 'image' | 'video' | 'file';
    /** DM ngoài cửa sổ 24h Facebook — message tag đã chọn. */
    messageTag?: string;
    /** Mode comment — đích gửi (công khai / nhắn riêng / cả hai). */
    commentTarget?: CommentTarget;
}

interface Props {
    mode: ComposerMode;
    provider: string;
    templates: MessageTemplate[];
    /** DM Facebook quá 24h: bắt buộc chọn message tag. */
    needsTag?: boolean;
    /** Hiện nút AI gợi ý (có provider AI). */
    aiAvailable?: boolean;
    /** Sinh gợi ý AI → trả text để điền vào ô soạn. */
    onAiSuggest?: () => Promise<string>;
    /** Gửi. Trả Promise: resolve ⇒ xoá ô soạn; reject ⇒ giữ nguyên (lỗi parent tự báo). */
    onSubmit: (payload: ComposerSubmit) => Promise<unknown>;
}

/** Media mỗi provider hỗ trợ (đồng bộ capability connector backend). */
const MEDIA_CAPS: Record<string, { image: boolean; video: boolean; file: boolean }> = {
    facebook_page: { image: true, video: true, file: true },
    tiktok_chat: { image: true, video: false, file: false },
    shopee_chat: { image: true, video: false, file: false },
    lazada_chat: { image: true, video: false, file: false },
    manual: { image: true, video: true, file: true },
};

const FB_MESSAGE_TAGS: Array<{ label: string; value: string }> = [
    { label: 'Nhân viên (7 ngày)', value: 'HUMAN_AGENT' },
    { label: 'Xác nhận sự kiện', value: 'CONFIRMED_EVENT_UPDATE' },
    { label: 'Sau mua hàng', value: 'POST_PURCHASE_UPDATE' },
    { label: 'Cập nhật tài khoản', value: 'ACCOUNT_UPDATE' },
];

const SLASH_RE = /^\/(\S*)$/;

export function MessageComposer({ mode, provider, templates, needsTag, aiAvailable, onAiSuggest, onSubmit }: Props) {
    const [text, setText] = useState('');
    const [pending, setPending] = useState<{ file: File; kind: 'image' | 'video' | 'file'; previewUrl?: string } | null>(null);
    const [emojiOpen, setEmojiOpen] = useState(false);
    const [msgTag, setMsgTag] = useState('HUMAN_AGENT');
    const [target, setTarget] = useState<'public' | 'private' | 'both'>('public');
    const [busy, setBusy] = useState(false);
    const [aiLoading, setAiLoading] = useState(false);
    const fileInputRef = useRef<HTMLInputElement | null>(null);
    const pendingKindRef = useRef<'image' | 'video' | 'file'>('image');

    // Comment: ảnh chỉ qua Facebook (image). DM: theo capability provider.
    const caps = mode === 'comment'
        ? { image: true, video: false, file: false }
        : (MEDIA_CAPS[provider] ?? { image: true, video: false, file: false });

    // ── Slash-command template ────────────────────────────────────────────────
    const slashMatch = SLASH_RE.exec(text);
    const slashQuery = slashMatch ? slashMatch[1].toLowerCase() : null;
    const slashOpen = slashQuery !== null;
    const slashMatches = useMemo<MessageTemplate[]>(() => {
        if (slashQuery === null) return [];
        const enabled = templates.filter((t) => t.enabled);
        if (slashQuery === '') return enabled;
        return enabled.filter((t) =>
            (t.shortcut_key && t.shortcut_key.toLowerCase().startsWith(slashQuery)) || t.name.toLowerCase().includes(slashQuery));
    }, [slashQuery, templates]);
    const [slashHighlight, setSlashHighlight] = useState(0);
    useEffect(() => { setSlashHighlight(0); }, [slashMatches]);

    const applyTemplate = useCallback((t: MessageTemplate) => setText(t.body), []);

    // Dọn preview URL khi đổi/huỷ ảnh (tránh leak object URL).
    useEffect(() => () => { if (pending?.previewUrl) URL.revokeObjectURL(pending.previewUrl); }, [pending]);

    const pickFile = (kind: 'image' | 'video' | 'file') => {
        pendingKindRef.current = kind;
        if (fileInputRef.current) {
            fileInputRef.current.accept = kind === 'image' ? 'image/*' : kind === 'video' ? 'video/*' : '*/*';
            fileInputRef.current.value = '';
            fileInputRef.current.click();
        }
    };

    const onFileChosen = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        const kind = pendingKindRef.current;
        if (pending?.previewUrl) URL.revokeObjectURL(pending.previewUrl);
        setPending({ file, kind, previewUrl: kind === 'image' ? URL.createObjectURL(file) : undefined });
    };

    const clearPending = () => {
        if (pending?.previewUrl) URL.revokeObjectURL(pending.previewUrl);
        setPending(null);
    };

    const canSend = (text.trim() !== '' || pending !== null) && !busy;

    const submit = async () => {
        if (!canSend) return;
        const payload: ComposerSubmit = { text: text.trim() };
        if (pending) { payload.file = pending.file; payload.kind = pending.kind; }
        if (mode === 'dm' && needsTag) payload.messageTag = msgTag;
        if (mode === 'comment') {
            payload.commentTarget = { public: target === 'public' || target === 'both', private: target === 'private' || target === 'both' };
        }
        setBusy(true);
        try {
            await onSubmit(payload);
            setText('');
            clearPending();
        } catch {
            // Lỗi do parent báo (message.error) — giữ nguyên ô soạn để gửi lại.
        } finally {
            setBusy(false);
        }
    };

    const runAi = async () => {
        if (!onAiSuggest) return;
        setAiLoading(true);
        try {
            const draft = await onAiSuggest();
            if (draft) setText(draft);
        } finally {
            setAiLoading(false);
        }
    };

    const handleEmoji = (emoji: { native?: string }) => {
        if (emoji.native) { setText((d) => d + emoji.native); setEmojiOpen(false); }
    };

    const placeholder = mode === 'comment'
        ? 'Trả lời bình luận… (Enter để gửi, Shift+Enter xuống dòng; /slug để chèn mẫu)'
        : 'Nhập tin nhắn… (Enter để gửi, Shift+Enter xuống dòng; /slug để chèn mẫu)';

    return (
        <div style={{ padding: 12, borderTop: '1px solid #F1F5F9' }}>
            {/* input file ẩn (1 cái cho mọi loại — accept set động) */}
            <input ref={fileInputRef} type="file" style={{ display: 'none' }} onChange={onFileChosen} />

            {/* DM Facebook quá 24h: chọn message tag */}
            {mode === 'dm' && needsTag && (
                <div style={{ marginBottom: 8 }}>
                    <span style={{ fontSize: 12, color: '#64748B', display: 'block', marginBottom: 4 }}>
                        Quá 24h từ tin cuối của khách — chọn loại thẻ tin nhắn để gửi (Facebook yêu cầu):
                    </span>
                    <Radio.Group size="small" optionType="button" buttonStyle="solid" options={FB_MESSAGE_TAGS} value={msgTag} onChange={(e) => setMsgTag(e.target.value)} />
                </div>
            )}

            {/* Comment: chọn đích gửi (đồng bộ với cấu hình tự động trả lời) */}
            {mode === 'comment' && (
                <div style={{ marginBottom: 8 }}>
                    <Segmented
                        size="small"
                        value={target}
                        onChange={(v) => setTarget(v as 'public' | 'private' | 'both')}
                        options={[
                            { label: 'Trả lời công khai', value: 'public' },
                            { label: 'Nhắn riêng', value: 'private' },
                            { label: 'Cả hai', value: 'both' },
                        ]}
                    />
                </div>
            )}

            {/* Ảnh/tệp đính kèm đang chờ gửi */}
            {pending && (
                <div style={{ marginBottom: 8, display: 'inline-flex', alignItems: 'center', gap: 8, padding: 6, border: '1px solid #E2E8F0', borderRadius: 8, position: 'relative' }}>
                    {pending.kind === 'image' && pending.previewUrl
                        ? <Image src={pending.previewUrl} alt="" width={64} height={64} style={{ objectFit: 'cover', borderRadius: 6 }} preview={false} />
                        : <Space size={4}>{pending.kind === 'video' ? <VideoCameraOutlined /> : <FileOutlined />}<span style={{ fontSize: 12, maxWidth: 180, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{pending.file.name}</span></Space>}
                    <Button size="small" type="text" icon={<CloseOutlined />} title="Bỏ tệp" onClick={clearPending} />
                </div>
            )}

            <Popover
                open={slashOpen}
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
                                    onClick={() => applyTemplate(t)}
                                    onMouseEnter={() => setSlashHighlight(idx)}
                                    style={{ padding: '6px 10px', borderRadius: 6, cursor: 'pointer', background: idx === slashHighlight ? '#EFF6FF' : undefined, display: 'flex', alignItems: 'baseline', gap: 8 }}
                                >
                                    <span style={{ fontWeight: 600, fontSize: 13, flex: '0 0 auto' }}>{t.name}</span>
                                    {t.shortcut_key && <Tag style={{ marginInlineEnd: 0, fontFamily: 'monospace', fontSize: 11 }}>/{t.shortcut_key}</Tag>}
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
                    value={text}
                    onChange={(e) => setText(e.target.value)}
                    placeholder={placeholder}
                    autoSize={{ minRows: 3, maxRows: 10 }}
                    onPressEnter={(e) => {
                        if (e.shiftKey) return;
                        if (slashOpen && slashMatches.length > 0) { e.preventDefault(); applyTemplate(slashMatches[slashHighlight] ?? slashMatches[0]); return; }
                        e.preventDefault();
                        void submit();
                    }}
                    onKeyDown={(e) => {
                        if (!slashOpen || slashMatches.length === 0) return;
                        if (e.key === 'ArrowDown') { e.preventDefault(); setSlashHighlight((h) => Math.min(h + 1, slashMatches.length - 1)); }
                        else if (e.key === 'ArrowUp') { e.preventDefault(); setSlashHighlight((h) => Math.max(h - 1, 0)); }
                        else if (e.key === 'Escape') { e.preventDefault(); setText(''); }
                    }}
                />
            </Popover>

            <Space style={{ marginTop: 8, justifyContent: 'space-between', width: '100%' }}>
                <Space size={4}>
                    {caps.image && <Button icon={<PictureOutlined />} title="Gửi ảnh" onClick={() => pickFile('image')} />}
                    {caps.video && <Button icon={<VideoCameraOutlined />} title="Gửi video" onClick={() => pickFile('video')} />}
                    {caps.file && <Button icon={<PaperClipOutlined />} title="Gửi tài liệu" onClick={() => pickFile('file')} />}
                    <Popover
                        open={emojiOpen}
                        onOpenChange={setEmojiOpen}
                        trigger="click"
                        content={<Picker data={emojiData} onEmojiSelect={(e: { native?: string }) => handleEmoji(e)} previewPosition="none" locale="vi" />}
                    >
                        <Button icon={<SmileOutlined />} title="Chèn emoji" />
                    </Popover>
                    {aiAvailable && onAiSuggest && (
                        <Button icon={<RobotOutlined />} loading={aiLoading} onClick={() => void runAi()}>AI gợi ý</Button>
                    )}
                </Space>
                <Button type="primary" icon={<SendOutlined />} loading={busy} onClick={() => void submit()} disabled={!canSend}>
                    {mode === 'comment' ? 'Gửi' : 'Gửi'}
                </Button>
            </Space>
        </div>
    );
}
