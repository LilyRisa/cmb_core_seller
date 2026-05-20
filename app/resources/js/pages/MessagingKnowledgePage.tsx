import { useState } from 'react';
import { App as AntApp, Button, Card, Form, Input, Modal, Popconfirm, Select, Space, Table, Tag } from 'antd';
import { DeleteOutlined, PlusOutlined } from '@ant-design/icons';
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
    const [form] = Form.useForm();
    const source = Form.useWatch('source', form) as 'inline' | 'url' | undefined;

    const submit = () => form.validateFields().then((v) => {
        create.mutate(v, {
            onSuccess: () => { message.success('Đã thêm tài liệu — đang index'); setOpen(false); form.resetFields(); },
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

            <Modal open={open} onCancel={() => setOpen(false)} onOk={submit} confirmLoading={create.isPending} title="Thêm tài liệu AI" okText="Thêm" cancelText="Huỷ">
                <Form form={form} layout="vertical" initialValues={{ source: 'inline' }}>
                    <Form.Item name="title" label="Tiêu đề" rules={[{ required: true }]}><Input /></Form.Item>
                    <Form.Item name="source" label="Nguồn" rules={[{ required: true }]}>
                        <Select options={[{ value: 'inline', label: 'Gõ trực tiếp' }, { value: 'url', label: 'Từ URL' }]} />
                    </Form.Item>
                    {source === 'url'
                        ? <Form.Item name="url" label="URL" rules={[{ required: true, type: 'url' }]}><Input placeholder="https://…" /></Form.Item>
                        : <Form.Item name="inline_text" label="Nội dung" rules={[{ required: true }]}><Input.TextArea rows={6} placeholder="Chính sách đổi trả: ..." /></Form.Item>}
                    <div style={{ color: '#94A3B8', fontSize: 12 }}>Upload file (PDF/DOCX) sẽ bổ sung sau — hiện hỗ trợ gõ trực tiếp & URL.</div>
                </Form>
            </Modal>
        </div>
    );
}
