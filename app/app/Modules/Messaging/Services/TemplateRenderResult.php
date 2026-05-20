<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

/**
 * Kết quả render 1 template.
 *
 * - `text`    — body sau khi thay biến.
 * - `missing` — danh sách biến `{{...}}` không có giá trị (và không có default).
 *               Caller (FE) dùng để cảnh báo NV "template còn biến chưa điền".
 * - `used`    — map biến → giá trị thực đã thay (audit / debug).
 */
final readonly class TemplateRenderResult
{
    /**
     * @param  list<string>  $missing
     * @param  array<string,string>  $used
     */
    public function __construct(
        public string $text,
        public array $missing = [],
        public array $used = [],
    ) {}

    public function hasMissing(): bool
    {
        return $this->missing !== [];
    }
}
