import { ColorPicker, Form, InputNumber } from 'antd';
import { BorderOutlined } from '@ant-design/icons';
import { Group, Rect } from 'react-konva';
import type { RectangleField } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

export const RectangleFieldDef: FieldDef<RectangleField> = {
    type: 'rectangle', label: 'Khung', icon: <BorderOutlined />, group: 'shape',
    defaultProps: () => ({ type: 'rectangle', x: 5, y: 5, w: 50, h: 30, borderThickness: 1, borderColor: '#222222', cornerRadius: 0, fillColor: '#ffffff' }),
    KonvaRenderer: ({ field, selected, zoom }) => (
        <Group x={mm2px(field.x, zoom)} y={mm2px(field.y, zoom)} rotation={field.rotation ?? 0}>
            <Rect width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)}
                  fill={field.fillColor ?? 'transparent'}
                  stroke={field.borderColor ?? '#222'} strokeWidth={field.borderThickness ?? 1}
                  cornerRadius={field.cornerRadius ?? 0} dash={selected ? [4, 2] : undefined} />
        </Group>
    ),
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Vien (px)">
                <InputNumber min={0} max={8} value={field.borderThickness ?? 1} onChange={(v) => onChange({ borderThickness: v ?? 1 })} />
            </Form.Item>
            <Form.Item label="Mau vien">
                <ColorPicker value={field.borderColor ?? '#222222'} onChange={(c) => onChange({ borderColor: c.toHexString() })} />
            </Form.Item>
            <Form.Item label="Bo goc (px)">
                <InputNumber min={0} max={20} value={field.cornerRadius ?? 0} onChange={(v) => onChange({ cornerRadius: v ?? 0 })} />
            </Form.Item>
            <Form.Item label="Mau nen">
                <ColorPicker value={field.fillColor ?? '#ffffff'} onChange={(c) => onChange({ fillColor: c.toHexString() })} />
            </Form.Item>
        </>
    ),
};
