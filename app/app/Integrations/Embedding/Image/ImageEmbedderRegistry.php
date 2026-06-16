<?php

namespace CMBcoreSeller\Integrations\Embedding\Image;

use CMBcoreSeller\Integrations\Embedding\Image\Contracts\ImageEmbedder;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

/** Registry trục Image Embedding — đổi vendor = thêm 1 register + 1 dòng config. */
class ImageEmbedderRegistry
{
    /** @var array<string, class-string<ImageEmbedder>> */
    private array $drivers = [];

    public function __construct(private Container $container) {}

    /** @param  class-string<ImageEmbedder>  $class */
    public function register(string $code, string $class): void
    {
        $this->drivers[$code] = $class;
    }

    public function for(string $code): ImageEmbedder
    {
        if (! isset($this->drivers[$code])) {
            throw new RuntimeException("Image embedder [{$code}] chưa đăng ký.");
        }

        return $this->container->make($this->drivers[$code]);
    }

    public function default(): ImageEmbedder
    {
        return $this->for((string) config('integrations.image_embedding.driver', 'clip'));
    }
}
