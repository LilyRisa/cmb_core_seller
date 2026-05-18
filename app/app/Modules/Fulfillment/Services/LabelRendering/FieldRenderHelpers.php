<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * Thin helpers cho field type renders — wrap QR/barcode/escape/format chung,
 * tránh field class tự import lib. Stub-friendly trong unit test.
 */
class FieldRenderHelpers
{
    private const CARRIER_META = [
        'ghn'        => ['short' => 'GHN',    'full' => 'GIAO HÀNG NHANH'],
        'ghtk'       => ['short' => 'GHTK',   'full' => 'GIAO HÀNG TIẾT KIỆM'],
        'jt'         => ['short' => 'J&T',    'full' => 'J&T EXPRESS'],
        'viettelpost'=> ['short' => 'VTP',    'full' => 'VIETTEL POST'],
        'ninjavan'   => ['short' => 'NJV',    'full' => 'NINJA VAN'],
        'spx'        => ['short' => 'SPX',    'full' => 'SPX EXPRESS'],
        'vnpost'     => ['short' => 'VNPost', 'full' => 'VIETNAM POST'],
        'ahamove'    => ['short' => 'AHA',    'full' => 'AHAMOVE'],
        'manual'     => ['short' => 'TỰ VC',  'full' => 'TỰ VẬN CHUYỂN'],
    ];

    public function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public function formatVnd(int $amount): string
    {
        return number_format($amount, 0, ',', '.') . ' đ';
    }

    public function formatDate(?\DateTimeInterface $t): string
    {
        return $t ? $t->format('d/m/Y H:i') : '';
    }

    public function carrierFullName(?string $carrier): string
    {
        $key = strtolower((string) $carrier);
        if ($key === '' || $carrier === null) {
            return 'ĐVVC';
        }

        return self::CARRIER_META[$key]['full'] ?? mb_strtoupper(str_replace('_', ' ', $key));
    }

    public function carrierShortName(?string $carrier): string
    {
        $key = strtolower((string) $carrier);
        if ($key === '' || $carrier === null) {
            return 'ĐVVC';
        }

        return self::CARRIER_META[$key]['short'] ?? mb_strtoupper($key);
    }

    public function carrierLogoImg(?string $carrier, int $widthMm, int $heightMm): string
    {
        $key = strtolower((string) $carrier);
        $path = __DIR__ . '/../../../../../resources/labels/carrier-logos/' . $key . '.svg';
        if ($carrier && is_file($path)) {
            $svg = (string) file_get_contents($path);
            $b64 = base64_encode($svg);

            return '<img alt="' . $this->escape($key) . '" src="data:image/svg+xml;base64,' . $b64 . '" style="width:' . $widthMm . 'mm;height:' . $heightMm . 'mm;object-fit:contain" />';
        }

        return '<div style="display:flex;align-items:center;justify-content:center;width:' . $widthMm . 'mm;height:' . $heightMm . 'mm;color:#8c8c8c;border:1px dashed #d9d9d9;font-size:9px">' . $this->escape($this->carrierShortName($carrier)) . '</div>';
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
        $writer   = new Writer($renderer);
        $png      = $writer->writeString($payload, 'UTF-8', $eccMap[$ecc] ?? $eccMap['M']);

        return 'data:image/png;base64,' . base64_encode($png);
    }

    public function barcodePng(string $payload, int $widthMm, int $heightMm, bool $withText): string
    {
        $generator   = new BarcodeGeneratorPNG();
        $pixelsPerMm = 4;
        $barPayload  = $payload === '' ? '0' : $payload;
        $widthFactor = max(1, (int) round($widthMm * $pixelsPerMm / max(strlen($barPayload), 1)));
        $barHeight   = max(1, ($heightMm - ($withText ? 4 : 0)) * $pixelsPerMm);
        $png         = $generator->getBarcode(
            $barPayload,
            BarcodeGeneratorPNG::TYPE_CODE_128,
            $widthFactor,
            $barHeight
        );

        return 'data:image/png;base64,' . base64_encode($png);
    }

    public function textStyle(array $s): array
    {
        return [
            'font-size'   => ((int) ($s['fontSize'] ?? 11)) . 'px',
            'font-weight' => (int) ($s['fontWeight'] ?? 400),
            'text-align'  => (string) ($s['align'] ?? 'left'),
            'color'       => (string) ($s['color'] ?? '#222'),
        ];
    }

    public function positionedBox(array $field, array $extraStyle, string $innerHtml): string
    {
        $style = array_merge([
            'position'   => 'absolute',
            'left'       => $field['x'] . 'mm',
            'top'        => $field['y'] . 'mm',
            'width'      => $field['w'] . 'mm',
            'height'     => $field['h'] . 'mm',
            'overflow'   => 'hidden',
            'box-sizing' => 'border-box',
        ], $extraStyle);

        if (! empty($field['rotation'])) {
            $style['transform']        = 'rotate(' . ((int) $field['rotation']) . 'deg)';
            $style['transform-origin'] = 'top left';
        }

        $css = '';
        foreach ($style as $k => $v) {
            $css .= $k . ':' . $v . ';';
        }

        return '<div style="' . $css . '">' . $innerHtml . '</div>';
    }
}
