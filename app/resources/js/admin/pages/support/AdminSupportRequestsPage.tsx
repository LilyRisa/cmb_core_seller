// /admin/support-requests — super-admin xem & nhắn nhiều tin + đóng hội thoại CSKH
// (tab "Hỏi CSKH" phía user) xuyên tenant (SPEC-0028).

import { useMemo, useRef, useState } from 'react';
import { App, Button, Card, Drawer, Input, Segmented, Space, Spin, Table, Tag, Typography } from 'antd';
import {
    CheckCircleFilled, CloseOutlined, CustomerServiceOutlined, PaperClipOutlined,
    ReloadOutlined, SendOutlined,
} from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { SupportAttachmentList } from '@/components/support/SupportAttachmentList';
import type { SupportMessage } from '@/lib/support';
import {
    useAdminSupportConversations, useAdminSupportThread, useCloseAdminSupportConversation,
    useSendAdminSupportMessage, type AdminSupportConversation,
} from '../../lib/supportRequests';

const { Text, Paragraph } = Typography;

const MAX_FILES = 5;
const ACCEPT = 'image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt';

const FILTERS = [
    { value: 'open', label: 'Đang mở' },
    { value: 'closed', label: 'Đã đóng' },
    { value: '', label: 'Tất cả' },
];

function fmt(iso: string | null): string {
    if (!iso) return '';
    const d = new Date(iso);
    return Number.isNaN(d.getTime()) ? '' : d.toLocaleString('vi-VN');
}

/** 1 tin trong thread (góc nhìn admin: CSKH = phải/xanh, người dùng = trái/xám, hệ thống = giữa). */
function MessageRow({ m }: { m: SupportMessage }) {
    if (m.type === 'system') {
        return (
            <div style={{ alignSelf: 'center', textAlign: 'center', color: '#94A3B8', fontSize: 12, padding: '6px 0', display: 'flex', alignItems: 'center', gap: 6 }}>
                <CheckCircleFilled style={{ color: '#94A3B8' }} /> <span>{m.body}</span>
            </div>
        );
    }
    const isCskh = m.sender === 'cskh';
    return (
        <div style={{ alignSelf: isCskh ? 'flex-end' : 'flex-start', maxWidth: '85%' }}>
            <div style={{ fontSize: 11, color: '#64748B', marginBottom: 2, textAlign: isCskh ? 'right' : 'left' }}>
                {isCskh ? 'CSKH' : 'Người dùng'}
            </div>
            {m.body && m.body.trim() !== '' && (
                <div style={{
                    background: isCskh ? '#2563EB' : '#fff', color: isCskh ? '#fff' : '#0F172A',
                    border: isCskh ? 'none' : '1px solid #E2E8F0', padding: '8px 12px', whiteSpace: 'pre-wrap',
                    borderRadius: isCskh ? '12px 12px 2px 12px' : '12px 12px 12px 2px',
                }}>{m.body}</div>
            )}
            <SupportAttachmentList attachments={m.attachments} />
            <div style={{ fontSize: 10, color: '#94A3B8', textAlign: isCskh ? 'right' : 'left', marginTop: 2 }}>{fmt(m.created_at)}</div>
        </div>
    );
}

