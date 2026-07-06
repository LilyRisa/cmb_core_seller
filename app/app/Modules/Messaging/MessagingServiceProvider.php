<?php

namespace CMBcoreSeller\Modules\Messaging;

use CMBcoreSeller\Integrations\Ai\Contracts\AiProviderCredentials;
use CMBcoreSeller\Modules\Messaging\Console\Commands\AutoReplyTick;
use CMBcoreSeller\Modules\Messaging\Console\Commands\DetectConversationPhones;
use CMBcoreSeller\Modules\Messaging\Console\Commands\PruneAiSuggestionDrafts;
use CMBcoreSeller\Modules\Messaging\Console\Commands\PruneMessagingPayloads;
use CMBcoreSeller\Modules\Messaging\Console\Commands\PushNewMessageDigest;
use CMBcoreSeller\Modules\Messaging\Console\Commands\RecomputeConversationPreviews;
use CMBcoreSeller\Modules\Messaging\Console\Commands\ReconcileMessagingSync;
use CMBcoreSeller\Modules\Messaging\Console\Commands\ReindexKnowledge;
use CMBcoreSeller\Modules\Messaging\Contracts\ExpoPushSenderContract;
use CMBcoreSeller\Modules\Messaging\Contracts\MessageInboxContract;
use CMBcoreSeller\Modules\Messaging\Events\CommentReceived;
use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Events\PostbackReceived;
use CMBcoreSeller\Modules\Messaging\Listeners\AdvanceFlowOnPostback;
use CMBcoreSeller\Modules\Messaging\Listeners\AiAutoModeOnInbound;
use CMBcoreSeller\Modules\Messaging\Listeners\IndexVisualKnowledgeItem;
use CMBcoreSeller\Modules\Messaging\Listeners\PurgeVisualKnowledgeItem;
use CMBcoreSeller\Modules\Messaging\Listeners\PushWebOnNewMessage;
use CMBcoreSeller\Modules\Messaging\Listeners\RunAutoReplyOnComment;
use CMBcoreSeller\Modules\Messaging\Listeners\RunAutoReplyOnInbound;
use CMBcoreSeller\Modules\Messaging\Listeners\RunAutoReplyOnOrderStatus;
use CMBcoreSeller\Modules\Messaging\Listeners\StartFlowOnComment;
use CMBcoreSeller\Modules\Messaging\Listeners\StartFlowOnInbound;
use CMBcoreSeller\Modules\Messaging\Services\DbAiProviderCredentials;
use CMBcoreSeller\Modules\Messaging\Services\ExpoPushSender;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\AiReplyNodeExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\ConditionNodeExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\EndNodeExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\NodeExecutorRegistry;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\PostRouterNodeExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\SendCommentReplyNodeExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\SendInteractiveNodeExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\SendMessageNodeExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\TriggerNodeExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\WaitReplyNodeExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\SendButtonsStep;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\SendMediaStep;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\SendTextStep;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\StepExecutorRegistry;
use CMBcoreSeller\Modules\Messaging\Services\MessageInboxReader;
use CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged;
use CMBcoreSeller\Modules\VisualSearch\Events\KnowledgeItemDeleted;
use CMBcoreSeller\Modules\VisualSearch\Events\KnowledgeItemSaved;
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

        // SPEC 0029 — Expo Push sender (mobile push, tách khỏi Web Push VAPID).
        $this->app->bind(ExpoPushSenderContract::class, ExpoPushSender::class);

        // AI provider credentials seam — connector (Integrations\Ai) đọc api_key/
        // model qua interface này thay vì import bảng ai_providers trực tiếp.
        $this->app->bind(
            AiProviderCredentials::class,
            DbAiProviderCredentials::class,
        );

        // Per-module config (media disk, size limits, signed URL TTL).
        $this->mergeConfigFrom(__DIR__.'/../../../config/messaging.php', 'messaging');

        // Flow builder: registry singleton wires all node executors so that
        // app(FlowEngine::class) resolves with a fully-wired registry.
        $this->app->singleton(NodeExecutorRegistry::class, function ($app) {
            $registry = new NodeExecutorRegistry($app);
            $registry->register('trigger', TriggerNodeExecutor::class);
            $registry->register('send_message', SendMessageNodeExecutor::class);
            $registry->register('send_buttons', SendInteractiveNodeExecutor::class);
            $registry->register('send_comment_reply', SendCommentReplyNodeExecutor::class);
            $registry->register('condition', ConditionNodeExecutor::class);
            $registry->register('post_router', PostRouterNodeExecutor::class);
            $registry->register('ai_reply', AiReplyNodeExecutor::class);
            $registry->register('wait_reply', WaitReplyNodeExecutor::class);
            $registry->register('end', EndNodeExecutor::class);

            return $registry;
        });

        // Step executor registry: map step.type → executor (send_text/send_media/send_buttons).
        // Mirror NodeExecutorRegistry — tất cả bước có thể inject service qua container.
        $this->app->singleton(StepExecutorRegistry::class, function ($app) {
            $registry = new StepExecutorRegistry($app);
            $registry->register(SendTextStep::TYPE, SendTextStep::class);
            $registry->register(SendMediaStep::TYPE, SendMediaStep::class);
            $registry->register(SendButtonsStep::TYPE, SendButtonsStep::class);

            return $registry;
        });
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

        // Web Push tức thì khi có tin nhắn inbound mới (event-driven) — bù cho digest 30'. Gửi cho sub
        // KHÔNG hoạt động (tab đóng/ẩn); queued trên messaging-bg. SPEC 0029.
        Event::listen(MessageReceived::class, PushWebOnNewMessage::class);

        // Auto-reply (S5): trigger theo event. away_no_response sweep qua command.
        Event::listen(MessageReceived::class, RunAutoReplyOnInbound::class);
        Event::listen(OrderStatusChanged::class, RunAutoReplyOnOrderStatus::class);

        // AI auto-mode (S7): tự trả lời qua guardrail intent (gated auto_mode + Business).
        Event::listen(MessageReceived::class, AiAutoModeOnInbound::class);

        // Auto-reply COMMENT: đường riêng (đích công khai/nhắn riêng theo rule;
        // nội dung mẫu/text/AI). Comment KHÔNG dùng MessageReceived/auto-mode DM.
        Event::listen(CommentReceived::class, RunAutoReplyOnComment::class);

        // Flow Builder (S1): kịch bản đa bước, chạy song song auto-reply phẳng.
        Event::listen(MessageReceived::class, StartFlowOnInbound::class);
        Event::listen(CommentReceived::class, StartFlowOnComment::class);

        // Flow Builder (S2): buyer bấm nút (postback) ⇒ tiến luồng theo nhánh nút.
        Event::listen(PostbackReceived::class, AdvanceFlowOnPostback::class);

        // KB-unification (A6): VisualSearch phát event khi CRUD item tri thức (hợp nhất
        // text+ảnh) — Messaging nghe để (re)index/purge RAG. Seam một chiều: Messaging
        // import VisualSearch\Events (event phẳng), KHÔNG import Services/Models nội bộ.
        Event::listen(KnowledgeItemSaved::class, IndexVisualKnowledgeItem::class);
        Event::listen(KnowledgeItemDeleted::class, PurgeVisualKnowledgeItem::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                AutoReplyTick::class,
                PruneMessagingPayloads::class,
                PruneAiSuggestionDrafts::class,
                ReconcileMessagingSync::class,
                DetectConversationPhones::class,
                RecomputeConversationPreviews::class,
                PushNewMessageDigest::class,
                ReindexKnowledge::class,
            ]);
        }
    }
}
