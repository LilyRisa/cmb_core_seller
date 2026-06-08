<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Modules\Marketing\Events\AdMonitorThresholdApproaching;
use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * SPEC 0036 — chiến dịch QC sắp đạt ngưỡng tắt ⇒ thông báo in-app cảnh báo. Dedup theo
 * monitor id ⇒ không spam mỗi 30' (RunAdMonitors), tới khi user đọc.
 */
class NotifyOnAdMonitorApproaching implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(AdMonitorThresholdApproaching $event): void
    {
        $body = $event->cpr !== null
            ? 'Chi phí/kết quả hiện ~'.number_format($event->cpr).'đ, ngưỡng tắt '.number_format($event->threshold).'đ.'
            : 'Đang tiêu ngân sách nhưng chưa có kết quả — gần ngưỡng tắt '.number_format($event->threshold).'đ.';

        $this->dispatcher->dispatch($event->tenantId, [
            'type' => NotificationType::ADS_MONITOR_APPROACHING,
            'level' => 'warning',
            'title' => "Chiến dịch {$event->name} sắp đạt mức cần tắt",
            'body' => $body,
            'action_url' => '/marketing',
            'data' => [
                'monitor_id' => $event->monitorId,
                'level' => $event->level,
                'cpr' => $event->cpr,
                'threshold' => $event->threshold,
            ],
            'dedup_key' => 'ads.approaching:'.$event->monitorId,
        ]);
    }
}
