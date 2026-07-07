<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

/**
 * Thin helpers cho field type renders — wrap QR/barcode/escape/format chung,
 * tránh field class tự import lib. Stub-friendly trong unit test.
 */
class FieldRenderHelpers
{
    private const CARRIER_META = [
        'ghn' => ['short' => 'GHN',    'full' => 'GIAO HÀNG NHANH'],
        'ghtk' => ['short' => 'GHTK',   'full' => 'GIAO HÀNG TIẾT KIỆM'],
        'jt' => ['short' => 'J&T',    'full' => 'J&T EXPRESS'],
        'viettelpost' => ['short' => 'VTP',    'full' => 'VIETTEL POST'],
        'ninjavan' => ['short' => 'NJV',    'full' => 'NINJA VAN'],
        'spx' => ['short' => 'SPX',    'full' => 'SPX EXPRESS'],
        'vnpost' => ['short' => 'VNPost', 'full' => 'VIETNAM POST'],
        'ahamove' => ['short' => 'AHA',    'full' => 'AHAMOVE'],
        'manual' => ['short' => 'TỰ VC',  'full' => 'TỰ VẬN CHUYỂN'],
    ];

    public function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public function formatVnd(int $amount): string
    {
        return number_format($amount, 0, ',', '.').' đ';
    }

    public function formatDate(?\DateTimeInterface $t): string
    {
        return $t ? $t->format('d/m/Y H:i') : '';
    }

    /**
     * Đơn manual đẩy ĐVVC ⇒ ShipmentService persists carrier as `manual_<code>` (ví dụ `manual_ghn`).
     * Mọi UX hiển thị ĐVVC (tên, logo, short-name) phải hiểu đây vẫn là GHN — không phải một carrier
     * mới tên "manual ghn". Đồng nhất với PrintTemplates.php:247 (legacy delivery slip strip-prefix).
     */
    private function normalizeCarrierKey(?string $carrier): string
    {
        $key = strtolower(trim((string) $carrier));
        if ($key !== 'manual' && str_starts_with($key, 'manual_')) {
            $key = substr($key, 7);
        }

        return $key;
    }

    public function carrierFullName(?string $carrier): string
    {
        $key = $this->normalizeCarrierKey($carrier);
        if ($key === '') {
            return 'ĐVVC';
        }

        return self::CARRIER_META[$key]['full'] ?? mb_strtoupper(str_replace('_', ' ', $key));
    }

    public function carrierShortName(?string $carrier): string
    {
        $key = $this->normalizeCarrierKey($carrier);
        if ($key === '') {
            return 'ĐVVC';
        }

        return self::CARRIER_META[$key]['short'] ?? mb_strtoupper($key);
    }

    public function carrierLogoImg(?string $carrier, int $widthMm, int $heightMm): string
    {
        $key = $this->normalizeCarrierKey($carrier);
        $path = __DIR__.'/../../../../../resources/labels/carrier-logos/'.$key.'.svg';
        if ($key !== '' && is_file($path)) {
            $svg = (string) file_get_contents($path);
            $b64 = base64_encode($svg);

            return '<img alt="'.$this->escape($key).'" src="data:image/svg+xml;base64,'.$b64.'" style="width:'.$widthMm.'mm;height:'.$heightMm.'mm;object-fit:contain" />';
        }

        // Fallback styled placeholder — short-name in bold, no dashed border (looks cleaner than
        // the previous "missing-image" hint, which leaked into production because we never
        // shipped SVG assets under resources/labels/carrier-logos/).
        return '<div style="display:flex;align-items:center;justify-content:center;width:'.$widthMm.'mm;height:'.$heightMm.'mm;color:#111;font-weight:700;font-size:'.max(8, min(16, (int) round($heightMm * 1.8))).'pt;letter-spacing:0.5px">'.$this->escape($this->carrierShortName($carrier)).'</div>';
    }

