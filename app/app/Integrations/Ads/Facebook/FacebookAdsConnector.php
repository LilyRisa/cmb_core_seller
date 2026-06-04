<?php

namespace CMBcoreSeller\Integrations\Ads\Facebook;

use CMBcoreSeller\Integrations\Ads\Contracts\AdsConnector;
use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Ads\DTO\AdAccountDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdEntityDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightThrottleDTO;
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
class FacebookAdsConnector implements AdsConnector
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
            'fields' => 'account_id,name,currency,account_status',
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
        $fields = match ($level) {
            'campaign' => 'id,name,status,effective_status,daily_budget,lifetime_budget',
            'adset' => 'id,name,status,effective_status,daily_budget,lifetime_budget,campaign_id',
            'ad' => 'id,name,status,effective_status,adset_id',
            default => 'id,name,status',
        };
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
            raw: $e,
        ), array_filter((array) $res->json('data', []), 'is_array')));
    }

    public function fetchInsights(string $accessToken, string $externalId, string $level, array $query = [], ?AdInsightThrottleDTO &$throttleOut = null): array
    {
        $params = [
            'fields' => 'spend,impressions,clicks,reach,ctr,cpc,cpm,frequency,purchase_roas',
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
                raw: $r,
            );
        }, array_filter((array) $res->json('data', []), 'is_array')));
    }
}
