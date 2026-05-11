<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Modules\Channels\Jobs\RefreshChannelToken;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

/**
 * Scheduled (~every 30'): dispatch RefreshChannelToken for active accounts whose
 * access token expires within the window. See docs/07-infra/queues-and-scheduler.md §2.
 */
class RefreshExpiringChannelTokens extends Command
{
    protected $signature = 'channels:refresh-expiring-tokens {--within=86400 : Refresh tokens expiring within this many seconds}';

    protected $description = 'Queue token refreshes for channel accounts whose OAuth token is about to expire';

    public function handle(): int
    {
        $threshold = now()->addSeconds((int) $this->option('within'));

        $count = ChannelAccount::withoutGlobalScope(TenantScope::class)
            ->where('status', ChannelAccount::STATUS_ACTIVE)
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', $threshold)
            ->get()
            ->each(fn (ChannelAccount $a) => RefreshChannelToken::dispatch((int) $a->getKey()))
            ->count();

        $this->info("Queued {$count} token refresh job(s).");

        return self::SUCCESS;
    }
}
