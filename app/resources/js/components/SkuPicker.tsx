import { useState } from 'react';
import { Avatar, Empty, Input, Popover, Space, Spin, Typography } from 'antd';
import { CloseCircleFilled, PictureOutlined, SearchOutlined } from '@ant-design/icons';
import { useSku, useSkus, type Sku } from '@/lib/inventory';

type SkuLite = Pick<Sku, 'sku_code' | 'name' | 'image_url' | 'spu_code'>;

/** One SKU rendered as image · name · code (used both in the list and as the field value). */
function SkuLine({ sku, avatarSize = 40, maxTextWidth = 240 }: { sku: SkuLite; avatarSize?: number; maxTextWidth?: number }) {
    return (
        <Space size={10} align="center" style={{ minWidth: 0, width: '100%' }}>
            <Avatar shape="square" size={avatarSize} src={sku.image_url ?? undefined} icon={<PictureOutlined />} style={{ background: '#f5f5f5', color: '#bfbfbf', flex: 'none' }} />
            <Space direction="vertical" size={0} style={{ minWidth: 0, flex: 'auto', textAlign: 'left' }}>
                <Typography.Text strong ellipsis={{ tooltip: sku.name }} style={{ display: 'block', maxWidth: maxTextWidth }}>{sku.name}</Typography.Text>
                <Typography.Text type="secondary" ellipsis={{ tooltip: `${sku.sku_code}${sku.spu_code ? ` · SPU: ${sku.spu_code}` : ''}` }} style={{ fontSize: 12, display: 'block', maxWidth: maxTextWidth }}>
                    SKU: {sku.sku_code}{sku.spu_code ? ` · SPU: ${sku.spu_code}` : ''}
                </Typography.Text>
            </Space>
        </Space>
    );
}

/**
 * Inline master-SKU picker — a search box over a scrollable list where each row shows the
 * SKU image, name and code. Replaces the old `<Select>` dropdowns for picking a system SKU
 * (mirrors the BigSeller "ghép nối SKU hàng hoá" panel, `ui_example/ghep_noi_sku.png`).
 * One system SKU may be linked to many channel SKUs — each linking row uses one picker.
 */
export function SkuPicker({ value, onChange, height = 260, width = 340 }: { value?: number; onChange?: (id?: number) => void; height?: number; width?: number }) {
    const [q, setQ] = useState('');
    const { data, isFetching } = useSkus({ q: q || undefined, per_page: 50 });
    const items = data?.data ?? [];
    return (
        <div style={{ border: '1px solid #d9d9d9', borderRadius: 8, overflow: 'hidden', width }}>
            <Input allowClear autoFocus prefix={<SearchOutlined style={{ color: '#bfbfbf' }} />} placeholder="Tìm mã / tên / barcode SKU…" variant="borderless"
                style={{ borderRadius: 0, borderBottom: '1px solid #f0f0f0' }} onChange={(e) => setQ(e.target.value)} />
            <div style={{ maxHeight: height, overflowY: 'auto' }}>
                {isFetching && items.length === 0 ? (
                    <div style={{ padding: 24, textAlign: 'center' }}><Spin size="small" /></div>
                ) : items.length === 0 ? (
                    <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Không có SKU phù hợp" style={{ margin: 16 }} />
                ) : items.map((s) => {
                    const selected = s.id === value;
                    return (
                        <div key={s.id} onClick={() => onChange?.(selected ? undefined : s.id)}
                            style={{ padding: '8px 12px', cursor: 'pointer', background: selected ? '#e6f4ff' : undefined, borderBottom: '1px solid #fafafa' }}>
                            <SkuLine sku={s} />
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

/**
 * Compact form field: a box showing the picked SKU (image · name · code); clicking it opens
 * the list picker in a popover. Works as an AntD form control (`value` / `onChange` are the SKU id).
 */
export function SkuPickerField({ value, onChange, placeholder = 'Chọn master SKU…', disabled, width = '100%', allowClear = true }: {
    value?: number; onChange?: (id?: number) => void; placeholder?: string; disabled?: boolean; width?: number | string; allowClear?: boolean;
}) {
    const [open, setOpen] = useState(false);
    const { data: sku } = useSku(value);
    const box = (
        <div style={{ width, minHeight: 40, border: '1px solid #d9d9d9', borderRadius: 8, padding: '4px 8px', display: 'flex', alignItems: 'center', gap: 8, cursor: disabled ? 'not-allowed' : 'pointer', background: disabled ? 'rgba(0,0,0,0.04)' : '#fff' }}>
            {value == null ? (
                <Typography.Text type="secondary" ellipsis style={{ flex: 'auto' }}>{placeholder}</Typography.Text>
            ) : (
                <div style={{ flex: 'auto', minWidth: 0 }}>{sku ? <SkuLine sku={sku} avatarSize={30} maxTextWidth={220} /> : <Typography.Text type="secondary">SKU #{value}</Typography.Text>}</div>
            )}
            {allowClear && value != null && !disabled && (
                <CloseCircleFilled style={{ color: '#bfbfbf', flex: 'none' }} onClick={(e) => { e.stopPropagation(); onChange?.(undefined); }} />
            )}
        </div>
    );
    if (disabled) return box;
    return (
        <Popover trigger="click" open={open} onOpenChange={setOpen} placement="bottomLeft" destroyTooltipOnHide
            content={<SkuPicker value={value} onChange={(id) => { onChange?.(id); setOpen(false); }} />}>
            {box}
        </Popover>
    );
}
