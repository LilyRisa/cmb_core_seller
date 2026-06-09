/**
 * In chứng từ kế toán (phiếu thu/chi, hoá đơn) phía client — mở cửa sổ in với mẫu A5,
 * không cần backend/Gotenberg. Dùng cho nút "In" ở AR/AP.
 */

const ONES = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];

function readThreeDigits(n: number, full: boolean): string {
    const hundred = Math.floor(n / 100);
    const ten = Math.floor((n % 100) / 10);
    const unit = n % 10;
    const parts: string[] = [];
    if (full || hundred > 0) {
        parts.push(`${ONES[hundred]} trăm`);
    }
    if (ten > 1) {
        parts.push(`${ONES[ten]} mươi`);
        if (unit === 1) parts.push('mốt');
        else if (unit === 5) parts.push('lăm');
        else if (unit > 0) parts.push(ONES[unit]);
    } else if (ten === 1) {
        parts.push('mười');
        if (unit === 5) parts.push('lăm');
        else if (unit > 0) parts.push(ONES[unit]);
    } else if (ten === 0) {
        if (unit > 0 && (full || hundred > 0)) parts.push(`lẻ ${ONES[unit]}`);
        else if (unit > 0) parts.push(ONES[unit]);
    }
    return parts.join(' ');
}

/** Đọc số tiền VND thành chữ tiếng Việt (vd 1.250.000 → "Một triệu hai trăm năm mươi nghìn đồng"). */
export function readVND(amount: number): string {
    const value = Math.abs(Math.round(amount));
    if (value === 0) return 'Không đồng';
    const units = ['', ' nghìn', ' triệu', ' tỷ', ' nghìn tỷ', ' triệu tỷ'];
    const groups: number[] = [];
    let rest = value;
    while (rest > 0) {
        groups.push(rest % 1000);
        rest = Math.floor(rest / 1000);
    }
    const chunks: string[] = [];
    for (let i = groups.length - 1; i >= 0; i--) {
        if (groups[i] === 0) continue;
        const full = i !== groups.length - 1; // nhóm sau nhóm cao nhất ⇒ đọc đủ 3 chữ số
        chunks.push(readThreeDigits(groups[i], full) + (units[i] ?? ''));
    }
    const text = chunks.join(' ').replace(/\s+/g, ' ').trim();
    const capitalized = text.charAt(0).toUpperCase() + text.slice(1);
    return `${capitalized} đồng${amount < 0 ? ' (âm)' : ''}`;
}

function fmt(amount: number): string {
    return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(amount);
}

function esc(s: string): string {
    return s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c] as string));
}

export interface VoucherField { label: string; value: string }

export interface PrintVoucherOptions {
    docTitle: string;          // "PHIẾU THU", "PHIẾU CHI", "CHỨNG TỪ GHI SỔ"…
    subtitle?: string;
    tenantName?: string;
    code: string;
    dateText: string;
    partyLabel?: string;       // "Người nộp tiền", "Người nhận tiền", "Nhà cung cấp"
    partyName?: string;
    reason?: string;
    amount: number;
    fields?: VoucherField[];   // dòng phụ: phương thức, TK Nợ/Có…
    signers?: string[];        // mặc định 4 chữ ký
}

export function printVoucher(o: PrintVoucherOptions): void {
    const signers = o.signers ?? ['Người lập phiếu', 'Người nộp/nhận tiền', 'Thủ quỹ', 'Kế toán trưởng'];
    const fieldsHtml = (o.fields ?? [])
        .map((f) => `<tr><td class="lbl">${esc(f.label)}</td><td>${esc(f.value)}</td></tr>`)
        .join('');

    const html = `<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><title>${esc(o.docTitle)} ${esc(o.code)}</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: "Times New Roman", Times, serif; color: #000; margin: 0; padding: 24px 28px; font-size: 14px; }
  .org { font-weight: 700; text-transform: uppercase; }
  .title { text-align: center; margin: 18px 0 4px; }
  .title h1 { font-size: 22px; margin: 0; letter-spacing: 1px; }
  .title .sub { font-style: italic; }
  .meta { text-align: center; margin-bottom: 14px; }
  table.info { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  table.info td { padding: 4px 2px; vertical-align: top; }
  table.info td.lbl { width: 170px; font-style: italic; }
  .amount { font-weight: 700; }
  .words { font-style: italic; }
  .signs { display: flex; justify-content: space-between; margin-top: 36px; text-align: center; }
  .signs div { width: 24%; }
  .signs .role { font-weight: 700; }
  .signs .hint { font-style: italic; font-size: 12px; }
  @media print { body { padding: 10mm; } }
</style></head>
<body>
  <div class="org">${esc(o.tenantName ?? '')}</div>
  <div class="title">
    <h1>${esc(o.docTitle)}</h1>
    ${o.subtitle ? `<div class="sub">${esc(o.subtitle)}</div>` : ''}
    <div>Ngày ${esc(o.dateText)}</div>
  </div>
  <div class="meta">Số: <b>${esc(o.code)}</b></div>
  <table class="info">
    ${o.partyName ? `<tr><td class="lbl">${esc(o.partyLabel ?? 'Đối tượng')}:</td><td>${esc(o.partyName)}</td></tr>` : ''}
    ${o.reason ? `<tr><td class="lbl">Lý do / Nội dung:</td><td>${esc(o.reason)}</td></tr>` : ''}
    <tr><td class="lbl">Số tiền:</td><td class="amount">${fmt(o.amount)} ₫</td></tr>
    <tr><td class="lbl">Bằng chữ:</td><td class="words">${esc(readVND(o.amount))}</td></tr>
    ${fieldsHtml}
  </table>
  <div class="signs">
    ${signers.map((s) => `<div><div class="role">${esc(s)}</div><div class="hint">(Ký, họ tên)</div></div>`).join('')}
  </div>
  <script>window.onload = function(){ window.print(); };</script>
</body></html>`;

    const w = window.open('', '_blank', 'width=900,height=700');
    if (!w) return;
    w.document.open();
    w.document.write(html);
    w.document.close();
}
