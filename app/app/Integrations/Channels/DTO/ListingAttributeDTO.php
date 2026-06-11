<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * A category attribute definition returned by the marketplace.
 */
final readonly class ListingAttributeDTO
{
    /**
     * @param  array<mixed>  $values
     * @param  array<string,mixed>  $raw
     */
    public function __construct(
        public string $id,
        public string $name,
        public bool $required,
        public bool $isSaleProp,
        public string $inputType,
        public array $values = [],
        public array $raw = [],
    ) {}
}
