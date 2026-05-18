<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\ImageField;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class ImageFieldTest extends TestCase
{
    use MakesDataContext;

    public function test_validate_requires_asset_path(): void
    {
        $this->expectException(ValidationException::class);
        (new ImageField)->validateProps(['fit' => 'contain']);
    }

    public function test_render_uses_object_fit(): void
    {
        $field = ['type' => 'image', 'x' => 0, 'y' => 0, 'w' => 20, 'h' => 20,
            'assetPath' => 'logos/shop.png', 'fit' => 'cover'];
        $html = (new ImageField)->renderHtml($field, $this->makeContext(), new FieldRenderHelpers);
        $this->assertStringContainsString('object-fit:cover', $html);
        $this->assertStringContainsString('logos/shop.png', $html);
    }
}
