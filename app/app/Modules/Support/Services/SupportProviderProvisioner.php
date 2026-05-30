<?php

namespace CMBcoreSeller\Modules\Support\Services;

use CMBcoreSeller\Modules\Messaging\Models\AiProvider;

/**
 * Tự provision 1 AI provider RIÊNG cho trợ lý Support từ env (tách hẳn provider
 * messaging của tenant). Chạy khi `php artisan help:index` (và deploy).
 *
 * Idempotent: upsert row `ai_providers` theo `code` = config('support.assistant.provider_code').
 * CHỈ tạo/cập nhật khi có `HELP_ASSISTANT_API_KEY` — không có ⇒ bỏ qua (widget chạy keyword,
 * không tạo row hỏng). `ai_providers` là catalog AI dùng chung (registry đọc nó); đây là
 * MATERIALIZE cấu hình từ env, không phải coupling nghiệp vụ runtime.
 */
class SupportProviderProvisioner
{
    /**
     * @return array{provisioned: bool, code: string, reason?: string}
     */
    public function ensure(): array
    {
        // Cùng nguồn HelpAssistant resolve: system_setting (admin) → config/env.
        $code = (string) system_setting('help_assistant.provider_code', config('support.assistant.provider_code', 'support'));
        $apiKey = (string) config('support.assistant.api_key', '');

        if ($code === '') {
            return ['provisioned' => false, 'code' => '', 'reason' => 'no_provider_code'];
        }
        if ($apiKey === '') {
            // Không có key ⇒ không tạo row (tránh provider rỗng). Admin có thể tự tạo ở /admin/ai-providers.
            return ['provisioned' => false, 'code' => $code, 'reason' => 'no_api_key'];
        }

        AiProvider::query()->updateOrCreate(
            ['code' => $code],
            [
                'adapter' => 'openai_compatible',
                'display_name' => 'Trợ lý trợ giúp (Support)',
                'api_key' => $apiKey,
                'base_url' => (string) config('support.assistant.base_url', 'https://api.openai.com'),
                'default_model' => (string) config('support.assistant.chat_model', 'gpt-4o-mini'),
                'is_active' => true,
                'notes' => 'Tự seed từ env HELP_ASSISTANT_* cho trợ lý Hỏi AI (SPEC-0028).',
            ],
        );

        return ['provisioned' => true, 'code' => $code];
    }
}
