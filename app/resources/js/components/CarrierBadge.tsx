import { Space, Tag } from 'antd';
import { CarOutlined, EditOutlined } from '@ant-design/icons';

/**
 * Badge tên ĐVVC + nhãn nguồn đơn (đơn sàn vs đơn tự tạo). SPEC 0021.
 *
 * Quy ước carrier code:
 *   - `ghn` / `ghtk` / `jt` / `viettelpost` / … → đơn sàn (TikTok/Shopee/Lazada gói qua ĐVVC này).
 *   - `manual_ghn` / `manual_ghtk` / … → ĐƠN TỰ TẠO (prefix `manual_` do ShipmentService gắn để phân biệt).
 *   - `manual` → đơn tự tạo + tự vận chuyển (không qua ĐVVC nào).
 *
 * Render: badge ĐVVC + chấm/tag nhỏ "Tự tạo" cho `manual_*` để vận hành kho phân biệt được nhanh.
 */
export const CARRIER_META: Record<string, { name: string; color: string }> = {
    ghn: { name: 'GHN', color: 'green' },
    ghtk: { name: 'GHTK', color: 'orange' },
    jt: { name: 'J&T', color: 'red' },
    viettelpost: { name: 'Viettel Post', color: 'volcano' },
    ninjavan: { name: 'NinjaVan', color: 'magenta' },
    spx: { name: 'SPX', color: 'geekblue' },
    vnpost: { name: 'VNPost', color: 'gold' },
    ahamove: { name: 'Ahamove', color: 'cyan' },
    manual: { name: 'Tự vận chuyển', color: 'default' },
};

/**
 * Parse carrier code → { base, isManual }.
 * 'manual_ghn' → { base: 'ghn', isManual: true }
 * 'ghn'        → { base: 'ghn', isManual: false }
 * 'manual'     → { base: 'manual', isManual: true }
 */
export function parseCarrier(code: string | null | undefined): { base: string; isManual: boolean } {
    if (!code) return { base: '', isManual: false };
    if (code === 'manual') return { base: 'manual', isManual: true };
    if (code.startsWith('manual_')) {
        const base = code.slice(7);
        return { base: base || 'manual', isManual: true };
    }
    return { base: code, isManual: false };
}

export function carrierDisplayName(code: string | null | undefined): string {
    const { base } = parseCarrier(code);
    if (!base) return '—';
    return CARRIER_META[base.toLowerCase()]?.name ?? base;
}

export function CarrierBadge({ code, size = 'small' }: { code: string | null | undefined; size?: 'small' | 'default' }) {
    if (!code) return <span style={{ color: '#bfbfbf' }}>—</span>;
    const { base, isManual } = parseCarrier(code);
    const meta = CARRIER_META[base.toLowerCase()] ?? { name: base, color: 'default' };
    const fontSize = size === 'small' ? 11 : 12;

    return (
        <Space size={4} wrap>
            <Tag color={meta.color} icon={<CarOutlined />} style={{ marginInlineEnd: 0, fontSize }}>
                {meta.name}
            </Tag>
            {isManual && base !== 'manual' && (
                <Tag color="purple" icon={<EditOutlined />} style={{ marginInlineEnd: 0, fontSize: 10, padding: '0 4px', lineHeight: '14px' }}>
                    Tự tạo
                </Tag>
            )}
        </Space>
    );
}
