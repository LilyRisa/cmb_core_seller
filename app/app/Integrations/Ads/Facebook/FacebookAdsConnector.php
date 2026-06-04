<?php

namespace CMBcoreSeller\Integrations\Ads\Facebook;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsConnector;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsWriteConnector;
use CMBcoreSeller\Integrations\Ads\DTO\AdAccountDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdEntityDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightThrottleDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AudienceSizeDTO;
use CMBcoreSeller\Integrations\Ads\DTO\CampaignSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\PagePostDTO;
use CMBcoreSeller\Integrations\Ads\DTO\PageRefDTO;
use CMBcoreSeller\Integrations\Ads\DTO\TargetingOptionDTO;
use CMBcoreSeller\Integrations\Ads\Exceptions\UnsupportedOperation;
use Illuminate\Support\Facades\Http;

/**
 * Facebook Ads (Marketing API) connector — SPEC 2026-06-04. Reads ad accounts /
 * entities / insights via Graph; writes (budget/pause/bid) land in Phase 3.
 *
 * MONEY: Graph returns spend/cpc/cpm as major-unit decimal strings. Phase 1
 * targets VND (zero-decimal) ⇒ round() is exact. Multi-currency minor-unit
 * scaling (×100 for 2-decimal currencies, keyed on ad_accounts.currency) is a
 * later-phase concern.
 */
class FacebookAdsConnector implements AdsConnector, AdsWriteConnector
{
    /** @param array<string,mixed> $config config('integrations.ads_facebook') */
    public function __construct(private array $config) {}

    public function code(): string
    {
        return 'facebook';
    }

    public function displayName(): string
    {
        return 'Facebook Ads';
    }

