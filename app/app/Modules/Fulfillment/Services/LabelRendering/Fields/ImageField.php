<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Concerns\ValidatesProps;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Validation\Rule;

class ImageField implements FieldType
{
    use ValidatesProps;

    public function __construct(private readonly ?MediaUploader $media = null) {}

    public function key(): string { return 'image'; }

    public function validateProps(array $props): array
    {
        $this->validatorFactory()->make($props, [
            'assetPath' => ['required', 'string', 'max:512'],
            'fit' => ['nullable', Rule::in(['contain', 'cover'])],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array { return []; }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        $path = (string) $field['assetPath'];
        $fit = (string) ($field['fit'] ?? 'contain');
        // If MediaUploader has a signedUrl method, use it; otherwise pass the path through.
        $src = ($this->media !== null && method_exists($this->media, 'signedUrl')) ? $this->media->signedUrl($path) : $path;
        $img = '<img src="'.$h->escape($src).'" style="width:100%;height:100%;object-fit:'.$fit.'" alt="" />';

        return $h->positionedBox($field, [], $img);
    }
}
