<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Concerns\ValidatesProps;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Validation\Rule;

class ItemsListField implements FieldType
{
    use ValidatesProps;

    public function key(): string
    {
        return 'items_list';
    }

    public function validateProps(array $props): array
    {
        $this->validatorFactory()->make($props, [
            'style.fontSize' => ['required', 'integer', 'min:6', 'max:24'],
            'style.lineHeight' => ['nullable', 'numeric', 'min:1', 'max:2.5'],
            'format' => ['nullable', Rule::in(['bullet', 'numbered'])],
            'maxRows' => ['nullable', 'integer', 'min:1', 'max:50'],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array
    {
        return ['items'];
    }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        $max = (int) ($field['maxRows'] ?? count($ctx->items));
        $items = array_slice($ctx->items, 0, $max);
        $rest = count($ctx->items) - count($items);
        $format = (string) ($field['format'] ?? 'bullet');
        $lh = (float) ($field['lineHeight'] ?? 1.25);
        $lines = '';
        foreach ($items as $i => $it) {
            $marker = $format === 'numbered' ? (($i + 1).'.') : '•';
            $sku = ! empty($it['sku']) ? ' <span style="color:#888;font-size:90%">['.$h->escape((string) $it['sku']).']</span>' : '';
            $lines .= '<div style="display:flex;gap:4px;line-height:'.$lh.';"><span>'.$h->escape($marker).'</span><span style="flex:1">'.$h->escape((string) $it['name']).$sku.' × '.((int) $it['qty']).'</span></div>';
        }
        if ($rest > 0) {
            $lines .= '<div style="color:#888;line-height:'.$lh.'">… và '.$rest.' sản phẩm khác</div>';
        }
        $style = $h->textStyle($field['style'] ?? []);

        return $h->positionedBox($field, $style, $lines);
    }
}
