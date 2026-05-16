<?php

namespace CMBcoreSeller\Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Broadcast email gửi từ admin tới user (owner/admin của tenant). SPEC 0023 §3.9.
 *
 * Body là markdown (an toàn — `league/commonmark` GFM converter có XSS-safe mode).
 * Render thành HTML rồi nhúng vào layout `notifications::broadcast`.
 *
 * Queue `notifications`, tries 3, backoff 10/60/300s.
 */
class BroadcastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public string $subjectLine,
        public string $bodyMarkdown,
        public int $broadcastId,
    ) {
        $this->queue = (string) config('notifications.queue', 'notifications');
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $brand = (string) config('notifications.brand.name', 'CMBcoreSeller');
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'escape',         // an toàn — escape mọi HTML user nhập
            'allow_unsafe_links' => false,
        ]);
        $bodyHtml = (string) $converter->convert($this->bodyMarkdown);

        return (new MailMessage)
            ->subject("[{$brand}] {$this->subjectLine}")
            ->view('notifications::broadcast', [
                'user' => $notifiable,
                'subjectLine' => $this->subjectLine,
                'bodyHtml' => $bodyHtml,
                'broadcastId' => $this->broadcastId,
            ]);
    }
}
