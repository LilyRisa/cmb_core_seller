import { useEffect, useLayoutEffect, useRef, useState, type ReactNode } from 'react';
import dayjs from 'dayjs';
import { App, Button, Dropdown, Image, Input, Modal, Popover, Space, Spin, Typography } from 'antd';
import {
    CloseOutlined, FileOutlined, MessageOutlined, PaperClipOutlined, PictureOutlined,
    SendOutlined, SmileOutlined, SnippetsOutlined, SoundOutlined, VideoCameraOutlined,
} from '@ant-design/icons';
import Picker from '@emoji-mart/react';
import emojiData from '@emoji-mart/data';
import { errorMessage } from '@/lib/api';
import { type Conversation, type Message, useCommentLinkedDm, useConversationThread, useRenderBody, useSendCommentPrivateMessage } from '@/lib/messaging';
import type { MessageTemplate } from '@/lib/messagingConfig';

const { Text } = Typography;

/** Nhãn + icon cho đính kèm không phải ảnh (hiển thị gọn trong lịch sử). */
function attKindMeta(kind: string, mime: string): { icon: ReactNode; label: string } {
    if (kind === 'image' || kind === 'sticker') return { icon: <PictureOutlined />, label: 'Hình ảnh' };
    if (kind === 'video') return { icon: <VideoCameraOutlined />, label: 'Video' };
    if (kind === 'audio' || mime.startsWith('audio/')) return { icon: <SoundOutlined />, label: 'Âm thanh' };
    return { icon: <FileOutlined />, label: 'Tệp đính kèm' };
}

/** Giờ hiển thị 1 tin lịch sử: cùng ngày → HH:mm; khác ngày → DD/MM HH:mm. */
function fmtHistoryTime(iso: string | null): string {
    if (!iso) return '';
    const d = dayjs(iso);
    return d.isSame(dayjs(), 'day') ? d.format('HH:mm') : d.format('DD/MM HH:mm');
}

/** Bong bóng tin lịch sử DM (gọn) — inbound trái/xám, outbound phải/xanh. */
function HistoryBubble({ m }: { m: Message }) {
    const out = m.direction === 'outbound';
    return (
        <div style={{ display: 'flex', justifyContent: out ? 'flex-end' : 'flex-start' }}>
            <div style={{
                maxWidth: '85%', padding: '6px 10px', borderRadius: 10,
                background: out ? '#2563EB' : '#F1F5F9', color: out ? '#fff' : '#0F172A',
            }}>
                {m.sent_by_ai && <div style={{ fontSize: 10, opacity: 0.8, marginBottom: 2 }}>AI</div>}
                {(m.attachments ?? []).map((a) => {
                    const meta = attKindMeta(a.kind, a.mime);
                    return a.kind === 'image' && a.download_url ? (
                        <Image key={a.id} src={a.download_url} alt="" style={{ maxWidth: 160, borderRadius: 6, marginBottom: m.body ? 4 : 0 }} />
                    ) : (
                        <Space key={a.id} size={4} style={{ fontSize: 12, opacity: 0.9 }}>{meta.icon}{a.filename ?? meta.label}</Space>
                    );
                })}
                {m.body != null && m.body !== '' && <div style={{ whiteSpace: 'pre-wrap' }}>{m.body}</div>}
                <div style={{ fontSize: 10, opacity: 0.6, textAlign: out ? 'right' : 'left', marginTop: 2 }}>
                    {fmtHistoryTime(m.sent_at ?? m.created_at)}
                </div>
            </div>
        </div>
    );
}

type Kind = 'image' | 'video' | 'file';

interface Pending {
    file: File;
    kind: Kind;
    previewUrl?: string;
}

interface SentItem {
    text: string;
    files: { name: string; kind: Kind }[];
}

interface Props {
    open: boolean;
    onClose: () => void;
    conversation: Conversation;
    /** Comment cụ thể được bấm "Nhắn riêng" (rỗng ⇒ comment gốc). */
    commentId?: string;
    templates: MessageTemplate[];
    /** Khi đã tạo hộp thoại DM (sau lần nhắn riêng đầu) — mở hộp thoại đó khi đóng modal. */
    onDmCreated?: (dmConversationId: number) => void;
}

