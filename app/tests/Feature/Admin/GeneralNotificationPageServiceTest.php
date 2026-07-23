<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Admin\Services\GeneralNotificationPageService;
use CMBcoreSeller\Modules\Notifications\Models\Notification;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralNotificationPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): GeneralNotificationPageService
    {
        return app(GeneralNotificationPageService::class);
    }

    public function test_resolve_tenant_ids_all_excludes_suspended(): void
    {
        $active = Tenant::create(['name' => 'Active', 'status' => 'active']);
        Tenant::create(['name' => 'Suspended', 'status' => 'suspended']);
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'slug-1', 'body_html' => '<p>x</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_ALL, 'status' => 'draft', 'created_by_user_id' => 1,
        ]);

        $ids = $this->svc()->resolveTenantIds($page);

        $this->assertSame([(int) $active->getKey()], $ids);
    }

    public function test_resolve_tenant_ids_tenant_ids_filters_to_selected_and_excludes_suspended(): void
    {
        $t1 = Tenant::create(['name' => 'A', 'status' => 'active']);
        $t2 = Tenant::create(['name' => 'B', 'status' => 'suspended']);
        $t3 = Tenant::create(['name' => 'C', 'status' => 'active']); // không được chọn
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'slug-2', 'body_html' => '<p>x</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_TENANT_IDS,
            'audience_tenant_ids' => [$t1->getKey(), $t2->getKey()],
            'status' => 'draft', 'created_by_user_id' => 1,
        ]);

        $ids = $this->svc()->resolveTenantIds($page);

        $this->assertSame([(int) $t1->getKey()], $ids);
        $this->assertNotContains((int) $t3->getKey(), $ids);
    }

    public function test_dispatch_fans_out_to_all_users_of_each_tenant_and_marks_sent(): void
    {
        $tenant = Tenant::create(['name' => 'Shop', 'status' => 'active']);
        $u1 = \CMBcoreSeller\Models\User::factory()->create();
        $u2 = \CMBcoreSeller\Models\User::factory()->create();
        $tenant->users()->attach($u1->getKey(), ['role' => \CMBcoreSeller\Modules\Tenancy\Enums\Role::Owner->value]);
        $tenant->users()->attach($u2->getKey(), ['role' => \CMBcoreSeller\Modules\Tenancy\Enums\Role::Owner->value]);
        $page = GeneralNotificationPage::create([
            'title' => 'Ưu đãi', 'slug' => 'slug-3', 'body_html' => '<p>x</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_ALL, 'status' => 'draft', 'created_by_user_id' => 1,
        ]);

        $sentTo = $this->svc()->dispatch($page);

        $this->assertSame(1, $sentTo);
        $this->assertSame(GeneralNotificationPage::STATUS_SENT, $page->fresh()->status);
        $this->assertNotNull($page->fresh()->sent_at);
        $rows = Notification::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->getKey())->get();
        $this->assertCount(2, $rows);
        $this->assertSame('general', $rows->first()->category);
        $this->assertSame('/notifications/general/slug-3', $rows->first()->action_url);
    }

    public function test_generate_unique_slug_appends_suffix_on_collision(): void
    {
        GeneralNotificationPage::create([
            'title' => 'Ưu đãi tháng 8', 'slug' => 'uu-dai-thang-8', 'body_html' => '<p>x</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_ALL, 'status' => 'draft', 'created_by_user_id' => 1,
        ]);

        $this->assertSame('uu-dai-thang-8-2', $this->svc()->generateUniqueSlug('Ưu đãi tháng 8'));
    }
}
