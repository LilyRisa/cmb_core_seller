import { Form, Input, InputNumber, Radio, Segmented } from 'antd';
import { DatabaseOutlined } from '@ant-design/icons';
import { Group, Rect, Text } from 'react-konva';
import type { DataField, DataKey } from '@/lib/shippingLabelTypes';
import { DATA_KEYS } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

const KEY_LABELS: Record<DataKey, string> = {
    carrier_logo: 'Logo DVVC', carrier_name: 'Ten DVVC',
    sender_name: 'Ten nguoi gui', sender_phone: 'SDT nguoi gui', sender_address: 'Dia chi gui',
    recipient_name: 'Ten nguoi nhan', recipient_phone: 'SDT nguoi nhan', recipient_address: 'Dia chi nhan (day du)',
    recipient_address_detail: 'Dia chi chi tiet', recipient_address_admin: 'Phuong/Quan/Tinh',
    order_number: 'Ma don', tracking_no: 'Ma van don',
    cod: 'COD', weight: 'Khoi luong', print_note: 'Ghi chu in',
    created_at: 'Ngay tao', total_qty: 'Tong SL',
};

export const DataFieldDef: FieldDef<DataField> = {
    type: 'data', label: 'Truong dong', icon: <DatabaseOutlined />, group: 'data',
    defaultProps: () => ({ type: 'data', x: 5, y: 5, w: 50, h: 6, key: 'recipient_name', style: { fontSize: 12, fontWeight: 700, align: 'left' } }),
    KonvaRenderer: ({ field, ctx, selected, zoom }) => {
        const sampleText = field.key === 'carrier_logo' ? (ctx.carrier_logo || 'GHN') : ((field.prefix ?? '') + (ctx[field.key] ?? '') + (field.suffix ?? ''));
        return (
            <Group x={mm2px(field.x, zoom)} y={mm2px(field.y, zoom)} rotation={field.rotation ?? 0}>
                <Rect width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)}
                      stroke={selected ? '#1677ff' : 'transparent'} strokeWidth={1} dash={[4, 2]} />
                <Text width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} padding={1}
                      text={sampleText} fontSize={field.style.fontSize * zoom * 0.9}
                      fontStyle={field.style.fontWeight === 700 ? 'bold' : field.style.fontWeight === 600 ? '600' : 'normal'}
                      align={field.style.align ?? 'left'} fill={field.style.color ?? '#222'} verticalAlign="middle" wrap="word" />
            </Group>
        );
    },
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Truong">
                <Radio.Group value={field.key} onChange={(e) => onChange({ key: e.target.value as DataKey })} style={{ display: 'flex', flexDirection: 'column' }}>
                    {DATA_KEYS.map((k) => <Radio key={k} value={k}>{KEY_LABELS[k]}</Radio>)}
                </Radio.Group>
            </Form.Item>
            <Form.Item label="Tien to">
                <Input value={field.prefix ?? ''} onChange={(e) => onChange({ prefix: e.target.value })} maxLength={32} />
            </Form.Item>
            <Form.Item label="Hau to">
                <Input value={field.suffix ?? ''} onChange={(e) => onChange({ suffix: e.target.value })} maxLength={32} />
            </Form.Item>
            <Form.Item label="Co chu (pt)">
                <InputNumber min={6} max={48} value={field.style.fontSize}
                    onChange={(v) => onChange({ style: { ...field.style, fontSize: v ?? 11 } })} />
            </Form.Item>
            <Form.Item label="Dam">
                <Segmented options={[{ label: 'Thuong', value: 400 }, { label: 'Dam vua', value: 600 }, { label: 'Dam', value: 700 }]}
                    value={field.style.fontWeight ?? 400}
                    onChange={(v) => onChange({ style: { ...field.style, fontWeight: v as 400 | 600 | 700 } })} />
            </Form.Item>
            <Form.Item label="Can">
                <Segmented options={[{ label: 'Trai', value: 'left' }, { label: 'Giua', value: 'center' }, { label: 'Phai', value: 'right' }]}
                    value={field.style.align ?? 'left'}
                    onChange={(v) => onChange({ style: { ...field.style, align: v as 'left' | 'center' | 'right' } })} />
            </Form.Item>
        </>
    ),
};