/** Drawer thread: xem toàn bộ tin + nhắn (nhiều lần) + đính kèm + đóng. */
function ThreadDrawer({ id, onClose }: { id: number | null; onClose: () => void }) {
    const { message } = App.useApp();
    const thread = useAdminSupportThread(id);
    const send = useSendAdminSupportMessage();
    const close = useCloseAdminSupportConversation();
    const [text, setText] = useState('');
    const [files, setFiles] = useState<File[]>([]);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const data = thread.data;
    const closed = data?.status === 'closed';

    const onPick = (e: React.ChangeEvent<HTMLInputElement>) => {
        const picked = Array.from(e.target.files ?? []);
        setFiles((prev) => {
            const merged = [...prev, ...picked];
            if (merged.length > MAX_FILES) message.warning(`Tối đa ${MAX_FILES} tệp mỗi tin nhắn.`);
            return merged.slice(0, MAX_FILES);
        });
        e.target.value = '';
    };

    const submit = () => {
        if (id == null) return;
        const body = text.trim();
        if ((body === '' && files.length === 0) || send.isPending) return;
        send.mutate(
            { id, body: body === '' ? undefined : body, files },
            {
                onSuccess: () => { setText(''); setFiles([]); },
                onError: (e) => message.error(errorMessage(e, 'Không gửi được, vui lòng thử lại.')),
            },
        );
    };

    return (
        <Drawer
            open={id !== null}
            onClose={onClose}
            width={480}
            title={data ? (
                <Space direction="vertical" size={0}>
                    <Text strong>{data.tenant?.name ?? `Tenant #${data.tenant_id}`}</Text>
                    {data.user && <Text type="secondary" style={{ fontSize: 12 }}>{data.user.name} · {data.user.email}</Text>}
                </Space>
            ) : 'Hội thoại CSKH'}
            extra={data && !closed && (
                <Button danger onClick={() => close.mutate(data.id, { onSuccess: () => message.success('Đã đóng hội thoại.') })} loading={close.isPending}>
                    Đóng hội thoại
                </Button>
            )}
            styles={{ body: { padding: 0, display: 'flex', flexDirection: 'column' } }}
        >
            <div style={{ flex: 1, overflowY: 'auto', padding: 12, display: 'flex', flexDirection: 'column', gap: 4, background: '#F8FAFC' }}>
                {thread.isLoading && <div style={{ margin: 'auto' }}><Spin /></div>}
                {data?.messages.map((m) => <MessageRow key={m.id} m={m} />)}
            </div>

            {files.length > 0 && (
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6, padding: '8px 10px 0', borderTop: '1px solid #F1F5F9' }}>
                    {files.map((f, i) => (
                        <span key={i} style={{ display: 'inline-flex', alignItems: 'center', gap: 6, background: '#EFF6FF', border: '1px solid #BFDBFE', borderRadius: 6, padding: '2px 6px', fontSize: 12, maxWidth: 220 }}>
                            <PaperClipOutlined style={{ color: '#2563EB' }} />
                            <span style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{f.name}</span>
                            <CloseOutlined style={{ cursor: 'pointer', fontSize: 11 }} onClick={() => setFiles((prev) => prev.filter((_, j) => j !== i))} />
                        </span>
                    ))}
                </div>
            )}

            {closed ? (
                <div style={{ padding: 12, borderTop: '1px solid #F1F5F9', color: '#94A3B8', fontSize: 13, textAlign: 'center' }}>
                    Hội thoại đã đóng. Người dùng nhắn tin mới sẽ mở cuộc trò chuyện mới.
                </div>
            ) : (
                <div style={{ padding: 10, borderTop: files.length > 0 ? 'none' : '1px solid #F1F5F9', display: 'flex', gap: 8, alignItems: 'flex-end' }}>
                    <input ref={fileInputRef} type="file" multiple accept={ACCEPT} style={{ display: 'none' }} onChange={onPick} />
                    <Button icon={<PaperClipOutlined />} onClick={() => fileInputRef.current?.click()} disabled={files.length >= MAX_FILES} title="Đính kèm" />
                    <Input.TextArea
                        value={text}
                        onChange={(e) => setText(e.target.value)}
                        placeholder="Nhập trả lời gửi người dùng…"
                        autoSize={{ minRows: 1, maxRows: 5 }}
                        onPressEnter={(e) => { if (!e.shiftKey) { e.preventDefault(); submit(); } }}
                    />
                    <Button type="primary" icon={<SendOutlined />} loading={send.isPending} onClick={submit} disabled={text.trim() === '' && files.length === 0} />
                </div>
            )}
        </Drawer>
    );
}

