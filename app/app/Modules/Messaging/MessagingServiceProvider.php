<?php

namespace CMBcoreSeller\Modules\Messaging;

use CMBcoreSeller\Modules\Messaging\Console\Commands\AutoReplyTick;
use CMBcoreSeller\Modules\Messaging\Console\Commands\PruneAiSuggestionDrafts;
use CMBcoreSeller\Modules\Messaging\Console\Commands\PruneMessagingPayloads;
use CMBcoreSeller\Modules\Messaging\Contracts\MessageInboxContract;
use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Listeners\AiAutoModeOnInbound;
use CMBcoreSeller\Modules\Messaging\Listeners\RunAutoReplyOnInbound;
use CMBcoreSeller\Modules\Messaging\Listeners\RunAutoReplyOnOrderStatus;
use CMBcoreSeller\Modules\Messaging\Services\MessageInboxReader;
use CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged;
use CMBcoreSeller\Support\Database\PartitionRegistry;
use Illuminate\Support\Facades\Event;
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

        // AI provider credentials seam — connector (Integrations\Ai) đọc api_key/
        // model qua interface này thay vì import bảng ai_providers trực tiếp.
        $this->app->bind(
            \CMBcoreSeller\Integrations\Ai\Contracts\AiProviderCredentials::class,
            \CMBcoreSeller\Modules\Messaging\Services\DbAiProviderCredentials::class,
        );

        // Per-module config (media disk, size limits, signed URL TTL).
        $this->mergeConfigFrom(__DIR__.'/../../../config/messaging.php', 'messaging');
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

        // Auto-reply (S5): trigger theo event. away_no_response sweep qua command.
        Event::listen(MessageReceived::class, RunAutoReplyOnInbound::class);
        Event::listen(OrderStatusChanged::class, RunAutoReplyOnOrderStatus::class);

        // AI auto-mode (S7): tự trả lời qua guardrail intent (gated auto_mode + Business).
        Event::listen(MessageReceived::class, AiAutoModeOnInbound::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                AutoReplyTick::class,
                PruneMessagingPayloads::class,
                PruneAiSuggestionDrafts::class,
            ]);
        }
    }
}
