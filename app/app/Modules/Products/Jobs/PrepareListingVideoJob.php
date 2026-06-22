<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Jobs;

use CMBcoreSeller\Integrations\Channels\PublisherRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Products\Models\ListingDraft;
use CMBcoreSeller\Modules\Products\Models\ProductPushJob;
use CMBcoreSeller\Modules\Products\Services\ListingDraftService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Chuẩn bị video cho một listing TRƯỚC khi đăng, KHÔNG giữ worker khi chờ sàn xử lý:
 * upload 1 lần (startVideoUpload) rồi POLL trạng thái bằng cách tự `release()` lại vào
 * hàng đợi sau mỗi nhịp (worker được trả về ngay). Khi sàn báo sẵn sàng → lưu
 * `attributes.video_external_id`; thất bại/quá hạn → `attributes.video_skipped` (đăng
 * không kèm video). Sau cùng dispatch {@see PushListingJob} để đăng thật.
 *
 * Tiến độ poll lưu trên draft (video_pending_id / video_poll_count) nên sống sót qua
 * mỗi lần release. queue: listings.
 */
class PrepareListingVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Bao trùm số nhịp poll tối đa (poll_count tự dừng sớm hơn). */
    public int $tries = 60;

    public int $timeout = 180;

    public function __construct(public int $jobRowId)
    {
        $this->onQueue('listings');
    }

    public function handle(PublisherRegistry $pubs, ListingDraftService $drafts, CurrentTenant $tenant): void
    {
        // Runs in a worker with no request-bound tenant — load the row past the global
        // TenantScope (else it resolves to tenant_id=0) and run AS its tenant so the
        // tenant-scoped reads below resolve correctly. Mirrors PushListingJob.
        $row = ProductPushJob::withoutGlobalScope(TenantScope::class)->find($this->jobRowId);
        if (! $row) {
            return;
        }
        $shop = Tenant::query()->find($row->tenant_id);
        if (! $shop) {
            return;
        }

        $tenant->runAs($shop, fn () => $this->prepare($pubs, $drafts, $row));
    }

    private function prepare(PublisherRegistry $pubs, ListingDraftService $drafts, ProductPushJob $row): void
    {
        $listing = ListingDraft::find($row->listing_draft_id);
        if (! $listing) {
            return;
        }

        $attrs = $listing->attributes ?? [];

        // Đã xong / bỏ qua / không có video → đăng luôn.
        if (empty($attrs['video_url']) || ! empty($attrs['video_external_id']) || ! empty($attrs['video_skipped'])) {
            $this->toPush();

            return;
        }

        $publisher = $pubs->for($listing->provider);
        if (! method_exists($publisher, 'startVideoUpload') || ! method_exists($publisher, 'videoUploadStatus')) {
            $this->skip($listing, $attrs);

            return;
        }

        $provider = $listing->provider;
        $maxPolls = (int) config("integrations.$provider.video_poll_attempts", 12);
        $delay = max(1, intdiv((int) config("integrations.$provider.video_poll_sleep_ms", 3000), 1000));
        $auth = ChannelAccount::findOrFail($listing->channel_account_id)->authContext();

        try {
            if (empty($attrs['video_pending_id'])) {
                $row->mark('running', 'Đang tải video lên sàn', 15);
                $pending = $publisher->startVideoUpload($auth, $drafts->toDraftDTO($listing));
                $this->stamp($listing, ['video_pending_id' => $pending, 'video_poll_count' => 0]);
                $this->release($delay);

                return;
            }

            $status = $publisher->videoUploadStatus($auth, (string) $attrs['video_pending_id']);
        } catch (\Throwable $e) {
            Log::warning('listing.video.prepare_failed', ['listing' => $listing->getKey(), 'message' => $e->getMessage()]);
            $this->skip($listing, $attrs);

            return;
        }

        if ($status === 'ready') {
            $this->stamp($listing, ['video_external_id' => (string) $attrs['video_pending_id'], 'video_pending_id' => null]);
            $this->toPush();

            return;
        }
        if ($status === 'failed') {
            $this->skip($listing, $attrs);

            return;
        }

        // processing — chưa xong: tăng đếm, hết hạn thì bỏ video, còn lại thì poll tiếp.
        $count = (int) ($attrs['video_poll_count'] ?? 0) + 1;
        if ($count >= $maxPolls) {
            Log::warning('listing.video.timeout', ['listing' => $listing->getKey()]);
            $this->skip($listing, $attrs);

            return;
        }
        $this->stamp($listing, ['video_poll_count' => $count]);
        $row->mark('running', 'Đang chờ sàn xử lý video', 20);
        $this->release($delay);
    }

    /** @param array<string,mixed> $patch */
    private function stamp(ListingDraft $listing, array $patch): void
    {
        $listing->attributes = array_merge($listing->attributes ?? [], $patch);
        $listing->save();
    }

    /** @param array<string,mixed> $attrs */
    private function skip(ListingDraft $listing, array $attrs): void
    {
        $this->stamp($listing, ['video_pending_id' => null, 'video_skipped' => true]);
        $this->toPush();
    }

    private function toPush(): void
    {
        PushListingJob::dispatch($this->jobRowId)->onQueue('listings');
    }

    public function failed(\Throwable $e): void
    {
        // Hỏng cứng khi chuẩn bị video → vẫn đăng (không kèm video) để không kẹt listing.
        $this->toPush();
    }
}
