import { useCallback, useEffect, useMemo, useRef, useState, type CSSProperties } from 'react';
import { App, Button, Input, Spin, Tabs, Tag, Typography } from 'antd';
import {
    CloseOutlined, CommentOutlined, CustomerServiceOutlined, RobotOutlined,
    SendOutlined, ClockCircleOutlined, CheckCircleFilled,
} from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { type ChatTurn, type HelpSource, type SupportRequestItem, useAskAssistant, useCreateSupportRequest, useSupportRequests } from '@/lib/support';

const { Text } = Typography;

const POS_KEY = 'omnisell.help-widget.pos';
const BTN = 56;        // đường kính nút
const PANEL_W = 360;
const PANEL_H = 500;
const DRAG_THRESHOLD = 5;

interface Pos { x: number; y: number }

/** Vị trí nút mặc định: góc dưới-phải. */
function defaultPos(): Pos {
    if (typeof window === 'undefined') return { x: 24, y: 24 };
    return { x: window.innerWidth - BTN - 24, y: window.innerHeight - BTN - 24 };
}

function loadPos(): Pos {
    try {
        const raw = localStorage.getItem(POS_KEY);
        if (raw) {
            const p = JSON.parse(raw) as Pos;
            if (typeof p.x === 'number' && typeof p.y === 'number') return clamp(p);
        }
    } catch { /* ignore */ }
    return defaultPos();
}

function clamp(p: Pos): Pos {
    if (typeof window === 'undefined') return p;
    return {
        x: Math.max(8, Math.min(p.x, window.innerWidth - BTN - 8)),
        y: Math.max(8, Math.min(p.y, window.innerHeight - BTN - 8)),
    };
}

/** Tin trợ lý AI: bong bóng + nguồn tham khảo. */
function AssistantBubble({ text, sources }: { text: string; sources?: HelpSource[] }) {
    return (
        <div style={{ background: '#F1F5F9', color: '#0F172A', padding: '8px 12px', borderRadius: 12, maxWidth: '88%', alignSelf: 'flex-start' }}>
            <div style={{ whiteSpace: 'pre-wrap' }}>{text}</div>
            {sources && sources.length > 0 && (
                <div style={{ marginTop: 6, display: 'flex', flexWrap: 'wrap', gap: 4 }}>
                    {sources.map((s, i) => (
                        <Tag key={i} color="blue" style={{ marginInlineEnd: 0, fontSize: 11 }}>
                            {s.title}{s.screen ? ` · ${s.screen}` : ''}
                        </Tag>
                    ))}
                </div>
            )}
        </div>
    );
}

/** Tab "Hỏi AI" — chat RAG hỏi-đáp cách dùng hệ thống. */
function AiTab() {
    const { message } = App.useApp();
    const ask = useAskAssistant();
    const [turns, setTurns] = useState<ChatTurn[]>([]);
    const [sources, setSources] = useState<Record<number, HelpSource[]>>({});
    const [pending, setPending] = useState<string | null>(null);
    const [input, setInput] = useState('');
    const bottomRef = useRef<HTMLDivElement>(null);

    useEffect(() => { bottomRef.current?.scrollIntoView({ behavior: 'smooth' }); }, [turns, pending]);

    const send = async () => {
        const q = input.trim();
        if (q === '' || ask.isPending) return;
        setInput('');
        setPending(q);
        const history = turns.slice(-8);
        try {
            const res = await ask.mutateAsync({ question: q, history });
            setTurns((prev) => [...prev, { role: 'user', content: q }, { role: 'assistant', content: res.answer }]);
            // gắn nguồn vào tin cuối qua map riêng
            setSources((prev) => ({ ...prev, [turns.length + 1]: res.sources }));
        } catch (e) {
            message.error(errorMessage(e, 'Trợ lý chưa phản hồi được, vui lòng thử lại.'));
            setTurns((prev) => [...prev, { role: 'user', content: q }]);
        } finally {
            setPending(null);
        }
    };

    return (
        <div style={{ display: 'flex', flexDirection: 'column', height: '100%' }}>
            <div style={{ flex: 1, overflowY: 'auto', padding: 12, display: 'flex', flexDirection: 'column', gap: 8 }}>
                {turns.length === 0 && !pending && (
                    <div style={{ margin: 'auto', textAlign: 'center', color: '#94A3B8', padding: 16 }}>
                        <RobotOutlined style={{ fontSize: 32, marginBottom: 8 }} />
                        <div>Hỏi tôi về cách dùng hệ thống</div>
                        <div style={{ fontSize: 12, marginTop: 4 }}>VD: “Làm sao kết nối gian hàng?”, “Cách in tem hàng loạt?”</div>
                    </div>
                )}
                {turns.map((t, i) => t.role === 'user' ? (
                    <div key={i} style={{ background: '#2563EB', color: '#fff', padding: '8px 12px', borderRadius: 12, maxWidth: '88%', alignSelf: 'flex-end', whiteSpace: 'pre-wrap' }}>{t.content}</div>
                ) : (
                    <AssistantBubble key={i} text={t.content} sources={sources[i]} />
                ))}
                {pending && (
                    <>
                        <div style={{ background: '#2563EB', color: '#fff', padding: '8px 12px', borderRadius: 12, maxWidth: '88%', alignSelf: 'flex-end', whiteSpace: 'pre-wrap' }}>{pending}</div>
                        <div style={{ alignSelf: 'flex-start', padding: '8px 12px' }}><Spin size="small" /> <Text type="secondary" style={{ fontSize: 12 }}>Đang tìm trong tài liệu…</Text></div>
                    </>
                )}
                <div ref={bottomRef} />
            </div>
            <div style={{ padding: 10, borderTop: '1px solid #F1F5F9', display: 'flex', gap: 8 }}>
                <Input.TextArea
                    value={input}
                    onChange={(e) => setInput(e.target.value)}
                    placeholder="Nhập câu hỏi…"
                    autoSize={{ minRows: 1, maxRows: 4 }}
                    onPressEnter={(e) => { if (!e.shiftKey) { e.preventDefault(); void send(); } }}
                />
                <Button type="primary" icon={<SendOutlined />} loading={ask.isPending} onClick={() => void send()} disabled={input.trim() === ''} />
            </div>
        </div>
    );
}

