<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Services\MessagingAvatarRelay;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Relay avatar về object storage rồi gán path. target:
 *   - 'page'         → messaging_account_meta.page_avatar_path (id = channel_account_id)
 *   - 'conversation' → conversations.buyer_avatar_path (id = conversation_id)
 */
class RelayMessagingAvatar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $target,
        public int $id,
        public string $url,
    ) {}

    public function handle(MessagingAvatarRelay $relay): void
    {
        if ($this->target === 'conversation') {
            $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($this->id);
            if (! $conv) {
                return;
            }
            $path = $relay->relay((int) $conv->tenant_id, $this->url);
            if ($path) {
                $conv->forceFill(['buyer_avatar_path' => $path])->save();
            }

            return;
        }

        if ($this->target === 'page') {
            $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find($this->id);
            if (! $meta) {
                return;
            }
            $path = $relay->relay((int) $meta->tenant_id, $this->url);
            if ($path) {
                $meta->forceFill(['page_avatar_path' => $path, 'page_avatar_synced_at' => now()])->save();
            }
        }
    }
}
