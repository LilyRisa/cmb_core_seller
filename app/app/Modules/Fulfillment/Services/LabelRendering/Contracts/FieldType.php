<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;

interface FieldType
{
    /** Khoá định danh, đối xứng FE registry. */
    public function key(): string;

    /**
     * Validate + normalize props. Throws ValidationException nếu sai shape.
     *
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public function validateProps(array $props): array;

    /**
     * Các DataContext key field này dùng — giúp resolver chỉ load đúng thứ cần.
     *
     * @return list<string>
     */
    public function dataKeys(): array;

    /**
     * Render thành 1 div absolute-position trên trang HTML PDF.
     *
     * @param  array<string, mixed>  $field
     */
    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string;
}
