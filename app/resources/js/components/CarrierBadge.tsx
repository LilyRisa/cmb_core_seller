import { Tag } from 'antd';
import { CarOutlined } from '@ant-design/icons';

/**
 * Badge tên ĐVVC + nhãn trạng thái vận đơn. SPEC 0021.
 *
 * Mở rộng: thêm carrier mới chỉ cần thêm entry vào CARRIER_META; component KHÔNG có if/switch
 * theo tên carrier ở chỗ khác trong app (theo `extensibility-rules.md` §1).
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
    // Đơn sàn: carrier nhận từ sàn về (vd 'GHN Vietnam' của Lazada) — fallback tên gốc.
};

export function CarrierBadge({ code, size = 'small' }: { code: string | null | undefined; size?: 'small' | 'default' }) {
    if (!code) return <span style={{ color: '#bfbfbf' }}>—</span>;
    const meta = CARRIER_META[code.toLowerCase()] ?? { name: code, color: 'default' };

    return (
        <Tag color={meta.color} icon={<CarOutlined />} style={{ marginInlineEnd: 0, fontSize: size === 'small' ? 11 : 12 }}>
            {meta.name}
        </Tag>
    );
}