/**
 * Modal nhắn riêng cho người bình luận Facebook — KHÔNG dùng tab trong ô soạn.
 *
 * Đầy đủ tính năng: text + nhiều đính kèm (ảnh/video/file) + chèn mẫu tin + emoji.
 * Gửi 1 lần (BE gửi tuần tự: phần đầu qua comment_id lấy PSID, phần sau qua PSID —
 * Facebook chỉ cho nhắn riêng 1 lần/comment). Hiển thị các tin đã gửi trong phiên.
 */
export function CommentPrivateMessageModal({ open, onClose, conversation, commentId, templates, onDmCreated }: Props) {
    const { message } = App.useApp();
    const [text, setText] = useState('');
    const [pending, setPending] = useState<Pending[]>([]);
    const [emojiOpen, setEmojiOpen] = useState(false);
    const [sent, setSent] = useState<SentItem[]>([]);
    const [dmId, setDmId] = useState<number | null>(null);
    const fileInputRef = useRef<HTMLInputElement | null>(null);
    const pendingKindRef = useRef<Kind>('image');

    const send = useSendCommentPrivateMessage(conversation.id);
    const resolveBody = useRenderBody(conversation.id);

    // Hội thoại DM đã liên kết với người bình luận này (nếu từng nhắn riêng) → nạp lịch
    // sử tin nhắn trước đó vào modal. Ưu tiên DM vừa tạo trong phiên (dmId), rồi tới DM
    // đã liên kết. Facebook ẩn danh tính nên chỉ có sau lần nhắn riêng đầu tiên.
    const linkedDm = useCommentLinkedDm(conversation.id, commentId, open);
    const historyDmId = open ? (dmId ?? linkedDm.data ?? null) : null;
    const historyThread = useConversationThread(historyDmId);
    // Trang đầu = cửa sổ tin gần nhất (đã sắp cũ→mới trong 1 trang) — đủ ngữ cảnh trước đó.
    const historyMessages: Message[] = historyThread.data?.pages[0]?.messages ?? [];
    const historyLoading = historyDmId != null && historyThread.isLoading;
    const historyScrollRef = useRef<HTMLDivElement | null>(null);
    // Cuộn xuống đáy khi có thêm tin lịch sử / mở modal.
    useLayoutEffect(() => {
        const el = historyScrollRef.current;
        if (el) el.scrollTop = el.scrollHeight;
    }, [historyMessages.length, open]);

    // Dọn object URL preview khi đóng modal / đổi danh sách (tránh leak).
    useEffect(() => {
        if (!open) {
            setText('');
            setPending((prev) => { prev.forEach((p) => p.previewUrl && URL.revokeObjectURL(p.previewUrl)); return []; });
            setSent([]);
            setDmId(null);
        }
    }, [open]);

    // Đóng modal: nếu đã tạo hộp thoại DM thì mở hộp thoại đó để người dùng nhắn tiếp.
    const handleClose = () => {
        if (dmId !== null) onDmCreated?.(dmId);
        onClose();
    };

    const pickFile = (kind: Kind) => {
        pendingKindRef.current = kind;
        if (fileInputRef.current) {
            fileInputRef.current.accept = kind === 'image' ? 'image/*' : kind === 'video' ? 'video/*' : '*/*';
            fileInputRef.current.value = '';
            fileInputRef.current.click();
        }
    };

    const onFileChosen = (e: React.ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(e.target.files ?? []);
        if (files.length === 0) return;
        const kind = pendingKindRef.current;
        setPending((prev) => [
            ...prev,
            ...files.map((file) => ({ file, kind, previewUrl: kind === 'image' ? URL.createObjectURL(file) : undefined })),
        ]);
    };

    const removePending = (idx: number) => {
        setPending((prev) => {
            const next = [...prev];
            const [removed] = next.splice(idx, 1);
            if (removed?.previewUrl) URL.revokeObjectURL(removed.previewUrl);
            return next;
        });
    };

    const canSend = (text.trim() !== '' || pending.length > 0) && !send.isPending;

    const handleSend = async () => {
        if (!canSend) return;
        let body = text.trim();
        // Resolve biến `{{...}}` (mẫu tin chèn thô) → giá trị thật NGAY TRƯỚC khi gửi,
        // như MessageComposer — khách không được thấy token thô. Lỗi resolve ⇒ vẫn gửi
        // body thô (hiếm) hơn là kẹt không gửi được.
        if (/\{\{/.test(body)) {
            try {
                const r = await resolveBody.mutateAsync(body);
                body = r.text;
            } catch {
                // giữ nguyên body thô
            }
        }
        const files = pending.map((p) => p.file);
        try {
            const { delivered, total, dmConversationId } = await send.mutateAsync({ body, commentId, files });
            if (dmConversationId) setDmId(dmConversationId);
            // Báo cáo trung thực: Facebook chỉ chắc chắn nhận phần đầu (private reply);
            // phần sau cần khách mở hội thoại. Chỉ ghi log các phần thực sự đã gửi.
            const sentParts: { text: string; kind?: Kind }[] = [];
            if (body) sentParts.push({ text: body });
            pending.forEach((p) => sentParts.push({ text: '', kind: p.kind }));
            const deliveredParts = sentParts.slice(0, delivered);
            if (deliveredParts.length > 0) {
                setSent((prev) => [...prev, {
                    text: deliveredParts.filter((p) => p.text).map((p) => p.text).join('\n'),
                    files: deliveredParts.filter((p) => p.kind).map((p, i) => ({ name: pending[i]?.file.name ?? 'Tệp', kind: p.kind! })),
                }]);
            }
            setText('');
            setPending((prev) => { prev.forEach((p) => p.previewUrl && URL.revokeObjectURL(p.previewUrl)); return []; });
            if (delivered >= total) {
                message.success('Đã gửi tin nhắn riêng.');
            } else {
                message.warning(`Đã gửi ${delivered}/${total} phần. Các phần còn lại sẽ gửi được sau khi khách trả lời trong Messenger.`);
            }
        } catch (e) {
            message.error(errorMessage(e, 'Không gửi được tin nhắn riêng.'));
        }
    };

    const buyerName = conversation.buyer_name ?? 'người bình luận';

    return (
        <Modal
            open={open}
            onCancel={handleClose}
            title={<Space><MessageOutlined />Nhắn riêng cho {buyerName}</Space>}
            footer={null}
            destroyOnClose
            width={520}
        >
            <input ref={fileInputRef} type="file" multiple style={{ display: 'none' }} onChange={onFileChosen} />

            {/* Lịch sử hội thoại trước đó (khi người này đã có DM) — nạp từ hội thoại DM đã
                liên kết. Chưa có DM ⇒ hiện các tin vừa gửi trong phiên (tiếp xúc lần đầu). */}
            {historyDmId != null ? (
                <div style={{ marginBottom: 12 }}>
                    <Text type="secondary" style={{ fontSize: 12, display: 'block', marginBottom: 6 }}>Hội thoại trước đó</Text>
                    <div
                        ref={historyScrollRef}
                        style={{ maxHeight: 220, overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: 6, background: '#F8FAFC', border: '1px solid #F1F5F9', borderRadius: 8, padding: 8 }}
                    >
                        {historyLoading ? (
                            <div style={{ textAlign: 'center', padding: 12 }}><Spin size="small" /></div>
                        ) : historyMessages.length === 0 ? (
                            <Text type="secondary" style={{ fontSize: 12, textAlign: 'center' }}>Chưa có tin nhắn nào trước đó.</Text>
                        ) : (
                            historyMessages.map((m) => <HistoryBubble key={m.id} m={m} />)
                        )}
                    </div>
                </div>
            ) : sent.length > 0 ? (
                <div style={{ marginBottom: 12, maxHeight: 140, overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: 6 }}>
                    {sent.map((s, i) => (
                        <div key={i} style={{ alignSelf: 'flex-end', maxWidth: '85%', background: '#2563EB', color: '#fff', padding: '6px 10px', borderRadius: 10 }}>
                            {s.text && <div style={{ whiteSpace: 'pre-wrap' }}>{s.text}</div>}
                            {s.files.map((f, j) => (
                                <Space key={j} size={4} style={{ fontSize: 12, opacity: 0.9 }}>
                                    {f.kind === 'image' ? <PictureOutlined /> : f.kind === 'video' ? <VideoCameraOutlined /> : <FileOutlined />}
                                    {f.name}
                                </Space>
                            ))}
                        </div>
                    ))}
                </div>
            ) : null}

            {/* Đính kèm đang chờ gửi */}
            {pending.length > 0 && (
                <div style={{ marginBottom: 8, display: 'flex', flexWrap: 'wrap', gap: 8 }}>
                    {pending.map((p, idx) => (
                        <div key={idx} style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: 6, border: '1px solid #E2E8F0', borderRadius: 8 }}>
                            {p.kind === 'image' && p.previewUrl
                                ? <Image src={p.previewUrl} alt="" width={48} height={48} style={{ objectFit: 'cover', borderRadius: 6 }} preview={false} />
                                : <Space size={4}>{p.kind === 'video' ? <VideoCameraOutlined /> : <FileOutlined />}<span style={{ fontSize: 12, maxWidth: 140, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{p.file.name}</span></Space>}
                            <Button size="small" type="text" icon={<CloseOutlined />} title="Bỏ tệp" onClick={() => removePending(idx)} />
                        </div>
                    ))}
                </div>
            )}

            <Input.TextArea
                value={text}
                onChange={(e) => setText(e.target.value)}
                placeholder="Nhập tin nhắn riêng…"
                autoSize={{ minRows: 3, maxRows: 8 }}
            />

            <Space style={{ marginTop: 8, justifyContent: 'space-between', width: '100%' }}>
                <Space size={4}>
                    <Button icon={<PictureOutlined />} title="Gửi ảnh" onClick={() => pickFile('image')} />
                    <Button icon={<VideoCameraOutlined />} title="Gửi video" onClick={() => pickFile('video')} />
                    <Button icon={<PaperClipOutlined />} title="Gửi tài liệu" onClick={() => pickFile('file')} />
                    <Popover
                        open={emojiOpen}
                        onOpenChange={setEmojiOpen}
                        trigger="click"
                        content={<Picker data={emojiData} onEmojiSelect={(em: { native?: string }) => { if (em.native) { setText((d) => d + em.native); setEmojiOpen(false); } }} previewPosition="none" locale="vi" />}
                    >
                        <Button icon={<SmileOutlined />} title="Chèn emoji" />
                    </Popover>
                    <Dropdown
                        trigger={['click']}
                        disabled={templates.filter((t) => t.enabled).length === 0}
                        menu={{
                            items: templates.filter((t) => t.enabled).map((t) => ({ key: String(t.id), label: t.name })),
                            onClick: ({ key }) => {
                                const t = templates.find((x) => String(x.id) === key);
                                if (t) setText(t.body);
                            },
                        }}
                    >
                        <Button icon={<SnippetsOutlined />} title="Chèn mẫu tin">Mẫu tin</Button>
                    </Dropdown>
                </Space>
                <Button type="primary" icon={<SendOutlined />} loading={send.isPending} onClick={() => void handleSend()} disabled={!canSend}>
                    Gửi
                </Button>
            </Space>

            <div style={{ marginTop: 10 }}>
                <Text type="secondary" style={{ fontSize: 12 }}>
                    Facebook chỉ cho nhắn riêng 1 lần cho mỗi bình luận; các tin tiếp theo gửi qua hội thoại tin nhắn của khách.
                </Text>
            </div>
        </Modal>
    );
}
