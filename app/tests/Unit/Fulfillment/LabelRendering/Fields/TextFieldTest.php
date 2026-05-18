<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\TextField;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class TextFieldTest extends TestCase
{
    use MakesDataContext;

    private TextField $f;
    private FieldRenderHelpers $h;

    protected function setUp(): void
    {
        $this->f = new TextField();
        $this->h = new FieldRenderHelpers();
    }

    public function test_key(): void
    {
        $this->assertSame('text', $this->f->key());
    }

    public function test_validate_props_requires_text(): void
    {
        $this->expectException(ValidationException::class);
        $this->f->validateProps(['style' => ['fontSize' => 11]]);
    }

    public function test_validate_props_rejects_font_size_out_of_range(): void
    {
        $this->expectException(ValidationException::class);
        $this->f->validateProps(['text' => 'Hi', 'style' => ['fontSize' => 5]]);
    }

    public function test_render_html_escapes_text(): void
    {
        $field = ['type' => 'text', 'x' => 0, 'y' => 0, 'w' => 30, 'h' => 5,
                  'text' => '<b>shop</b>', 'style' => ['fontSize' => 11]];
        $html = $this->f->renderHtml($field, $this->makeContext(), $this->h);
        $this->assertStringContainsString('&lt;b&gt;shop&lt;/b&gt;', $html);
    }
}
