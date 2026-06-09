import { Fragment, useEffect, useMemo, useRef, useState } from 'react';
import dayjs from 'dayjs';
import { App, Button, Input } from 'antd';
import {
    CheckCircleFilled, ClockCircleOutlined, CloseOutlined,
    CustomerServiceOutlined, LockOutlined, PaperClipOutlined, SendOutlined,
} from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import {
    type SupportConversation, type SupportMessage,
    useMarkSupportRead, useSendSupportMessage, useSupportConversations,
} from '@/lib/support';
import { DISPLAY_TZ, formatDate } from '@/lib/format';
import { SupportAttachmentList } from './SupportAttachmentList';

const MAX_FILES = 5;
const ACCEPT = 'image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt';

/** Giờ gọn theo giờ VN: hôm nay → HH:mm, khác ngày → DD/MM HH:mm. */
function fmtTime(iso: string | null): string {
    if (!iso) return '';
    const d = dayjs(iso).tz(DISPLAY_TZ);
    if (!d.isValid()) return '';
    const now = dayjs().tz(DISPLAY_TZ);
    return d.isSame(now, 'day') ? d.format('HH:mm') : d.format('DD/MM HH:mm');
}

/** Ngày + giờ đầy đủ (cho mốc đã đóng), theo giờ VN. */
function fmtFull(iso: string | null): string {
    if (!iso) return '';
    return formatDate(iso);
}

function humanSize(bytes: number): string {
    if (bytes <= 0) return '';
    const u = ['B', 'KB', 'MB', 'GB'];
    let n = bytes;
    let i = 0;
    while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
    return `${n.toFixed(n >= 10 || i === 0 ? 0 : 1)} ${u[i]}`;
}

/** 1 tin: hệ thống (canh giữa), user (phải, xanh), CSKH (trái, xám). */
function MessageRow({ m }: { m: SupportMessage }) {
    if (m.type === 'system') {
        return (
            <div style={{ alignSelf: 'center', maxWidth: '92%', textAlign: 'center', color: '#94A3B8', fontSize: 12, padding: '6px 0', display: 'flex', alignItems: 'center', gap: 6 }}>
                <CheckCircleFilled style={{ color: '#94A3B8' }} />
                <span>{m.body}</span>
            </div>
        );
    }

    const isUser = m.sender === 'user';
    return (
        <div style={{ alignSelf: isUser ? 'flex-end' : 'flex-start', maxWidth: '85%' }}>
            {!isUser && (
                <div style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 11, color: '#64748B', marginBottom: 2 }}>
                    <CustomerServiceOutlined /> Nhân viên CSKH
                </div>
            )}
            {m.body && m.body.trim() !== '' && (
                <div style={{
                    background: isUser ? '#2563EB' : '#fff', color: isUser ? '#fff' : '#0F172A',
                    border: isUser ? 'none' : '1px solid #E2E8F0', padding: '8px 12px', whiteSpace: 'pre-wrap',
                    borderRadius: isUser ? '12px 12px 2px 12px' : '12px 12px 12px 2px',
                }}>{m.body}</div>
            )}
            <SupportAttachmentList attachments={m.attachments} />
            <div style={{ fontSize: 10, color: '#94A3B8', textAlign: isUser ? 'right' : 'left', marginTop: 2 }}>{fmtTime(m.created_at)}</div>
        </div>
    );
}

/**
 * Tab "Hỏi CSKH" — hội thoại nhiều tin như app nhắn tin + đính kèm file/ảnh/video.
 *
 * Hiển thị TẤT CẢ cuộc theo thứ tự thời gian thành luồng liên tục; cuộc đã đóng có tin
 * hệ thống báo đóng. Gửi tin khi cuộc gần nhất đã đóng ⇒ backend tự mở cuộc MỚI. Vào tab
 * ⇒ đánh dấu đã đọc (badge về 0). Phát hiện tin mới + âm thanh nằm ở HelpChatWidget.
 */
