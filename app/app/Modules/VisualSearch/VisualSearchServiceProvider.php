<?php

namespace CMBcoreSeller\Modules\VisualSearch;

use CMBcoreSeller\Modules\VisualSearch\Console\Commands\ReindexVisualTraining;
use CMBcoreSeller\Modules\VisualSearch\Contracts\KnowledgeItemStore;
use CMBcoreSeller\Modules\VisualSearch\Contracts\VisualItemSearch;
use CMBcoreSeller\Modules\VisualSearch\Services\KnowledgeItemRepository;
use CMBcoreSeller\Modules\VisualSearch\Services\VisualMatcher;
use Illuminate\Support\ServiceProvider;

/**
 * Module VisualSearch (SPEC 2026-06-16) — visual training & tìm sản phẩm bằng ảnh.
 *
 * Dùng trục Integration Vector (Qdrant) + Image Embedding (CLIP) + AI vision.analyze.
 * Messaging tiêu thụ qua Contracts\VisualItemSearch.
 */
class VisualSearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VisualItemSearch::class, VisualMatcher::class);
        $this->app->bind(KnowledgeItemStore::class, KnowledgeItemRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([ReindexVisualTraining::class]);
        }
    }
}
