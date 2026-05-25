<?php

namespace CMBcoreSeller\Modules\Orders\Contracts;

use CMBcoreSeller\Integrations\Channels\DTO\ReturnDTO;
use CMBcoreSeller\Modules\Orders\Models\OrderReturn;

/**
 * Idempotent upsert of a normalized after-sales record (cancel/return/refund).
 * The Channels sync jobs (ProcessWebhookEvent / SyncReturnsForShop) call this. See SPEC 0025.
 */
interface ReturnUpsertContract
{
    public function upsert(ReturnDTO $dto, int $tenantId, ?int $channelAccountId, string $source): OrderReturn;
}
