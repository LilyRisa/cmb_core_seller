<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\Contracts;

use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;

/**
 * Validates a listing draft before it is sent to a marketplace connector.
 *
 * Each connector may provide its own implementation to enforce
 * marketplace-specific rules (required attributes, image count, title length,
 * etc.). Returns an empty array when the draft is valid.
 */
interface ListingValidator
{
    /**
     * Validate the draft and return any violations.
     *
     * @return array<string, string> field => message (empty array = valid)
     */
    public function validate(ListingDraftDTO $draft): array;
}
