import { useState } from 'react';
import { App as AntApp, Button, Card, Drawer, Empty, Form, Input, Modal, Popconfirm, Segmented, Space, Spin, Table, Tag, Tooltip, Typography, Upload } from 'antd';
import { DeleteOutlined, EyeOutlined, PlusOutlined, ReloadOutlined, UploadOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { MessagingNav } from '@/components/MessagingNav';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { type KnowledgeDoc, useCreateKnowledge, useDeleteKnowledge, useKnowledgeChunks, useKnowledgeDocs, useReindexKnowledge } from '@/lib/messagingConfig';

const STATUS: Record<KnowledgeDoc['status'], { color: string; label: string }> = {
    pending: { color: 'processing', label: 'Đang xử lý' },
    ready: { color: 'green', label: 'Sẵn sàng' },
    failed: { color: 'red', label: 'Lỗi' },
};

/** /messaging/knowledge — tài liệu AI training (RAG). SPEC-0024 §6.2. */
export function MessagingKnowledgePage() {
    const { message } = AntApp.useApp();
    const canManage = useCan('messaging.ai.train');
    const { data, isFetching } = useKnowledgeDocs();
    const create = useCreateKnowledge();
    const del = useDeleteKnowledge();
    const reindex = useReindexKnowledge();
    const [open, setOpen] = useState(false);
    const [file, setFile] = useState<File | null>(null);
    const [viewingId, setViewingId] = useState<number | null>(null);
    const [form] = Form.useForm();
    const source = Form.useWatch('source', form) as 'inline' | 'url' | 'upload' | undefined;

    const close = () => { setOpen(false); setFile(null); form.resetFields(); };

    const submit = () => form.validateFields().then((v) => {
        if (v.source === 'upload' && !file) { message.error('Chọn file để tải lên'); return; }
        create.mutate({ title: v.title, source: v.source, inline_text: v.inline_text, url: v.url, file: file ?? undefined }, {
            onSuccess: () => { message.success('Đã thêm tài liệu — đang index'); close(); },
            onError: (e) => message.error(errorMessage(e)),
        });
    });

    const columns: ColumnsType<KnowledgeDoc> = [
        { title: 'Tiêu đề', dataIndex: 'title' },
        { title: 'Nguồn', dataIndex: 'source', width: 110, render: (v) => <Tag>{v}</Tag> },
        { title: 'Trạng thái', dataIndex: 'status', width: 140, render: (v: KnowledgeDoc['status'], r) => <Space direction="vertical" size={0}><Tag color={STATUS[v].color}>{STATUS[v].label}</Tag>{r.error && <span style={{ fontSize: 11, color: '#EF4444' }}>{r.error}</span>}</Space> },
        { title: 'Số đoạn', dataIndex: 'chunk_count', width: 90, align: 'center' },
        {
            title: '', width: 130, render: (_: unknown, r: KnowledgeDoc) => (
                <Space size={2}>
                    <Tooltip title="Xem nội dung đã lấy">
                        <Button size="small" type="text" icon={<EyeOutlined />} onClick={() => setViewingId(r.id)} />
                    </Tooltip>
                    {canManage && r.source !== 'inline' && (
                        <Tooltip title="Tải lại (lấy dữ liệu mới từ nguồn)">
                            <Button size="small" type="text" icon={<ReloadOutlined />} loading={reindex.isPending && reindex.variables === r.id}
                                onClick={() => reindex.mutate(r.id, { onSuccess: () => message.success('Đang tải lại tài liệu'), onError: (e) => message.error(errorMessage(e)) })} />
                        </Tooltip>
                    )}
                    {canManage && (
                        <Popconfirm title="Xoá tài liệu?" okText="Xoá" cancelText="Huỷ" okButtonProps={{ danger: true }}
                            onConfirm={() => del.mutate(r.id, { onSuccess: () => message.success('Đã xoá'), onError: (e) => message.error(errorMessage(e)) })}>
                            <Button size="small" type="text" danger icon={<DeleteOutlined />} />
                        </Popconfirm>
                    )}
                </Space>
            ),
        },
    ];

    return (
        <div>
            <PageHeader title="AI training (tài liệu)" subtitle="Thêm FAQ / chính sách / mô tả SP để AI tham chiếu khi gợi ý trả lời (RAG)."
                extra={canManage && <Button type="primary" icon={<PlusOutlined />} onClick={() => setOpen(true)}>Thêm tài liệu</Button>} />
            <MessagingNav />
            <Card>
                <Table<KnowledgeDoc> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns} pagination={false} />
            </Card>

            <Modal open={open} onCancel={close} onOk={submit} confirmLoading={create.isPending} title="Thêm tài liệu AI" okText="Thêm" cancelText="Huỷ">
                <Form form={form} layout="vertical" initialValues={{ source: 'inline' }}>
                    <Form.Item name="title" label="Tiêu đề" rules={[{ required: true }]}><Input /></Form.Item>
                    <Form.Item name="source" label="Nguồn" rules={[{ required: true }]}>
                        <Segmented block options={[
                            { value: 'inline', label: 'Gõ trực tiếp' },
                            { value: 'url', label: 'URL / Google Sheets' },
                            { value: 'upload', label: 'Tải file' },
                        ]} />
                    </Form.Item>
                    {source === 'url' && (
                        <>
                            <Form.Item name="url" label="URL" rules={[{ required: true, type: 'url' }]}><Input placeholder="https://…" /></Form.Item>
                            <div style={{ color: '#94A3B8', fontSize: 12 }}>Trang web hoặc link Google Sheets (chia sẻ “Bất kỳ ai có liên kết → Người xem”) sẽ được tải về & trích nội dung. Dùng nút “Tải lại” khi nguồn có dữ liệu mới.</div>
                        </>
                    )}
                    {source === 'upload' && (
                        <Form.Item label="File">
                            <Upload beforeUpload={(f) => { setFile(f as File); return false; }} onRemove={() => setFile(null)}
                                maxCount={1} showUploadList={false} accept=".txt,.md,.csv,.tsv,.docx,.xlsx,.pdf">
                                <Button icon={<UploadOutlined />}>Chọn file</Button>
                            </Upload>
                            {file && <Typography.Text style={{ marginLeft: 8 }}>{file.name}</Typography.Text>}
                            <div style={{ color: '#94A3B8', fontSize: 12, marginTop: 4 }}>Hỗ trợ PDF, Word (.docx), Excel (.xlsx), CSV/TSV, văn bản (.txt/.md). Tối đa 25MB.</div>
                        </Form.Item>
                    )}
                    {(source === 'inline' || source === undefined) && (
                        <Form.Item name="inline_text" label="Nội dung" rules={[{ required: true }]}><Input.TextArea rows={6} placeholder="Chính sách đổi trả: ..." /></Form.Item>
                    )}
                </Form>
            </Modal>

            <KnowledgeContentDrawer id={viewingId} onClose={() => setViewingId(null)} />
        </div>
    );
}

