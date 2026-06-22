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
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
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

    public function handle(PublisherRegistry $pubs, MediaPrepService $media, ListingDraftService $drafts, CurrentTenant $tenant): void
    {
        // The queue worker runs without a request-bound tenant, so the global
        // TenantScope would constrain every query to tenant_id=0 and hide the row.
        // Load the job past the scope to discover its tenant, then run the whole push
        // AS that tenant so the tenant-scoped reads below (ListingDraft, ChannelAccount,
        // ProductPushBatch, …) all resolve correctly. Mirrors RenderPrintJob.
        $row = ProductPushJob::withoutGlobalScope(TenantScope::class)->find($this->jobRowId);
        if ($row === null) {
            return;
        }
        $shop = Tenant::query()->find($row->tenant_id);
        if ($shop === null) {
            return;
        }

        $tenant->runAs($shop, fn () => $this->push($pubs, $media, $drafts, $row));
    }

    private function push(PublisherRegistry $pubs, MediaPrepService $media, ListingDraftService $drafts, ProductPushJob $row): void
    {
        $listing = ListingDraft::with('skus')->findOrFail($row->listing_draft_id);

        try {
            // Idempotency guard: if this draft already has a marketplace item id,
            // a previous run already created it. Do NOT call createListing again
            // (that would create a duplicate listing on the marketplace) — just
            // confirm it live and finish.
            if (! empty($listing->external_item_id)) {
                $listing->update(['status' => ListingDraft::STATUS_LIVE, 'last_error' => null]);
                $row->mark('success', 'Đã tồn tại trên sàn', 100);

                return;
            }

            // Video: chuẩn bị TRƯỚC ở job riêng (upload + chờ sàn xử lý qua re-queue, KHÔNG giữ
            // worker). Khi xong (có video_external_id) / bỏ qua (video_skipped) nó dispatch lại
            // PushListingJob để đăng thật.
            $attrs = $listing->attributes ?? [];
            if (! empty($attrs['video_url']) && empty($attrs['video_external_id']) && empty($attrs['video_skipped'])) {
                PrepareListingVideoJob::dispatch($this->jobRowId)->onQueue('listings');

                return;
            }

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

            // Sàn luôn xét duyệt: map raw QC → reviewing/live/failed (KHÔNG mặc định live).
            // Trạng thái cuối cập nhật qua webhook/poll product_update (RefreshListingQcStatus).
            $status = ListingDraftService::statusFromRaw($result->rawStatus);
            $listing->update([
                'status' => $status,
                'external_item_id' => $result->externalItemId,
                'raw_qc_status' => $result->rawStatus,
                'pushed_at' => now(),
                'last_error' => null,
            ]);
            $row->mark('success', $status === ListingDraft::STATUS_REVIEWING ? 'Đã đẩy — chờ sàn duyệt' : 'Hoàn tất', 100);
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
