import { Form, Input, InputNumber, Segmented } from 'antd';
import { FontSizeOutlined } from '@ant-design/icons';
import { Rect, Text } from 'react-konva';
import type { TextField } from '@/lib/shippingLabelTypes';
import { mm2px, ptToCanvasPx } from '@/lib/labelEditor/coords';
import { LABEL_FONT_STACK, fitBlockPx } from '@/lib/labelEditor/fitText';
import type { FieldDef } from './index';

export const TextFieldDef: FieldDef<TextField> = {
    type: 'text',
    label: 'Văn bản',
    icon: <FontSizeOutlined />,
    group: 'display',
    defaultProps: () => ({ type: 'text', x: 5, y: 5, w: 50, h: 6, text: 'Văn bản', style: { fontSize: 11, fontWeight: 400, align: 'left' } }),
    KonvaRenderer: ({ field, selected, zoom }) => {
        const w = mm2px(field.w, zoom);
        const h = mm2px(field.h, zoom);
        const designFs = ptToCanvasPx(field.style.fontSize, zoom);
        const fontStyle = field.style.fontWeight === 700 ? 'bold' : 'normal';
        const fs = fitBlockPx(field.text, w, h, designFs, fontStyle, 1.15, zoom);
        return (
            <>
                <Rect width={w} height={h} stroke={selected ? '#1677ff' : 'transparent'} strokeWidth={1} dash={[4, 2]} />
                <Text width={w} height={h} padding={1}
                      text={field.text} fontSize={fs} lineHeight={1.15} fontFamily={LABEL_FONT_STACK}
                      fontStyle={fontStyle} align={field.style.align ?? 'left'}
                      fill={field.style.color ?? '#222'} verticalAlign="middle" wrap="word" />
            </>
        );
    },
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
