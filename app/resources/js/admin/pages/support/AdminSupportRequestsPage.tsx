// /admin/support-requests — super-admin xem & trả lời yêu cầu CSKH (tab "Hỏi CSKH"
// người dùng gửi) xuyên tenant. Lọc theo trạng thái, trả lời, đóng.

import { useMemo, useState } from 'react';
import { App, Button, Card, Input, Modal, Segmented, Space, Table, Tag, Typography } from 'antd';
import { CustomerServiceOutlined, ReloadOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import {
    useAdminSupportRequests, useAnswerSupportRequest, useCloseSupportRequest,
    type AdminSupportRequest, type SupportRequestStatus,
} from '../../lib/supportRequests';

const { Text, Paragraph } = Typography;

const STATUS_TAG: Record<SupportRequestStatus, { color: string; label: string }> = {
    pending: { color: 'orange', label: 'Đang chờ' },
    answered: { color: 'green', label: 'Đã trả lời' },
    closed: { color: 'default', label: 'Đã đóng' },
};

const FILTERS = [
    { value: 'pending', label: 'Đang chờ' },
    { value: 'answered', label: 'Đã trả lời' },
    { value: 'closed', label: 'Đã đóng' },
    { value: '', label: 'Tất cả' },
];

function fmt(iso: string | null): string {
    if (!iso) return '';
    const d = new Date(iso);
    return Number.isNaN(d.getTime()) ? '' : d.toLocaleString('vi-VN');
}

export function AdminSupportRequestsPage() {
    const { message } = App.useApp();
    const [status, setStatus] = useState('pending');
    const [search, setSearch] = useState('');
    const [q, setQ] = useState('');
    const [page, setPage] = useState(1);
    const { data, isLoading, refetch } = useAdminSupportRequests({ status, q, page });
    const answer = useAnswerSupportRequest();
    const close = useCloseSupportRequest();

    // Modal trả lời
    const [editing, setEditing] = useState<AdminSupportRequest | null>(null);
    const [answerText, setAnswerText] = useState('');

    const openAnswer = (row: AdminSupportRequest) => {
        setEditing(row);
        setAnswerText(row.answer ?? '');
    };

    const submitAnswer = () => {
        if (!editing || answerText.trim() === '') return;
        answer.mutate({ id: editing.id, answer: answerText.trim() }, {
            onSuccess: () => { message.success('Đã gửi trả lời.'); setEditing(null); },
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    const columns = useMemo(() => [
        {
            title: 'Tenant', dataIndex: 'tenant', key: 'tenant', width: 160,
            render: (_: unknown, r: AdminSupportRequest) =>
                r.tenant ? <Text>{r.tenant.name}</Text> : <Text type="secondary">#{r.tenant_id}</Text>,
        },
        {
            title: 'Người gửi', dataIndex: 'user', key: 'user', width: 180,
            render: (_: unknown, r: AdminSupportRequest) =>
                r.user ? <Space direction="vertical" size={0}><Text>{r.user.name}</Text><Text type="secondary" style={{ fontSize: 11 }}>{r.user.email}</Text></Space> : <Text type="secondary">—</Text>,
        },
        {
            title: 'Câu hỏi', dataIndex: 'question', key: 'question',
            render: (_: unknown, r: AdminSupportRequest) => (
                <div>
                    <Paragraph style={{ marginBottom: 4 }} ellipsis={{ rows: 3, expandable: true, symbol: 'xem thêm' }}>{r.question}</Paragraph>
                    {r.answer && (
                        <div style={{ background: '#F0FDF4', borderLeft: '3px solid #16A34A', padding: '4px 8px', borderRadius: 4 }}>
                            <Text type="secondary" style={{ fontSize: 11 }}>Trả lời:</Text>
                            <Paragraph style={{ marginBottom: 0, fontSize: 13 }} ellipsis={{ rows: 3, expandable: true, symbol: 'xem thêm' }}>{r.answer}</Paragraph>
                        </div>
                    )}
                </div>
            ),
        },
        {
            title: 'Trạng thái', dataIndex: 'status', key: 'status', width: 110,
            render: (s: SupportRequestStatus) => <Tag color={STATUS_TAG[s].color}>{STATUS_TAG[s].label}</Tag>,
        },
        {
            title: 'Gửi lúc', dataIndex: 'created_at', key: 'created_at', width: 150,
            render: (v: string | null) => <Text type="secondary" style={{ fontSize: 12 }}>{fmt(v)}</Text>,
        },
        {
            title: 'Hành động', key: 'actions', width: 160,
            render: (_: unknown, r: AdminSupportRequest) => (
                <Space>
                    <Button size="small" type="primary" onClick={() => openAnswer(r)}>
                        {r.status === 'answered' ? 'Sửa trả lời' : 'Trả lời'}
                    </Button>
                    {r.status !== 'closed' && (
                        <Button size="small" onClick={() => close.mutate(r.id, { onSuccess: () => message.success('Đã đóng.') })}>Đóng</Button>
                    )}
                </Space>
            ),
        },
    ], [close, message]);

    return (
        <Card
            title={<Space><CustomerServiceOutlined /> Yêu cầu hỗ trợ CSKH</Space>}
            extra={<Button icon={<ReloadOutlined />} onClick={() => refetch()}>Tải lại</Button>}
        >
            <Space style={{ marginBottom: 16, width: '100%', justifyContent: 'space-between' }} wrap>
                <Segmented
                    options={FILTERS}
                    value={status}
                    onChange={(v) => { setStatus(v as string); setPage(1); }}
                />
                <Input.Search
                    placeholder="Tìm trong câu hỏi…"
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
                pagination={{
                    current: page, pageSize: 50, total: data?.meta.pagination.total ?? 0,
                    showSizeChanger: false, onChange: setPage,
                }}
            />

            <Modal
                open={editing !== null}
                title={`Trả lời yêu cầu #${editing?.id ?? ''}`}
                onCancel={() => setEditing(null)}
                onOk={submitAnswer}
                okText="Gửi trả lời"
                confirmLoading={answer.isPending}
                okButtonProps={{ disabled: answerText.trim() === '' }}
                destroyOnClose
            >
                {editing && (
                    <>
                        <div style={{ background: '#F8FAFC', borderRadius: 6, padding: 10, marginBottom: 12 }}>
                            <Text type="secondary" style={{ fontSize: 12 }}>
                                {editing.tenant?.name ?? `Tenant #${editing.tenant_id}`}
                                {editing.user ? ` · ${editing.user.name}` : ''}
                            </Text>
                            <Paragraph style={{ marginBottom: 0, marginTop: 4 }}>{editing.question}</Paragraph>
                        </div>
                        <Input.TextArea
                            value={answerText}
                            onChange={(e) => setAnswerText(e.target.value)}
                            placeholder="Nhập câu trả lời cho người dùng…"
                            autoSize={{ minRows: 4, maxRows: 12 }}
                            maxLength={8000}
                            showCount
                        />
                    </>
                )}
            </Modal>
        </Card>
    );
}
