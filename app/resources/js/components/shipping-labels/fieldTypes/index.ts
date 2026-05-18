import type { ReactNode } from 'react';
import type { Field } from '@/lib/shippingLabelTypes';
import type { SampleContext } from '@/lib/labelEditor/sampleData';
import { QrFieldDef } from './QrFieldDef';
import { BarcodeFieldDef } from './BarcodeFieldDef';
import { TextFieldDef } from './TextFieldDef';
import { ImageFieldDef } from './ImageFieldDef';
import { DataFieldDef } from './DataFieldDef';
import { ItemsListFieldDef } from './ItemsListFieldDef';
import { DividerFieldDef } from './DividerFieldDef';
import { RectangleFieldDef } from './RectangleFieldDef';

export interface FieldDef<F extends Field = Field> {
    type: F['type'];
    label: string;
    icon: ReactNode;
    group: 'codes' | 'display' | 'data' | 'list' | 'shape';
    defaultProps: () => Omit<F, 'id'>;
    KonvaRenderer: React.FC<{ field: F; ctx: SampleContext; selected: boolean; zoom: number }>;
    InspectorPanel: React.FC<{ field: F; onChange: (patch: Partial<F>) => void }>;
}

export const FIELD_REGISTRY: Record<Field['type'], FieldDef> = {
    qr: QrFieldDef as FieldDef,
    barcode: BarcodeFieldDef as FieldDef,
    text: TextFieldDef as FieldDef,
    image: ImageFieldDef as FieldDef,
    data: DataFieldDef as FieldDef,
    items_list: ItemsListFieldDef as FieldDef,
    divider: DividerFieldDef as FieldDef,
    rectangle: RectangleFieldDef as FieldDef,
} as const;

export const FIELD_GROUPS: Array<{ key: FieldDef['group']; label: string }> = [
    { key: 'codes', label: 'Mã' }, { key: 'display', label: 'Hiển thị' },
    { key: 'data', label: 'Trường động' }, { key: 'list', label: 'Danh sách' }, { key: 'shape', label: 'Khung' },
];
