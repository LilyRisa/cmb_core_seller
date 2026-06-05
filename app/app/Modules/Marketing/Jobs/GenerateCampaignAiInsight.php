<?php

namespace CMBcoreSeller\Modules\Marketing\Jobs;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Services\CampaignInsightAnalysisService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Generate the per-campaign AI analysis (cooldown already gated by the controller
 * ⇒ force). Result is read back via GET (no email for G v1 — see spec §8).
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

        app(CampaignInsightAnalysisService::class)->generate($account, $this->campaignExternalId, $this->params, true);
    }
}
