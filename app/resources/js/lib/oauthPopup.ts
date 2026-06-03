/**
 * Mở cửa sổ popup cho luồng OAuth. Callback (blade `oauth-callback`) sẽ
 * postMessage `{source:'cmb-oauth', redirect}` về cửa sổ cha rồi tự đóng.
 *
 * - Popup bị trình duyệt chặn → fallback redirect toàn trang (luồng cũ); promise
 *   không resolve vì trang sẽ điều hướng đi.
 * - Người dùng tự đóng popup trước khi xong → resolve `{status:'cancelled'}`.
 */
export interface OAuthPopupOutcome {
    status: 'done' | 'cancelled';
    /** Đường dẫn SPA kèm query (vd `/channels?connected=tiktok`). */
    redirect?: string;
}

export function openOAuthPopup(authUrl: string): Promise<OAuthPopupOutcome> {
    // Trang đăng nhập / đồng ý của sàn (đặc biệt Lazada) khá rộng — popup nhỏ ép người dùng phải cuộn
    // ngang/dọc. Mở to (≈1100×820) nhưng clamp theo kích thước màn hình để không tràn ra ngoài / mở lệch.
    const width = Math.min(1100, window.screen.availWidth - 80);
    const height = Math.min(880, window.screen.availHeight - 120);
    const left = window.screenX + Math.max(0, (window.outerWidth - width) / 2);
    const top = window.screenY + Math.max(0, (window.outerHeight - height) / 2);

    const popup = window.open(
        authUrl,
        'cmb_oauth',
        `width=${width},height=${height},left=${left},top=${top},menubar=no,toolbar=no,location=yes,status=no`,
    );

    if (!popup) {
        // Popup bị chặn → giữ hành vi cũ: redirect toàn trang.
        window.location.href = authUrl;
        return new Promise<OAuthPopupOutcome>(() => {}); // trang sẽ điều hướng đi
    }

    return new Promise<OAuthPopupOutcome>((resolve) => {
        let settled = false;

        const cleanup = () => {
            window.removeEventListener('message', onMessage);
            window.clearInterval(timer);
        };

        const finish = (outcome: OAuthPopupOutcome) => {
            if (settled) return;
            settled = true;
            cleanup();
            try { if (!popup.closed) popup.close(); } catch { /* noop */ }
            resolve(outcome);
        };

        const onMessage = (e: MessageEvent) => {
            if (e.origin !== window.location.origin) return;
            const data = e.data as { source?: string; redirect?: string } | null;
            if (!data || data.source !== 'cmb-oauth') return;
            finish({ status: 'done', redirect: data.redirect });
        };

        const timer = window.setInterval(() => {
            if (popup.closed) finish({ status: 'cancelled' });
        }, 500);

        window.addEventListener('message', onMessage);
    });
}
