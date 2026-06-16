<?php

namespace CMBcoreSeller\Modules\VisualSearch;

use Illuminate\Support\ServiceProvider;

/**
 * Module VisualSearch (SPEC 2026-06-16) — visual training & tìm sản phẩm bằng ảnh.
 *
 * Dùng trục Integration Vector (Qdrant) + Image Embedding (CLIP) + AI vision.analyze.
 * Messaging tiêu thụ qua Contracts\VisualItemSearch (gắn binding ở Phase matching).
 */
class VisualSearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Binding Contracts\VisualItemSearch => Services\VisualMatcher thêm ở phase matching.
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
