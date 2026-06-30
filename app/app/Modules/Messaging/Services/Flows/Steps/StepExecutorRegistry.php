<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Steps;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Map `step.type` → class executor. Resolve qua container (executor có thể inject
 * service). Đăng ký ở MessagingServiceProvider.
 */
class StepExecutorRegistry
{
    /** @var array<string,class-string<StepExecutor>> */
    private array $map = [];

    public function __construct(private ?Container $container = null) {}

    /** @param class-string<StepExecutor> $executorClass */
    public function register(string $type, string $executorClass): void
    {
        $this->map[$type] = $executorClass;
    }

    public function has(string $type): bool
    {
        return isset($this->map[$type]);
    }

    public function for(string $type): StepExecutor
    {
        if (! isset($this->map[$type])) {
            throw new InvalidArgumentException("No StepExecutor registered for step type [{$type}].");
        }
        $class = $this->map[$type];

        return $this->container ? $this->container->make($class) : new $class;
    }
}
