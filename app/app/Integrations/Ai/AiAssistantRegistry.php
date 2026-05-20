<?php

namespace CMBcoreSeller\Integrations\Ai;

use CMBcoreSeller\Integrations\Ai\Contracts\AiAssistantConnector;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
use Illuminate\Contracts\Container\Container;

/**
 * Singleton — tập hợp AI assistant connector active. Khác `ChannelRegistry`:
 * `is_active` đọc từ DB (`system_settings.ai_providers.<code>.is_active`)
 * thay vì config — super-admin có thể bật/tắt provider runtime mà không deploy.
 *
 * Resolve qua `for($code)` — provider không có hoặc `is_active=false` ⇒ ném
 * `ProviderNotConfigured` (caller mapping → `422 AI_PROVIDER_NOT_AVAILABLE`).
 */
class AiAssistantRegistry
{
    /** @var array<string, class-string<AiAssistantConnector>> */
    protected array $connectors = [];

    public function __construct(protected Container $container) {}

    /**
     * @param  class-string<AiAssistantConnector>  $connectorClass
     */
    public function register(string $code, string $connectorClass): void
    {
        $this->connectors[$code] = $connectorClass;
    }

    public function has(string $code): bool
    {
        return isset($this->connectors[$code]);
    }

    /** @return list<string> */
    public function providers(): array
    {
        return array_keys($this->connectors);
    }

    /**
     * Resolve connector. Nếu super-admin chưa cấu hình hoặc `is_active=false`
     * ⇒ ném `ProviderNotConfigured` (caller mapping 422).
     */
    public function for(string $code): AiAssistantConnector
    {
        if (! $this->has($code)) {
            throw new ProviderNotConfigured("AI provider [{$code}] is not registered.");
        }

        if (! $this->isActive($code)) {
            throw new ProviderNotConfigured("AI provider [{$code}] is not active.");
        }

        return $this->container->make($this->connectors[$code]);
    }

    /**
     * Subset providers đang active (cho `/tenant/settings/messaging` list).
     *
     * @return list<string>
     */
    public function activeProviders(): array
    {
        return array_values(array_filter($this->providers(), fn (string $code) => $this->isActive($code)));
    }

    /**
     * Đọc `is_active` từ `system_settings`. Chống tight-couple — không import
     * SystemSettingService trực tiếp; dùng helper `system_setting()` (Settings module).
     */
    protected function isActive(string $code): bool
    {
        if (! function_exists('system_setting')) {
            return false;
        }

        return (bool) system_setting("ai_providers.{$code}.is_active", false);
    }
}
