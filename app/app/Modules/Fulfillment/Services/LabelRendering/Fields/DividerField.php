<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Concerns\ValidatesProps;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;

class DividerField implements FieldType
{
    use ValidatesProps;

    public function key(): string { return 'divider'; }

    public function validateProps(array $props): array
    {
        $this->validatorFactory()->make($props, [
            'thickness' => ['nullable', 'integer', 'min:1', 'max:8'],
            'color' => ['nullable', 'regex:/^#[0-9a-f]{6}$/i'],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array { return []; }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        $thickness = (int) ($field['thickness'] ?? 1);
        $color = (string) ($field['color'] ?? '#cccccc');
        $bar = '<div style="width:100%;height:'.$thickness.'px;background:'.$color.'"></div>';

        return $h->positionedBox($field, ['display' => 'flex', 'align-items' => 'center'], $bar);
    }
}
