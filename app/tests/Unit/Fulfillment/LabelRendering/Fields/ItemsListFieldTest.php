<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\ItemsListField;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class ItemsListFieldTest extends TestCase
{
    use MakesDataContext;

    public function test_render_lists_each_item_name_and_qty(): void
    {
        $ctx = $this->makeContext(['items' => [
            ['name' => 'Áo thun', 'sku' => 'AT01', 'qty' => 2],
            ['name' => 'Quần jean', 'sku' => 'QJ02', 'qty' => 1],
        ]]);
        $field = ['type' => 'items_list', 'x' => 0, 'y' => 0, 'w' => 80, 'h' => 30,
            'style' => ['fontSize' => 10]];
        $html = (new ItemsListField)->renderHtml($field, $ctx, new FieldRenderHelpers);
        $this->assertStringContainsString('Áo thun', $html);
        $this->assertStringContainsString('× 2', $html);
        $this->assertStringContainsString('Quần jean', $html);
    }

    public function test_render_truncates_when_exceeding_max_rows(): void
    {
        $items = array_map(fn ($i) => ['name' => "SP $i", 'sku' => null, 'qty' => 1], range(1, 10));
        $ctx = $this->makeContext(['items' => $items]);
        $field = ['type' => 'items_list', 'x' => 0, 'y' => 0, 'w' => 80, 'h' => 30,
            'style' => ['fontSize' => 10], 'maxRows' => 3];
        $html = (new ItemsListField)->renderHtml($field, $ctx, new FieldRenderHelpers);
        $this->assertStringContainsString('và 7 sản phẩm khác', $html);
    }
}
