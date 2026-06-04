<?php

namespace CMBcoreSeller\Modules\Support;

use CMBcoreSeller\Modules\Support\Console\Commands\IndexHelpDocs;
use CMBcoreSeller\Modules\Support\Console\Commands\PublishHelpImages;
use CMBcoreSeller\Modules\Support\Services\QdrantClient;
use Illuminate\Support\ServiceProvider;

/**
 * Module Support — trợ lý trợ giúp sản phẩm (RAG hỏi-đáp cách dùng) + yêu cầu CSKH.
 *
 * Tự chứa: AI dùng SupportAiClient riêng (credentials Support — KHÔNG đụng bảng
 * ai_providers/registry messaging) và không phụ thuộc ruột module khác.
 * help_chunks là GLOBAL; hội thoại CSKH (support_conversations/messages/attachments) theo tenant.
 */
class SupportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../config/support.php', 'support');

        $this->app->singleton(QdrantClient::class, fn () => new QdrantClient);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([IndexHelpDocs::class, PublishHelpImages::class]);
        }
    }
}
