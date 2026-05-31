import { useEffect, useRef, useState, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { Avatar } from 'antd';

/**
 * Ảnh thumbnail (Avatar vuông) — DI CHUỘT vào hiện khung ảnh phóng to BÁM theo con trỏ,
 * xuất hiện mượt (fade + scale). Dùng cho danh sách đơn hàng (cột "Sản phẩm").
 *
 * Khung phóng to render qua portal ra <body> (z-index cao) để không bị bảng cắt
 * (overflow), vị trí `fixed` theo con trỏ + tự nắn vào trong viewport. Không có `src`
 * ⇒ chỉ hiện Avatar fallback, không có hover.
 */
export function HoverPreviewImage({
    src,
    size = 40,
    previewSize = 260,
    fallback = null,
    alt = '',
}: {
    src?: string | null;
    size?: number;
    previewSize?: number;
    fallback?: ReactNode;
    alt?: string;
}) {
    const [hover, setHover] = useState(false);
    const [shown, setShown] = useState(false); // tách khỏi hover để fade-in (mount xong mới bật)
    const [pos, setPos] = useState({ x: 0, y: 0 });
    const rafRef = useRef<number | null>(null);

    // Bật `shown` ở frame kế tiếp sau khi mount ⇒ transition opacity/scale chạy mượt.
    useEffect(() => {
        if (!hover) { setShown(false); return; }
        rafRef.current = requestAnimationFrame(() => setShown(true));
        return () => { if (rafRef.current !== null) cancelAnimationFrame(rafRef.current); };
    }, [hover]);

    if (!src) {
        return <Avatar shape="square" size={size} style={{ background: '#f0f0f0' }}>{fallback}</Avatar>;
    }

    // Nắn khung vào viewport: mặc định nằm dưới-phải con trỏ, tràn thì lật sang trái / lên trên.
    const PAD = 16;
    const vw = typeof window !== 'undefined' ? window.innerWidth : 1280;
    const vh = typeof window !== 'undefined' ? window.innerHeight : 800;
    let left = pos.x + PAD;
    let top = pos.y + PAD;
    if (left + previewSize + 8 > vw) left = pos.x - previewSize - PAD;
    if (top + previewSize + 8 > vh) top = vh - previewSize - 8;
    if (left < 8) left = 8;
    if (top < 8) top = 8;

    return (
        <>
            <span
                onMouseEnter={(e) => { setPos({ x: e.clientX, y: e.clientY }); setHover(true); }}
                onMouseMove={(e) => setPos({ x: e.clientX, y: e.clientY })}
                onMouseLeave={() => setHover(false)}
                style={{ display: 'inline-flex', cursor: 'zoom-in' }}
            >
                <Avatar shape="square" size={size} src={src} style={{ background: '#f0f0f0' }}>{fallback}</Avatar>
            </span>

            {hover && createPortal(
                <div
                    style={{
                        position: 'fixed', left, top, width: previewSize, zIndex: 2000,
                        pointerEvents: 'none',
                        opacity: shown ? 1 : 0,
                        transform: shown ? 'scale(1)' : 'scale(0.96)',
                        transition: 'opacity 120ms ease, transform 120ms ease',
                        background: '#fff', border: '1px solid #e2e8f0', borderRadius: 10,
                        boxShadow: '0 12px 36px rgba(15,23,42,0.28)', overflow: 'hidden', padding: 4,
                    }}
                >
                    <img
                        src={src}
                        alt={alt}
                        style={{ width: '100%', maxHeight: previewSize, objectFit: 'contain', display: 'block', borderRadius: 6 }}
                    />
                </div>,
                document.body,
            )}
        </>
    );
}
