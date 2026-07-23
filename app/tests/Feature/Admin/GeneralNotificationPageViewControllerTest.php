<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPageView;
use CMBcoreSeller\Modules\Admin\Services\GeneralNotificationPageService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralNotificationPageViewControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'ViewShop', 'status' => 'active']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeAndSendPage(array $attrs = []): GeneralNotificationPage
    {
        $page = GeneralNotificationPage::create(array_merge([
            'title' => 'Ưu đãi', 'slug' => 'view-page', 'body_html' => '<p>Nội dung</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_ALL, 'status' => 'draft', 'created_by_user_id' => 1,
        ], $attrs));
        app(GeneralNotificationPageService::class)->dispatch($page);

        return $page->fresh();
    }

    public function test_tenant_that_received_page_can_view_and_records_view(): void
    {
        $page = $this->makeAndSendPage();

        $res = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/notifications/general/view-page');

        $res->assertOk()->assertJsonPath('data.title', 'Ưu đãi');
        $this->assertSame(1, GeneralNotificationPageView::query()
            ->where('page_id', $page->getKey())->where('user_id', $this->owner->getKey())->count());
    }

    public function test_viewing_twice_only_records_one_view(): void
    {
        $this->makeAndSendPage();

        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/notifications/general/view-page')->assertOk();
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/notifications/general/view-page')->assertOk();

        $this->assertSame(1, GeneralNotificationPageView::query()->where('user_id', $this->owner->getKey())->count());
    }

    public function test_tenant_not_in_audience_gets_forbidden(): void
    {
        // Gửi cho tenant KHÁC, không phải $this->tenant.
        $other = Tenant::create(['name' => 'OtherShop', 'status' => 'active']);
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'other-page', 'body_html' => '<p>x</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_TENANT_IDS,
            'audience_tenant_ids' => [$other->getKey()], 'status' => 'draft', 'created_by_user_id' => 1,
        ]);
        app(GeneralNotificationPageService::class)->dispatch($page);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/notifications/general/other-page')->assertForbidden();
    }

    public function test_expired_page_returns_410(): void
    {
        $this->makeAndSendPage(['slug' => 'expired-page', 'expires_at' => now()->subDay()]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/notifications/general/expired-page')->assertStatus(410);
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/notifications/general/khong-ton-tai')->assertNotFound();
    }
}
