<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Graph;

/** Node bất biến trong đồ thị flow. */
final class FlowNode
{
    /** @param array<string,mixed> $data */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly array $data,
    ) {}
}
