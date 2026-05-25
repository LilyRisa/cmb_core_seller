import { useState } from 'react';
import { App as AntApp, Button, Card, Form, Input, Modal, Popconfirm, Segmented, Space, Table, Tag, Typography, Upload } from 'antd';
import { DeleteOutlined, PlusOutlined, UploadOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { MessagingNav } from '@/components/MessagingNav';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { type KnowledgeDoc, useCreateKnowledge, useDeleteKnowledge, useKnowledgeDocs } from '@/lib/messagingConfig';

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
    const [open, setOpen] = useState(false);
    const [file, setFile] = useState<File | null>(null);
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
        { title: 'Số đoạn', dataIndex: 'chunk_count', width: 100, align: 'center' },
        ...(canManage ? [{ title: '', width: 60, render: (_: unknown, r: KnowledgeDoc) => (
            <Popconfirm title="Xoá tài liệu?" okText="Xoá" cancelText="Huỷ" okButtonProps={{ danger: true }}
                onConfirm={() => del.mutate(r.id, { onSuccess: () => message.success('Đã xoá'), onError: (e) => message.error(errorMessage(e)) })}>
                <Button size="small" type="text" danger icon={<DeleteOutlined />} />
            </Popconfirm>
        ) }] : []),
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
                            <div style={{ color: '#94A3B8', fontSize: 12 }}>Trang web hoặc link Google Sheets (chia sẻ “Bất kỳ ai có liên kết”) sẽ được tải về & trích nội dung.</div>
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
        </div>
    );
}
