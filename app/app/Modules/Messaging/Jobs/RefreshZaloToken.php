<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Services\ZaloTokenRefresher;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshZaloToken implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $channelAccountId)
    {
        $this->onQueue('tokens');
    }

    public function uniqueId(): string
    {
        return "refresh-zalo-token:{$this->channelAccountId}";
    }

    public function uniqueFor(): int
    {
        return 300;
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(ZaloTokenRefresher $refresher): void
    {
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);
        if ($account) {
            $refresher->refresh($account);
        }
    }
}
