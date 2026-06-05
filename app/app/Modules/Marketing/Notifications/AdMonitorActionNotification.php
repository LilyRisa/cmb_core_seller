<?php

namespace CMBcoreSeller\Modules\Marketing\Notifications;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email khi giám sát tự động thực hiện hành động (tăng ngân sách / tạm dừng).
 *
 * @param  list<array<string,mixed>>  $actions
 */
class AdMonitorActionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @param list<array<string,mixed>> $actions */
    public function __construct(public AdAccount $account, public array $actions)
    {
        $this->queue = (string) config('notifications.queue', 'notifications');
    }

    /** @return array<int,int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $brand = (string) system_setting('notifications.brand_name', config('notifications.brand.name', 'CMBcoreSeller'));

        return (new MailMessage)
            ->subject("[{$brand}] Giám sát quảng cáo đã thực hiện ".count($this->actions).' hành động')
            ->view('notifications::marketing-monitor-action', [
                'account' => $this->account,
                'actions' => $this->actions,
                'currency' => $this->account->currency,
                'appUrl' => rtrim((string) config('app.url'), '/').'/marketing',
            ]);
    }
}
