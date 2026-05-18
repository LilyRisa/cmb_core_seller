<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\RectangleField;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class RectangleFieldTest extends TestCase
{
    use MakesDataContext;

    public function test_render_applies_border_and_corner(): void
    {
        $field = ['type' => 'rectangle', 'x' => 0, 'y' => 0, 'w' => 50, 'h' => 30,
            'borderThickness' => 1, 'borderColor' => '#cccccc',
            'cornerRadius' => 4, 'fillColor' => '#f5f5f5'];
        $html = (new RectangleField)->renderHtml($field, $this->makeContext(), new FieldRenderHelpers);
        $this->assertStringContainsString('border:1px solid #cccccc', $html);
        $this->assertStringContainsString('border-radius:4px', $html);
        $this->assertStringContainsString('background:#f5f5f5', $html);
    }
}
