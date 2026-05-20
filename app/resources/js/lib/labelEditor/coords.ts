export const PX_PER_MM = 4;                          // editor base scale; zoom multiplier riêng

export const mm2px = (mm: number, zoom = 1): number => mm * PX_PER_MM * zoom;
export const px2mm = (px: number, zoom = 1): number => px / (PX_PER_MM * zoom);

/**
 * Convert spec font size (pt-equivalent) to Konva canvas pixels so editor matches PDF print.
 *   1pt = 0.3528mm; 1mm on canvas = PX_PER_MM * zoom Konva-px.
 *   Therefore 1pt = 0.3528 * PX_PER_MM * zoom Konva-px ≈ 1.411 * zoom Konva-px.
 * Previously fields used `fontSize * zoom * 0.9` which undersized text by ~36% — labels
 * looked airy in the editor but text overlapped neighbours once Gotenberg rendered at pt.
 */
export const ptToCanvasPx = (pt: number, zoom = 1): number => pt * 0.3528 * PX_PER_MM * zoom;

export function snap(value: number, grid: number): number {
    if (grid <= 0) return Math.round(value * 10) / 10;
    return Math.round(value / grid) * grid;
}

export function clampBox(box: { x: number; y: number; w: number; h: number }, paperW: number, paperH: number) {
    const w = Math.max(5, Math.min(box.w, paperW));
    const h = Math.max(5, Math.min(box.h, paperH > 0 ? paperH : 9999));
    const x = Math.max(0, Math.min(box.x, paperW - w));
    const y = Math.max(0, Math.min(box.y, (paperH > 0 ? paperH - h : 9999)));
    return { x, y, w, h };
}
