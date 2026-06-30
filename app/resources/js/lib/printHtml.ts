/**
 * In một tài liệu HTML hoàn chỉnh PHÍA TRÌNH DUYỆT qua iframe ẩn → mở hộp thoại in của máy.
 *
 * Vì HTML dùng `@page { size: auto }`, máy in tự co theo khổ giấy đang chọn (nhiệt K80, A6,
 * A5, A4…) — "responsive theo khổ máy in". Dùng iframe (không phải window.open) để tránh bị
 * trình duyệt chặn popup và chỉ in đúng nội dung phiếu.
 */
export function printHtmlDocument(html: string): void {
    const iframe = document.createElement('iframe');
    iframe.setAttribute('aria-hidden', 'true');
    Object.assign(iframe.style, {
        position: 'fixed', right: '0', bottom: '0', width: '0', height: '0', border: '0',
    } as Partial<CSSStyleDeclaration>);
    document.body.appendChild(iframe);

    const cw = iframe.contentWindow;
    if (!cw) { iframe.remove(); return; }

    let done = false;
    const cleanup = () => { if (!iframe.parentNode) return; iframe.remove(); };
    const doPrint = () => {
        if (done) return;
        done = true;
        try {
            cw.focus();
            cw.print();
        } catch {
            /* ignore */
        }
        // Dọn iframe sau khi đóng hộp thoại in (hoặc fallback sau 60s).
        cw.onafterprint = () => setTimeout(cleanup, 300);
        setTimeout(cleanup, 60_000);
    };

    cw.document.open();
    cw.document.write(html);
    cw.document.close();

    // Ảnh barcode/QR là data-URI nên render gần như tức thì; vẫn chờ load + 1 nhịp cho chắc.
    if (cw.document.readyState === 'complete') {
        setTimeout(doPrint, 200);
    } else {
        iframe.onload = () => setTimeout(doPrint, 200);
        setTimeout(doPrint, 800); // fallback nếu onload không kích
    }
}
