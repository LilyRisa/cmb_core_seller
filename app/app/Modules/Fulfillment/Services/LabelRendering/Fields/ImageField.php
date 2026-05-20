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

    public function key(): string
    {
        return 'image';
    }

    public function validateProps(array $props): array
    {
        $this->validatorFactory()->make($props, [
            'assetPath' => ['required', 'string', 'max:512'],
            'fit' => ['nullable', Rule::in(['contain', 'cover'])],
        ])->validate();

        return $props;
    }

    public function dataKeys(): array
    {
        return [];
    }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        $path = (string) $field['assetPath'];
        $fit = (string) ($field['fit'] ?? 'contain');
        // Previously this checked `method_exists($this->media, 'signedUrl')` and fell through to
        // $path raw — MediaUploader has no signedUrl method, so the <img src> was always the bare
        // R2 object key (e.g. "tenants/1/logos/shop.png") and never loaded. Use url() so the disk's
        // configured public URL (Cloudflare R2 / local media disk) resolves properly. If the asset
        // is already a full URL the user pasted in (http(s)://…, data:…), pass it through as-is.
        if ($path === '') {
            return $h->positionedBox($field, [], '');
        }
        $isAbsolute = str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:');
        $src = ($this->media !== null && ! $isAbsolute) ? $this->media->url($path) : $path;
        $img = '<img src="'.$h->escape($src).'" style="width:100%;height:100%;object-fit:'.$fit.'" alt="" />';

        return $h->positionedBox($field, [], $img);
    }
}
