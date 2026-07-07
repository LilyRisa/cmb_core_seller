<?php

namespace Tests\Unit\Fulfillment\LabelRendering;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use PHPUnit\Framework\TestCase;

class FieldRenderHelpersTest extends TestCase
{
    private FieldRenderHelpers $h;

    protected function setUp(): void
    {
        $this->h = new FieldRenderHelpers;
    }

    public function test_escape_handles_html(): void
    {
        $this->assertSame('&lt;b&gt;A&amp;B&lt;/b&gt;', $this->h->escape('<b>A&B</b>'));
    }

    public function test_format_vnd_with_separator(): void
    {
        $this->assertSame('1.234.567 đ', $this->h->formatVnd(1234567));
    }

    public function test_format_vnd_zero(): void
    {
        $this->assertSame('0 đ', $this->h->formatVnd(0));
    }

    public function test_positioned_box_renders_mm_coords(): void
    {
        $field = ['x' => 5, 'y' => 10, 'w' => 40, 'h' => 8, 'rotation' => 0];
        $html = $this->h->positionedBox($field, ['font-size' => '11px'], 'hello');
        $this->assertStringContainsString('left:5mm', $html);
        $this->assertStringContainsString('top:10mm', $html);
        $this->assertStringContainsString('width:40mm', $html);
        $this->assertStringContainsString('height:8mm', $html);
        $this->assertStringContainsString('font-size:11px', $html);
        $this->assertStringContainsString('>hello<', $html);
    }

    public function test_positioned_box_applies_rotation(): void
    {
        $field = ['x' => 0, 'y' => 0, 'w' => 10, 'h' => 10, 'rotation' => 45];
        $html = $this->h->positionedBox($field, [], '');
        $this->assertStringContainsString('transform:rotate(45deg)', $html);
    }

    public function test_carrier_full_name_known(): void
    {
        $this->assertSame('GIAO HÀNG NHANH', $this->h->carrierFullName('ghn'));
    }

    public function test_carrier_full_name_unknown_fallback(): void
    {
        $this->assertSame('CARRIER X', $this->h->carrierFullName('carrier_x'));
    }

    /**
     * Manual orders persist carrier as `manual_<code>` (ShipmentService:475). Without the prefix
     * strip the field rendered "MANUAL GHN" / "MANUAL_GHN" — the user-facing "tên ĐVVC dùng manual"
     * bug. After the fix every carrier-* helper resolves `manual_<x>` to `<x>` and looks up the
     * canonical CARRIER_META entry.
     */
    public function test_carrier_full_name_strips_manual_prefix(): void
    {
        $this->assertSame('GIAO HÀNG NHANH', $this->h->carrierFullName('manual_ghn'));
        $this->assertSame('GIAO HÀNG TIẾT KIỆM', $this->h->carrierFullName('manual_ghtk'));
        $this->assertSame('J&T EXPRESS', $this->h->carrierFullName('manual_jt'));
        // Bare 'manual' (no underlying carrier) stays as self-ship — strip only applies to `manual_`.
        $this->assertSame('TỰ VẬN CHUYỂN', $this->h->carrierFullName('manual'));
    }

    public function test_carrier_short_name_strips_manual_prefix(): void
    {
        $this->assertSame('GHN', $this->h->carrierShortName('manual_ghn'));
        $this->assertSame('GHTK', $this->h->carrierShortName('manual_ghtk'));
        $this->assertSame('TỰ VC', $this->h->carrierShortName('manual'));
    }

    public function test_carrier_logo_renders_real_brand_image_and_strips_manual_prefix(): void
    {
        // Dùng logo thương hiệu thật trong public/images/ (đồng bộ CarrierLogo.tsx). `manual_ghn`
        // phải resolve về asset `ghn` (log_ghn.png) và render <img>, không lộ "MANUAL_GHN".
        $html = $this->h->carrierLogoImg('manual_ghn', 30, 12);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('alt="ghn"', $html);
        $this->assertStringNotContainsString('MANUAL_GHN', $html);
        $this->assertStringNotContainsString('MANUAL GHN', $html);
    }

    public function test_carrier_logo_falls_back_to_short_name_text_for_unknown_carrier(): void
    {
        // No SVG asset for an unknown carrier ⇒ styled text placeholder with the short name.
        $html = $this->h->carrierLogoImg('carrier_x', 30, 12);
        $this->assertStringNotContainsString('data:image/svg+xml', $html);
        $this->assertStringContainsString('CARRIER_X', $html);
    }

    public function test_qr_png_returns_base64_data_url(): void
    {
        $url = $this->h->qrPng('TEST123', 30);
        $this->assertStringStartsWith('data:image/png;base64,', $url);
    }

    public function test_barcode_png_returns_base64_data_url(): void
    {
        $url = $this->h->barcodePng('TEST123', 50, 15, true);
        $this->assertStringStartsWith('data:image/png;base64,', $url);
    }

    /**
     * Regression for "barcode không nhận height". The PNG path (barcodePng) was rendered through
     * <img object-fit:contain>, which preserved the PNG's ~75:1 native aspect ratio and squeezed
     * each barcode down to ~1mm regardless of field height. The SVG variant ships the markup with
     * preserveAspectRatio="none" so the consumer's CSS width:100%/height:Xmm stretches the bars
     * to fill the field box.
     */
    public function test_barcode_svg_returns_data_url_with_stretchable_aspect(): void
    {
        $url = $this->h->barcodeSvg('TEST123', 50, 15);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $url);
        $decoded = base64_decode(substr($url, strlen('data:image/svg+xml;base64,')));
        $this->assertStringContainsString('preserveAspectRatio="none"', $decoded);
        $this->assertStringStartsWith('<', ltrim($decoded));
    }
}
