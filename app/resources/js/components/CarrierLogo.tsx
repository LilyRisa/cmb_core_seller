import { CarOutlined } from '@ant-design/icons';
import { CARRIER_META } from '@/components/CarrierBadge';

/**
 * Bảng icon nhận diện ĐVVC (file ở /public/images/).
 * Khi thêm ĐVVC mới ⇒ thêm 1 entry ở đây.
 */
export const CARRIER_ICON: Record<string, string> = {
    ghn: '/images/log_ghn.png',
    ghtk: '/images/logo_ghtk.png',
    jt: '/images/logo_jt.png',
    viettelpost: '/images/viettel_pot_logo.png',
    spx: '/images/spx_express_logo.png',
    vnpost: '/images/vietnam_post_logo.png',
    ahamove: '/images/ahamove_logo.webp',
    lalamove: '/images/lalamove_logo.png',
    bestexpress: '/images/logo_bestexpress.png',
};

export const CARRIER_TAGLINE: Record<string, string> = {
    ghn: 'Giao Hàng Nhanh',
    ghtk: 'Giao Hàng Tiết Kiệm',
    jt: 'J&T Express',
    viettelpost: 'Viettel Post',
    ninjavan: 'NinjaVan',
    spx: 'Shopee Express',
    vnpost: 'Vietnam Post',
    ahamove: 'Ahamove',
    lalamove: 'Lalamove',
    bestexpress: 'BEST Express',
    manual: 'Tự vận chuyển — tự nhập mã vận đơn',
};

/**
 * Logo ĐVVC (chỉ ảnh, không tag/text). Khi không có icon ⇒ fallback CarOutlined trên nền màu carrier.
 */
export function CarrierLogo({ code, size = 32, rounded = true }: { code: string; size?: number; rounded?: boolean }) {
    const base = code.startsWith('manual_') ? code.slice(7) : code;
    const icon = CARRIER_ICON[base];
    const meta = CARRIER_META[base] ?? { name: base, color: 'default' };
    if (!icon) {
        return (
            <span style={{
                width: size, height: size, borderRadius: rounded ? size / 4 : 0,
                background: 'linear-gradient(135deg, #FDFCF8 0%, #F5F2EA 100%)',
                color: 'var(--ink-700)', boxShadow: 'inset 0 0 0 1px rgba(11,20,55,0.08)',
                display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
                fontSize: Math.max(10, size * 0.45), flexShrink: 0,
            }} aria-label={meta.name}>
                <CarOutlined />
            </span>
        );
    }
    return (
        <span style={{
            width: size, height: size, borderRadius: rounded ? size / 4 : 0,
            background: '#fff',
            display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
            padding: Math.max(2, size * 0.08), flexShrink: 0,
            boxShadow: 'inset 0 0 0 1px rgba(11,20,55,0.08)',
            overflow: 'hidden',
        }} aria-label={meta.name}>
            <img src={icon} alt={meta.name} style={{ width: '100%', height: '100%', objectFit: 'contain', display: 'block' }} />
        </span>
    );
}
