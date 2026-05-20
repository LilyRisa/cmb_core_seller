<?php

namespace CMBcoreSeller\Integrations\Ai\DTO;

/**
 * Reply do LLM sinh ra. `cost_micro_vnd` ước tính từ `pricing` của provider
 * (super-admin nhập) — ghi vào `ai_assistant_runs` để charge per-tenant.
 */
final readonly class AiReplyDTO
{
    public function __construct(
        public string $body,
        public int $promptTokens = 0,
        public int $completionTokens = 0,
        public int $costMicroVnd = 0,
        public int $durationMs = 0,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