    public function qrPng(string $payload, int $widthMm, string $ecc = 'M'): string
    {
        $pixels = max(64, (int) round($widthMm * 8));
        $eccMap = [
            'L' => ErrorCorrectionLevel::L(),
            'M' => ErrorCorrectionLevel::M(),
            'Q' => ErrorCorrectionLevel::Q(),
            'H' => ErrorCorrectionLevel::H(),
        ];
        $renderer = new GDLibRenderer($pixels, 1);
        $writer = new Writer($renderer);
        $png = $writer->writeString($payload, 'UTF-8', $eccMap[$ecc] ?? $eccMap['M']);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    public function barcodePng(string $payload, int $widthMm, int $heightMm, bool $withText): string
    {
        $generator = new BarcodeGeneratorPNG;
        $pixelsPerMm = 4;
        $barPayload = $payload === '' ? '0' : $payload;
        $widthFactor = max(1, (int) round($widthMm * $pixelsPerMm / max(strlen($barPayload), 1)));
        $barHeight = max(1, ($heightMm - ($withText ? 4 : 0)) * $pixelsPerMm);
        $png = $generator->getBarcode(
            $barPayload,
            BarcodeGeneratorPNG::TYPE_CODE_128,
            $widthFactor,
            $barHeight
        );

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * SVG variant — used by BarcodeField. SVG scales without rasterization artefacts in Chromium/PDF
     * and the <svg preserveAspectRatio="none"> override lets us stretch the bars to exactly fill the
     * field box. The previous PNG path was rendered with `object-fit:contain`, which kept the PNG's
     * native ~75:1 aspect ratio and shrank the bars to ~1mm tall regardless of the field's `h` —
     * the symptom users described as "barcode không nhận height".
     */
    public function barcodeSvg(string $payload, int $widthMm, int $heightMm): string
    {
        $generator = new BarcodeGeneratorSVG;
        $barPayload = $payload === '' ? '0' : $payload;
        // widthFactor=2 keeps each bar at minimum 2 user-units (Picqer doesn't honour total width;
        // we rely on preserveAspectRatio="none" + CSS width:100% to scale to the field). barHeight
        // is the SVG's height attribute — vertical stretch via CSS handles any field.h.
        $svg = $generator->getBarcode($barPayload, BarcodeGeneratorSVG::TYPE_CODE_128, 2, max(20, $heightMm * 4), 'black');
        // Picqer emits `<svg width="..." height="..." ...>` with default preserveAspectRatio. Force
        // 'none' so the <img> sized to width:100%;height:<h>mm stretches the bars to fill the box
        // without letterboxing.
        if (! str_contains($svg, 'preserveAspectRatio=')) {
            $svg = (string) preg_replace('/<svg\b/', '<svg preserveAspectRatio="none"', $svg, 1);
        }

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function textStyle(array $s): array
    {
        // Spec §4.4: fontSize is in pt-equivalent. Using 'px' here previously made print 25%
        // smaller than designed (11px = 2.91mm vs 11pt = 3.88mm), causing fields to look
        // tight in the editor but overlap once printed at the physically larger size.
        $lh = isset($s['lineHeight']) ? (float) $s['lineHeight'] : 1.15;

        return [
            'font-size' => ((int) ($s['fontSize'] ?? 11)).'pt',
            'font-weight' => (int) ($s['fontWeight'] ?? 400),
            'text-align' => (string) ($s['align'] ?? 'left'),
            'color' => (string) ($s['color'] ?? '#222'),
            'line-height' => (string) $lh,
        ];
    }

    public function positionedBox(array $field, array $extraStyle, string $innerHtml, string $cls = ''): string
    {
        $style = array_merge([
            'position' => 'absolute',
            'left' => $field['x'].'mm',
            'top' => $field['y'].'mm',
            'width' => $field['w'].'mm',
            'height' => $field['h'].'mm',
            'overflow' => 'hidden',
            'box-sizing' => 'border-box',
        ], $extraStyle);

        if (! empty($field['rotation'])) {
            $style['transform'] = 'rotate('.((int) $field['rotation']).'deg)';
            $style['transform-origin'] = 'top left';
        }

        $css = '';
        foreach ($style as $k => $v) {
            $css .= $k.':'.$v.';';
        }

        $classAttr = $cls !== '' ? ' class="'.$cls.'"' : '';

        return '<div'.$classAttr.' style="'.$css.'">'.$innerHtml.'</div>';
    }

    /**
     * Font chuẩn cho tem: DÙNG CHUNG với editor (Konva) — xem resources/js/lib/labelEditor/fitText.ts.
     * DejaVu Sans có sẵn trong container Gotenberg (không cần nhúng) và phủ đủ tiếng Việt.
     */
    public function labelFontStack(): string
    {
        return 'DejaVu Sans, Arial, sans-serif';
    }

    /**
     * Script tự-co-chữ chạy trong Chromium (Gotenberg) TRƯỚC khi chụp PDF: mọi box `.fit-line`
     * (giá trị 1 dòng — tên/SĐT/COD…) co theo CHIỀU RỘNG; `.fit-block` (địa chỉ/ghi chú/danh sách SP)
     * co theo CHIỀU CAO. Nhờ vậy dữ liệu thật dài hơn mẫu vẫn không tràn/cắt/đè. Sàn font tối thiểu 6pt.
     * Chạy đồng bộ ở cuối <body> — DejaVu Sans là font hệ thống nên metric ổn định ngay, không chờ tải.
     */
    public function autofitScript(): string
    {
        return <<<'JS'
            <script>(function(){
              var MIN=6*96/72;
              function shrink(el,byWidth){
                var fs=parseFloat(getComputedStyle(el).fontSize)||12,g=400;
                while(g-->0 && fs>MIN){
                  var over=byWidth?(el.scrollWidth>el.clientWidth+0.5):(el.scrollHeight>el.clientHeight+0.5);
                  if(!over) break;
                  fs=Math.max(MIN,fs-0.5); el.style.fontSize=fs+'px';
                }
              }
              document.querySelectorAll('.fit-line').forEach(function(e){shrink(e,true);});
              document.querySelectorAll('.fit-block').forEach(function(e){shrink(e,false);});
            })();</script>
            JS;
    }
}