/** Giờ gọn cho bong bóng chat: hôm nay → HH:mm, khác ngày → DD/MM HH:mm. */
function fmtTime(iso: string | null): string {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    const now = new Date();
    const hm = d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    return d.toDateString() === now.toDateString() ? hm : `${d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })} ${hm}`;
}

/**
 * Tab "Hỏi CSKH" — giao diện CHAT như app nhắn tin: câu hỏi của mình (phải, xanh),
 * trả lời CSKH (trái, xám). Realtime qua polling (useSupportRequests). Khi có câu trả
 * lời MỚI từ CSKH → phát âm thanh quick-ting.mp3.
 *
 * Mỗi support_request = 1 cặp (câu hỏi + trả lời). Hiển thị mọi request theo thứ tự
 * thời gian thành luồng hội thoại liên tục.
 */
function CskhTab({ active }: { active: boolean }) {
    const { message } = App.useApp();
    const create = useCreateSupportRequest();
    const list = useSupportRequests(active);
    const [text, setText] = useState('');
    const bottomRef = useRef<HTMLDivElement>(null);
    const audioRef = useRef<HTMLAudioElement | null>(null);
    // Tập id các request ĐÃ có câu trả lời (lần trước) — để phát hiện phản hồi MỚI.
    const answeredSeen = useRef<Set<number> | null>(null);

    // Chuẩn bị âm thanh (lazy).
    useEffect(() => {
        if (audioRef.current === null && typeof Audio !== 'undefined') {
            const a = new Audio('/quick-ting.mp3');
            a.volume = 0.6;
            audioRef.current = a;
        }
    }, []);

    // useMemo để mảng ổn định giữa các render (tránh useEffect chạy thừa khi list chưa đổi).
    const rows: SupportRequestItem[] = useMemo(() => list.data ?? [], [list.data]);

    // Phát hiện câu trả lời mới → ping. Lần đầu chỉ ghi nhận (không kêu cho lịch sử cũ).
    useEffect(() => {
        const answeredIds = rows.filter((r) => r.answer && r.answer.trim() !== '').map((r) => r.id);
        if (answeredSeen.current === null) {
            answeredSeen.current = new Set(answeredIds); // baseline, không kêu
            return;
        }
        const hasNew = answeredIds.some((id) => !answeredSeen.current!.has(id));
        answeredSeen.current = new Set(answeredIds);
        if (hasNew) {
            audioRef.current?.play().catch(() => { /* autoplay bị chặn tới khi user tương tác */ });
        }
    }, [rows]);

    // Tự cuộn xuống đáy khi có tin mới / mở tab.
    useEffect(() => { bottomRef.current?.scrollIntoView({ behavior: 'smooth' }); }, [rows, active]);

    const submit = async () => {
        const q = text.trim();
        if (q === '' || create.isPending) return;
        try {
            await create.mutateAsync({ question: q });
            setText('');
            void list.refetch();
        } catch (e) {
            message.error(errorMessage(e, 'Không gửi được yêu cầu, vui lòng thử lại.'));
        }
    };

    return (
        <div style={{ display: 'flex', flexDirection: 'column', height: '100%' }}>
            <div style={{ flex: 1, overflowY: 'auto', padding: 12, display: 'flex', flexDirection: 'column', gap: 4, background: '#F8FAFC' }}>
                {rows.length === 0 && (
                    <div style={{ margin: 'auto', textAlign: 'center', color: '#94A3B8', padding: 16 }}>
                        <CustomerServiceOutlined style={{ fontSize: 32, marginBottom: 8 }} />
                        <div>Gửi câu hỏi cho nhân viên CSKH</div>
                        <div style={{ fontSize: 12, marginTop: 4 }}>Nhân viên sẽ phản hồi trong giờ làm việc.</div>
                    </div>
                )}

                {/* Mỗi request: bong bóng câu hỏi (phải) + bong bóng trả lời/đang chờ (trái). */}
                {[...rows].reverse().map((r) => (
                    <div key={r.id} style={{ display: 'flex', flexDirection: 'column', gap: 4, marginBottom: 8 }}>
                        {/* Câu hỏi của mình */}
                        <div style={{ alignSelf: 'flex-end', maxWidth: '85%' }}>
                            <div style={{ background: '#2563EB', color: '#fff', padding: '8px 12px', borderRadius: '12px 12px 2px 12px', whiteSpace: 'pre-wrap' }}>{r.question}</div>
                            <div style={{ fontSize: 10, color: '#94A3B8', textAlign: 'right', marginTop: 2 }}>{fmtTime(r.created_at)}</div>
                        </div>
                        {/* Trả lời CSKH (nếu có) hoặc trạng thái chờ */}
                        {r.answer ? (
                            <div style={{ alignSelf: 'flex-start', maxWidth: '85%' }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 11, color: '#64748B', marginBottom: 2 }}>
                                    <CustomerServiceOutlined /> Nhân viên CSKH
                                </div>
                                <div style={{ background: '#fff', color: '#0F172A', border: '1px solid #E2E8F0', padding: '8px 12px', borderRadius: '12px 12px 12px 2px', whiteSpace: 'pre-wrap' }}>{r.answer}</div>
                                <div style={{ fontSize: 10, color: '#94A3B8', marginTop: 2 }}>{fmtTime(r.answered_at)}</div>
                            </div>
                        ) : r.status !== 'closed' ? (
                            <div style={{ alignSelf: 'flex-start', fontSize: 12, color: '#D97706', display: 'flex', alignItems: 'center', gap: 4, padding: '2px 4px' }}>
                                <ClockCircleOutlined /> Đang chờ CSKH phản hồi…
                            </div>
                        ) : (
                            <div style={{ alignSelf: 'flex-start', fontSize: 12, color: '#94A3B8', display: 'flex', alignItems: 'center', gap: 4, padding: '2px 4px' }}>
                                <CheckCircleFilled /> Đã đóng
                            </div>
                        )}
                    </div>
                ))}
                <div ref={bottomRef} />
            </div>
            <div style={{ padding: 10, borderTop: '1px solid #F1F5F9', display: 'flex', gap: 8 }}>
                <Input.TextArea
                    value={text}
                    onChange={(e) => setText(e.target.value)}
                    placeholder="Nhập câu hỏi gửi CSKH…"
                    autoSize={{ minRows: 1, maxRows: 4 }}
                    onPressEnter={(e) => { if (!e.shiftKey) { e.preventDefault(); void submit(); } }}
                />
                <Button type="primary" icon={<SendOutlined />} loading={create.isPending} onClick={() => void submit()} disabled={text.trim() === ''} />
            </div>
        </div>
    );
}

