import { Button, Empty, Form, InputNumber, Space, Typography } from 'antd';
import { DeleteOutlined } from '@ant-design/icons';
import { useEditorStore } from '@/lib/labelEditor/editorStore';
import { FIELD_REGISTRY } from './fieldTypes';
import type { Field } from '@/lib/shippingLabelTypes';

export function FieldInspector() {
    const selection = useEditorStore((s) => s.selection);
    const fields = useEditorStore((s) => s.fields);
    const updateField = useEditorStore((s) => s.updateField);
    const commitTransform = useEditorStore((s) => s.commitTransform);
    const removeFields = useEditorStore((s) => s.removeFields);

    const selected = fields.find((f) => f.id === selection[0]);
    if (!selected) {
        return <div style={{ padding: 16 }}><Empty description="Chọn 1 trường để chỉnh sửa" image={Empty.PRESENTED_IMAGE_SIMPLE} /></div>;
    }
    const def = FIELD_REGISTRY[selected.type];
    const Panel = def.InspectorPanel as React.FC<{ field: Field; onChange: (p: Partial<Field>) => void }>;

    return (
        <div style={{ padding: 12, width: 280, borderLeft: '1px solid #f0f0f0', overflow: 'auto' }}>
            <Space direction="vertical" size={8} style={{ width: '100%' }}>
                <Typography.Text strong>{def.label}</Typography.Text>
                <Form layout="vertical" size="small">
                    <Space.Compact block>
                        <Form.Item label="X" style={{ flex: 1, margin: 0 }}>
                            <InputNumber min={0} value={selected.x}
                                onChange={(v) => commitTransform(selected.id, { x: v ?? 0, y: selected.y, w: selected.w, h: selected.h })} />
                        </Form.Item>
                        <Form.Item label="Y" style={{ flex: 1, margin: 0 }}>
                            <InputNumber min={0} value={selected.y}
                                onChange={(v) => commitTransform(selected.id, { x: selected.x, y: v ?? 0, w: selected.w, h: selected.h })} />
                        </Form.Item>
                    </Space.Compact>
                    <Space.Compact block style={{ marginTop: 8 }}>
                        <Form.Item label="W" style={{ flex: 1, margin: 0 }}>
                            <InputNumber min={5} value={selected.w}
                                onChange={(v) => commitTransform(selected.id, { x: selected.x, y: selected.y, w: v ?? 5, h: selected.h })} />
                        </Form.Item>
                        <Form.Item label="H" style={{ flex: 1, margin: 0 }}>
                            <InputNumber min={5} value={selected.h}
                                onChange={(v) => commitTransform(selected.id, { x: selected.x, y: selected.y, w: selected.w, h: v ?? 5 })} />
                        </Form.Item>
                    </Space.Compact>
                    <Panel field={selected} onChange={(p) => updateField(selected.id, p)} />
                    <Button danger icon={<DeleteOutlined />} block onClick={() => removeFields([selected.id])} style={{ marginTop: 12 }}>
                        Xoá trường
                    </Button>
                </Form>
            </Space>
        </div>
    );
}
