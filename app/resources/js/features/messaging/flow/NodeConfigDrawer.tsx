import { useEffect } from 'react';
import { Alert, Button, Drawer, Form, Input, Radio, Select, Space, Typography } from 'antd';
import { DeleteOutlined, PlusOutlined } from '@ant-design/icons';
import { nanoid } from 'nanoid';
import type { Node } from '@xyflow/react';
import type { FlowNodeData, FlowNodeType } from '@/lib/messagingFlows';
import { metaFor } from './nodes';

/**
 * Drawer cấu hình node đang chọn — TOÀN FORM, không nhập JSON thô. Mỗi loại node
 * một form riêng. Ghi thẳng vào node.data qua onChange mỗi khi đổi giá trị.
 */
export function NodeConfigDrawer({
    node,
    open,
    onClose,
    onChange,
    readOnly,
}: {
    node: Node | null;
    open: boolean;
    onClose: () => void;
    onChange: (nodeId: string, data: FlowNodeData) => void;
    readOnly?: boolean;
}) {
    const [form] = Form.useForm();
    const type = node?.type as FlowNodeType | undefined;

    useEffect(() => {
        if (node) {
            form.setFieldsValue({ buttons: [], keywords: [], match: 'any', text: '', ...(node.data as FlowNodeData) });
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

                    {type === 'send_message' && (
                        <Form.Item name="text" label="Nội dung tin nhắn" rules={[{ required: true, message: 'Nhập nội dung' }]}>
                            <Input.TextArea rows={5} maxLength={2000} placeholder="vd: Chào bạn, shop có thể giúp gì ạ?" />
                        </Form.Item>
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
