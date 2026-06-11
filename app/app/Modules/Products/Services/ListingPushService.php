<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Services;

use CMBcoreSeller\Modules\Products\Jobs\PushListingJob;
use CMBcoreSeller\Modules\Products\Models\ListingDraft;
use CMBcoreSeller\Modules\Products\Models\ProductPushBatch;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;

/**
 * Orchestrates publishing one or many READY {@see ListingDraft}s to their
 * marketplaces. Each push creates a {@see ProductPushBatch} with one tracked
 * job per listing, flips the listing to PUSHING, and dispatches a
 * {@see PushListingJob} onto the `listings` queue. Progress is read back via
 * the batch + its jobs.
 */
final class ListingPushService
{
    /**
     * @param  int[]  $listingIds
     */
    public function push(array $listingIds, int $userId, string $type = 'push'): ProductPushBatch
    {
        $tenantId = app(CurrentTenant::class)->id();

        $batch = ProductPushBatch::create([
            'tenant_id' => $tenantId,
            'type' => $type,
            'total' => count($listingIds),
            'succeeded' => 0,
            'failed' => 0,
            'status' => 'running',
            'created_by' => $userId,
        ]);

        foreach ($listingIds as $lid) {
            $listing = ListingDraft::findOrFail($lid);
            abort_unless($listing->status === ListingDraft::STATUS_READY, 422, "Listing $lid chưa ready");

            $listing->update(['status' => ListingDraft::STATUS_PUSHING]);

            $row = $batch->jobs()->create([
                'tenant_id' => $tenantId,
                'listing_draft_id' => $lid,
                'status' => 'queued',
                'progress' => 0,
            ]);

            PushListingJob::dispatch((int) $row->getKey())->onQueue('listings');
        }

        return $batch;
    }
}
