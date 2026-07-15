<?php

namespace CMBcoreSeller\Modules\Admin\Notifications\Listeners;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use CMBcoreSeller\Modules\Admin\Notifications\Services\AdminNotificationDispatcher;
use CMBcoreSeller\Modules\Support\Events\SupportNewConversationOpened;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Nghe `SupportNewConversationOpened` (module Support) ⇒ báo admin qua email (SPEC
 * 2026-07-15). Giao tiếp qua domain event — không `use` Services nội bộ của Support.
 */
class NotifyAdminsOnNewSupportConversation implements ShouldQueue
{
    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(private readonly AdminNotificationDispatcher $dispatcher) {}

    public function handle(SupportNewConversationOpened $event): void
    {
        $tenant = Tenant::find($event->tenantId);
        $tenantName = $tenant instanceof Tenant ? $tenant->name : '(không rõ shop)';

        $senderName = $event->userId !== null ? (User::find($event->userId)?->name) : null;

        $this->dispatcher->notify(NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION, [
            'tenant_name' => $tenantName,
            'sender_name' => $senderName ?? '(không rõ người gửi)',
            'snippet' => $event->snippet,
            'conversation_id' => $event->conversationId,
        ]);
    }
}
