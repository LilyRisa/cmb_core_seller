import { Form, Input, InputNumber, Segmented } from 'antd';
import { FontSizeOutlined } from '@ant-design/icons';
import { Group, Rect, Text } from 'react-konva';
import type { TextField } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

export const TextFieldDef: FieldDef<TextField> = {
    type: 'text',
    label: 'Văn bản',
    icon: <FontSizeOutlined />,
    group: 'display',
    defaultProps: () => ({ type: 'text', x: 5, y: 5, w: 50, h: 6, text: 'Văn bản', style: { fontSize: 11, fontWeight: 400, align: 'left' } }),
    KonvaRenderer: ({ field, selected, zoom }) => (
        <Group x={mm2px(field.x, zoom)} y={mm2px(field.y, zoom)} rotation={field.rotation ?? 0}>
            <Rect width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} stroke={selected ? '#1677ff' : 'transparent'} strokeWidth={1} dash={[4, 2]} />
            <Text width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} padding={1}
                  text={field.text} fontSize={field.style.fontSize * zoom * 0.9}
                  fontStyle={field.style.fontWeight === 700 ? 'bold' : 'normal'} align={field.style.align ?? 'left'}
                  fill={field.style.color ?? '#222'} verticalAlign="middle" />
        </Group>
    ),
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Nội dung">
                <Input.TextArea rows={2} value={field.text} onChange={(e) => onChange({ text: e.target.value })} maxLength={500} />
            </Form.Item>
            <Form.Item label="Cỡ chữ (pt)">
                <InputNumber min={6} max={48} value={field.style.fontSize}
                    onChange={(v) => onChange({ style: { ...field.style, fontSize: v ?? 11 } })} />
            </Form.Item>
            <Form.Item label="Đậm">
                <Segmented options={[{ label: 'Thường', value: 400 }, { label: 'Đậm vừa', value: 600 }, { label: 'Đậm', value: 700 }]}
                    value={field.style.fontWeight ?? 400}
                    onChange={(v) => onChange({ style: { ...field.style, fontWeight: v as 400 | 600 | 700 } })} />
            </Form.Item>
            <Form.Item label="Căn">
                <Segmented options={[{ label: 'Trái', value: 'left' }, { label: 'Giữa', value: 'center' }, { label: 'Phải', value: 'right' }]}
                    value={field.style.align ?? 'left'}
                    onChange={(v) => onChange({ style: { ...field.style, align: v as 'left' | 'center' | 'right' } })} />
            </Form.Item>
        </>
    ),
};
