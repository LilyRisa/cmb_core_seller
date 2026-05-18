import { Form, Radio, Segmented } from 'antd';
import { QrcodeOutlined } from '@ant-design/icons';
import { Group, Rect, Text } from 'react-konva';
import type { QrField } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

export const QrFieldDef: FieldDef<QrField> = {
    type: 'qr', label: 'Mã QR', icon: <QrcodeOutlined />, group: 'codes',
    defaultProps: () => ({ type: 'qr', x: 5, y: 5, w: 25, h: 25, source: 'tracking_no', ecc: 'M' }),
    KonvaRenderer: ({ field, selected, zoom }) => {
        const size = Math.min(mm2px(field.w, zoom), mm2px(field.h, zoom));
        return (
            <Group x={mm2px(field.x, zoom)} y={mm2px(field.y, zoom)} rotation={field.rotation ?? 0}>
                <Rect width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} fill="#f5f5f5"
                      stroke={selected ? '#1677ff' : '#d9d9d9'} strokeWidth={1} dash={selected ? [4, 2] : []} />
                <Text width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)}
                      text="QR" fontSize={size * 0.25} align="center" verticalAlign="middle" fill="#8c8c8c" />
            </Group>
        );
    },
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Nguồn dữ liệu">
                <Radio.Group value={field.source} onChange={(e) => onChange({ source: e.target.value })}>
                    <Radio value="tracking_no">Mã vận đơn</Radio>
                    <Radio value="order_number">Mã đơn</Radio>
                </Radio.Group>
            </Form.Item>
            <Form.Item label="Mức chống lỗi (ECC)">
                <Segmented options={['L', 'M', 'Q', 'H']} value={field.ecc ?? 'M'}
                    onChange={(v) => onChange({ ecc: v as 'L' | 'M' | 'Q' | 'H' })} />
            </Form.Item>
        </>
    ),
};
