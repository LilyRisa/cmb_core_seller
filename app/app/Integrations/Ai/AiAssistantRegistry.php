<?php

namespace CMBcoreSeller\Integrations\Ai;

use CMBcoreSeller\Integrations\Ai\Contracts\AiAssistantConnector;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
use Illuminate\Contracts\Container\Container;

/**
 * Singleton — tập hợp AI assistant connector active. Khác `ChannelRegistry`:
 * `is_active` đọc từ DB (bảng `ai_providers`, super-admin quản qua
 * `/admin/ai-providers`) thay vì config — bật/tắt runtime không cần deploy.
 * (ADR-0018 revised: bảng riêng thay vì `system_settings` — catalog Settings là
 * allowlist key tĩnh, không nhận key động.)
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
     * Resolve connector KHÔNG kiểm `is_active` — chỉ super-admin dùng (test
     * connection / inspect capability của provider chưa bật). Runtime tenant
     * luôn đi qua `for()` (có active guard).
     */
    public function make(string $code): AiAssistantConnector
    {
        if (! $this->has($code)) {
            throw new ProviderNotConfigured("AI provider [{$code}] is not registered.");
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
     * Đọc `is_active` từ bảng `ai_providers` (super-admin quản).
     *
     * Trước đây đọc `system_setting("ai_providers.{code}.is_active")` — nhưng
     * `SystemSettingsCatalog` là allowlist key TĨNH, không nhận key động ⇒ luôn
     * trả false ⇒ KHÔNG provider nào active được (lỗi S1). Bảng riêng sửa triệt để.
     *
     * Bọc try/catch: boot/migration pending (bảng chưa có) ⇒ trả false, không crash.
     */
    protected function isActive(string $code): bool
    {
        try {
            return \CMBcoreSeller\Modules\Messaging\Models\AiProvider::query()
                ->whereKey($code)
                ->where('is_active', true)
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}