export function CskhTab({ active }: { active: boolean }) {
    const { message } = App.useApp();
    const convos = useSupportConversations(active);
    const send = useSendSupportMessage();
    const { mutate: markReadConv } = useMarkSupportRead();
    const [text, setText] = useState('');
    const [files, setFiles] = useState<File[]>([]);
    const bottomRef = useRef<HTMLDivElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    // Backend trả mới-nhất-trước; đảo lại để hiển thị cũ→mới (luồng liên tục).
    const conversations: SupportConversation[] = useMemo(() => [...(convos.data ?? [])].reverse(), [convos.data]);
    const latestClosed = (convos.data ?? [])[0]?.status === 'closed';
    const isEmpty = conversations.length === 0;

    // Vào tab / có tin mới ⇒ đánh dấu các cuộc còn unread là đã đọc (badge về 0).
    useEffect(() => {
        if (!active || !convos.data) return;
        convos.data.filter((c) => c.user_unread_count > 0).forEach((c) => markReadConv(c.id));
    }, [active, convos.data, markReadConv]);

    // Cuộn xuống đáy khi có tin mới / mở tab.
    useEffect(() => { bottomRef.current?.scrollIntoView({ behavior: 'smooth' }); }, [convos.data, active]);

    const onPick = (e: React.ChangeEvent<HTMLInputElement>) => {
        const picked = Array.from(e.target.files ?? []);
        setFiles((prev) => {
            const merged = [...prev, ...picked];
            if (merged.length > MAX_FILES) message.warning(`Tối đa ${MAX_FILES} tệp mỗi tin nhắn.`);
            return merged.slice(0, MAX_FILES);
        });
        e.target.value = ''; // cho phép chọn lại cùng tệp
    };

    const submit = () => {
        const body = text.trim();
        if ((body === '' && files.length === 0) || send.isPending) return;
        send.mutate(
            { body: body === '' ? undefined : body, files },
            {
                onSuccess: () => { setText(''); setFiles([]); },
                onError: (e) => message.error(errorMessage(e, 'Không gửi được, vui lòng thử lại.')),
            },
        );
    };

    return (
        <div style={{ display: 'flex', flexDirection: 'column', height: '100%' }}>
            <div style={{ flex: 1, overflowY: 'auto', padding: 12, display: 'flex', flexDirection: 'column', gap: 4, background: '#F8FAFC' }}>
                {isEmpty && (
                    <div style={{ margin: 'auto', textAlign: 'center', color: '#94A3B8', padding: 16 }}>
                        <CustomerServiceOutlined style={{ fontSize: 32, marginBottom: 8 }} />
                        <div>Gửi câu hỏi cho nhân viên CSKH</div>
                        <div style={{ fontSize: 12, marginTop: 4 }}>Kèm được ảnh / video / tệp để mô tả vấn đề.</div>
                    </div>
                )}

                {conversations.map((conv, idx) => (
                    <Fragment key={conv.id}>
                        {idx > 0 && <div style={{ borderTop: '1px dashed #E2E8F0', margin: '8px 0' }} />}
                        {conv.status === 'closed' ? (
                            // Đã đóng ⇒ KHÔNG hiện lại tin cũ, chỉ mã hội thoại + thời điểm đóng.
                            <div style={{ alignSelf: 'center', textAlign: 'center', color: '#94A3B8', fontSize: 12, padding: '10px 0', display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 2 }}>
                                <span style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                                    <LockOutlined /> Đoạn hội thoại #{conv.id} đã đóng
                                </span>
                                {conv.closed_at && <span>lúc {fmtFull(conv.closed_at)}</span>}
                            </div>
                        ) : (
                            <>
                                {conv.messages.map((m) => <MessageRow key={m.id} m={m} />)}
                                {conv.last_sender === 'user' && (
                                    <div style={{ alignSelf: 'flex-start', fontSize: 12, color: '#D97706', display: 'flex', alignItems: 'center', gap: 4, padding: '2px 4px' }}>
                                        <ClockCircleOutlined /> Đang chờ CSKH phản hồi…
                                    </div>
                                )}
                            </>
                        )}
                    </Fragment>
                ))}
                <div ref={bottomRef} />
            </div>

            {/* Preview tệp đã chọn */}
            {files.length > 0 && (
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6, padding: '8px 10px 0', borderTop: '1px solid #F1F5F9' }}>
                    {files.map((f, i) => (
                        <span key={i} style={{ display: 'inline-flex', alignItems: 'center', gap: 6, background: '#EFF6FF', border: '1px solid #BFDBFE', borderRadius: 6, padding: '2px 6px', fontSize: 12, maxWidth: 200 }}>
                            <PaperClipOutlined style={{ color: '#2563EB' }} />
                            <span style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{f.name}</span>
                            <span style={{ color: '#94A3B8' }}>{humanSize(f.size)}</span>
                            <CloseOutlined style={{ cursor: 'pointer', fontSize: 11 }} onClick={() => setFiles((prev) => prev.filter((_, j) => j !== i))} />
                        </span>
                    ))}
                </div>
            )}

            <div style={{ padding: 10, borderTop: files.length > 0 ? 'none' : '1px solid #F1F5F9', display: 'flex', gap: 8, alignItems: 'flex-end' }}>
                <input ref={fileInputRef} type="file" multiple accept={ACCEPT} style={{ display: 'none' }} onChange={onPick} />
                <Button
                    icon={<PaperClipOutlined />}
                    onClick={() => fileInputRef.current?.click()}
                    disabled={files.length >= MAX_FILES}
                    title="Đính kèm ảnh / video / tệp"
                />
                <Input.TextArea
                    value={text}
                    onChange={(e) => setText(e.target.value)}
                    placeholder={latestClosed ? 'Nhập tin để mở cuộc trò chuyện mới…' : 'Nhập câu hỏi gửi CSKH…'}
                    autoSize={{ minRows: 1, maxRows: 4 }}
                    onPressEnter={(e) => { if (!e.shiftKey) { e.preventDefault(); submit(); } }}
                />
                <Button
                    type="primary"
                    icon={<SendOutlined />}
                    loading={send.isPending}
                    onClick={submit}
                    disabled={text.trim() === '' && files.length === 0}
                />
            </div>
        </div>
    );
}
