import { Form, InputNumber, Segmented } from 'antd';
import { UnorderedListOutlined } from '@ant-design/icons';
import { Rect, Text } from 'react-konva';
import type { ItemsListField } from '@/lib/shippingLabelTypes';
import { mm2px, ptToCanvasPx } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

export const ItemsListFieldDef: FieldDef<ItemsListField> = {
    type: 'items_list', label: 'Danh sách SP', icon: <UnorderedListOutlined />, group: 'list',
    defaultProps: () => ({ type: 'items_list', x: 5, y: 5, w: 80, h: 30, style: { fontSize: 10 }, format: 'bullet', maxRows: 5 }),
    KonvaRenderer: ({ field, ctx, selected, zoom }) => {
        const items = ctx.items.slice(0, field.maxRows ?? ctx.items.length);
        const lines = items.map((it, i) => ((field.format ?? 'bullet') === 'numbered' ? `${i + 1}.` : '•') + ' ' + it.name + ' x ' + it.qty);
        const w = mm2px(field.w, zoom);
        const h = mm2px(field.h, zoom);
        return (
            <>
                <Rect width={w} height={h} stroke={selected ? '#1677ff' : 'transparent'} strokeWidth={1} dash={[4, 2]} />
                <Text width={w} height={h} padding={1}
                      text={lines.join('\n')} fontSize={ptToCanvasPx(field.style.fontSize, zoom)} lineHeight={field.style.lineHeight ?? 1.25} fill="#222" wrap="word" />
            </>
        );
    },
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Định dạng">
                <Segmented options={[{ label: 'Bullet', value: 'bullet' }, { label: 'So TT', value: 'numbered' }]}
                    value={field.format ?? 'bullet'} onChange={(v) => onChange({ format: v as 'bullet' | 'numbered' })} />
            </Form.Item>
            <Form.Item label="Số dòng tối đa">
                <InputNumber min={1} max={50} value={field.maxRows ?? 5} onChange={(v) => onChange({ maxRows: v ?? 5 })} />
            </Form.Item>
            <Form.Item label="Cỡ chữ (pt)">
                <InputNumber min={6} max={24} value={field.style.fontSize}
                    onChange={(v) => onChange({ style: { ...field.style, fontSize: v ?? 10 } })} />
            </Form.Item>
        </>
    ),
};
