<?php

namespace CMBcoreSeller\Integrations\Ai\Contracts;

use CMBcoreSeller\Integrations\Ai\DTO\AiProviderRuntimeConfig;

/**
 * Seam để connector (tầng Integrations) đọc credentials runtime của provider
 * mà KHÔNG import model của Module (giữ Integrations\Ai độc lập — modules.md §3).
 *
 * Implementation: `Modules\Messaging\Services\DbAiProviderCredentials` (đọc bảng
 * `ai_providers`), bind ở `MessagingServiceProvider`. Connector inject interface
 * này qua DI và gọi `resolve(code())` ngay trước khi gọi LLM.
 */
interface AiProviderCredentials
{
    /** Trả config (api_key đã giải mã, base_url, default_model, pricing) hoặc null nếu chưa cấu hình. */
    public function resolve(string $code): ?AiProviderRuntimeConfig;
}
