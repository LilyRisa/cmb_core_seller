<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Concerns\ValidatesProps;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Validation\Rule;

class QrField implements FieldType
{
    use ValidatesProps;

    public function key(): string { return 'qr'; }

    public function validateProps(array $props): array
    {
        $this->validatorFactory()->make($props, [
            'source' => ['required', Rule::in(['tracking_no', 'order_number'])],
            'ecc' => ['nullable', Rule::in(['L', 'M', 'Q', 'H'])],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array
    {
        return ['tracking_no', 'order_number'];
    }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        $payload = $field['source'] === 'tracking_no' ? ($ctx->tracking_no ?: $ctx->order_number) : $ctx->order_number;
        $img = '<img src="'.$h->qrPng((string) $payload, (int) $field['w'], (string) ($field['ecc'] ?? 'M')).'" style="width:100%;height:100%;object-fit:contain" alt="qr" />';

        return $h->positionedBox($field, [], $img);
    }
}
