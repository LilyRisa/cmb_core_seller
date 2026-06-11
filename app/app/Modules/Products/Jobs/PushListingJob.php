<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Jobs;

use CMBcoreSeller\Integrations\Channels\PublisherRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Products\Models\ListingDraft;
use CMBcoreSeller\Modules\Products\Models\ProductPushBatch;
use CMBcoreSeller\Modules\Products\Models\ProductPushJob;
use CMBcoreSeller\Modules\Products\Services\ListingDraftService;
use CMBcoreSeller\Modules\Products\Services\MediaPrepService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Publishes a single {@see ListingDraft} to its marketplace, tracking progress
 * on the matching {@see ProductPushJob} row. Runs on the `listings` queue.
 *
 * Prepares images (when source URLs are present), maps the draft to a normalized
 * DTO via {@see ListingDraftService::toDraftDTO()} and creates the listing through
 * the {@see PublisherRegistry}. Any failure is caught and recorded on both the
 * listing and the job row so one listing's failure never aborts the batch; the
 * batch is always recounted in the finally block.
 */
class PushListingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(public int $jobRowId)
    {
        $this->onQueue('listings');
    }

    public function handle(PublisherRegistry $pubs, MediaPrepService $media, ListingDraftService $drafts): void
    {
        $row = ProductPushJob::findOrFail($this->jobRowId);
        $listing = ListingDraft::with('skus')->findOrFail($row->listing_draft_id);

        try {
            $row->mark('running', 'Đang chuẩn bị ảnh', 10);
            $auth = ChannelAccount::findOrFail($listing->channel_account_id)->authContext();

            $sourceUrls = $listing->media_refs['source_urls'] ?? [];
            if ($sourceUrls) {
                $refs = $media->prepare($listing->provider, $auth, $sourceUrls);
                $listing->media_refs = array_merge($listing->media_refs ?? [], [
                    'prepared' => array_map(fn ($r) => ['ref' => $r->ref, 'kind' => $r->kind], $refs),
                ]);
                $listing->save();
            }

            $row->mark('running', 'Đang tạo listing trên sàn', 60);
            $dto = $drafts->toDraftDTO($listing);
            $result = $pubs->for($listing->provider)->createListing($auth, $dto);

            $listing->update([
                'status' => ListingDraft::STATUS_LIVE,
                'external_item_id' => $result->externalItemId,
                'raw_qc_status' => $result->rawStatus,
                'pushed_at' => now(),
                'last_error' => null,
            ]);
            $row->mark('success', 'Hoàn tất', 100);
        } catch (\Throwable $e) {
            $listing->update([
                'status' => ListingDraft::STATUS_FAILED,
                'last_error' => ['message' => $e->getMessage()],
            ]);
            $row->mark('failed', 'Lỗi', 100, ['message' => $e->getMessage()]);
        } finally {
            ProductPushBatch::findOrFail($row->product_push_batch_id)->recountAndFinish();
        }
    }
}
