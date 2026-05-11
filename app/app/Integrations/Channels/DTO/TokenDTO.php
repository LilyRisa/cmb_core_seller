<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

use Carbon\CarbonImmutable;

final readonly class TokenDTO
{
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken = null,
        public ?CarbonImmutable $expiresAt = null,
        public ?CarbonImmutable $refreshExpiresAt = null,
        public ?string $scope = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
