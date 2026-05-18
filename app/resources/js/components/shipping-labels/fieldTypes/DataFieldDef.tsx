import { Form, Input, InputNumber, Radio, Segmented } from 'antd';
import { DatabaseOutlined } from '@ant-design/icons';
import { Rect, Text } from 'react-konva';
import type { DataField, DataKey } from '@/lib/shippingLabelTypes';
import { DATA_KEYS } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

const KEY_LABELS: Record<DataKey, string> = {
    carrier_logo: 'Logo ĐVVC',
    carrier_name: 'Tên ĐVVC',
    sender_name: 'Tên người gửi',
    sender_phone: 'SĐT người gửi',
    sender_address: 'Địa chỉ gửi',
    recipient_name: 'Tên người nhận',
    recipient_phone: 'SĐT người nhận',
    recipient_address: 'Địa chỉ nhận (đầy đủ)',
    recipient_address_detail: 'Địa chỉ chi tiết',
    recipient_address_admin: 'Phường/Quận/Tỉnh',
    order_number: 'Mã đơn',
    tracking_no: 'Mã vận đơn',
    cod: 'COD',
    weight: 'Khối lượng',
    print_note: 'Ghi chú in',
    created_at: 'Ngày tạo',
    total_qty: 'Tổng SL',
};

export const DataFieldDef: FieldDef<DataField> = {
    type: 'data', label: 'Trường động', icon: <DatabaseOutlined />, group: 'data',
    defaultProps: () => ({ type: 'data', x: 5, y: 5, w: 50, h: 6, key: 'recipient_name', style: { fontSize: 12, fontWeight: 700, align: 'left' } }),
    KonvaRenderer: ({ field, ctx, selected, zoom }) => {
        const sampleText = field.key === 'carrier_logo' ? (ctx.carrier_logo || 'GHN') : ((field.prefix ?? '') + (ctx[field.key] ?? '') + (field.suffix ?? ''));
        const w = mm2px(field.w, zoom);
        const h = mm2px(field.h, zoom);
        return (
            <>
                <Rect width={w} height={h} stroke={selected ? '#1677ff' : 'transparent'} strokeWidth={1} dash={[4, 2]} />
                <Text width={w} height={h} padding={1}
                      text={sampleText} fontSize={field.style.fontSize * zoom * 0.9}
                      fontStyle={field.style.fontWeight === 700 ? 'bold' : field.style.fontWeight === 600 ? '600' : 'normal'}
                      align={field.style.align ?? 'left'} fill={field.style.color ?? '#222'} verticalAlign="middle" wrap="word" />
            </>
        );
    },
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Trường">
                <Radio.Group value={field.key} onChange={(e) => onChange({ key: e.target.value as DataKey })} style={{ display: 'flex', flexDirection: 'column' }}>
                    {DATA_KEYS.map((k) => <Radio key={k} value={k}>{KEY_LABELS[k]}</Radio>)}
                </Radio.Group>
            </Form.Item>
            <Form.Item label="Tiền tố">
                <Input value={field.prefix ?? ''} onChange={(e) => onChange({ prefix: e.target.value })} maxLength={32} />
            </Form.Item>
            <Form.Item label="Hậu tố">
                <Input value={field.suffix ?? ''} onChange={(e) => onChange({ suffix: e.target.value })} maxLength={32} />
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
