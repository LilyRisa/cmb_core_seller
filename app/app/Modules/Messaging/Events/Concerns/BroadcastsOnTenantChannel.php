<?php

namespace CMBcoreSeller\Modules\Messaging\Events\Concerns;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Broadcast event Messaging lên private channel theo tenant của conversation:
 * `tenant.{tenantId}.messaging`. FE (`useInbox`) subscribe để cập nhật realtime
 * (SPEC-0024 §6.3). Driver mặc định `null` (no-op) tới khi bật Reverb — fire vào
 * void, KHÔNG lỗi. Cần class dùng có thuộc tính `$conversationId`.
 */
trait BroadcastsOnTenantChannel
{
    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        $tenantId = Conversation::withoutGlobalScope(TenantScope::class)
            ->whereKey($this->conversationId)
            ->value('tenant_id');

        return $tenantId ? [new PrivateChannel("tenant.{$tenantId}.messaging")] : [];
    }
}
