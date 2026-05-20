<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Ai\Contracts\AiProviderCredentials;
use CMBcoreSeller\Integrations\Ai\DTO\AiProviderRuntimeConfig;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;

/**
 * Đọc credentials provider từ bảng `ai_providers` (super-admin quản). `api_key`
 * tự giải mã qua model cast `encrypted`. Bind `AiProviderCredentials` →
 * implementation này ở `MessagingServiceProvider`.
 */
class DbAiProviderCredentials implements AiProviderCredentials
{
    public function resolve(string $code): ?AiProviderRuntimeConfig
    {
        $row = AiProvider::query()->find($code);
        if (! $row) {
            return null;
        }

        return new AiProviderRuntimeConfig(
            apiKey: $row->api_key,            // decrypt qua cast
            baseUrl: $row->base_url,
            defaultModel: $row->default_model,
            pricing: array_values((array) ($row->pricing ?? [])),
        );
    }
}
