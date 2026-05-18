<?php

namespace Tests\Unit\Fulfillment\LabelRendering;

use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldTypeRegistry;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\BarcodeField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\DataField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\DividerField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\ImageField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\ItemsListField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\QrField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\RectangleField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\TextField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\LabelDataResolver;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\LabelRenderer;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\SampleDataFactory;
use PHPUnit\Framework\TestCase;

class LabelRendererTest extends TestCase
{
    private function makeRenderer(): LabelRenderer
    {
        $r = new FieldTypeRegistry();
        $r->register(new QrField());
        $r->register(new BarcodeField());
        $r->register(new TextField());
        $r->register(new ImageField());
        $r->register(new DataField());
        $r->register(new ItemsListField());
        $r->register(new DividerField());
        $r->register(new RectangleField());

        return new LabelRenderer($r, new FieldRenderHelpers(), new LabelDataResolver());
    }

    public function test_unknown_field_type_is_skipped(): void
    {
        $tpl = new ShippingLabelTemplate([
            'paper' => 'A6', 'paper_w_mm' => 105, 'paper_h_mm' => 148,
            'schema' => ['fields' => [
                ['id' => 'a', 'type' => 'unknown_type', 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10],
                ['id' => 'b', 'type' => 'text', 'x' => 0, 'y' => 0, 'w' => 30, 'h' => 5,
                 'text' => 'HELLO', 'style' => ['fontSize' => 11]],
            ]],
        ]);
        $factory = new SampleDataFactory();
        $html = $this->makeRenderer()->renderSample('one_item_short_address', $tpl, $factory);
        $this->assertStringContainsString('HELLO', $html);
    }

    public function test_kitchen_sink_snapshot(): void
    {
        $json = json_decode((string) file_get_contents(__DIR__.'/../../../fixtures/labels/kitchen-sink.json'), true);
        $tpl = new ShippingLabelTemplate($json);
        $factory = new SampleDataFactory();
        $html = $this->makeRenderer()->renderSample('three_items_long_address', $tpl, $factory);

        $goldPath = __DIR__.'/../../../fixtures/labels/kitchen-sink.html';
        if (! is_file($goldPath) || getenv('UPDATE_SNAPSHOTS') === '1') {
            file_put_contents($goldPath, $html);
            $this->markTestIncomplete('Golden snapshot ghi mới — chạy lại test để verify.');
        }
        $expected = (string) file_get_contents($goldPath);
        $this->assertSame($expected, $html, 'Renderer output mismatch — chạy `UPDATE_SNAPSHOTS=1 phpunit ...` nếu thay đổi có chủ ý.');
    }
}
