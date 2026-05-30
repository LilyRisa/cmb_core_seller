<?php

namespace CMBcoreSeller\Integrations\Ai\DTO;

/**
 * Context để AI sinh reply — tenant chọn (provider_code, model, temperature)
 * + per-call meta. KHÔNG chứa API key — key sống trong `system_settings`
 * group `ai_providers.<code>` (ADR-0018).
 */
final readonly class AiContext
{
    public function __construct(
        public int $tenantId,
        public string $providerCode,
        public ?string $model = null,         // null = dùng default_model của provider
        public ?float $temperature = null,
        public ?int $maxTokens = null,
        public string $languageHint = 'vi',
        /** Prompt chung do super-admin cấu hình, ghép sau persona mặc định khi sinh reply (ADR-0022 / system_setting messaging.ai.system_prompt). null = không có. */
        public ?string $systemPromptExtra = null,
        /** @var array<string, mixed> */
        public array $meta = [],
    ) {}
}
