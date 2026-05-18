<?php

namespace Tests\Unit\Fulfillment\LabelRendering;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use PHPUnit\Framework\TestCase;

class FieldRenderHelpersTest extends TestCase
{
    private FieldRenderHelpers $h;

    protected function setUp(): void
    {
        $this->h = new FieldRenderHelpers();
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
}
