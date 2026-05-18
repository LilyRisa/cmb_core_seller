import { Form, Input, Segmented } from 'antd';
import { PictureOutlined } from '@ant-design/icons';
import { Group, Rect, Text } from 'react-konva';
import type { ImageField } from '@/lib/shippingLabelTypes';
import { mm2px } from '@/lib/labelEditor/coords';
import type { FieldDef } from './index';

export const ImageFieldDef: FieldDef<ImageField> = {
    type: 'image', label: 'Hình ảnh', icon: <PictureOutlined />, group: 'display',
    defaultProps: () => ({ type: 'image', x: 5, y: 5, w: 20, h: 20, assetPath: '', fit: 'contain' }),
    KonvaRenderer: ({ field, selected, zoom }) => (
        <Group x={mm2px(field.x, zoom)} y={mm2px(field.y, zoom)} rotation={field.rotation ?? 0}>
            <Rect width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)} fill="#fafafa"
                  stroke={selected ? '#1677ff' : '#d9d9d9'} strokeWidth={1} dash={selected ? [4, 2] : []} />
            <Text width={mm2px(field.w, zoom)} height={mm2px(field.h, zoom)}
                  text="Anh" fontSize={Math.min(mm2px(field.w, zoom), mm2px(field.h, zoom)) * 0.4} align="center" verticalAlign="middle" fill="#8c8c8c" />
        </Group>
    ),
    InspectorPanel: ({ field, onChange }) => (
        <>
            <Form.Item label="Đường dẫn ảnh (R2 path / URL)">
                <Input value={field.assetPath} onChange={(e) => onChange({ assetPath: e.target.value })} placeholder="logos/shop.png" />
            </Form.Item>
            <Form.Item label="Cách lấp khung">
                <Segmented options={[{ label: 'Vừa khung', value: 'contain' }, { label: 'Lấp đầy', value: 'cover' }]}
                    value={field.fit ?? 'contain'} onChange={(v) => onChange({ fit: v as 'contain' | 'cover' })} />
            </Form.Item>
        </>
    ),
};
