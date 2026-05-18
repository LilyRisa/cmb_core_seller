<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Concerns\ValidatesProps;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use Illuminate\Validation\Rule;

class DataField implements FieldType
{
    use ValidatesProps;

    public const KEYS = [
        'carrier_logo', 'carrier_name',
        'sender_name', 'sender_phone', 'sender_address',
        'recipient_name', 'recipient_phone', 'recipient_address',
        'recipient_address_detail', 'recipient_address_admin',
        'order_number', 'tracking_no',
        'cod', 'weight', 'print_note', 'created_at', 'total_qty',
    ];

    public function key(): string { return 'data'; }

    public function validateProps(array $props): array
    {
        $this->validatorFactory()->make($props, [
            'key' => ['required', Rule::in(self::KEYS)],
            'style.fontSize' => ['required', 'integer', 'min:6', 'max:48'],
            'style.fontWeight' => ['nullable', Rule::in([400, 600, 700])],
            'style.align' => ['nullable', Rule::in(['left', 'center', 'right'])],
            'style.color' => ['nullable', 'regex:/^#[0-9a-f]{6}$/i'],
            'prefix' => ['nullable', 'string', 'max:32'],
            'suffix' => ['nullable', 'string', 'max:32'],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array
    {
        return self::KEYS;
    }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        $key = (string) $field['key'];
        if ($key === 'carrier_logo') {
            return $h->positionedBox($field, [], $h->carrierLogoImg($ctx->carrier, (int) $field['w'], (int) $field['h']));
        }
        $value = match ($key) {
            'carrier_name' => $h->carrierFullName($ctx->carrier),
            'sender_name' => $ctx->sender_name,
            'sender_phone' => $ctx->sender_phone,
            'sender_address' => $ctx->sender_address,
            'recipient_name' => $ctx->recipient_name,
            'recipient_phone' => $ctx->recipient_phone,
            'recipient_address' => $ctx->recipient_address,
            'recipient_address_detail' => $ctx->recipient_address_detail,
            'recipient_address_admin' => $ctx->recipient_address_admin,
            'order_number' => $ctx->order_number,
            'tracking_no' => $ctx->tracking_no ?: '—',
            'cod' => $ctx->cod > 0 ? $h->formatVnd($ctx->cod) : '—',
            'weight' => $ctx->weight_g !== null ? ($ctx->weight_g.'g') : '—',
            'print_note' => $ctx->print_note,
            'created_at' => $ctx->created_at_fmt,
            'total_qty' => (string) $ctx->total_qty,
            default => '',
        };
        $rendered = $h->escape((string) ($field['prefix'] ?? '')).$h->escape($value).$h->escape((string) ($field['suffix'] ?? ''));
        $style = $h->textStyle($field['style'] ?? []);
        $style['display'] = 'flex';
        $style['align-items'] = 'center';
        $style['line-height'] = '1.15';

        return $h->positionedBox($field, $style, '<span style="width:100%">'.$rendered.'</span>');
    }
}