/**
 * Widget Trợ giúp nổi — nút tròn KÉO–THẢ tự do (nhớ vị trí), bấm mở cửa sổ nhỏ
 * gồm 2 tab: "Hỏi AI" (RAG) và "Hỏi CSKH" (gửi yêu cầu, báo chờ). Mount global ở AppLayout.
 */
export function HelpChatWidget() {
    const [pos, setPos] = useState<Pos>(loadPos);
    const [open, setOpen] = useState(false);
    const [tab, setTab] = useState('ai');
    const drag = useRef<{ active: boolean; moved: boolean; dx: number; dy: number }>({ active: false, moved: false, dx: 0, dy: 0 });

    // Giữ nút trong màn hình khi resize.
    useEffect(() => {
        const onResize = () => setPos((p) => clamp(p));
        window.addEventListener('resize', onResize);
        return () => window.removeEventListener('resize', onResize);
    }, []);

    const onPointerDown = useCallback((e: React.PointerEvent) => {
        (e.target as HTMLElement).setPointerCapture?.(e.pointerId);
        drag.current = { active: true, moved: false, dx: e.clientX - pos.x, dy: e.clientY - pos.y };
    }, [pos]);

    const onPointerMove = useCallback((e: React.PointerEvent) => {
        if (!drag.current.active) return;
        const nx = e.clientX - drag.current.dx;
        const ny = e.clientY - drag.current.dy;
        if (Math.abs(e.clientX - (drag.current.dx + pos.x)) > DRAG_THRESHOLD || Math.abs(e.clientY - (drag.current.dy + pos.y)) > DRAG_THRESHOLD) {
            drag.current.moved = true;
        }
        setPos(clamp({ x: nx, y: ny }));
    }, [pos]);

    const onPointerUp = useCallback(() => {
        if (!drag.current.active) return;
        const wasDrag = drag.current.moved;
        drag.current.active = false;
        if (wasDrag) {
            setPos((p) => { const c = clamp(p); try { localStorage.setItem(POS_KEY, JSON.stringify(c)); } catch { /* ignore */ } return c; });
        } else {
            setOpen((v) => !v);
        }
    }, []);

    // Panel mở phía trên-trái nút, clamp trong viewport.
    const panelStyle: CSSProperties = (() => {
        const vw = typeof window !== 'undefined' ? window.innerWidth : 1280;
        const vh = typeof window !== 'undefined' ? window.innerHeight : 800;
        let left = pos.x + BTN - PANEL_W;
        let top = pos.y - PANEL_H - 12;
        left = Math.max(8, Math.min(left, vw - PANEL_W - 8));
        top = Math.max(8, Math.min(top, vh - PANEL_H - 8));
        return { position: 'fixed', left, top, width: PANEL_W, height: PANEL_H, zIndex: 1001 };
    })();

    return (
        <>
            {open && (
                <div style={panelStyle}>
                    <div style={{ background: '#fff', borderRadius: 12, boxShadow: '0 8px 30px rgba(15,23,42,0.18)', height: '100%', display: 'flex', flexDirection: 'column', overflow: 'hidden', border: '1px solid #E2E8F0' }}>
                        <div style={{ background: '#2563EB', color: '#fff', padding: '10px 14px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                            <span style={{ fontWeight: 600 }}><CommentOutlined style={{ marginInlineEnd: 8 }} />Trợ giúp CMBcoreSeller</span>
                            <Button type="text" size="small" icon={<CloseOutlined style={{ color: '#fff' }} />} onClick={() => setOpen(false)} />
                        </div>
                        <Tabs
                            activeKey={tab}
                            onChange={setTab}
                            centered
                            style={{ flex: 1, minHeight: 0 }}
                            tabBarStyle={{ marginBottom: 0, paddingInline: 12 }}
                            items={[
                                { key: 'ai', label: <span><RobotOutlined /> Hỏi AI</span>, children: <div style={{ height: PANEL_H - 96 }}><AiTab /></div> },
                                { key: 'cskh', label: <span><CustomerServiceOutlined /> Hỏi CSKH</span>, children: <div style={{ height: PANEL_H - 96 }}><CskhTab active={tab === 'cskh'} /></div> },
                            ]}
                        />
                    </div>
                </div>
            )}

            <div
                role="button"
                aria-label="Trợ giúp"
                title="Trợ giúp"
                onPointerDown={onPointerDown}
                onPointerMove={onPointerMove}
                onPointerUp={onPointerUp}
                style={{
                    position: 'fixed', left: pos.x, top: pos.y, width: BTN, height: BTN, borderRadius: '50%',
                    background: open ? '#1E40AF' : '#2563EB', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center',
                    cursor: 'grab', boxShadow: '0 6px 18px rgba(37,99,235,0.45)', zIndex: 1000, touchAction: 'none', userSelect: 'none',
                }}
            >
                <CustomerServiceOutlined style={{ fontSize: 26 }} />
            </div>
        </>
    );
}
