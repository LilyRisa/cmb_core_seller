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

    public function key(): string
    {
        return 'barcode';
    }

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
        $totalH = (int) $field['h'];
        // Explicit mm heights for both img and text — previously the img used a 70/30% split
        // while barcodePng was generated at (h - 4mm), so the PNG height didn't match the
        // rendered img height; small fields could clip part of the bars.
        $textHmm = $showText ? min(4, max(3, (int) floor($totalH * 0.25))) : 0;
        $imgHmm = max(2, $totalH - $textHmm);
        $img = '<img src="'.$h->barcodePng($payload, (int) $field['w'], $imgHmm, false).'" style="display:block;width:100%;height:'.$imgHmm.'mm;object-fit:contain" alt="barcode" />';
        $textLine = $showText
            ? '<div style="text-align:center;font-size:8pt;line-height:1;font-family:monospace;letter-spacing:1px;height:'.$textHmm.'mm;overflow:hidden">'.$h->escape($payload).'</div>'
            : '';

        return $h->positionedBox($field, [], $img.$textLine);
    }
}
