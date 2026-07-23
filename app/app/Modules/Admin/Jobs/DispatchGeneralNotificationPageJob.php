<?php

namespace CMBcoreSeller\Modules\Admin\Jobs;

use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Admin\Services\GeneralNotificationPageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Plan C (2026-07-23) — chạy fan-out ngoài request HTTP (audience có thể hàng nghìn tenant).
 * Dùng cho cả "Gửi ngay" (controller dispatch job) lẫn lịch gửi (scheduled command dispatch job
 * cho từng page đến hạn). Guard `status !== sent` tránh gửi trùng nếu job chạy lại (retry/race).
 *
 * ShouldBeUnique (khoá theo pageId, 10 phút): scheduled command re-queue job này mỗi phút cho
 * mọi page còn `status=scheduled` — nếu fan-out chạy lâu hơn 1 job vẫn còn giữ khoá thì lần
 * re-queue sau bị chặn, tránh chạy chồng chéo cùng 1 page. timeout=600s cho fan-out đủ thời
 * gian với audience lớn thay vì bị worker mặc định 120s giết giữa chừng.
 */
class DispatchGeneralNotificationPageJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 600;

    public int $timeout = 600;

    public function __construct(public readonly int $pageId)
    {
        $this->queue = 'notifications';
    }

    public function uniqueId(): string
    {
        return (string) $this->pageId;
    }

    public function handle(GeneralNotificationPageService $service): void
    {
        $page = GeneralNotificationPage::query()->find($this->pageId);
        if ($page === null || $page->status === GeneralNotificationPage::STATUS_SENT) {
            return;
        }
        $service->dispatch($page);
    }
}
