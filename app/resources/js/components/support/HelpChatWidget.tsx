import { useCallback, useEffect, useRef, useState, type CSSProperties } from 'react';
import { Link } from 'react-router-dom';
import { App, Badge, Button, Input, Spin, Tabs, Tag, Typography } from 'antd';
import { CloseOutlined, CommentOutlined, CustomerServiceOutlined, RobotOutlined, SendOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { type ChatTurn, type HelpSource, useAskAssistant, useSupportUnread } from '@/lib/support';
import { useCurrentTenantId } from '@/lib/tenant';
import { CskhTab } from './CskhTab';

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
                        <div style={{ fontSize: 12, marginTop: 10 }}><Link to="/support">Xem tất cả bài hướng dẫn →</Link></div>
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

/**
 * Widget Trợ giúp nổi — nút tròn KÉO–THẢ tự do (nhớ vị trí), bấm mở cửa sổ nhỏ gồm 2 tab:
 * "Hỏi AI" (RAG) và "Hỏi CSKH" (hội thoại nhiều tin + đính kèm). Mount global ở AppLayout.
 *
 * Badge + âm thanh TOÀN CỤC: poll nhẹ `GET /support/unread` (20s, kể cả khi đóng) → có tin
 * CSKH chưa đọc thì hiện badge & kêu khi số tăng. Mở tab CSKH ⇒ CskhTab gọi read ⇒ badge về 0.
 * Server-side unread nên không kêu lại khi reload / không kêu cho backlog lúc mở app.
 */
export function HelpChatWidget() {
    const [pos, setPos] = useState<Pos>(loadPos);
    const [open, setOpen] = useState(false);
    const [tab, setTab] = useState('ai');
    const drag = useRef<{ active: boolean; moved: boolean; dx: number; dy: number }>({ active: false, moved: false, dx: 0, dy: 0 });

    const tenantId = useCurrentTenantId();
    const unreadQuery = useSupportUnread(tenantId != null);
    const unread = unreadQuery.data ?? 0;
    const audioRef = useRef<HTMLAudioElement | null>(null);
    const prevUnreadRef = useRef<number | null>(null);

    // Chuẩn bị âm thanh (lazy).
    useEffect(() => {
        if (audioRef.current === null && typeof Audio !== 'undefined') {
            const a = new Audio('/quick-ting.mp3');
            a.volume = 0.6;
            audioRef.current = a;
        }
    }, []);

    // Kêu khi số chưa đọc TĂNG. Lần tải đầu chỉ lập mốc (không kêu cho backlog).
    useEffect(() => {
        if (unreadQuery.data === undefined) return;
        const prev = prevUnreadRef.current;
        prevUnreadRef.current = unread;
        if (prev !== null && unread > prev) {
            audioRef.current?.play().catch(() => { /* autoplay bị chặn tới khi user tương tác */ });
        }
    }, [unread, unreadQuery.data]);

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
            setOpen((v) => {
                // Mở widget mà đang có tin CSKH chưa đọc ⇒ nhảy thẳng tab "Hỏi CSKH".
                if (!v && unread > 0) setTab('cskh');
                return !v;
            });
        }
    }, [unread]);

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
                                {
                                    key: 'cskh',
                                    label: <span><Badge dot={unread > 0} offset={[6, 0]}><CustomerServiceOutlined /></Badge> Hỏi CSKH</span>,
                                    children: <div style={{ height: PANEL_H - 96 }}><CskhTab active={tab === 'cskh'} /></div>,
                                },
                            ]}
                        />
                    </div>
                </div>
            )}

            <div style={{ position: 'fixed', left: pos.x, top: pos.y, zIndex: 1000, touchAction: 'none' }}>
                <Badge count={unread} size="default" overflowCount={9} offset={[-6, 6]}>
                    <div
                        role="button"
                        aria-label={unread > 0 ? `Trợ giúp — ${unread} tin CSKH mới` : 'Trợ giúp'}
                        title={unread > 0 ? `${unread} tin CSKH mới` : 'Trợ giúp'}
                        onPointerDown={onPointerDown}
                        onPointerMove={onPointerMove}
                        onPointerUp={onPointerUp}
                        style={{
                            width: BTN, height: BTN, borderRadius: '50%',
                            background: open ? '#1E40AF' : '#2563EB', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center',
                            cursor: 'grab', boxShadow: '0 6px 18px rgba(37,99,235,0.45)', userSelect: 'none',
                        }}
                    >
                        <CustomerServiceOutlined style={{ fontSize: 26 }} />
                    </div>
                </Badge>
            </div>
        </>
    );
}
