import { useEffect } from 'react';
import { Alert, App as AntApp, Button, Drawer, Form, Input, Radio, Select, Space, Tag, Typography, Upload } from 'antd';
import { DeleteOutlined, PaperClipOutlined, PlusOutlined, UploadOutlined } from '@ant-design/icons';
import type { RcFile } from 'antd/es/upload';
import { nanoid } from 'nanoid';
import type { Node } from '@xyflow/react';
import { errorMessage } from '@/lib/api';
import { type FlowAttachment, type FlowNodeData, type FlowNodeType, mediaKindFromMime, useUploadFlowMedia } from '@/lib/messagingFlows';
import { metaFor } from './nodes';

const KIND_LABEL: Record<FlowAttachment['kind'], string> = { image: 'Ảnh', video: 'Video', audio: 'Âm thanh', file: 'Tệp' };

/**
 * Drawer cấu hình node đang chọn — TOÀN FORM, không nhập JSON thô. Mỗi loại node
 * một form riêng. Ghi thẳng vào node.data qua onChange mỗi khi đổi giá trị.
 */
export function NodeConfigDrawer({
    node,
    open,
    flowId,
    onClose,
    onChange,
    readOnly,
}: {
    node: Node | null;
    open: boolean;
    flowId: number;
    onClose: () => void;
    onChange: (nodeId: string, data: FlowNodeData) => void;
    readOnly?: boolean;
}) {
    const [form] = Form.useForm();
    const { message } = AntApp.useApp();
    const upload = useUploadFlowMedia(flowId);
    const type = node?.type as FlowNodeType | undefined;
    const attachments: FlowAttachment[] = Form.useWatch('attachments', form) ?? [];

    useEffect(() => {
        if (node) {
            form.setFieldsValue({ buttons: [], keywords: [], match: 'any', text: '', attachments: [], ...(node.data as FlowNodeData) });
        }
    }, [node, form]);

    const handleValues = () => {
        if (node) {
            onChange(node.id, form.getFieldsValue(true) as FlowNodeData);
        }
    };

    const handleUpload = (file: RcFile) => {
        const kind = mediaKindFromMime(file.type || '');
        upload.mutate({ file, kind }, {
            onSuccess: (att) => {
                form.setFieldValue('attachments', [...(form.getFieldValue('attachments') ?? []), att]);
                handleValues();
                message.success(`Đã tải lên ${att.filename ?? KIND_LABEL[att.kind]}`);
            },
            onError: (e) => message.error(errorMessage(e)),
        });
        return false; // tự upload, không để AntD gửi
    };

    const removeAttachment = (idx: number) => {
        form.setFieldValue('attachments', (form.getFieldValue('attachments') ?? []).filter((_: unknown, i: number) => i !== idx));
        handleValues();
    };

    return (
        <Drawer
            open={open}
            onClose={onClose}
            width={420}
            title={node ? `Cấu hình: ${metaFor(node.type as FlowNodeType)?.label ?? node.type}` : 'Cấu hình bước'}
            mask={false}
        >
            {node == null && <Typography.Text type="secondary">Chọn một bước trên sơ đồ để cấu hình.</Typography.Text>}

            {node != null && (
                <Form form={form} layout="vertical" disabled={readOnly} onValuesChange={handleValues}>
                    {type === 'trigger' && (
                        <Alert type="info" showIcon message="Đây là điểm bắt đầu của kịch bản. Cấu hình điều kiện kích hoạt ở thanh trên cùng." />
                    )}

                    {type === 'send_message' && (
                        <>
                            <Form.Item name="text" label="Nội dung tin nhắn" extra="Có thể để trống nếu chỉ gửi tệp đính kèm.">
                                <Input.TextArea rows={4} maxLength={2000} placeholder="vd: Chào bạn, shop có thể giúp gì ạ?" />
                            </Form.Item>

                            <Typography.Text strong>Đính kèm</Typography.Text>
                            <div style={{ marginTop: 6, display: 'flex', flexDirection: 'column', gap: 6 }}>
                                {attachments.map((a, idx) => (
                                    <div key={`${a.storage_path}-${idx}`} style={{ display: 'flex', alignItems: 'center', gap: 8, border: '1px solid #f0f0f0', borderRadius: 6, padding: '4px 8px' }}>
                                        <PaperClipOutlined />
                                        <Tag>{KIND_LABEL[a.kind]}</Tag>
                                        <span style={{ flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{a.filename ?? a.storage_path.split('/').pop()}</span>
                                        {!readOnly && <Button size="small" type="text" danger icon={<DeleteOutlined />} onClick={() => removeAttachment(idx)} />}
                                    </div>
                                ))}
                                {attachments.length === 0 && <Typography.Text type="secondary" style={{ fontSize: 12 }}>Chưa có tệp nào.</Typography.Text>}
                                <Upload beforeUpload={handleUpload} showUploadList={false} multiple disabled={readOnly}
                                    accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv">
                                    <Button icon={<UploadOutlined />} loading={upload.isPending} disabled={readOnly}>Tải lên ảnh / video / âm thanh / tệp</Button>
                                </Upload>
                                <Typography.Text type="secondary" style={{ fontSize: 12 }}>Mỗi tệp được gửi thành một tin riêng theo thứ tự.</Typography.Text>
                            </div>
                        </>
                    )}

                    {type === 'send_buttons' && (
                        <>
                            <Form.Item name="text" label="Nội dung tin nhắn" rules={[{ required: true, message: 'Nhập nội dung' }]}>
                                <Input.TextArea rows={3} maxLength={640} placeholder="vd: Bạn cần hỗ trợ gì ạ?" />
                            </Form.Item>
                            <Typography.Text strong>Nút bấm (tối đa 3)</Typography.Text>
                            <Form.List name="buttons">
                                {(fields, { add, remove }) => (
                                    <div style={{ marginTop: 8, display: 'flex', flexDirection: 'column', gap: 8 }}>
                                        {fields.map((field) => (
                                            <ButtonRow key={field.key} field={field} remove={remove} form={form} />
                                        ))}
                                        <Button
                                            type="dashed"
                                            icon={<PlusOutlined />}
                                            disabled={readOnly || fields.length >= 3}
                                            onClick={() => { add({ id: nanoid(8), label: '', type: 'postback' }); }}
                                        >
                                            Thêm nút
                                        </Button>
                                        <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                                            Mỗi nút "trả lời bước" tạo 1 nhánh ra trên sơ đồ — nối nhánh tới bước kế tiếp.
                                        </Typography.Text>
                                    </div>
                                )}
                            </Form.List>
                        </>
                    )}

                    {type === 'condition' && (
                        <>
                            <Form.Item name="keywords" label="Từ khoá" tooltip="Gõ từ khoá rồi Enter. Khớp khi nội dung khách chứa từ khoá.">
                                <Select mode="tags" tokenSeparators={[',']} placeholder="vd: giá, ship, còn hàng" open={false} suffixIcon={null} />
                            </Form.Item>
                            <Form.Item name="match" label="Điều kiện khớp">
                                <Radio.Group optionType="button" buttonStyle="solid">
                                    <Radio.Button value="any">Chứa bất kỳ</Radio.Button>
                                    <Radio.Button value="all">Chứa tất cả</Radio.Button>
                                </Radio.Group>
                            </Form.Item>
                            <Alert type="info" showIcon message="Nhánh “khớp” chạy khi điều kiện đúng; nhánh “không khớp” chạy khi sai." />
                        </>
                    )}

                    {type === 'ai_reply' && (
                        <Alert type="info" showIcon message="AI tự soạn & gửi câu trả lời dựa trên kho tri thức + lịch sử hội thoại (chặn intent nhạy cảm, ẩn PII). Trả lời được ⇒ đi nhánh “đã trả lời”; gặp khiếu nại/hoàn tiền, hết hạn mức hoặc lỗi ⇒ đi nhánh “cần người”." />
                    )}

                    {type === 'wait_reply' && (
                        <Alert type="info" showIcon message="Kịch bản dừng lại tới khi khách nhắn tin tiếp, rồi đi theo nhánh ra của bước này." />
                    )}

                    {type === 'end' && <Alert type="info" showIcon message="Kết thúc kịch bản tại đây." />}
                </Form>
            )}
        </Drawer>
    );
}

function ButtonRow({ field, remove, form }: { field: { key: number; name: number }; remove: (i: number) => void; form: ReturnType<typeof Form.useForm>[0] }) {
    const type = Form.useWatch(['buttons', field.name, 'type'], form);
    return (
        <div style={{ border: '1px solid #f0f0f0', borderRadius: 6, padding: 8 }}>
            <Space.Compact block>
                <Form.Item name={[field.name, 'label']} noStyle rules={[{ required: true, message: 'Nhập nhãn nút' }]}>
                    <Input placeholder="Nhãn nút (vd: Mua hàng)" maxLength={20} />
                </Form.Item>
                <Button danger icon={<DeleteOutlined />} onClick={() => remove(field.name)} />
            </Space.Compact>
            <Form.Item name={[field.name, 'type']} noStyle>
                <Radio.Group size="small" optionType="button" style={{ marginTop: 6 }}>
                    <Radio.Button value="postback">Trả lời bước</Radio.Button>
                    <Radio.Button value="url">Mở liên kết</Radio.Button>
                </Radio.Group>
            </Form.Item>
            {type === 'url' && (
                <Form.Item name={[field.name, 'url']} noStyle rules={[{ required: true, type: 'url', message: 'Nhập URL hợp lệ' }]}>
                    <Input placeholder="https://..." style={{ marginTop: 6 }} />
                </Form.Item>
            )}
        </div>
    );
}
