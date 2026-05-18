<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Concerns\ValidatesProps;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Validation\Rule;

class TextField implements FieldType
{
    use ValidatesProps;

    public function key(): string { return 'text'; }

    public function validateProps(array $props): array
    {
        $this->validatorFactory()->make($props, [
            'text' => ['required', 'string', 'max:500'],
            'style.fontSize' => ['required', 'integer', 'min:6', 'max:48'],
            'style.fontWeight' => ['nullable', Rule::in([400, 600, 700])],
            'style.align' => ['nullable', Rule::in(['left', 'center', 'right'])],
            'style.color' => ['nullable', 'regex:/^#[0-9a-f]{6}$/i'],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array { return []; }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        return $h->positionedBox($field, $h->textStyle($field['style'] ?? []), $h->escape((string) ($field['text'] ?? '')));
    }
}
