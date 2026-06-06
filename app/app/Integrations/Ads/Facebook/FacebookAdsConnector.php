<?php

namespace CMBcoreSeller\Integrations\Ads\Facebook;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsConnector;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsWriteConnector;
use CMBcoreSeller\Integrations\Ads\DTO\AdAccountDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdCreativeDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdEntityDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightThrottleDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdPixelDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdPreviewDTO;
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
            'actions.budget' => true,
            'actions.status' => true,
            'actions.bid' => false,    // Phase 3
            'ads.create' => true,
            'creative.upload' => true,
            'creatives.read' => true,
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
            'fields' => 'account_id,name,currency,account_status,disable_reason,business{id,name,profile_picture_uri}',
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
            businessPictureUrl: isset($a['business']['profile_picture_uri']) ? (string) $a['business']['profile_picture_uri'] : null,
            accountStatus: isset($a['account_status']) ? (int) $a['account_status'] : null,
            disableReason: isset($a['disable_reason']) ? (int) $a['disable_reason'] : null,
            raw: $a,
        ), array_filter((array) $res->json('data', []), 'is_array')));
    }

    public function fetchAccountStatus(string $accessToken, string $externalAccountId): array
    {
        $res = Http::timeout(20)->get($this->graphUrl($externalAccountId), [
            'fields' => 'account_status,disable_reason',
            'access_token' => $accessToken,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads fetchAccountStatus failed: '.$res->body());
        }

        return [
            'account_status' => $res->json('account_status') !== null ? (int) $res->json('account_status') : null,
            'disable_reason' => $res->json('disable_reason') !== null ? (int) $res->json('disable_reason') : null,
        ];
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
            // promoted_object.custom_event_type cho biết "Kết quả" của chiến dịch chuyển đổi là sự kiện nào
            // (COMPLETE_REGISTRATION / PURCHASE / …) — Ads Manager đếm đúng sự kiện này.
            'adset' => 'id,name,status,effective_status,daily_budget,lifetime_budget,campaign_id,optimization_goal,billing_event,promoted_object{custom_event_type}',
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
            optimizationGoal: isset($e['optimization_goal']) ? (string) $e['optimization_goal'] : null,
            customEventType: isset($e['promoted_object']['custom_event_type']) ? (string) $e['promoted_object']['custom_event_type'] : null,
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
            // Messaging conversations come under different action types depending on the
            // objective/optimisation — FacebookResultMap takes the largest variant.
            $conversations = FacebookResultMap::count($actions, 'messaging');
            $leads = FacebookResultMap::count($actions, 'lead');
            // Purchases — the unified omni count, else the largest purchase variant.
            $purchases = FacebookResultMap::count($actions, 'purchase');
            // Generic "Kết quả" fallback = the deepest conversion the campaign actually drove.
            // The AUTHORITATIVE per-entity result (keyed to the ad set's optimisation event /
            // custom_event_type) is computed in AdsReportService via FacebookResultMap — the
            // connector lacks per-row objective/optimisation context here.
            $results = FacebookResultMap::genericResultTyped($actions)[1];

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
                purchases: $purchases,
                results: $results,
                actions: $actions,
                raw: $r,
            );
        }, array_filter((array) $res->json('data', []), 'is_array')));
    }

    public function fetchAdCreatives(string $accessToken, string $externalAccountId): array
    {
        $res = Http::timeout(30)->get($this->graphUrl($externalAccountId.'/ads'), [
            'fields' => 'id,name,effective_status,creative{body,title,effective_object_story_id,'
                .'object_story_spec{link_data{message,name,link,call_to_action{type,value}},video_data{message,title,call_to_action{type,value}}},'
                .'asset_feed_spec{link_urls{website_url},bodies{text},titles{text},call_to_action_types}}',
            'access_token' => $accessToken,
            'limit' => 200,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads fetchAdCreatives failed: '.$res->body());
        }

        return array_values(array_map(function (array $a) {
            $creative = (array) ($a['creative'] ?? []);
            $spec = (array) ($creative['object_story_spec'] ?? []);
            $linkData = (array) ($spec['link_data'] ?? []);
            $videoData = (array) ($spec['video_data'] ?? []);
            $assetFeed = (array) ($creative['asset_feed_spec'] ?? []);

            // Landing/destination URL across the common creative shapes: link ads
            // (link_data), video ads (video_data CTA), and Advantage+/dynamic creative
            // (asset_feed_spec.link_urls). Existing-post ads (effective_object_story_id
            // only) carry no link in the spec, so linkUrl stays null for those.
            $linkUrl = $linkData['link']
                ?? $linkData['call_to_action']['value']['link']
                ?? $videoData['call_to_action']['value']['link']
                ?? $assetFeed['link_urls'][0]['website_url']
                ?? null;

            return new AdCreativeDTO(
                adId: (string) ($a['id'] ?? ''),
                adName: isset($a['name']) ? (string) $a['name'] : null,
                effectiveStatus: isset($a['effective_status']) ? (string) $a['effective_status'] : null,
                primaryText: $this->firstString([
                    $linkData['message'] ?? null, $videoData['message'] ?? null,
                    $assetFeed['bodies'][0]['text'] ?? null, $creative['body'] ?? null,
                ]),
                headline: $this->firstString([
                    $linkData['name'] ?? null, $videoData['title'] ?? null,
                    $assetFeed['titles'][0]['text'] ?? null, $creative['title'] ?? null,
                ]),
                cta: $this->firstString([
                    $linkData['call_to_action']['type'] ?? null,
                    $videoData['call_to_action']['type'] ?? null,
                    $assetFeed['call_to_action_types'][0] ?? null,
                ]),
                pagePostId: isset($creative['effective_object_story_id']) ? (string) $creative['effective_object_story_id'] : null,
                linkUrl: $linkUrl !== null ? (string) $linkUrl : null,
                raw: $a,
            );
        }, array_filter((array) $res->json('data', []), 'is_array')));
    }

    /**
     * First non-empty scalar in the list, cast to string (or null if none).
     *
     * @param  array<int,mixed>  $values
     */
    private function firstString(array $values): ?string
    {
        foreach ($values as $v) {
            if ($v !== null && $v !== '' && (is_string($v) || is_numeric($v))) {
                return (string) $v;
            }
        }

        return null;
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

        $params = [
            'name' => $spec->name,
            'objective' => $objective,
            'status' => $spec->status,
            'special_ad_categories' => json_encode($spec->specialAdCategories),
            'access_token' => $accessToken,
        ];
        if ($spec->dailyBudgetMajor !== null && $spec->dailyBudgetMajor > 0) {
            $params['daily_budget'] = FacebookMoney::toMinorUnits($spec->dailyBudgetMajor, (string) ($spec->currency ?? 'VND'));
            $params['bid_strategy'] = $spec->bidStrategy;
        } else {
            // No campaign budget (CBO off) ⇒ ad sets carry their own budget. Graph now
            // REQUIRES this flag be set explicitly; false = ad sets don't share budget.
            $params['is_adset_budget_sharing_enabled'] = 'false';
        }

        $res = Http::timeout(30)->asForm()->post($this->graphUrl($externalAccountId.'/campaigns'), $params);
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
            'billing_event' => $map['billing_event'],
            'optimization_goal' => $map['optimization_goal'],
            'targeting' => json_encode($this->mergePlacements($spec->targeting, $spec->placementConfig)),
            'status' => $spec->status,
            'start_time' => $spec->startTime ?? now()->toIso8601String(),
            'access_token' => $accessToken,
        ];
        if ($spec->endTime !== null) {
            $params['end_time'] = $spec->endTime;
        }
        if ($spec->dailyBudgetMajor > 0) {
            $params['daily_budget'] = FacebookMoney::toMinorUnits($spec->dailyBudgetMajor, $spec->currency);
            // An ad-set budget (no CBO) must carry its own bid strategy — the campaign
            // no longer defines one, and Graph rejects the ad set without it.
            $params['bid_strategy'] = 'LOWEST_COST_WITHOUT_CAP';
        }
        if ($map['destination_type'] !== null) {
            $params['destination_type'] = $map['destination_type'];
        }
        if ($map['needs_promoted_object']) {
            $params['promoted_object'] = json_encode($this->buildPromotedObject($map['promoted_object'], $spec));
        }

        $res = Http::timeout(30)->asForm()->post($this->graphUrl($externalAccountId.'/adsets'), $params);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads createAdSet failed: '.$res->body());
        }

        return (string) $res->json('id');
    }

    /**
     * @param  array<string,mixed>  $targeting
     * @param  array<string,mixed>|null  $pc
     * @return array<string,mixed>
     */
    private function mergePlacements(array $targeting, ?array $pc): array
    {
        if ($pc === null || ! empty($pc['automatic'])) {
            return $targeting;
        }
        foreach (['device_platforms', 'publisher_platforms'] as $k) {
            if (! empty($pc[$k]) && is_array($pc[$k])) {
                $targeting[$k] = array_values($pc[$k]);
            }
        }
        foreach (['facebook', 'instagram', 'messenger', 'audience_network'] as $plat) {
            $pos = $pc['positions'][$plat] ?? [];
            if (! empty($pos) && is_array($pos)) {
                $targeting["{$plat}_positions"] = array_values($pos);
            }
        }

        return $targeting;
    }

    /**
     * Build the promoted_object for an ad set based on the objective's kind.
     * 'pixel' ⇒ conversions ({ pixel_id, custom_event_type }); else page ({ page_id }).
     *
     * @return array<string,string>
     */
    private function buildPromotedObject(?string $kind, AdSetSpecDTO $spec): array
    {
        if ($kind === 'pixel') {
            if ($spec->pixelId === null) {
                throw new \RuntimeException("Facebook Ads createAdSet: objective '{$spec->objective}' requires pixelId.");
            }

            return ['pixel_id' => $spec->pixelId, 'custom_event_type' => $spec->conversionEvent ?? 'PURCHASE'];
        }

        if ($spec->pageId === null) {
            throw new \RuntimeException("Facebook Ads createAdSet: objective '{$spec->objective}' requires pageId.");
        }

        return ['page_id' => $spec->pageId];
    }

    public function listPixels(string $accessToken, string $externalAccountId): array
    {
        $res = Http::timeout(30)->get($this->graphUrl($externalAccountId.'/adspixels'), [
            'fields' => 'id,name,last_fired_time,is_unavailable',
            'access_token' => $accessToken,
            'limit' => 100,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads listPixels failed: '.$res->body());
        }

        return array_values(array_map(fn (array $p) => new AdPixelDTO(
            id: (string) ($p['id'] ?? ''),
            name: isset($p['name']) ? (string) $p['name'] : (string) ($p['id'] ?? ''),
            lastFiredTime: isset($p['last_fired_time']) ? (string) $p['last_fired_time'] : null,
            isUnavailable: isset($p['is_unavailable']) ? (bool) $p['is_unavailable'] : null,
        ), array_filter((array) $res->json('data', []), 'is_array')));
    }

    /**
     * Share a pixel with another ad account (Graph shared_accounts edge). The
     * pixel's owning business + the target account's numeric id are required.
     */
    public function sharePixel(string $accessToken, string $pixelId, string $businessId, string $targetAccountId): void
    {
        // Graph wants the bare numeric account id (strip the act_ prefix if present).
        $accountId = str_starts_with($targetAccountId, 'act_') ? substr($targetAccountId, 4) : $targetAccountId;

        $res = Http::timeout(30)->asForm()->post($this->graphUrl($pixelId.'/shared_accounts'), [
            'business' => $businessId,
            'account_id' => $accountId,
            'access_token' => $accessToken,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads sharePixel failed: '.$res->body());
        }
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

        // Advantage+ creative (standard enhancements): Meta auto-optimises the creative.
        if ($spec->standardEnhancements) {
            $creative['degrees_of_freedom_spec'] = [
                'creative_features_spec' => ['standard_enhancements' => ['enroll_status' => 'OPT_IN']],
            ];
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

    public function updateEntity(string $accessToken, string $level, string $externalId, array $fields, string $currency = 'VND'): void
    {
        $params = ['access_token' => $accessToken];
        if (isset($fields['name']) && (string) $fields['name'] !== '') {
            $params['name'] = (string) $fields['name'];
        }
        if (isset($fields['daily_budget_major'])) {
            $params['daily_budget'] = FacebookMoney::toMinorUnits((int) $fields['daily_budget_major'], $currency);
        }
        if (isset($fields['status'])) {
            $params['status'] = (string) $fields['status'];
        }
        if (count($params) === 1) {
            return; // nothing but the token ⇒ no-op
        }

        $res = Http::timeout(30)->asForm()->post($this->graphUrl($externalId), $params);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads updateEntity failed: '.$res->body());
        }
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
            'fields' => 'id,message,created_time,full_picture,'
                .'attachments{media_type,media,target,unshimmed_url,type,title},'
                .'call_to_action{type,value},'
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
            $cta = (array) ($p['call_to_action'] ?? []);

            $linkUrl = $cta['value']['link']
                ?? $attachment['target']['url']
                ?? $attachment['unshimmed_url']
                ?? null;

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
                linkUrl: $linkUrl !== null ? (string) $linkUrl : null,
                ctaType: isset($cta['type']) ? (string) $cta['type'] : null,
                raw: $p,
            );
        }, array_filter((array) $res->json('data', []), 'is_array')));
    }

    public function fetchPostEngagement(string $accessToken, array $postIds): array
    {
        $ids = array_values(array_filter(array_map('strval', $postIds), fn ($s) => $s !== ''));
        if ($ids === []) {
            return [];
        }

        $res = Http::timeout(30)->get($this->graphUrl(''), [
            'ids' => implode(',', $ids),
            'fields' => 'id,message,likes.summary(true).limit(0),comments.summary(true).limit(0),shares',
            'access_token' => $accessToken,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads fetchPostEngagement failed: '.$res->body());
        }

        $out = [];
        foreach ((array) $res->json() as $id => $p) {
            if (! is_string($id) || ! is_array($p)) {
                continue;
            }
            $out[$id] = [
                'likes' => (int) ($p['likes']['summary']['total_count'] ?? 0),
                'comments' => (int) ($p['comments']['summary']['total_count'] ?? 0),
                'shares' => (int) ($p['shares']['count'] ?? 0),
                'message' => isset($p['message']) ? (string) $p['message'] : null,
            ];
        }

        return $out;
    }

    /**
     * Resolve each existing-post ad's destination URL from the page-post call-to-action.
     * The ad creative of a boosted/existing-post ad exposes no link (object_story_spec is
     * empty), so the URL must be read from the post's `call_to_action.value.link` using a
     * Page access token (needs pages_show_list + pages_read_engagement). Batched per page.
     *
     * @param  list<string>  $postIds  "<page_id>_<post_id>"
     * @return array<string,string> postId => destination URL
     */
    public function fetchPostLinks(string $accessToken, array $postIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $postIds), fn ($s) => $s !== '')));
        if ($ids === []) {
            return [];
        }

        // page_id => page access token
        $pageTokens = [];
        foreach ($this->listPages($accessToken) as $page) {
            if ($page->accessToken !== '') {
                $pageTokens[$page->id] = $page->accessToken;
            }
        }

        // Group posts by their owning page so each page token batches its own posts.
        $byPage = [];
        foreach ($ids as $postId) {
            $pageId = explode('_', $postId)[0];
            if (isset($pageTokens[$pageId])) {
                $byPage[$pageId][] = $postId;
            }
        }

        $out = [];
        foreach ($byPage as $pageId => $posts) {
            $res = Http::timeout(20)->get($this->graphUrl(''), [
                'ids' => implode(',', $posts),
                'fields' => 'call_to_action{type,value}',
                'access_token' => $pageTokens[$pageId],
            ]);
            if (! $res->successful()) {
                continue; // best-effort — skip pages we cannot read
            }
            foreach ((array) $res->json() as $postId => $p) {
                if (! is_string($postId) || ! is_array($p)) {
                    continue;
                }
                $link = $p['call_to_action']['value']['link'] ?? null;
                if (is_string($link) && $link !== '') {
                    $out[$postId] = $link;
                }
            }
        }

        return $out;
    }

    public function searchTargeting(string $accessToken, string $query, string $type = 'adinterest'): array
    {
        $isGeo = $type === 'adgeolocation';
        $params = [
            'type' => $type,
            'q' => $query,
            'limit' => 50,
            'access_token' => $accessToken,
        ];
        if ($isGeo) {
            $params['location_types'] = json_encode(['country', 'region', 'city']);
        }

        $res = Http::timeout(30)->get($this->graphUrl('search'), $params);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads searchTargeting failed: '.$res->body());
        }

        if ($isGeo) {
            return array_values(array_map(fn (array $o) => new TargetingOptionDTO(
                id: (string) ($o['key'] ?? ''),
                name: $this->geoLabel($o),
                type: (string) ($o['type'] ?? 'geo'),
                audienceSize: null,
                raw: $o,
            ), array_filter((array) $res->json('data', []), 'is_array')));
        }

        // Unified detailed-targeting search (interests + behaviors + demographics):
        // each result carries its OWN flexible_spec key in the result's `type`
        // field, so we surface that per-result (not the search type label).
        if ($type === 'adTargetingCategory') {
            return array_values(array_map(fn (array $o) => new TargetingOptionDTO(
                id: (string) ($o['id'] ?? ''),
                name: (string) ($o['name'] ?? ''),
                type: (string) ($o['type'] ?? 'interests'),
                audienceSize: isset($o['audience_size_lower_bound']) ? (int) $o['audience_size_lower_bound'] : null,
                raw: $o,
            ), array_filter((array) $res->json('data', []), 'is_array')));
        }

        // Label derived from the Graph search type so a 'adbehavior' search isn't
        // mislabelled as 'interests' (the DTO type must reflect what was searched).
        $typeLabel = match ($type) {
            'adinterest' => 'interests',
            'adbehavior' => 'behaviors',
            default => $type,
        };

        return array_values(array_map(fn (array $o) => new TargetingOptionDTO(
            id: (string) ($o['id'] ?? ''),
            name: (string) ($o['name'] ?? ''),
            type: $typeLabel,
            audienceSize: isset($o['audience_size_lower_bound']) ? (int) $o['audience_size_lower_bound'] : null,
            raw: $o,
        ), array_filter((array) $res->json('data', []), 'is_array')));
    }

    /** @param array<string,mixed> $o */
    private function geoLabel(array $o): string
    {
        $name = (string) ($o['name'] ?? '');
        $parts = array_values(array_filter([
            is_string($o['region'] ?? null) ? $o['region'] : null,
            is_string($o['country_name'] ?? null) ? $o['country_name'] : (is_string($o['country_code'] ?? null) ? $o['country_code'] : null),
        ], fn ($p) => is_string($p) && $p !== '' && $p !== $name));

        return $parts === [] ? $name : $name.' · '.implode(', ', $parts);
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
        // An existing page post previews via object_story_id; a new creative via
        // object_story_spec.
        $creative = isset($creativeSpec['object_story_id'])
            ? ['object_story_id' => (string) $creativeSpec['object_story_id']]
            : ['object_story_spec' => $creativeSpec];

        $out = [];
        foreach ($formats as $format) {
            $res = Http::timeout(30)->get($this->graphUrl($externalAccountId.'/generatepreviews'), [
                'creative' => json_encode($creative),
                'ad_format' => $format,
                'access_token' => $accessToken,
            ]);
            // Best-effort: a format Graph can't render is skipped, not fatal.
            if (! $res->successful()) {
                continue;
            }
            $body = $res->json('data.0.body');
            if (is_string($body) && $body !== '') {
                $out[] = new AdPreviewDTO(format: (string) $format, body: $body, raw: (array) ($res->json('data.0') ?? []));
            }
        }

        return $out;
    }
}
