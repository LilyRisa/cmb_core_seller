<?php

namespace CMBcoreSeller\Integrations\Embedding\Image\DTO;

final readonly class ImageVectorDTO
{
    /** @param  list<float>  $vector */
    public function __construct(
        public array $vector,
        public int $dim,
        public string $model,
    ) {}
}
