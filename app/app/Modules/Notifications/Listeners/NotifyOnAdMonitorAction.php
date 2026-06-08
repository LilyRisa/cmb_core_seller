<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Modules\Marketing\Events\AdMonitorActionTaken;
use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * SPEC 0036 — AdMonitor đã tự động tạm dừng / tăng ngân sách ⇒ thông báo in-app. Dedup
 * theo action id (mỗi hành động là 1 bản ghi riêng) ⇒ luôn báo hành động mới.
 */
class NotifyOnAdMonitorAction implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(AdMonitorActionTaken $event): void
    {
        $verb = $event->type === 'pause' ? 'tạm dừng' : 'tăng ngân sách';

        $this->dispatcher->dispatch($event->tenantId, [
            'type' => NotificationType::ADS_MONITOR_ACTION,
            'level' => 'info',
            'title' => "Chiến dịch {$event->name} đã được tự động {$verb}",
            'body' => 'AdMonitor vừa tự động xử lý theo quy tắc bạn đã đặt.',
            'action_url' => '/marketing',
            'data' => [
                'action_id' => $event->actionId,
                'type' => $event->type,
            ],
            'dedup_key' => 'ads.action:'.$event->actionId,
        ]);
    }
}
