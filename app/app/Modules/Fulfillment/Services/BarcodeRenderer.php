<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

/**
 * SPEC 0021 — sinh QR code + barcode embed vào HTML phiếu giao hàng (rendered → PDF qua Gotenberg).
 *
 * Trả base64 data URL để chèn thẳng vào `<img src="data:...">` — không cần serve file riêng. PDF render
 * giữ nguyên vector cho cả QR (SVG) và barcode (SVG) ⇒ scan ra mã rõ kể cả ở khổ A6.
 *
 * Fallback PNG khi không có ext-gd / svg-backend lỗi (tránh crash render).
 */
class BarcodeRenderer
{
    /** Generate QR code SVG data URL từ chuỗi (mã vận đơn / tracking). */
    public function qrSvgDataUrl(string $data, int $sizePx = 120): string
    {
        if ($data === '') {
            return '';
        }
        try {
            $renderer = new ImageRenderer(new RendererStyle($sizePx), new SvgImageBackEnd());
            $svg = (new Writer($renderer))->writeString($data);

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        } catch (\Throwable) {
            return $this->qrPngDataUrl($data, $sizePx);
        }
    }

    /** Fallback PNG khi SVG backend không sẵn. Cần ext-gd. */
    public function qrPngDataUrl(string $data, int $sizePx = 120): string
    {
        if ($data === '' || ! extension_loaded('gd')) {
            return '';
        }
        try {
            $renderer = new GDLibRenderer($sizePx);
            $bytes = (new Writer($renderer))->writeString($data);

            return 'data:image/png;base64,'.base64_encode($bytes);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Generate Code128 barcode SVG data URL (chuẩn dùng cho mã vận đơn).
     * GHN/GHTK/J&T đều dùng Code128 hoặc Code39 cho tracking number; Code128 hỗ trợ alphanumeric tốt hơn.
     */
    public function code128SvgDataUrl(string $data, int $widthFactor = 2, int $height = 50): string
    {
        if ($data === '') {
            return '';
        }
        try {
            $g = new BarcodeGeneratorSVG();
            $svg = $g->getBarcode($data, $g::TYPE_CODE_128, $widthFactor, $height, 'black');

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        } catch (\Throwable) {
            return $this->code128PngDataUrl($data, $widthFactor, $height);
        }
    }

    /** Fallback PNG nếu SVG backend gặp lỗi. */
    public function code128PngDataUrl(string $data, int $widthFactor = 2, int $height = 50): string
    {
        if ($data === '' || ! extension_loaded('gd')) {
            return '';
        }
        try {
            $g = new BarcodeGeneratorPNG();
            $bytes = $g->getBarcode($data, $g::TYPE_CODE_128, $widthFactor, $height);

            return 'data:image/png;base64,'.base64_encode($bytes);
        } catch (\Throwable) {
            return '';
        }
    }
}
