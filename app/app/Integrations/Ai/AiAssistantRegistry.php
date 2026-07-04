<?php

namespace CMBcoreSeller\Integrations\Ai;

use CMBcoreSeller\Integrations\Ai\Contracts\AiAssistantConnector;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Contracts\Container\Container;

/**
 * Registry AI assistant. Map giờ là **adapter → connector class**
 * (anthropic|openai_compatible|manual). 1 connector phục vụ NHIỀU instance
 * (rows `ai_providers`): resolve theo `adapter`, inject instance `code` để
 * connector tự lấy đúng credentials (`resolve($this->code())`).
 *
 * `for($code)`/`make($code)` vẫn nhận **code instance** (call-site không đổi).
 */
class AiAssistantRegistry
{
    /** @var array<string, class-string<AiAssistantConnector>> adapter => class */
    protected array $adapters = [];

    public function __construct(protected Container $container) {}

    /** @param  class-string<AiAssistantConnector>  $connectorClass */
    public function register(string $adapter, string $connectorClass): void
    {
        $this->adapters[$adapter] = $connectorClass;
    }

    public function hasAdapter(string $adapter): bool
    {
        return isset($this->adapters[$adapter]);
    }

    /** @return list<string> */
    public function adapters(): array
    {
        return array_keys($this->adapters);
    }

    /** Resolve connector cho 1 instance code (có active guard). */
    public function for(string $code): AiAssistantConnector
    {
        $row = $this->row($code);
        if (! $row || ! $row->is_active) {
            throw new ProviderNotConfigured("AI provider [{$code}] is not active.");
        }

        return $this->resolveAdapter((string) $row->adapter, $code);
    }

    /** Resolve KHÔNG check active (admin test/inspect). */
    public function make(string $code): AiAssistantConnector
    {
        $row = $this->row($code);
        if (! $row) {
            throw new ProviderNotConfigured("AI provider [{$code}] not found.");
        }

        return $this->resolveAdapter((string) $row->adapter, $code);
    }

    /** @return list<string> active codes có adapter đã register, lọc theo `role` (mặc định 'chat') */
    public function activeProviders(string $role = 'chat'): array
    {
        try {
            return AiProvider::query()
                ->where('is_active', true)
                ->where('role', $role)
                ->orderBy('sort_order')->orderBy('code')
                ->get(['code', 'adapter'])
                ->filter(fn ($r) => isset($this->adapters[$r->adapter]))
                ->pluck('code')
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function resolveAdapter(string $adapter, string $code): AiAssistantConnector
    {
        if (! isset($this->adapters[$adapter])) {
            throw new ProviderNotConfigured("AI adapter [{$adapter}] is not registered.");
        }

        return $this->container->makeWith($this->adapters[$adapter], ['code' => $code]);
    }

    private function row(string $code): ?AiProvider
    {
        try {
            return AiProvider::query()->find($code);
        } catch (\Throwable) {
            return null;
        }
    }
}
