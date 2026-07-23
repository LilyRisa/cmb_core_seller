<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Admin\Jobs\DispatchGeneralNotificationPageJob;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPageView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminGeneralNotificationPageControllerTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
    }

    private function actingAsAdmin(): self
    {
        $this->actingAs($this->admin, 'admin_web');

        return $this;
    }

    public function test_store_creates_draft_page_with_sanitized_html(): void
    {
        $res = $this->actingAsAdmin()->postJson('/api/v1/admin/general-notification-pages', [
            'title' => 'Ưu đãi tháng 8',
            'body_html' => '<p>Nội dung</p><script>alert(1)</script>',
            'audience_type' => 'all',
        ]);

        $res->assertCreated()->assertJsonPath('data.status', 'draft')->assertJsonPath('data.slug', 'uu-dai-thang-8');
        $this->assertStringNotContainsString('<script>', $res->json('data.body_html'));
    }

    public function test_store_requires_tenant_ids_when_audience_is_tenant_ids(): void
    {
        $this->actingAsAdmin()->postJson('/api/v1/admin/general-notification-pages', [
            'title' => 'T', 'body_html' => '<p>x</p>', 'audience_type' => 'tenant_ids',
        ])->assertStatus(422);
    }

    public function test_store_with_scheduled_at_sets_status_scheduled(): void
    {
        $res = $this->actingAsAdmin()->postJson('/api/v1/admin/general-notification-pages', [
            'title' => 'Lên lịch', 'body_html' => '<p>x</p>', 'audience_type' => 'all',
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ]);

        $res->assertCreated()->assertJsonPath('data.status', 'scheduled');
    }

    public function test_update_rejects_when_already_sent(): void
    {
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'sent-page', 'body_html' => '<p>x</p>',
            'audience_type' => 'all', 'status' => GeneralNotificationPage::STATUS_SENT,
            'sent_at' => now(), 'created_by_user_id' => $this->admin->getKey(),
        ]);

        $this->actingAsAdmin()->patchJson("/api/v1/admin/general-notification-pages/{$page->getKey()}", ['title' => 'X'])
            ->assertStatus(422)->assertJsonPath('error.code', 'PAGE_ALREADY_SENT');
    }

    public function test_destroy_deletes_page(): void
    {
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'to-delete', 'body_html' => '<p>x</p>',
            'audience_type' => 'all', 'status' => 'draft', 'created_by_user_id' => $this->admin->getKey(),
        ]);

        $this->actingAsAdmin()->deleteJson("/api/v1/admin/general-notification-pages/{$page->getKey()}")
            ->assertOk()->assertJsonPath('data.deleted', true);
        $this->assertDatabaseMissing('general_notification_pages', ['id' => $page->getKey()]);
    }

    public function test_send_dispatches_job_for_draft_page(): void
    {
        Queue::fake();
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'to-send', 'body_html' => '<p>x</p>',
            'audience_type' => 'all', 'status' => 'draft', 'created_by_user_id' => $this->admin->getKey(),
        ]);

        $this->actingAsAdmin()->postJson("/api/v1/admin/general-notification-pages/{$page->getKey()}/send")
            ->assertOk()->assertJsonPath('data.dispatched', true);

        Queue::assertPushed(DispatchGeneralNotificationPageJob::class, fn ($j) => $j->pageId === (int) $page->getKey());
    }

    public function test_send_rejects_already_sent_page(): void
    {
        Queue::fake();
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'already-sent', 'body_html' => '<p>x</p>',
            'audience_type' => 'all', 'status' => GeneralNotificationPage::STATUS_SENT, 'sent_at' => now(),
            'created_by_user_id' => $this->admin->getKey(),
        ]);

        $this->actingAsAdmin()->postJson("/api/v1/admin/general-notification-pages/{$page->getKey()}/send")
            ->assertStatus(422)->assertJsonPath('error.code', 'PAGE_ALREADY_SENT');
        Queue::assertNotPushed(DispatchGeneralNotificationPageJob::class);
    }

    public function test_stats_returns_view_count(): void
    {
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'stats-page', 'body_html' => '<p>x</p>',
            'audience_type' => 'all', 'status' => GeneralNotificationPage::STATUS_SENT, 'sent_at' => now(),
            'created_by_user_id' => $this->admin->getKey(),
        ]);
        GeneralNotificationPageView::create(['page_id' => $page->getKey(), 'tenant_id' => 1, 'user_id' => 1, 'viewed_at' => now()]);
        GeneralNotificationPageView::create(['page_id' => $page->getKey(), 'tenant_id' => 2, 'user_id' => 2, 'viewed_at' => now()]);

        $this->actingAsAdmin()->getJson("/api/v1/admin/general-notification-pages/{$page->getKey()}/stats")
            ->assertOk()->assertJsonPath('data.view_count', 2);
    }
}
