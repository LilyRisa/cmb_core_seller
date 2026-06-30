import { useCallback, useEffect, useState } from 'react';
import { Alert, Button, Checkbox, Drawer, Form, Input, Radio, Select, Space, Typography } from 'antd';
import { DeleteOutlined, PlusOutlined } from '@ant-design/icons';
import { nanoid } from 'nanoid';
import type { Node } from '@xyflow/react';
import {
    type FlowAttachment,
    type FlowButton,
    type FlowNodeData,
    type FlowNodeType,
    type FlowStep,
    useUploadFlowMedia,
} from '@/lib/messagingFlows';
import { metaFor } from './nodes';
import { newStep } from './steps';
import { StepListEditor } from './StepListEditor';

/**
 * Khoi tao danh sach step khi mo drawer cho node send_message.
 * - Neu node.data.steps da co => dung nguyen.
 * - Nguoc lai migrate tu du lieu legacy (text / attachments / buttons).
 * - Khong bao gio xoa field cu -- chi doc, ghi steps moi vao ben canh.
 */
function deriveSteps(data: FlowNodeData): FlowStep[] {
    if (Array.isArray(data.steps) && data.steps.length > 0) {
        return data.steps as FlowStep[];
    }
    const migrated: FlowStep[] = [];
    const text = (data.text as string | undefined)?.trim();
    if (text) {
        migrated.push({ ...newStep('send_text'), text });
    }
    const attachments = data.attachments as FlowAttachment[] | undefined;
    if (Array.isArray(attachments)) {
        attachments.forEach((att) => {
            const kind = (att.kind === 'audio' ? 'file' : att.kind) as 'image' | 'video' | 'file';
            migrated.push({ ...newStep('send_media'), kind, attachment: att });
        });
    }
    const buttons = data.buttons as FlowButton[] | undefined;
    if (Array.isArray(buttons) && buttons.length > 0) {
        migrated.push({ ...newStep('send_buttons'), buttons });
    }
    return migrated;
}

/**
 * Drawer cau hinh node dang chon -- TOAN FORM, khong nhap JSON tho. Moi loai node
 * mot form rieng. Ghi thang vao node.data qua onChange moi khi doi gia tri.
 *
 * Node send_message dung StepListEditor (node-with-steps Phase 2A).
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
    const upload = useUploadFlowMedia(flowId);
    const type = node?.type as FlowNodeType | undefined;

    // ── State buoc cho send_message ──────────────────────────────────────────
    const [localSteps, setLocalSteps] = useState<FlowStep[]>(() =>
        node?.type === 'send_message' ? deriveSteps(node.data as FlowNodeData) : [],
    );

    // Khoi tao lai moi khi chuyen sang node khac (theo id)
    useEffect(() => {
        if (node?.type === 'send_message') {
            setLocalSteps(deriveSteps(node.data as FlowNodeData));
        } else {
            setLocalSteps([]);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [node?.id, type]);

    const handleStepsChange = useCallback(
        (steps: FlowStep[]) => {
            setLocalSteps(steps);
            if (node) {
                // Ghi steps vao node.data, giu nguyen cac field legacy de rollback an toan
                onChange(node.id, { ...(node.data as FlowNodeData), steps });
            }
        },
        [node, onChange],
    );

    // ── Form cho cac node type khac ──────────────────────────────────────────
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

                    {/* ── send_message: StepListEditor (node-with-steps Phase 2A) ── */}
                    {type === 'send_message' && (
                        <StepListEditor
                            value={localSteps}
                            onChange={handleStepsChange}
                            upload={upload}
                            readOnly={readOnly}
                        />
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
                                            Mỗi nút trả lời bước tạo 1 nhánh ra trên sơ đồ — nối nhánh tới bước kế tiếp.
                                        </Typography.Text>
                                    </div>
                                )}
                            </Form.List>
                        </>
                    )}

                    {type === 'send_comment_reply' && (
                        <>
                            <Form.Item name="text" label="Nội dung trả lời" rules={[{ required: true, message: 'Nhập nội dung' }]}>
                                <Input.TextArea rows={4} maxLength={2000} placeholder="vd: Cảm ơn bạn đã quan tâm! Shop nhắn riêng cho bạn nhé." />
                            </Form.Item>
                            <Typography.Text strong>Gửi tới</Typography.Text>
                            <div style={{ marginTop: 6, display: 'flex', flexDirection: 'column', gap: 4 }}>
                                <Form.Item name={['target', 'public']} valuePropName="checked" noStyle>
                                    <Checkbox>Trả lời công khai dưới bình luận</Checkbox>
                                </Form.Item>
                                <Form.Item name={['target', 'private']} valuePropName="checked" noStyle>
                                    <Checkbox>Nhắn tin riêng cho người bình luận</Checkbox>
                                </Form.Item>
                            </div>
                            <Alert style={{ marginTop: 10 }} type="info" showIcon message="Nhắn riêng chỉ gửi được 1 lần/bình luận, trong vòng 7 ngày (giới hạn Facebook)." />
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
                            <Alert type="info" showIcon message="Nhánh khớp chạy khi điều kiện đúng; nhánh không khớp chạy khi sai." />
                        </>
                    )}

                    {type === 'ai_reply' && (
                        <Alert type="info" showIcon message="AI tự soạn & gửi câu trả lời dựa trên kho tri thức + lịch sử hội thoại. Trả lời được => đi nhánh đã trả lời; gặp khiếu nại/hoàn tiền => đi nhánh cần người." />
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
