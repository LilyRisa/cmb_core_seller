<?php

namespace CMBcoreSeller\Modules\Messaging\Console\Commands;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillFacebookComments;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

/**
 * Lưới an toàn: định kỳ dispatch backfill INCREMENTAL (since=last_synced_at) cho
 * mọi kênh active + messaging_enabled mà connector hỗ trợ `inbound.backfill`
 * (vd Facebook). Vá tin webhook lọt. Backfill idempotent (dedup theo message id).
 *
 * Khác `messaging-chat-poll` (chạy connector inbound.polling — Lazada). Chạy thưa
 * hơn (hằng giờ) vì webhook đã lo realtime.
 */
class ReconcileMessagingSync extends Command
{
    protected $signature = 'messaging:reconcile-sync';

    protected $description = 'Định kỳ đối soát backfill (incremental) cho kênh hỗ trợ inbound.backfill.';

    public function handle(MessagingRegistry $registry): int
    {
        ChannelAccount::withoutGlobalScope(TenantScope::class)
            ->where('status', ChannelAccount::STATUS_ACTIVE)
            ->where('messaging_enabled', true)
            ->orderBy('id')
            ->each(function (ChannelAccount $a) use ($registry) {
                $code = $a->messagingConnectorCode();
                if ($code === null || ! $registry->has($code) || ! $registry->for($code)->supports('inbound.backfill')) {
                    return;
                }
                $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find($a->id);
                $since = $meta?->last_synced_at?->toIso8601String();
                BackfillMessagingChannel::dispatch((int) $a->getKey(), $since);
                if ($registry->for($code)->supports('inbound.comments')) {
                    BackfillFacebookComments::dispatch((int) $a->getKey(), $since);
                }
            });

        return self::SUCCESS;
    }
}
