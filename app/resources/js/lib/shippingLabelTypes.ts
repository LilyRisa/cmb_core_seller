export type Paper = 'A4' | 'A5' | 'A6' | '100x150mm' | '80mm' | 'custom';

export const PAPER_PRESETS: Record<Exclude<Paper, 'custom'>, { w: number; h: number; label: string }> = {
    A4: { w: 210, h: 297, label: 'A4 (210×297mm)' },
    A5: { w: 148, h: 210, label: 'A5 (148×210mm)' },
    A6: { w: 105, h: 148, label: 'A6 (105×148mm)' },
    '100x150mm': { w: 100, h: 150, label: '100×150mm (tem nhiệt)' },
    '80mm': { w: 80, h: 0, label: '80mm cuộn (auto)' },
};

export type TextStyle = {
    fontSize: number;
    fontWeight?: 400 | 600 | 700;
    align?: 'left' | 'center' | 'right';
    color?: string;
    lineHeight?: number;
};

export type DataKey =
    | 'carrier_logo' | 'carrier_name'
    | 'sender_name' | 'sender_phone' | 'sender_address'
    | 'recipient_name' | 'recipient_phone' | 'recipient_address'
    | 'recipient_address_detail' | 'recipient_address_admin'
    | 'order_number' | 'tracking_no'
    | 'cod' | 'weight' | 'print_note' | 'created_at' | 'total_qty';

export const DATA_KEYS: DataKey[] = [
    'carrier_logo', 'carrier_name',
    'sender_name', 'sender_phone', 'sender_address',
    'recipient_name', 'recipient_phone', 'recipient_address',
    'recipient_address_detail', 'recipient_address_admin',
    'order_number', 'tracking_no',
    'cod', 'weight', 'print_note', 'created_at', 'total_qty',
];

export type FieldBase = { id: string; x: number; y: number; w: number; h: number; rotation?: number };

export type QrField        = FieldBase & { type: 'qr'; source: 'tracking_no' | 'order_number'; ecc?: 'L' | 'M' | 'Q' | 'H' };
export type BarcodeField   = FieldBase & { type: 'barcode'; source: 'tracking_no' | 'order_number'; format?: 'code128'; showText?: boolean };
export type TextField      = FieldBase & { type: 'text'; text: string; style: TextStyle };
export type ImageField     = FieldBase & { type: 'image'; assetPath: string; fit?: 'contain' | 'cover' };
export type DataField      = FieldBase & { type: 'data'; key: DataKey; style: TextStyle; prefix?: string; suffix?: string };
export type ItemsListField = FieldBase & { type: 'items_list'; style: TextStyle; format?: 'bullet' | 'numbered'; maxRows?: number };
export type DividerField   = FieldBase & { type: 'divider'; thickness?: number; color?: string };
export type RectangleField = FieldBase & { type: 'rectangle'; borderThickness?: number; borderColor?: string; cornerRadius?: number; fillColor?: string };

export type Field = QrField | BarcodeField | TextField | ImageField | DataField | ItemsListField | DividerField | RectangleField;

export type Template = {
    id: number;
    name: string;
    paper: Paper;
    paper_w_mm: number;
    paper_h_mm: number;
    schema_version: number;
    schema: { fields: Field[] };
    is_default: boolean;
    created_at: string;
    updated_at: string;
};

export type SampleProfile = 'one_item_short_address' | 'three_items_long_address' | 'cod_with_print_note';
export const SAMPLE_PROFILES: SampleProfile[] = ['one_item_short_address', 'three_items_long_address', 'cod_with_print_note'];
