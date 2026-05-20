import { ColorPicker, Form, InputNumber } from 'antd';
import { MinusOutlined } from '@ant-design/icons';
import { Rect } from 'react-konva';
import type { DividerField } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

export const DividerFieldDef: FieldDef<DividerField> = {
    type: 'divider', label: 'Đường kẻ', icon: <MinusOutlined />, group: 'shape',
    defaultProps: () => ({ type: 'divider', x: 5, y: 5, w: 80, h: 1, thickness: 1, color: '#222222' }),
    KonvaRenderer: ({ field, selected, zoom }) => {
        const w = mm2px(field.w, zoom);
        const h = mm2px(field.h, zoom);
        const t = field.thickness ?? 1;
        return (
            <>
                <Rect width={w} height={h}
                      fill={selected ? 'rgba(22,119,255,0.1)' : 'transparent'}
                      stroke={selected ? '#1677ff' : 'transparent'} strokeWidth={1} dash={[4, 2]} />
                <Rect y={(h - t) / 2} width={w} height={t} fill={field.color ?? '#222222'} />
            </>
        );
    },
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