    public function capabilities(): array
    {
        return [
            'insights.read' => true,
            'insights.async' => true,
            'entities.list' => true,
            'actions.budget' => false, // Phase 3
            'actions.status' => false, // Phase 3
            'actions.bid' => false,    // Phase 3
            'ads.create' => true,
            'creative.upload' => true,
            'page.posts.read' => true,
            'preview.generate' => true,
            'targeting.search' => true,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        return 'https://www.facebook.com/'.$this->graphVersion().'/dialog/oauth?'.http_build_query([
            'client_id' => (string) ($this->config['app_id'] ?? ''),
            'redirect_uri' => $opts['redirect_uri'] ?? $this->redirectUri(),
            'state' => $state,
            'scope' => (string) ($this->config['scopes'] ?? 'ads_read,business_management'),
            'response_type' => 'code',
        ]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        $res = Http::get('https://graph.facebook.com/'.$this->graphVersion().'/oauth/access_token', [
            'client_id' => $this->config['app_id'] ?? '',
            'client_secret' => $this->config['app_secret'] ?? '',
            'redirect_uri' => $this->redirectUri(),
            'code' => $code,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads token exchange failed: '.$res->body());
        }

        return [
            'access_token' => (string) $res->json('access_token'),
            'expires_at' => $res->json('expires_in') ? CarbonImmutable::now()->addSeconds((int) $res->json('expires_in')) : null,
            'raw' => (array) $res->json(),
        ];
    }

    /** Redirect URI OAuth — phải đăng ký y hệt trong Meta app. Mặc định suy từ APP_URL. */
    private function redirectUri(): string
    {
        $configured = (string) ($this->config['redirect_uri'] ?? '');
        if ($configured !== '') {
            return $configured;
        }

        return rtrim((string) config('app.url'), '/').'/oauth/facebook_ads/callback';
    }

    private function graphVersion(): string
    {
        return (string) ($this->config['graph_version'] ?? 'v19.0');
    }

    private function graphUrl(string $path): string
    {
        return 'https://graph.facebook.com/'.$this->graphVersion().'/'.ltrim($path, '/');
    }

    public function listAdAccounts(string $accessToken): array
    {
        $res = Http::timeout(30)->get($this->graphUrl('me/adaccounts'), [
            'fields' => 'account_id,name,currency,account_status,business{id,name}',
            'access_token' => $accessToken, 'limit' => 200,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads listAdAccounts failed: '.$res->body());
        }

        return array_values(array_map(fn (array $a) => new AdAccountDTO(
            externalAccountId: (string) ($a['id'] ?? ('act_'.($a['account_id'] ?? ''))),
            name: $a['name'] ?? null,
            currency: $a['currency'] ?? null,
            status: isset($a['account_status']) ? (string) $a['account_status'] : null,
            businessId: isset($a['business']['id']) ? (string) $a['business']['id'] : null,
            businessName: isset($a['business']['name']) ? (string) $a['business']['name'] : null,
            raw: $a,
        ), array_filter((array) $res->json('data', []), 'is_array')));
    }

    public function listEntities(string $accessToken, string $externalAccountId, string $level): array
    {
        $edge = match ($level) {
            'campaign' => 'campaigns',
            'adset' => 'adsets',
            'ad' => 'ads',
            default => throw UnsupportedOperation::for($this->code(), "listEntities({$level})"),
        };
        $fields = [
            'campaign' => 'id,name,status,effective_status,daily_budget,lifetime_budget,objective',
            'adset' => 'id,name,status,effective_status,daily_budget,lifetime_budget,campaign_id,optimization_goal,billing_event',
            'ad' => 'id,name,status,effective_status,adset_id',
        ][$level];
        $res = Http::timeout(30)->get($this->graphUrl($externalAccountId.'/'.$edge), [
            'fields' => $fields, 'access_token' => $accessToken, 'limit' => 500,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException("Facebook Ads listEntities({$level}) failed: ".$res->body());
        }

        return array_values(array_map(fn (array $e) => new AdEntityDTO(
            level: $level,
            externalId: (string) ($e['id'] ?? ''),
            parentExternalId: isset($e['campaign_id']) ? (string) $e['campaign_id'] : (isset($e['adset_id']) ? (string) $e['adset_id'] : null),
            name: $e['name'] ?? null,
            status: $e['status'] ?? null,
            effectiveStatus: $e['effective_status'] ?? null,
            dailyBudget: isset($e['daily_budget']) ? (int) $e['daily_budget'] : null,
            lifetimeBudget: isset($e['lifetime_budget']) ? (int) $e['lifetime_budget'] : null,
            objective: isset($e['objective']) ? (string) $e['objective'] : null,
            raw: $e,
        ), array_filter((array) $res->json('data', []), 'is_array')));
    }

    public function fetchInsights(string $accessToken, string $externalId, string $level, array $query = [], ?AdInsightThrottleDTO &$throttleOut = null): array
    {
        // The breakdown id (campaign_id/adset_id/ad_id) MUST be requested explicitly:
        // Graph v19+ dropped implicit fields, so without it the caller can't key rows
        // back to the right entity (all rows would collapse onto the account id).
        $idField = ['campaign' => 'campaign_id', 'adset' => 'adset_id', 'ad' => 'ad_id'][$level] ?? null;
        $fields = 'spend,impressions,clicks,reach,ctr,cpc,cpm,frequency,purchase_roas,actions';

        $params = [
            'fields' => $idField !== null ? $fields.','.$idField : $fields,
            'level' => $level === 'account' ? 'account' : $level,
            'date_preset' => (string) ($query['date_preset'] ?? 'today'),
            'access_token' => $accessToken,
        ];
        if (! empty($query['time_range'])) {
            $params['time_range'] = is_string($query['time_range']) ? $query['time_range'] : (string) json_encode($query['time_range']);
            unset($params['date_preset']);
        }

        $res = Http::timeout(40)->get($this->graphUrl($externalId.'/insights'), $params);

        // Throttle header → adaptive pacing (best-effort).
        $hdr = $res->header('x-fb-ads-insights-throttle');
        $t = $hdr ? (array) json_decode($hdr, true) : [];
        $throttleOut = new AdInsightThrottleDTO(
            appUtilPct: (float) ($t['app_id_util_pct'] ?? 0),
            accUtilPct: (float) ($t['acc_id_util_pct'] ?? 0),
            accessTier: (string) ($t['ads_api_access_tier'] ?? 'development'),
        );

        if (! $res->successful()) {
            throw new \RuntimeException("Facebook Ads fetchInsights({$level}) failed: ".$res->body());
        }

        return array_values(array_map(function (array $r) use ($level, $externalId) {
            $roas = isset($r['purchase_roas'][0]['value']) ? (float) $r['purchase_roas'][0]['value'] : null;
            $actions = $this->indexActions($r['actions'] ?? []);
            $conversations = (int) ($actions['onsite_conversion.messaging_conversation_started_7d'] ?? 0);
            $leads = (int) (($actions['lead'] ?? 0) + ($actions['leadgen.other'] ?? 0) + ($actions['onsite_conversion.lead_grouped'] ?? 0));

            return new AdInsightDTO(
                level: $level,
                externalId: $externalId,
                dateStart: (string) ($r['date_start'] ?? ''),
                dateStop: (string) ($r['date_stop'] ?? ''),
                spend: (int) round((float) ($r['spend'] ?? 0)),
                impressions: (int) ($r['impressions'] ?? 0),
                clicks: (int) ($r['clicks'] ?? 0),
                reach: (int) ($r['reach'] ?? 0),
                ctr: isset($r['ctr']) ? (float) $r['ctr'] : null,
                cpc: isset($r['cpc']) ? (int) round((float) $r['cpc']) : null,
                cpm: isset($r['cpm']) ? (int) round((float) $r['cpm']) : null,
                frequency: isset($r['frequency']) ? (float) $r['frequency'] : null,
                purchaseRoas: $roas,
                messagingConversations: $conversations,
                leads: $leads,
                raw: $r,
            );
        }, array_filter((array) $res->json('data', []), 'is_array')));
    }

    /**
     * Index a Graph `actions` array (`[{action_type, value}, …]`) by action_type → int value.
     *
     * @param  array<int,array<string,mixed>>  $actions
     * @return array<string,int>
     */
    private function indexActions(array $actions): array
    {
        $out = [];
        foreach ($actions as $a) {
            if (isset($a['action_type'])) {
                $out[(string) $a['action_type']] = (int) round((float) ($a['value'] ?? 0));
            }
        }

        return $out;
    }

    public function createCampaign(string $accessToken, string $externalAccountId, CampaignSpecDTO $spec): string
    {
        $objective = FacebookObjectiveMap::spec($spec->objective)['objective'];

        $res = Http::timeout(30)->asForm()->post($this->graphUrl($externalAccountId.'/campaigns'), [
            'name' => $spec->name,
            'objective' => $objective,
            'status' => $spec->status,
            'special_ad_categories' => json_encode($spec->specialAdCategories),
            'access_token' => $accessToken,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads createCampaign failed: '.$res->body());
        }

        return (string) $res->json('id');
    }

    public function createAdSet(string $accessToken, string $externalAccountId, AdSetSpecDTO $spec): string
    {
        $map = FacebookObjectiveMap::spec($spec->objective);

        $params = [
            'name' => $spec->name,
            'campaign_id' => $spec->campaignExternalId,
            'daily_budget' => FacebookMoney::toMinorUnits($spec->dailyBudgetMajor, $spec->currency),
            'billing_event' => $map['billing_event'],
            'optimization_goal' => $map['optimization_goal'],
            'targeting' => json_encode($spec->targeting),
            'status' => $spec->status,
            'start_time' => $spec->startTime ?? now()->toIso8601String(),
            'access_token' => $accessToken,
        ];
        if ($map['destination_type'] !== null) {
            $params['destination_type'] = $map['destination_type'];
        }
        if ($map['needs_promoted_object']) {
            if ($spec->pageId === null) {
                throw new \RuntimeException("Facebook Ads createAdSet: objective '{$spec->objective}' requires pageId.");
            }
            $params['promoted_object'] = json_encode(['page_id' => $spec->pageId]);
        }

        $res = Http::timeout(30)->asForm()->post($this->graphUrl($externalAccountId.'/adsets'), $params);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads createAdSet failed: '.$res->body());
        }

        return (string) $res->json('id');
    }

    public function createAd(string $accessToken, string $externalAccountId, AdSpecDTO $spec): string
    {
        // Existing page post keeps social proof (likes/comments/shares); else build a new creative.
        if ($spec->pagePostId !== null) {
            $creative = ['object_story_id' => $spec->pagePostId];
        } else {
            $linkData = array_filter([
                'image_hash' => $spec->imageHash,
                'video_id' => $spec->videoId,
                'message' => $spec->primaryText,
                'name' => $spec->headline,
                'link' => $spec->linkUrl,
                'call_to_action' => ['type' => $spec->cta],
            ], fn ($v) => $v !== null);
            $creative = ['object_story_spec' => ['page_id' => $spec->pageId, 'link_data' => $linkData]];
        }

        $res = Http::timeout(30)->asForm()->post($this->graphUrl($externalAccountId.'/ads'), [
            'name' => $spec->name,
            'adset_id' => $spec->adSetExternalId,
            'creative' => json_encode($creative),
            'status' => $spec->status,
            'access_token' => $accessToken,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads createAd failed: '.$res->body());
        }

        return (string) $res->json('id');
    }

    public function listPages(string $accessToken): array
    {
        $res = Http::timeout(30)->get($this->graphUrl('me/accounts'), [
            'fields' => 'id,name,access_token',
            'access_token' => $accessToken,
            'limit' => 200,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads listPages failed: '.$res->body());
        }

        return array_values(array_map(fn (array $p) => new PageRefDTO(
            id: (string) ($p['id'] ?? ''),
            name: (string) ($p['name'] ?? ''),
            accessToken: (string) ($p['access_token'] ?? ''),
            raw: $p,
        ), array_filter((array) $res->json('data', []), 'is_array')));
    }

    public function listPagePosts(string $pageAccessToken, string $pageId, int $limit = 25): array
    {
        $res = Http::timeout(30)->get($this->graphUrl($pageId.'/published_posts'), [
            'fields' => 'id,message,created_time,full_picture,attachments{media_type,media},'
                .'likes.summary(true).limit(0),comments.summary(true).limit(0),shares',
            'access_token' => $pageAccessToken,
            'limit' => $limit,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads listPagePosts failed: '.$res->body());
        }

        return array_values(array_map(function (array $p) {
            $attachment = (array) ($p['attachments']['data'][0] ?? []);
            $mediaType = (string) ($attachment['media_type'] ?? 'status');

            return new PagePostDTO(
                id: (string) ($p['id'] ?? ''),
                message: isset($p['message']) ? (string) $p['message'] : null,
                createdTime: (string) ($p['created_time'] ?? ''),
                mediaType: $mediaType,
                imageUrl: isset($p['full_picture']) ? (string) $p['full_picture'] : null,
                videoId: null,
                likes: (int) ($p['likes']['summary']['total_count'] ?? 0),
                comments: (int) ($p['comments']['summary']['total_count'] ?? 0),
                shares: (int) ($p['shares']['count'] ?? 0),
                raw: $p,
            );
        }, array_filter((array) $res->json('data', []), 'is_array')));
    }

    public function searchTargeting(string $accessToken, string $query, string $type = 'adinterest'): array
    {
        $res = Http::timeout(30)->get($this->graphUrl('search'), [
            'type' => $type,
            'q' => $query,
            'limit' => 50,
            'access_token' => $accessToken,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads searchTargeting failed: '.$res->body());
        }

        return array_values(array_map(fn (array $o) => new TargetingOptionDTO(
            id: (string) ($o['id'] ?? ''),
            name: (string) ($o['name'] ?? ''),
            type: 'interests',
            audienceSize: isset($o['audience_size_lower_bound']) ? (int) $o['audience_size_lower_bound'] : null,
            raw: $o,
        ), array_filter((array) $res->json('data', []), 'is_array')));
    }

    public function estimateAudience(string $accessToken, string $externalAccountId, array $targetingSpec, string $optimizationGoal): AudienceSizeDTO
    {
        $res = Http::timeout(30)->get($this->graphUrl($externalAccountId.'/delivery_estimate'), [
            'optimization_goal' => $optimizationGoal,
            'targeting_spec' => json_encode($targetingSpec),
            'access_token' => $accessToken,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads estimateAudience failed: '.$res->body());
        }
        $row = (array) ($res->json('data.0') ?? []);

        return new AudienceSizeDTO(
            lowerBound: isset($row['estimate_mau_lower_bound']) ? (int) $row['estimate_mau_lower_bound'] : null,
            upperBound: isset($row['estimate_mau_upper_bound']) ? (int) $row['estimate_mau_upper_bound'] : null,
            raw: $row,
        );
    }

    public function generatePreviews(string $accessToken, string $externalAccountId, array $creativeSpec, array $formats): array
    {
        return [];
    }
}
