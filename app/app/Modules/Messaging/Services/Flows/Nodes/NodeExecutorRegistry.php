<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use Illuminate\Contracts\Container\Container;
use RuntimeException;

/**
 * Map `node.type` → class executor. Resolve qua container (executor có thể inject
 * service). Đăng ký ở MessagingServiceProvider.
 */
class NodeExecutorRegistry
{
    /** @var array<string,class-string<NodeExecutor>> */
    private array $map = [];

    public function __construct(private ?Container $container = null) {}

    /** @param class-string<NodeExecutor> $executorClass */
    public function register(string $type, string $executorClass): void
    {
        $this->map[$type] = $executorClass;
    }

    public function has(string $type): bool
    {
        return isset($this->map[$type]);
    }

    public function for(string $type): NodeExecutor
    {
        if (! isset($this->map[$type])) {
            throw new RuntimeException("No NodeExecutor registered for node type [{$type}].");
        }
        $class = $this->map[$type];

        return $this->container ? $this->container->make($class) : new $class();
    }
}
