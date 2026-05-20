<?php

namespace CMBcoreSeller\Modules\Messaging;

use CMBcoreSeller\Modules\Messaging\Contracts\MessageInboxContract;
use CMBcoreSeller\Modules\Messaging\Services\MessageInboxReader;
use CMBcoreSeller\Support\Database\PartitionRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider cho Messaging module (SPEC-0024 Phase 7.x đề xuất).
 *
 * Bind:
 *   - `MessageInboxContract` → `MessageInboxReader` (cho Orders/Customers đọc)
 *
 * Wire:
 *   - migrations
 *   - routes/messaging.php
 *   - PartitionRegistry::register('messages', 'created_at') — S1 bảng phẳng,
 *     pre-register để khi nâng partition sau, scheduler đã có hook.
 *
 * KHÔNG register messaging connectors ở đây — register ở
 * `IntegrationsServiceProvider` (cùng pattern Channels/Carriers/Payments).
 */
class MessagingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MessageInboxContract::class, MessageInboxReader::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }

        // Pre-register partition target. Phase sau nâng partition không cần đổi
        // gọi `db:partitions:ensure` — registry đã có.
        PartitionRegistry::register('messages', 'created_at');
    }
}
