<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\DividerField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class DividerFieldTest extends TestCase
{
    use MakesDataContext;

    public function test_render_with_thickness_and_color(): void
    {
        $field = ['type' => 'divider', 'x' => 5, 'y' => 10, 'w' => 80, 'h' => 1,
                  'thickness' => 2, 'color' => '#222222'];
        $html = (new DividerField())->renderHtml($field, $this->makeContext(), new FieldRenderHelpers());
        $this->assertStringContainsString('background:#222222', $html);
        $this->assertStringContainsString('height:2px', $html);
    }
}
