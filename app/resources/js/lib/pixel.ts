import { useEffect, useRef } from 'react';
import { useLocation } from 'react-router-dom';

declare global {
    interface Window {
        fbq?: (...args: unknown[]) => void;
    }
}

/**
 * Path public/pre-auth cho phép bắn thêm PageView khi đổi route trong SPA (base Pixel
 * code ở app.blade.php chỉ tự bắn 1 PageView lúc load cứng đầu tiên — SPA dùng React
 * Router nên các lượt điều hướng sau không reload trang). KHÔNG bắn cho route trong app
 * đã đăng nhập — tránh lẫn hành vi nội bộ khách hàng vào tài khoản quảng cáo Meta.
 */
const PUBLIC_PIXEL_PATHS = ['/', '/pricing', '/tools', '/api-docs', '/download', '/login', '/register'];

export function usePixelPageview(): void {
    const location = useLocation();
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false; // base Pixel code đã bắn 1 lần lúc load cứng — bỏ qua lần đầu.
            return;
        }
        if (!PUBLIC_PIXEL_PATHS.includes(location.pathname) || typeof window.fbq !== 'function') {
            return;
        }
        window.fbq('track', 'PageView');
    }, [location.pathname]);
}
