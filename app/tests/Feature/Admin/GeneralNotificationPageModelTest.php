<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPageView;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralNotificationPageModelTest extends TestCase
{
    use RefreshDatabase;

    private function makePage(array $attrs = []): GeneralNotificationPage
    {
        return GeneralNotificationPage::create(array_merge([
            'title' => 'Ưu đãi tháng 8', 'slug' => 'uu-dai-thang-8', 'body_html' => '<p>Nội dung</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_ALL,
            'status' => GeneralNotificationPage::STATUS_DRAFT,
            'created_by_user_id' => 1,
        ], $attrs));
    }

    public function test_slug_is_unique(): void
    {
        $this->makePage();
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->makePage();
    }

    public function test_is_expired_false_when_no_expiry_set(): void
    {
        $page = $this->makePage();
        $this->assertFalse($page->isExpired());
    }

    public function test_is_expired_true_when_expires_at_in_past(): void
    {
        $page = $this->makePage(['expires_at' => now()->subDay()]);
        $this->assertTrue($page->isExpired());
    }

    public function test_is_expired_false_when_expires_at_in_future(): void
    {
        $page = $this->makePage(['expires_at' => now()->addDay()]);
        $this->assertFalse($page->isExpired());
    }

    public function test_audience_tenant_ids_casts_to_array(): void
    {
        $page = $this->makePage(['audience_type' => GeneralNotificationPage::AUDIENCE_TENANT_IDS, 'audience_tenant_ids' => [1, 2, 3]]);
        $this->assertSame([1, 2, 3], $page->fresh()->audience_tenant_ids);
    }

    public function test_page_view_unique_per_page_and_user(): void
    {
        $page = $this->makePage();
        GeneralNotificationPageView::create(['page_id' => $page->getKey(), 'tenant_id' => 5, 'user_id' => 9, 'viewed_at' => now()]);

        $this->expectException(UniqueConstraintViolationException::class);
        GeneralNotificationPageView::create(['page_id' => $page->getKey(), 'tenant_id' => 5, 'user_id' => 9, 'viewed_at' => now()]);
    }
}
