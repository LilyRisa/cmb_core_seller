<?php

namespace CMBcoreSeller\Integrations\Vector;

use CMBcoreSeller\Integrations\Vector\Contracts\VectorStore;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

/** Registry trục Vector — đổi driver = thêm 1 register + 1 dòng config. */
class VectorStoreRegistry
{
    /** @var array<string, class-string<VectorStore>> */
    private array $drivers = [];

    public function __construct(private Container $container) {}

    /** @param  class-string<VectorStore>  $class */
    public function register(string $code, string $class): void
    {
        $this->drivers[$code] = $class;
    }

    public function for(string $code): VectorStore
    {
        if (! isset($this->drivers[$code])) {
            throw new RuntimeException("Vector driver [{$code}] chưa đăng ký.");
        }

        return $this->container->make($this->drivers[$code]);
    }

    public function default(): VectorStore
    {
        return $this->for((string) config('integrations.vector.driver', 'qdrant'));
    }
}
