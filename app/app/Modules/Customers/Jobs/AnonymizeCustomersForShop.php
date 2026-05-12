<?php

namespace CMBcoreSeller\Modules\Customers\Jobs;

use CMBcoreSeller\Modules\Customers\Services\CustomerAnonymizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Anonymize buyer PII for one shop (data-deletion request, or N days after the
 * shop is disconnected). Idempotent — already-anonymized customers are skipped.
 * queue: customers. See SPEC 0002 §8, §6.3.
 */
class AnonymizeCustomersForShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $tenantId, public int $channelAccountId)
    {
        $this->onQueue('customers');
    }

    public function handle(CustomerAnonymizer $anonymizer): void
    {
        $anonymizer->anonymizeForShop($this->tenantId, $this->channelAccountId);
    }
}
