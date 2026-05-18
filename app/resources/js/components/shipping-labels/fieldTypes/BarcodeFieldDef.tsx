import { Form, Radio, Switch } from 'antd';
import { BarcodeOutlined } from '@ant-design/icons';
import { Group, Rect, Text } from 'react-konva';
import type { BarcodeField } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

export const BarcodeFieldDef: FieldDef<BarcodeField> = {
    type: 'barcode', label: 'Mã vạch', icon: <BarcodeOutlined />, group: 'codes',
    defaultProps: () => ({ type: 'barcode', x: 5, y: 5, w: 60, h: 15, source: 'tracking_no', showText: true }),
    KonvaRenderer: ({ field, selected, zoom }) => (
        <Group x={mm2px(field.x, zoom)} y={mm2px(field.y, zoom)} rotation={field.rotation ?? 0}>
            <Rect width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} fill="#fff"
                  stroke={selected ? '#1677ff' : '#d9d9d9'} strokeWidth={1} dash={selected ? [4, 2] : []} />
            {Array.from({ length: Math.floor(mm2px(field.w, zoom) / 3) }).map((_, i) => (
                <Rect key={i} x={i * 3 + 2} y={2}
                      width={i % 3 === 0 ? 2 : 1} height={mm2px(field.h, zoom) - (field.showText ? 14 : 4)} fill="#222" />
            ))}
            {field.showText && (
                <Text x={0} y={mm2px(field.h, zoom) - 12} width={mm2px(field.w, zoom)}
                      text="1234567890" fontSize={10} align="center" fontFamily="monospace" fill="#222" />
            )}
        </Group>
    ),
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Nguồn dữ liệu">
                <Radio.Group value={field.source} onChange={(e) => onChange({ source: e.target.value })}>
                    <Radio value="tracking_no">Mã vận đơn</Radio>
                    <Radio value="order_number">Mã đơn</Radio>
                </Radio.Group>
            </Form.Item>
            <Form.Item label="Hiện chữ bên dưới">
                <Switch checked={field.showText ?? true} onChange={(v) => onChange({ showText: v })} />
            </Form.Item>
        </>
    ),
};
