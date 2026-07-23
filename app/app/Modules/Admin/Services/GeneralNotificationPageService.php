<?php

namespace CMBcoreSeller\Modules\Admin\Services;

use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Notifications\Contracts\NotificationDispatcherContract;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Str;

/**
 * Plan C (2026-07-23) — soạn + gửi "trang thông báo chung" (ưu đãi/tin chung) tới tenant. Gửi
 * qua {@see NotificationDispatcherContract} (module Notifications) — mỗi tenant trong audience
 * nhận 1 loạt `app_notifications` fan-out cho TOÀN BỘ user của tenant đó (category=general).
 */
class GeneralNotificationPageService
{
    public function __construct(private NotificationDispatcherContract $dispatcher) {}

    /** @return list<int> */
    public function resolveTenantIds(GeneralNotificationPage $page): array
    {
        if ($page->audience_type === GeneralNotificationPage::AUDIENCE_ALL) {
            return Tenant::query()->where('status', '!=', 'suspended')
                ->pluck('id')->map(fn ($v) => (int) $v)->all();
        }

        $ids = collect($page->audience_tenant_ids ?? [])->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();
        if ($ids === []) {
            return [];
        }

        return Tenant::query()->whereIn('id', $ids)->where('status', '!=', 'suspended')
            ->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    /** Fan-out tới toàn bộ user của mỗi tenant trong audience; đánh dấu page đã gửi. Trả số tenant đã gửi. */
    public function dispatch(GeneralNotificationPage $page): int
    {
        $tenantIds = $this->resolveTenantIds($page);

        foreach ($tenantIds as $tenantId) {
            $this->dispatcher->dispatch($tenantId, [
                'type' => NotificationType::GENERAL_PAGE,
                'level' => 'info',
                'title' => $page->title,
                'action_url' => '/notifications/general/'.$page->slug,
                'data' => ['page_id' => (int) $page->getKey(), 'slug' => $page->slug],
                'dedup_key' => 'general.page:'.$page->getKey(),
            ]);
        }

        $page->forceFill(['status' => GeneralNotificationPage::STATUS_SENT, 'sent_at' => now()])->save();

        return count($tenantIds);
    }

    /** Slug duy nhất từ tiêu đề — thêm hậu tố `-2`, `-3`... nếu trùng. */
    public function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'thong-bao';
        $slug = $base;
        $suffix = 2;
        while (GeneralNotificationPage::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
