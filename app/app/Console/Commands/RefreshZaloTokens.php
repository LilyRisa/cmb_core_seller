<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\RefreshZaloToken;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

class RefreshZaloTokens extends Command
{
    protected $signature = 'messaging:zalo:refresh-tokens {--within=21600 : Refresh Zalo OA tokens expiring within this many seconds}';

    protected $description = 'Queue Zalo OA access-token refreshes before expiry (rotating refresh token)';

    public function handle(): int
    {
        $threshold = now()->addSeconds((int) $this->option('within'));

        $count = ChannelAccount::withoutGlobalScope(TenantScope::class)
            ->where('provider', 'zalo_oa')
            ->where('status', ChannelAccount::STATUS_ACTIVE)
            ->whereNotNull('refresh_token')
            ->where(function ($q) use ($threshold) {
                $q->whereNull('token_expires_at')->orWhere('token_expires_at', '<=', $threshold);
            })
            ->get()
            ->each(fn (ChannelAccount $a) => RefreshZaloToken::dispatch((int) $a->getKey()))
            ->count();

        $this->info("Queued {$count} Zalo token refresh job(s).");

        return self::SUCCESS;
    }
}
