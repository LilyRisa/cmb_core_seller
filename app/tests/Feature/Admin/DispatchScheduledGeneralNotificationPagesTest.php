<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Jobs\DispatchGeneralNotificationPageJob;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchScheduledGeneralNotificationPagesTest extends TestCase
{
    use RefreshDatabase;

    private function makePage(string $slug, string $status, ?Carbon $scheduledAt): GeneralNotificationPage
    {
        return GeneralNotificationPage::create([
            'title' => 'T', 'slug' => $slug, 'body_html' => '<p>x</p>', 'audience_type' => 'all',
            'status' => $status, 'scheduled_at' => $scheduledAt, 'created_by_user_id' => 1,
        ]);
    }

    public function test_dispatches_only_due_scheduled_pages(): void
    {
        Queue::fake();
        $due = $this->makePage('due', GeneralNotificationPage::STATUS_SCHEDULED, now()->subMinute());
        $future = $this->makePage('future', GeneralNotificationPage::STATUS_SCHEDULED, now()->addHour());
        $draft = $this->makePage('draft', GeneralNotificationPage::STATUS_DRAFT, null);
        $sent = $this->makePage('sent', GeneralNotificationPage::STATUS_SENT, now()->subDay());

        $this->artisan('notifications:dispatch-scheduled-general-pages')->assertExitCode(0);

        Queue::assertPushed(DispatchGeneralNotificationPageJob::class, 1);
        Queue::assertPushed(DispatchGeneralNotificationPageJob::class, fn ($j) => $j->pageId === (int) $due->getKey());
    }
}
