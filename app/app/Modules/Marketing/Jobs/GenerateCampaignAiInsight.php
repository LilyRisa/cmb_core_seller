<?php

namespace CMBcoreSeller\Modules\Marketing\Jobs;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdEntity;
use CMBcoreSeller\Modules\Marketing\Notifications\MarketingCampaignInsightReadyNotification;
use CMBcoreSeller\Modules\Marketing\Services\CampaignInsightAnalysisService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

/**
 * Generate the per-campaign AI analysis (cooldown already gated by the controller
 * ⇒ force), then email it to every Owner/Admin of the tenant. Result is also read
 * back via GET.
 *
 * @param  array{days?:int, metrics?:list<string>, include_engagement?:bool}  $params
 */
class GenerateCampaignAiInsight implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 900;

    /** @param array<string,mixed> $params */
    public function __construct(
        public int $adAccountId,
        public string $campaignExternalId,
        public array $params,
    ) {
        $this->onQueue('marketing-ai');
    }

    public function uniqueId(): string
    {
        return "campaign-insight:{$this->adAccountId}:{$this->campaignExternalId}";
    }

    public function handle(): void
    {
        /** @var AdAccount|null $account */
        $account = AdAccount::withoutGlobalScope(TenantScope::class)->find($this->adAccountId);
        if (! $account) {
            return;
        }

        $insight = app(CampaignInsightAnalysisService::class)->generate($account, $this->campaignExternalId, $this->params, true);

        /** @var Tenant|null $tenant */
        $tenant = Tenant::find($account->tenant_id);
        if (! $tenant) {
            return;
        }

        $campaignName = AdEntity::withoutGlobalScope(TenantScope::class)
            ->where('ad_account_id', $account->getKey())
            ->where('level', AdEntity::LEVEL_CAMPAIGN)
            ->where('external_id', $this->campaignExternalId)
            ->value('name');

        $recipients = $tenant->users()->wherePivotIn('role', [Role::Owner->value, Role::Admin->value])->get();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new MarketingCampaignInsightReadyNotification($account, $insight, $campaignName));
        }
    }
}
