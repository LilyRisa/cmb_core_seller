<?php

namespace CMBcoreSeller\Modules\Marketing\Jobs;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Marketing\Services\AdsSyncService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Sync the campaign→adset→ad tree for one ad account (idempotent upsert).
 * Mirror pattern: SyncConversationsForShop (Messaging).
 */
class SyncAdAccountEntities implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 900;

    public function __construct(public int $adAccountId)
    {
        $this->onQueue('marketing-sync');
    }

    public function uniqueId(): string
    {
        return "ads-entities:{$this->adAccountId}";
    }

    public function handle(AdsRegistry $registry): void
    {
        /** @var AdAccount|null $account */
        $account = AdAccount::withoutGlobalScope(TenantScope::class)->find($this->adAccountId);
        if (! $account || $account->status !== AdAccount::STATUS_ACTIVE || ! $registry->has($account->provider)) {
            return;
        }

        $connector = $registry->for($account->provider);
        $sync = app(AdsSyncService::class);
        $token = (string) $account->access_token;

        // Parents first so children resolve parent_id.
        foreach ([AdEntity::LEVEL_CAMPAIGN, AdEntity::LEVEL_ADSET, AdEntity::LEVEL_AD] as $level) {
            foreach ($connector->listEntities($token, $account->external_account_id, $level) as $dto) {
                $sync->upsertEntity($account, $dto);
            }
        }

        $account->forceFill(['last_synced_at' => now()])->save();
    }
}
