<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\BarcodeField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class BarcodeFieldTest extends TestCase
{
    use MakesDataContext;

    public function test_render_includes_text_when_show_text(): void
    {
        $f = new BarcodeField();
        $field = ['type' => 'barcode', 'x' => 0, 'y' => 0, 'w' => 50, 'h' => 15, 'source' => 'tracking_no', 'showText' => true];
        $html = $f->renderHtml($field, $this->makeContext(['tracking_no' => 'AWB-9']), new FieldRenderHelpers());
        $this->assertStringContainsString('AWB-9', $html);
    }

    public function test_render_hides_text_when_not_show_text(): void
    {
        $f = new BarcodeField();
        $field = ['type' => 'barcode', 'x' => 0, 'y' => 0, 'w' => 50, 'h' => 15, 'source' => 'tracking_no', 'showText' => false];
        $html = $f->renderHtml($field, $this->makeContext(['tracking_no' => 'AWB-9']), new FieldRenderHelpers());
        $this->assertStringNotContainsString('AWB-9', $html);
    }
}
