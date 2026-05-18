import { ColorPicker, Form, InputNumber } from 'antd';
import { MinusOutlined } from '@ant-design/icons';
import { Group, Rect } from 'react-konva';
import type { DividerField } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

export const DividerFieldDef: FieldDef<DividerField> = {
    type: 'divider', label: 'Đường kẻ', icon: <MinusOutlined />, group: 'shape',
    defaultProps: () => ({ type: 'divider', x: 5, y: 5, w: 80, h: 1, thickness: 1, color: '#222222' }),
    KonvaRenderer: ({ field, selected, zoom }) => (
        <Group x={mm2px(field.x, zoom)} y={mm2px(field.y, zoom)} rotation={field.rotation ?? 0}>
            <Rect width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} fill={selected ? 'rgba(22,119,255,0.1)' : 'transparent'} />
            <Rect y={(mm2px(field.h, zoom) - (field.thickness ?? 1)) / 2}
                  width={mm2px(field.w, zoom)} height={field.thickness ?? 1} fill={field.color ?? '#222222'} />
        </Group>
    ),
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Độ dày (px)">
                <InputNumber min={1} max={8} value={field.thickness ?? 1} onChange={(v) => onChange({ thickness: v ?? 1 })} />
            </Form.Item>
            <Form.Item label="Màu">
                <ColorPicker value={field.color ?? '#222222'} onChange={(c) => onChange({ color: c.toHexString() })} />
            </Form.Item>
        </>
    ),
};