export function AdminSupportRequestsPage() {
    const [status, setStatus] = useState('open');
    const [search, setSearch] = useState('');
    const [q, setQ] = useState('');
    const [awaiting, setAwaiting] = useState(false);
    const [page, setPage] = useState(1);
    const [activeId, setActiveId] = useState<number | null>(null);
    const { data, isLoading, refetch } = useAdminSupportConversations({ status, awaiting, q, page });

    const columns = useMemo(() => [
        {
            title: 'Tenant', dataIndex: 'tenant', key: 'tenant', width: 150,
            render: (_: unknown, r: AdminSupportConversation) =>
                r.tenant ? <Text>{r.tenant.name}</Text> : <Text type="secondary">#{r.tenant_id}</Text>,
        },
        {
            title: 'Người gửi', dataIndex: 'user', key: 'user', width: 170,
            render: (_: unknown, r: AdminSupportConversation) =>
                r.user ? <Space direction="vertical" size={0}><Text>{r.user.name}</Text><Text type="secondary" style={{ fontSize: 11 }}>{r.user.email}</Text></Space> : <Text type="secondary">—</Text>,
        },
        {
            title: 'Tin gần nhất', key: 'preview',
            render: (_: unknown, r: AdminSupportConversation) => (
                <Paragraph style={{ marginBottom: 0 }} ellipsis={{ rows: 2 }}>
                    <Text type="secondary" style={{ fontSize: 11 }}>{r.last_sender === 'cskh' ? 'CSKH: ' : ''}</Text>
                    {r.last_preview ?? <Text type="secondary">(đính kèm)</Text>}
                </Paragraph>
            ),
        },
        {
            title: 'Trạng thái', key: 'status', width: 130,
            render: (_: unknown, r: AdminSupportConversation) => (
                <Space direction="vertical" size={2}>
                    <Tag color={r.status === 'open' ? 'blue' : 'default'}>{r.status === 'open' ? 'Đang mở' : 'Đã đóng'}</Tag>
                    {r.awaiting && <Tag color="orange">Chờ CSKH</Tag>}
                </Space>
            ),
        },
        {
            title: 'Cập nhật', dataIndex: 'last_message_at', key: 'last_message_at', width: 150,
            render: (v: string | null) => <Text type="secondary" style={{ fontSize: 12 }}>{fmt(v)}</Text>,
        },
        {
            title: 'Hành động', key: 'actions', width: 100,
            render: (_: unknown, r: AdminSupportConversation) => (
                <Button size="small" type="primary" onClick={() => setActiveId(r.id)}>Mở</Button>
            ),
        },
    ], []);

    return (
        <Card
            title={<Space><CustomerServiceOutlined /> Hội thoại hỗ trợ CSKH</Space>}
            extra={<Button icon={<ReloadOutlined />} onClick={() => refetch()}>Tải lại</Button>}
        >
            <Space style={{ marginBottom: 16, width: '100%', justifyContent: 'space-between' }} wrap>
                <Space wrap>
                    <Segmented options={FILTERS} value={status} onChange={(v) => { setStatus(v as string); setPage(1); }} />
                    <Segmented
                        options={[{ value: 'all', label: 'Tất cả' }, { value: 'awaiting', label: 'Chờ CSKH trả lời' }]}
                        value={awaiting ? 'awaiting' : 'all'}
                        onChange={(v) => { setAwaiting(v === 'awaiting'); setPage(1); }}
                    />
                </Space>
                <Input.Search
                    placeholder="Tìm trong nội dung tin…"
                    style={{ width: 280 }}
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    onSearch={(v) => { setQ(v); setPage(1); }}
                    allowClear
                />
            </Space>

            <Table
                rowKey="id"
                loading={isLoading}
                dataSource={data?.data ?? []}
                columns={columns}
                onRow={(r) => ({ onClick: () => setActiveId(r.id), style: { cursor: 'pointer' } })}
                pagination={{
                    current: page, pageSize: 50, total: data?.meta.pagination.total ?? 0,
                    showSizeChanger: false, onChange: setPage,
                }}
            />

            <ThreadDrawer id={activeId} onClose={() => setActiveId(null)} />
        </Card>
    );
}
