<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\QrField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class QrFieldTest extends TestCase
{
    use MakesDataContext;

    private QrField $f;
    private FieldRenderHelpers $h;

    protected function setUp(): void
    {
        $this->f = new QrField();
        $this->h = new FieldRenderHelpers();
    }

    public function test_validate_props_rejects_unknown_source(): void
    {
        $this->expectException(ValidationException::class);
        $this->f->validateProps(['source' => 'xyz']);
    }

    public function test_data_keys_includes_both_sources(): void
    {
        $this->assertEqualsCanonicalizing(['tracking_no', 'order_number'], $this->f->dataKeys());
    }

    public function test_render_encodes_tracking_no(): void
    {
        $field = ['type' => 'qr', 'x' => 0, 'y' => 0, 'w' => 20, 'h' => 20, 'source' => 'tracking_no'];
        $html = $this->f->renderHtml($field, $this->makeContext(['tracking_no' => 'AWB-9']), $this->h);
        $this->assertStringContainsString('data:image/png;base64,', $html);
    }

    public function test_render_falls_back_to_order_number_when_tracking_missing(): void
    {
        $field = ['type' => 'qr', 'x' => 0, 'y' => 0, 'w' => 20, 'h' => 20, 'source' => 'tracking_no'];
        $html = $this->f->renderHtml($field, $this->makeContext(['tracking_no' => null, 'order_number' => 'M-77']), $this->h);
        $this->assertStringContainsString('data:image/png;base64,', $html);
    }
}
