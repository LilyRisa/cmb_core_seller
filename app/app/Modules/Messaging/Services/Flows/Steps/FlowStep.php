<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Steps;

use Illuminate\Support\Arr;

/** Step bất biến trong danh sách bước của một node. */
final class FlowStep
{
    /** @param array<string,mixed> $config */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly array $config,
    ) {}

    /**
     * Tạo từ mảng step thô (JSON node.data.steps[]).
     * config = phần còn lại sau khi bỏ id + type.
     *
     * @param  array<string,mixed>  $a
     */
    public static function fromArray(array $a): self
    {
        return new self(
            id: (string) $a['id'],
            type: (string) $a['type'],
            config: Arr::except($a, ['id', 'type']),
        );
    }
}
