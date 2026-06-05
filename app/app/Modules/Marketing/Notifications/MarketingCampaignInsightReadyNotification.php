<?php

namespace CMBcoreSeller\Modules\Marketing\Notifications;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\CampaignAiInsight;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email phân tích AI cho một chiến dịch cụ thể (gửi Owner/Admin của tenant).
 * Queue `notifications`.
 */
class MarketingCampaignInsightReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public AdAccount $account,
        public CampaignAiInsight $insight,
        public ?string $campaignName,
    ) {
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
        $label = $this->campaignName ?? $this->insight->campaign_external_id;

        return (new MailMessage)
            ->subject("[{$brand}] Phân tích AI chiến dịch: {$label}")
            ->view('notifications::marketing-campaign-insight-ready', [
                'account' => $this->account,
                'campaignName' => $label,
                'payload' => (array) $this->insight->payload,
                'params' => (array) $this->insight->params,
                'generatedAt' => $this->insight->generated_at,
                'appUrl' => rtrim((string) config('app.url'), '/').'/marketing',
            ]);
    }
}
