<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Admin\Jobs\DispatchGeneralNotificationPageJob;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Admin\Services\GeneralNotificationPageService;
use CMBcoreSeller\Modules\Notifications\Models\Notification;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchGeneralNotificationPageJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_dispatches_page_and_marks_sent(): void
    {
        $tenant = Tenant::create(['name' => 'JobShop', 'status' => 'active']);
        $user = User::factory()->create();
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $page = GeneralNotificationPage::create([
            'title' => 'Tin gấp', 'slug' => 'tin-gap', 'body_html' => '<p>x</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_ALL, 'status' => 'draft', 'created_by_user_id' => 1,
        ]);

        (new DispatchGeneralNotificationPageJob((int) $page->getKey()))->handle(app(GeneralNotificationPageService::class));

        $this->assertSame(GeneralNotificationPage::STATUS_SENT, $page->fresh()->status);
        $this->assertSame(1, Notification::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->getKey())->count());
    }

    public function test_job_is_noop_when_already_sent(): void
    {
        $tenant = Tenant::create(['name' => 'JobShop2', 'status' => 'active']);
        $user = User::factory()->create();
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $page = GeneralNotificationPage::create([
            'title' => 'Tin gấp 2', 'slug' => 'tin-gap-2', 'body_html' => '<p>x</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_ALL, 'status' => GeneralNotificationPage::STATUS_SENT,
            'sent_at' => now()->subHour(), 'created_by_user_id' => 1,
        ]);

        (new DispatchGeneralNotificationPageJob((int) $page->getKey()))->handle(app(GeneralNotificationPageService::class));

        $this->assertSame(0, Notification::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->getKey())->count());
    }

    public function test_job_is_noop_when_page_not_found(): void
    {
        // Không throw — page đã bị xoá giữa lúc job chờ hàng đợi là tình huống hợp lệ.
        (new DispatchGeneralNotificationPageJob(999999))->handle(app(GeneralNotificationPageService::class));
        $this->assertTrue(true);
    }
}
