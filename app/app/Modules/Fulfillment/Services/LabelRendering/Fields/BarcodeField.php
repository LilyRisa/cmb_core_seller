<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Concerns\ValidatesProps;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Validation\Rule;

class BarcodeField implements FieldType
{
    use ValidatesProps;

    public function key(): string { return 'barcode'; }

    public function validateProps(array $props): array
    {
        $this->validatorFactory()->make($props, [
            'source' => ['required', Rule::in(['tracking_no', 'order_number'])],
            'format' => ['nullable', Rule::in(['code128'])],
            'showText' => ['nullable', 'boolean'],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array
    {
        return ['tracking_no', 'order_number'];
    }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        $payload = (string) ($field['source'] === 'tracking_no' ? ($ctx->tracking_no ?: $ctx->order_number) : $ctx->order_number);
        $showText = (bool) ($field['showText'] ?? true);
        $barH = max(4, (int) $field['h'] - ($showText ? 4 : 0));
        $img = '<img src="'.$h->barcodePng($payload, (int) $field['w'], $barH, false).'" style="width:100%;height:'.($showText ? (100 - 30) : 100).'%;object-fit:contain" alt="barcode" />';
        $textLine = $showText ? '<div style="text-align:center;font-size:9px;font-family:monospace;letter-spacing:1px">'.$h->escape($payload).'</div>' : '';

        return $h->positionedBox($field, [], $img.$textLine);
    }
}
