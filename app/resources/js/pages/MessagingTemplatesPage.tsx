import { useRef, useState } from 'react';
import { App as AntApp, Button, Card, Form, Image, Input, Modal, Popconfirm, Space, Spin, Switch, Table, Tag, Tooltip, Typography } from 'antd';
import { CloseCircleFilled, DeleteOutlined, EditOutlined, PictureOutlined, PlusOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { type MessageTemplate, type TemplateAttachment, useDeleteTemplate, useSaveTemplate, useTemplates, useUploadTemplateAttachment } from '@/lib/messagingConfig';
import { MessagingNav } from '@/components/MessagingNav';
import { ChipTextEditor, type ChipTextEditorHandle } from '@/components/messaging/ChipTextEditor';
import { TEMPLATE_VARS, labelForVarKey } from '@/lib/templateVars';

/** /messaging/templates — quản lý mẫu tin trả lời nhanh (SPEC-0024 §6.2). */
export function MessagingTemplatesPage() {
    const { message } = AntApp.useApp();
    const canManage = useCan('messaging.template.manage');
    const { data, isFetching } = useTemplates();
    const save = useSaveTemplate();
    const del = useDeleteTemplate();
    const upload = useUploadTemplateAttachment();
    const [editing, setEditing] = useState<MessageTemplate | null>(null);
    const [open, setOpen] = useState(false);
    const [form] = Form.useForm();
    const editorRef = useRef<ChipTextEditorHandle | null>(null);
    // Bump để ép ChipTextEditor dựng lại DOM từ body khi mở modal / đổi mẫu sửa.
    const [resetKey, setResetKey] = useState(0);
    // Ảnh đính kèm quản lý ngoài Form (không phải input chuẩn) rồi gộp vào payload lưu.
    const [attachments, setAttachments] = useState<TemplateAttachment[]>([]);
    const fileRef = useRef<HTMLInputElement | null>(null);

    const openForm = (t?: MessageTemplate) => {
        setEditing(t ?? null);
        form.setFieldsValue(t ?? { enabled: true, code: '', name: '', body: '', shortcut_key: '' });
        setAttachments(t?.attachments ?? []);
        setResetKey((k) => k + 1);
        setOpen(true);
    };

    const submit = () => form.validateFields().then((v) => {
        save.mutate({ ...(editing ? { id: editing.id } : {}), ...v, attachments }, {
            onSuccess: () => { message.success('Đã lưu mẫu tin'); setOpen(false); },
            onError: (e) => message.error(errorMessage(e)),
        });
    });

    const pickImages = () => { if (fileRef.current) { fileRef.current.value = ''; fileRef.current.click(); } };
    const onFilesChosen = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(e.target.files ?? []);
        for (const file of files) {
            try {
                const att = await upload.mutateAsync(file);
                setAttachments((prev) => [...prev, att]);
            } catch (err) {
                message.error(errorMessage(err, `Không tải được ảnh ${file.name}`));
            }
        }
    };
    const removeAttachment = (idx: number) => setAttachments((prev) => prev.filter((_, i) => i !== idx));

    const columns: ColumnsType<MessageTemplate> = [
        { title: 'Mã', dataIndex: 'code', width: 150, render: (v) => <Typography.Text code>{v}</Typography.Text> },
        { title: 'Tên', dataIndex: 'name' },
        { title: 'Nội dung', dataIndex: 'body', ellipsis: true, render: (v) => <Typography.Text type="secondary">{v}</Typography.Text> },
        { title: 'Ảnh', dataIndex: 'attachments', width: 80, render: (atts: TemplateAttachment[]) => (
            (atts ?? []).length > 0
                ? <Space size={4}><PictureOutlined style={{ color: '#2563eb' }} /><span>{atts.length}</span></Space>
                : <Typography.Text type="secondary">—</Typography.Text>
        ) },
        { title: 'Bật', dataIndex: 'enabled', width: 70, render: (v) => <Tag color={v ? 'green' : 'default'}>{v ? 'Bật' : 'Tắt'}</Tag> },
        ...(canManage ? [{ title: '', width: 90, render: (_: unknown, r: MessageTemplate) => (
            <Space size={2}>
                <Button size="small" type="text" icon={<EditOutlined />} onClick={() => openForm(r)} />
                <Popconfirm title="Xoá mẫu tin?" okText="Xoá" cancelText="Huỷ" okButtonProps={{ danger: true }}
                    onConfirm={() => del.mutate(r.id, { onSuccess: () => message.success('Đã xoá'), onError: (e) => message.error(errorMessage(e)) })}>
                    <Button size="small" type="text" danger icon={<DeleteOutlined />} />
                </Popconfirm>
            </Space>
        ) }] : []),
    ];

    return (
        <div>
            <PageHeader title="Mẫu tin nhắn" subtitle="Soạn sẵn câu trả lời nhanh — chèn biến (tên khách, mã đơn…) bằng cách bấm chip, có thể đính ảnh."
                extra={canManage && <Button type="primary" icon={<PlusOutlined />} onClick={() => openForm()}>Thêm mẫu</Button>} />
            <MessagingNav />
            <Card>
                <Table<MessageTemplate> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns} pagination={false} />
            </Card>

            <input ref={fileRef} type="file" accept="image/*" multiple style={{ display: 'none' }} onChange={onFilesChosen} />

            <Modal open={open} onCancel={() => setOpen(false)} onOk={submit} confirmLoading={save.isPending} width={620}
                title={editing ? `Sửa mẫu — ${editing.code}` : 'Thêm mẫu tin'} okText="Lưu" cancelText="Huỷ">
                <Form form={form} layout="vertical">
                    <Form.Item name="code" label="Mã (slug)" rules={[{ required: true, pattern: /^[a-z0-9_-]+$/, message: 'Chỉ chữ thường, số, _ -' }]}>
                        <Input placeholder="vd: cam_on" disabled={!!editing} />
                    </Form.Item>
                    <Form.Item name="name" label="Tên" rules={[{ required: true }]}><Input /></Form.Item>
                    <Form.Item
                        name="shortcut_key"
                        label="Phím tắt (slash command)"
                        extra="Gõ /phimtat trong ô soạn tin để chèn nhanh mẫu này."
                    >
                        <Input placeholder="vd: cam_on" maxLength={32} prefix="/" />
                    </Form.Item>
                    <Form.Item label="Nội dung" required style={{ marginBottom: 8 }}>
                        {/* Chip biến — bấm để chèn chip vào vị trí con trỏ. onMouseDown preventDefault
                            để không cướp focus khỏi ô soạn ⇒ giữ nguyên vị trí con trỏ. */}
                        <div style={{ marginBottom: 8, display: 'flex', flexWrap: 'wrap', gap: 4 }}>
                            {TEMPLATE_VARS.map((v) => (
                                <Tag
                                    key={v.key}
                                    color="blue"
                                    style={{ cursor: 'pointer', userSelect: 'none' }}
                                    onMouseDown={(e) => e.preventDefault()}
                                    onClick={() => editorRef.current?.insertVar(v.key, v.label)}
                                >
                                    + {v.label}
                                </Tag>
                            ))}
                        </div>
                        <Form.Item name="body" rules={[{ required: true, message: 'Vui lòng nhập nội dung' }]} style={{ marginBottom: 0 }}>
                            <ChipTextEditor
                                ref={editorRef}
                                resetSignal={resetKey}
                                labelFor={labelForVarKey}
                                placeholder="Cảm ơn [Tên khách] đã đặt đơn [Mã đơn]!"
                            />
                        </Form.Item>
                    </Form.Item>

                    {/* Ảnh đính kèm — gửi kèm text khi dùng mẫu. */}
                    <Form.Item label="Ảnh đính kèm (tuỳ chọn)" style={{ marginBottom: 8 }}>
                        <Space wrap size={8}>
                            {attachments.map((att, idx) => (
                                <div key={att.storage_path} style={{ position: 'relative', width: 72, height: 72 }}>
                                    <Image src={att.url ?? undefined} width={72} height={72} style={{ objectFit: 'cover', borderRadius: 6 }} />
                                    <CloseCircleFilled
                                        onClick={() => removeAttachment(idx)}
                                        style={{ position: 'absolute', top: -6, right: -6, color: '#cf1322', background: '#fff', borderRadius: '50%', cursor: 'pointer', fontSize: 16 }}
                                    />
                                </div>
                            ))}
                            <Tooltip title="Thêm ảnh">
                                <Button onClick={pickImages} loading={upload.isPending} style={{ width: 72, height: 72 }} icon={<PictureOutlined style={{ fontSize: 20 }} />} />
                            </Tooltip>
                        </Space>
                        {upload.isPending && <div style={{ marginTop: 6 }}><Spin size="small" /> <Typography.Text type="secondary">Đang tải ảnh…</Typography.Text></div>}
                    </Form.Item>

                    <Form.Item name="enabled" label="Kích hoạt" valuePropName="checked"><Switch /></Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
