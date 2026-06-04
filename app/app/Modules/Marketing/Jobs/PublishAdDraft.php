<?php

namespace CMBcoreSeller\Modules\Marketing\Jobs;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsWriteConnector;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Marketing\Services\AdDraftSpecMapper;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Publish one AdDraft to Facebook: create Campaign → N ad sets → N ads (PAUSED),
 * resume-first (skip any node whose external id is already stored in payload ⇒
 * idempotent on retry). On any error the draft is marked `failed` with the message;
 * partial ids are kept so a re-publish resumes from the failed node. No auto-retry
 * (`tries = 1`).
 */
class PublishAdDraft implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 600;

    public function __construct(public int $draftId)
    {
        $this->onQueue('marketing-publish');
    }

    public function uniqueId(): string
    {
        return "publish-draft:{$this->draftId}";
    }

    public function handle(AdsRegistry $registry, AdDraftSpecMapper $mapper): void
    {
        /** @var AdDraft|null $draft */
        $draft = AdDraft::withoutGlobalScope(TenantScope::class)->find($this->draftId);
        if (! $draft) {
            return;
        }
        /** @var AdAccount|null $account */
        $account = AdAccount::withoutGlobalScope(TenantScope::class)->find($draft->ad_account_id);

        $connector = $account && $registry->has($account->provider) ? $registry->for($account->provider) : null;
        if (! $account || ! $connector instanceof AdsWriteConnector || ! $connector->supports('ads.create')) {
            $draft->forceFill(['status' => AdDraft::STATUS_FAILED, 'last_error' => 'Tài khoản/quảng cáo không hỗ trợ tạo.'])->save();

            return;
        }

        $token = (string) $account->access_token;
        $acc = $account->external_account_id;
        $draft->forceFill(['status' => AdDraft::STATUS_PUBLISHING, 'last_error' => null])->save();

        try {
            if (! $draft->campaign_external_id) {
                $draft->campaign_external_id = $connector->createCampaign($token, $acc, $mapper->campaign($draft, (string) $account->currency));
                $draft->save();
            }

            // Normalize to the tree shape so per-node external ids can persist (resume).
            $payload = (array) ($draft->payload ?? []);
            $payload['adsets'] = $mapper->adsetNodes($draft);
            $draft->payload = $payload;
            $draft->save();

            $currency = (string) $account->currency;
            $campaignId = (string) $draft->campaign_external_id;

            foreach ($payload['adsets'] as $i => $adsetNode) {
                if (empty($payload['adsets'][$i]['external_id'])) {
                    $payload['adsets'][$i]['external_id'] = $connector->createAdSet($token, $acc, $mapper->adSet($draft, $adsetNode, $campaignId, $currency));
                    $draft->payload = $payload;
                    $draft->save();
                }
                $adsetId = (string) $payload['adsets'][$i]['external_id'];

                foreach ((array) ($adsetNode['ads'] ?? []) as $j => $adNode) {
                    if (empty($payload['adsets'][$i]['ads'][$j]['external_id'])) {
                        $payload['adsets'][$i]['ads'][$j]['external_id'] = $connector->createAd($token, $acc, $mapper->ad($draft, (array) $adNode, $adsetId));
                        $draft->payload = $payload;
                        $draft->save();
                    }
                }
            }

            $draft->forceFill(['status' => AdDraft::STATUS_PUBLISHED])->save();
        } catch (\Throwable $e) {
            $draft->forceFill(['status' => AdDraft::STATUS_FAILED, 'last_error' => $e->getMessage()])->save();
        }
    }
}
