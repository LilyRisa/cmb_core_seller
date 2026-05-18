<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Concerns\ValidatesProps;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;

class RectangleField implements FieldType
{
    use ValidatesProps;

    public function key(): string { return 'rectangle'; }

    public function validateProps(array $props): array
    {
        $this->validatorFactory()->make($props, [
            'borderThickness' => ['nullable', 'integer', 'min:0', 'max:8'],
            'borderColor' => ['nullable', 'regex:/^#[0-9a-f]{6}$/i'],
            'cornerRadius' => ['nullable', 'integer', 'min:0', 'max:20'],
            'fillColor' => ['nullable', 'regex:/^#[0-9a-f]{6}$/i'],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array { return []; }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        $bt = (int) ($field['borderThickness'] ?? 1);
        $bc = (string) ($field['borderColor'] ?? '#cccccc');
        $cr = (int) ($field['cornerRadius'] ?? 0);
        $fill = (string) ($field['fillColor'] ?? 'transparent');
        $style = [
            'border' => $bt.'px solid '.$bc,
            'border-radius' => $cr.'px',
            'background' => $fill,
        ];

        return $h->positionedBox($field, $style, '');
    }
}
