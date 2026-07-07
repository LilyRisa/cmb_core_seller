import Konva from 'konva';
import type { DataKey } from '@/lib/shippingLabelTypes';
import { ptToCanvasPx } from './coords';

/**
 * Font tem DÙNG CHUNG với bản in PDF (Gotenberg) — xem FieldRenderHelpers::labelFontStack().
 * Trình duyệt thường không cài DejaVu Sans nên thực tế fallback về Arial; điều đó KHÔNG sao vì
 * cả editor lẫn bản in đều "tự co chữ" cho vừa box (fixed mm) ⇒ bố cục khớp nhau, chỉ khác chút
 * cỡ chữ do metric font. Khai cùng stack để nếu máy có DejaVu thì khớp tuyệt đối.
 */
export const LABEL_FONT_STACK = 'DejaVu Sans, Arial, sans-serif';

/** Trường dữ liệu dạng "khối" (dài, nhiều dòng) — wrap + co theo chiều cao. Đồng bộ với DataField::BLOCK_KEYS (PHP). */
export const BLOCK_DATA_KEYS: ReadonlySet<DataKey> = new Set<DataKey>([
    'sender_address',
    'recipient_address',
    'recipient_address_detail',
    'recipient_address_admin',
    'print_note',
]);

// Node đo đạc dùng lại (không gắn vào Layer nào) — Konva tính width/height đồng bộ.
let measurer: Konva.Text | null = null;
const node = (): Konva.Text => (measurer ??= new Konva.Text({}));

/**
 * Cỡ chữ (canvas px) để text 1 DÒNG vừa CHIỀU RỘNG box. Với 1 dòng, bề rộng tỉ lệ tuyến tính với
 * cỡ chữ nên chỉ cần 1 phép đo. Sàn tối thiểu 6pt (mirror script Chromium bên PDF).
 */
export function fitLinePx(text: string, boxWpx: number, designFsPx: number, fontStyle: string, zoom: number): number {
    if (!text) return designFsPx;
    const min = ptToCanvasPx(6, zoom);
    const m = node();
    m.setAttrs({ text, fontSize: designFsPx, fontFamily: LABEL_FONT_STACK, fontStyle, padding: 1, wrap: 'none', lineHeight: 1.15 });
    const avail = Math.max(1, boxWpx - 2);
    const w = m.width();
    if (w <= avail) return designFsPx;
    return Math.max(min, (designFsPx * avail) / w);
}

/**
 * Cỡ chữ (canvas px) để text NHIỀU DÒNG (wrap) vừa CHIỀU CAO box. Giảm dần cho tới khi vừa
 * hoặc chạm sàn 6pt. Bước nhân 0.94 ⇒ hội tụ nhanh (log).
 */
export function fitBlockPx(text: string, boxWpx: number, boxHpx: number, designFsPx: number, fontStyle: string, lineHeight: number, zoom: number): number {
    if (!text) return designFsPx;
    const min = ptToCanvasPx(6, zoom);
    const m = node();
    let fs = designFsPx;
    for (let i = 0; i < 40 && fs > min; i++) {
        m.setAttrs({ text, width: boxWpx, wrap: 'word', fontSize: fs, fontFamily: LABEL_FONT_STACK, fontStyle, padding: 1, lineHeight });
        if (m.height() <= boxHpx) return fs;
        fs = Math.max(min, fs * 0.94);
    }
    return fs;
}
