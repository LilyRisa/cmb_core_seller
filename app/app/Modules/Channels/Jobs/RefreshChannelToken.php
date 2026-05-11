<?php

namespace CMBcoreSeller\Modules\Channels\Jobs;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Support\TokenRefresher;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Refresh one channel account's OAuth token (queue: tokens — a stalled token kills sync). */
class RefreshChannelToken implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $channelAccountId)
    {
        $this->onQueue('tokens');
    }

    public function uniqueId(): string
    {
        return "refresh-token:{$this->channelAccountId}";
    }

    public function uniqueFor(): int
    {
        return 300;
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(TokenRefresher $refresher): void
    {
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);
        if ($account) {
            $refresher->refresh($account);
        }
    }
}
