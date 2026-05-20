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
        $textHmm = $showText ? min(4, max(3, (int) floor($totalH * 0.25))) : 0;
        $imgHmm = max(2, $totalH - $textHmm);
        // SVG + preserveAspectRatio="none" stretches the bars to fill the box exactly — without
        // this the previous PNG path was rendered through object-fit:contain, which preserved the
        // PNG's ~75:1 native aspect and squeezed every barcode down to ~1mm regardless of field.h.
        $img = '<img src="'.$h->barcodeSvg($payload, (int) $field['w'], $imgHmm).'" style="display:block;width:100%;height:'.$imgHmm.'mm" alt="barcode" />';
        $textLine = $showText
            ? '<div style="text-align:center;font-size:8pt;line-height:1;font-family:monospace;letter-spacing:1px;height:'.$textHmm.'mm;overflow:hidden">'.$h->escape($payload).'</div>'
            : '';

        return $h->positionedBox($field, [], $img.$textLine);
    }
}