/** Drawer xem nội dung đã trích (chunk) — kiểm tra dữ liệu AI thực sự lấy được. */
function KnowledgeContentDrawer({ id, onClose }: { id: number | null; onClose: () => void }) {
    const { data, isFetching } = useKnowledgeChunks(id);

    return (
        <Drawer open={id != null} onClose={onClose} width={560} title={data ? `Nội dung: ${data.title}` : 'Nội dung tài liệu'}>
            {isFetching && <div style={{ textAlign: 'center', padding: 32 }}><Spin /></div>}
            {!isFetching && data && (
                <Space direction="vertical" style={{ width: '100%' }} size="middle">
                    <Space wrap>
                        <Tag>{data.source}</Tag>
                        <Tag color={STATUS[data.status].color}>{STATUS[data.status].label}</Tag>
                        <Tag>{data.chunk_count} đoạn</Tag>
                    </Space>
                    {data.url && <Typography.Link href={data.url} target="_blank" rel="noreferrer">{data.url}</Typography.Link>}
                    {data.error && <Typography.Text type="danger">{data.error}</Typography.Text>}
                    {data.chunks.length === 0 && <Empty description="Chưa có nội dung được trích. Thử “Tải lại”." />}
                    {data.chunks.map((c) => (
                        <Card key={c.index} size="small" title={`Đoạn ${c.index + 1}`}>
                            <Typography.Paragraph style={{ whiteSpace: 'pre-wrap', marginBottom: 0 }}>{c.text}</Typography.Paragraph>
                        </Card>
                    ))}
                </Space>
            )}
        </Drawer>
    );
}
