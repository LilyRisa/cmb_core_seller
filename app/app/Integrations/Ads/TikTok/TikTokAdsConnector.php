<?php

namespace CMBcoreSeller\Integrations\Ads\TikTok;

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
use CMBcoreSeller\Integrations\Ads\Exceptions\UnsupportedOperation;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TikTok Marketing API (Ads) connector — ADR-0025, read-only (Phase 1). Nguồn:
 * tai_lieu_tiktok_ads/ (TikTok API for Business v1.3).
 *
 * Khác Facebook:
 *  - Token DÀI HẠN không hết hạn (đổi auth_code 1 lần qua /oauth2/access_token/).
 *  - Mọi API gọi bằng header `Access-Token`; envelope `{code,message,data,request_id}`
 *    với code 0 = OK.
 *  - "Ad group" của TikTok map vào level chuẩn `adset` để tái dùng schema/Jobs/FE.
 *  - Báo cáo (/report/integrated/get/) LUÔN advertiser-scoped: ở account level
 *    `externalId` = advertiser_id; mỗi row trả về có `externalId` = id entity tương
 *    ứng (campaign/adgroup/ad) — khớp fallback của AdsReportService. SyncAdInsights
 *    dùng nhánh capability `insights.account_report` để gộp call theo level.
 *
 * MONEY: report trả số tiền theo currency account, KHÔNG số thập phân với VND ⇒
 * (int) round() chính xác. Đa tiền tệ minor-unit là việc của phase sau.
 */
class TikTokAdsConnector implements AdsConnector, AdsWriteConnector
{
    /** @param array<string,mixed> $config config('integrations.ads_tiktok') */
    public function __construct(private array $config) {}

    public function code(): string
    {
        return 'tiktok';
    }

    public function displayName(): string
    {
        return 'TikTok Ads';
    }

    public function capabilities(): array
    {
        return [
            'insights.read' => true,
            'entities.list' => true,
            // Báo cáo advertiser-scoped: SyncAdInsights gộp 3 call/level thay vì N+1.
            'insights.account_report' => true,
            // Write tối thiểu: đổi trạng thái (campaign/adgroup) + ngân sách (adgroup) —
            // đủ cho giám sát tự-động (AdMonitor). Tạo quảng cáo chưa hỗ trợ.
            'actions.status' => true,
            'actions.budget' => true,
            'insights.async' => false,
            'actions.bid' => false,
            'ads.create' => false,
            'creative.upload' => false,
            'creatives.read' => false,
            'page.posts.read' => false,
            'preview.generate' => false,
            'targeting.search' => false,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    // --- OAuth ---------------------------------------------------------------

    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        return $this->authUrl().'?'.http_build_query([
            'app_id' => (string) ($this->config['app_id'] ?? ''),
            'state' => $state,
            'redirect_uri' => $opts['redirect_uri'] ?? $this->redirectUri(),
        ]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        // /oauth2/access_token/ chỉ nhận application/json. Token KHÔNG hết hạn.
        $res = Http::timeout(30)->asJson()->post($this->apiUrl('oauth2/access_token/'), [
            'app_id' => (string) ($this->config['app_id'] ?? ''),
            'secret' => (string) ($this->config['app_secret'] ?? ''),
            'auth_code' => $code,
        ]);
        $data = $this->unwrap($res, 'exchangeCodeForToken');

        return [
            'access_token' => (string) ($data['access_token'] ?? ''),
            'expires_at' => null, // long-term token, không hết hạn
            'raw' => $data,
        ];
    }

    // --- Read ----------------------------------------------------------------

    public function listAdAccounts(string $accessToken): array
    {
        // 1) Danh sách advertiser mà token truy cập được.
        $res = Http::timeout(30)->withHeaders(['Access-Token' => $accessToken])
            ->get($this->apiUrl('oauth2/advertiser/get/'), [
                'app_id' => (string) ($this->config['app_id'] ?? ''),
                'secret' => (string) ($this->config['app_secret'] ?? ''),
            ]);
        $data = $this->unwrap($res, 'listAdAccounts');

        $names = [];
        $ids = [];
        foreach ((array) ($data['list'] ?? []) as $a) {
            if (! is_array($a) || ! isset($a['advertiser_id'])) {
                continue;
            }
            $id = (string) $a['advertiser_id'];
            $ids[] = $id;
            $names[$id] = isset($a['advertiser_name']) ? (string) $a['advertiser_name'] : null;
        }
        if ($ids === []) {
            return [];
        }

        // 2) Chi tiết (currency/status/timezone/owner_bc_id) — batch ≤ 100 id.
        $details = [];
        foreach (array_chunk($ids, 100) as $chunk) {
            $info = Http::timeout(30)->withHeaders(['Access-Token' => $accessToken])
                ->get($this->apiUrl('advertiser/info/'), [
                    'advertiser_ids' => json_encode($chunk),
                    'fields' => json_encode(['advertiser_id', 'name', 'currency', 'status', 'timezone', 'owner_bc_id']),
                ]);
            $infoData = $this->unwrap($info, 'listAdAccounts.info');
            foreach ((array) ($infoData['list'] ?? []) as $row) {
                if (is_array($row) && isset($row['advertiser_id'])) {
                    $details[(string) $row['advertiser_id']] = $row;
                }
            }
        }

        $out = [];
        foreach ($ids as $id) {
            $d = $details[$id] ?? [];
            $out[] = new AdAccountDTO(
                externalAccountId: $id,
                name: isset($d['name']) ? (string) $d['name'] : $names[$id],
                currency: isset($d['currency']) ? (string) $d['currency'] : null,
                status: isset($d['status']) ? (string) $d['status'] : null,
                businessId: isset($d['owner_bc_id']) ? (string) $d['owner_bc_id'] : null,
                businessName: null,
                businessPictureUrl: null,
                accountStatus: null, // TikTok không dùng mã int kiểu FB
                disableReason: null,
                raw: $d + ['advertiser_id' => $id, 'advertiser_name' => $names[$id]],
            );
        }

        return $out;
    }

    public function fetchAccountStatus(string $accessToken, string $externalAccountId): array
    {
        // TikTok không có mã health dạng int như FB — trạng thái dạng chuỗi nằm ở
        // listAdAccounts/entity. Trả null để job health không làm bẩn dữ liệu.
        return ['account_status' => null, 'disable_reason' => null];
    }

    public function listEntities(string $accessToken, string $externalAccountId, string $level): array
    {
        [$path] = $this->levelEndpoint($level);

        $rows = $this->paged($accessToken, $path, ['advertiser_id' => $externalAccountId]);

        return array_map(function (array $e) use ($level) {
            $opStatus = isset($e['operation_status']) ? (string) $e['operation_status'] : null;
            $budgetMode = isset($e['budget_mode']) ? (string) $e['budget_mode'] : null;
            $budget = isset($e['budget']) ? (int) round((float) $e['budget']) : null;

            return new AdEntityDTO(
                level: $level, // adgroup đã được gọi với level 'adset'
                externalId: (string) ($this->entityId($e, $level) ?? ''),
                parentExternalId: $this->parentId($e, $level),
                name: $this->entityName($e, $level),
                status: $opStatus,
                effectiveStatus: isset($e['secondary_status']) ? (string) $e['secondary_status'] : $opStatus,
                dailyBudget: $budgetMode === 'BUDGET_MODE_DAY' ? $budget : null,
                lifetimeBudget: $budgetMode === 'BUDGET_MODE_TOTAL' ? $budget : null,
                objective: isset($e['objective_type']) ? (string) $e['objective_type'] : null,
                optimizationGoal: isset($e['optimization_goal']) ? (string) $e['optimization_goal'] : null,
                customEventType: null,
                raw: $e,
            );
        }, $rows);
    }

    public function fetchInsights(string $accessToken, string $externalId, string $level, array $query = [], ?AdInsightThrottleDTO &$throttleOut = null): array
    {
        // TikTok không có header throttle kiểu FB ⇒ mặc định "không hot".
        $throttleOut = new AdInsightThrottleDTO;

        [$start, $end] = $this->dateRange($query);
        $dataLevel = [
            'account' => 'AUCTION_ADVERTISER',
            'campaign' => 'AUCTION_CAMPAIGN',
            'adset' => 'AUCTION_ADGROUP',
            'ad' => 'AUCTION_AD',
        ][$level] ?? throw UnsupportedOperation::for($this->code(), "fetchInsights({$level})");
        $idDim = [
            'account' => 'advertiser_id',
            'campaign' => 'campaign_id',
            'adset' => 'adgroup_id',
            'ad' => 'ad_id',
        ][$level];

        $rows = $this->paged($accessToken, 'report/integrated/get/', [
            'advertiser_id' => $externalId, // ở account level = advertiser_id; AdsReportService cũng truyền account id
            'report_type' => 'BASIC',
            'data_level' => $dataLevel,
            'dimensions' => json_encode([$idDim]),
            'metrics' => json_encode([
                'spend', 'impressions', 'clicks', 'ctr', 'cpc', 'cpm', 'reach', 'frequency',
                'conversion', 'cost_per_conversion', 'conversion_rate',
            ]),
            'start_date' => $start,
            'end_date' => $end,
        ]);

        return array_map(function (array $r) use ($level, $idDim, $externalId, $start, $end) {
            $dim = (array) ($r['dimensions'] ?? []);
            $m = (array) ($r['metrics'] ?? []);
            // externalId mỗi row = id entity (từ dimensions); account level = advertiser_id.
            $rowId = $level === 'account' ? $externalId : (string) ($dim[$idDim] ?? $externalId);

            return new AdInsightDTO(
                level: $level,
                externalId: $rowId,
                dateStart: $start,
                dateStop: $end,
                spend: (int) round($this->num($m['spend'] ?? 0)),
                impressions: (int) round($this->num($m['impressions'] ?? 0)),
                clicks: (int) round($this->num($m['clicks'] ?? 0)),
                reach: (int) round($this->num($m['reach'] ?? 0)),
                ctr: isset($m['ctr']) ? $this->num($m['ctr']) : null,
                cpc: isset($m['cpc']) ? (int) round($this->num($m['cpc'])) : null,
                cpm: isset($m['cpm']) ? (int) round($this->num($m['cpm'])) : null,
                frequency: isset($m['frequency']) ? $this->num($m['frequency']) : null,
                purchaseRoas: null,
                messagingConversations: 0,
                leads: 0,
                purchases: (int) round($this->num($m['conversion'] ?? 0)),
                results: (int) round($this->num($m['conversion'] ?? 0)),
                actions: [],
                // raw mang cả id field (campaign_id/adgroup_id/ad_id) cho AdsReportService.
                raw: $m + $dim + [$idDim => $dim[$idDim] ?? null],
            );
        }, $rows);
    }

    public function fetchAdCreatives(string $accessToken, string $externalAccountId): array
    {
        throw UnsupportedOperation::for($this->code(), 'fetchAdCreatives');
    }

    // --- Write (AdsWriteConnector) — tối thiểu cho giám sát ------------------

    /**
     * Đổi trạng thái (ENABLE/DISABLE) và/hoặc ngân sách ngày. TikTok cần advertiser_id
     * (không có trong chữ ký interface) ⇒ caller truyền qua `$fields['_advertiser_id']`.
     *
     * @param  array<string,mixed>  $fields  name?/status?(ACTIVE|PAUSED)/daily_budget_major?/_advertiser_id
     */
    public function updateEntity(string $accessToken, string $level, string $externalId, array $fields, string $currency = 'VND'): void
    {
        $advertiserId = (string) ($fields['_advertiser_id'] ?? '');
        if ($advertiserId === '') {
            throw new \RuntimeException('TikTok updateEntity thiếu _advertiser_id (advertiser id) trong $fields.');
        }

        // Trạng thái: ACTIVE→ENABLE, PAUSED→DISABLE. ad-level không dùng cho giám sát.
        if (isset($fields['status'])) {
            $op = ((string) $fields['status'] === 'ACTIVE') ? 'ENABLE' : 'DISABLE';
            if ($level === 'campaign') {
                $this->writePost($accessToken, 'campaign/status/update/', ['advertiser_id' => $advertiserId, 'campaign_ids' => [$externalId], 'operation_status' => $op]);
            } elseif ($level === 'adset') {
                $this->writePost($accessToken, 'adgroup/status/update/', ['advertiser_id' => $advertiserId, 'adgroup_ids' => [$externalId], 'operation_status' => $op]);
            } else {
                throw UnsupportedOperation::for($this->code(), "updateEntity status({$level})");
            }
        }

        // Ngân sách ngày: chỉ adgroup. Campaign CBO cần full /campaign/update/ ⇒ bỏ qua (log).
        if (isset($fields['daily_budget_major'])) {
            $budget = (int) $fields['daily_budget_major'];
            if ($level === 'adset') {
                $this->writePost($accessToken, 'adgroup/budget/update/', [
                    'advertiser_id' => $advertiserId,
                    'budgets' => [['adgroup_id' => $externalId, 'budget' => $budget, 'budget_mode' => 'BUDGET_MODE_DAY']],
                ]);
            } else {
                Log::info('tiktok.updateEntity.campaign_budget_unsupported', ['campaign' => $externalId]);
            }
        }
        // name: TikTok cần /campaign|adgroup/update/ (full) — chưa hỗ trợ ở giám sát; bỏ qua êm.
    }

    public function createCampaign(string $accessToken, string $externalAccountId, CampaignSpecDTO $spec): string
    {
        throw UnsupportedOperation::for($this->code(), 'createCampaign');
    }

    public function createAdSet(string $accessToken, string $externalAccountId, AdSetSpecDTO $spec): string
    {
        throw UnsupportedOperation::for($this->code(), 'createAdSet');
    }

    public function createAd(string $accessToken, string $externalAccountId, AdSpecDTO $spec): string
    {
        throw UnsupportedOperation::for($this->code(), 'createAd');
    }

    public function listPages(string $accessToken): array
    {
        throw UnsupportedOperation::for($this->code(), 'listPages');
    }

    public function listPixels(string $accessToken, string $externalAccountId): array
    {
        throw UnsupportedOperation::for($this->code(), 'listPixels');
    }

    public function sharePixel(string $accessToken, string $pixelId, string $businessId, string $targetAccountId): void
    {
        throw UnsupportedOperation::for($this->code(), 'sharePixel');
    }

    public function listPagePosts(string $pageAccessToken, string $pageId, int $limit = 25): array
    {
        throw UnsupportedOperation::for($this->code(), 'listPagePosts');
    }

    public function fetchPostEngagement(string $accessToken, array $postIds): array
    {
        throw UnsupportedOperation::for($this->code(), 'fetchPostEngagement');
    }

    public function fetchPostLinks(string $accessToken, array $postIds): array
    {
        throw UnsupportedOperation::for($this->code(), 'fetchPostLinks');
    }

    public function searchTargeting(string $accessToken, string $query, string $type = 'adinterest'): array
    {
        throw UnsupportedOperation::for($this->code(), 'searchTargeting');
    }

    public function estimateAudience(string $accessToken, string $externalAccountId, array $targetingSpec, string $optimizationGoal): AudienceSizeDTO
    {
        throw UnsupportedOperation::for($this->code(), 'estimateAudience');
    }

    public function generatePreviews(string $accessToken, string $externalAccountId, array $creativeSpec, array $formats): array
    {
        throw UnsupportedOperation::for($this->code(), 'generatePreviews');
    }

    // --- Helpers -------------------------------------------------------------

    /**
     * POST JSON tới endpoint write (header Access-Token); ném khi transport lỗi hoặc code != 0.
     *
     * @param  array<string,mixed>  $body
     */
    private function writePost(string $accessToken, string $path, array $body): void
    {
        $res = Http::timeout(30)->withHeaders(['Access-Token' => $accessToken])->asJson()->post($this->apiUrl($path), $body);
        $this->unwrap($res, $path);
    }

    /** @return array{0:string} [endpoint path] */
    private function levelEndpoint(string $level): array
    {
        return match ($level) {
            'campaign' => ['campaign/get/'],
            'adset' => ['adgroup/get/'],
            'ad' => ['ad/get/'],
            default => throw UnsupportedOperation::for($this->code(), "listEntities({$level})"),
        };
    }

    /** @param array<string,mixed> $e */
    private function entityId(array $e, string $level): ?string
    {
        $key = ['campaign' => 'campaign_id', 'adset' => 'adgroup_id', 'ad' => 'ad_id'][$level];

        return isset($e[$key]) ? (string) $e[$key] : null;
    }

    /** @param array<string,mixed> $e */
    private function entityName(array $e, string $level): ?string
    {
        $key = ['campaign' => 'campaign_name', 'adset' => 'adgroup_name', 'ad' => 'ad_name'][$level];

        return isset($e[$key]) ? (string) $e[$key] : null;
    }

    /** @param array<string,mixed> $e */
    private function parentId(array $e, string $level): ?string
    {
        $key = ['campaign' => null, 'adset' => 'campaign_id', 'ad' => 'adgroup_id'][$level];

        return $key !== null && isset($e[$key]) ? (string) $e[$key] : null;
    }

    /**
     * Page through a list endpoint (page/page_size + page_info) and return all rows.
     *
     * @param  array<string,mixed>  $params
     * @return list<array<string,mixed>>
     */
    private function paged(string $accessToken, string $path, array $params): array
    {
        $out = [];
        $page = 1;
        do {
            $res = Http::timeout(40)->withHeaders(['Access-Token' => $accessToken])
                ->get($this->apiUrl($path), $params + ['page' => $page, 'page_size' => 1000]);
            $data = $this->unwrap($res, $path);
            foreach ((array) ($data['list'] ?? []) as $row) {
                if (is_array($row)) {
                    $out[] = $row;
                }
            }
            $pageInfo = (array) ($data['page_info'] ?? []);
            $totalPage = (int) ($pageInfo['total_page'] ?? 1);
            $page++;
        } while ($page <= $totalPage && $page <= 200); // trần an toàn

        return $out;
    }

    /**
     * Unwrap TikTok envelope; throw on transport error or `code != 0`.
     *
     * @return array<string,mixed>
     */
    private function unwrap(Response $res, string $op): array
    {
        if (! $res->successful()) {
            throw new \RuntimeException("TikTok Ads {$op} HTTP failed: ".$res->body());
        }
        $code = (int) $res->json('code', -1);
        if ($code !== 0) {
            throw new \RuntimeException("TikTok Ads {$op} failed (code {$code}): ".(string) $res->json('message', ''));
        }

        return (array) $res->json('data', []);
    }

    /**
     * @param  array<string,mixed>  $query
     * @return array{0:string,1:string} [start_date, end_date] YYYY-MM-DD
     */
    private function dateRange(array $query): array
    {
        if (! empty($query['time_range']) && is_array($query['time_range'])) {
            $since = (string) ($query['time_range']['since'] ?? '');
            $until = (string) ($query['time_range']['until'] ?? '');
            if ($since !== '' && $until !== '') {
                return [$since, $until];
            }
        }
        $preset = (string) ($query['date_preset'] ?? 'today');
        $today = CarbonImmutable::now();

        return match ($preset) {
            'yesterday' => [$today->subDay()->toDateString(), $today->subDay()->toDateString()],
            'last_7d', 'last_7_days' => [$today->subDays(6)->toDateString(), $today->toDateString()],
            'last_30d', 'last_30_days' => [$today->subDays(29)->toDateString(), $today->toDateString()],
            default => [$today->toDateString(), $today->toDateString()], // today
        };
    }

    private function num(mixed $v): float
    {
        return is_numeric($v) ? (float) $v : 0.0;
    }

    private function redirectUri(): string
    {
        $configured = (string) ($this->config['redirect_uri'] ?? '');
        if ($configured !== '') {
            return $configured;
        }

        return rtrim((string) config('app.url'), '/').'/oauth/tiktok_marketing/redirect';
    }

    private function apiUrl(string $path): string
    {
        $base = (string) ($this->config['base_url'] ?? 'https://business-api.tiktok.com/open_api/v1.3');

        return rtrim($base, '/').'/'.ltrim($path, '/');
    }

    private function authUrl(): string
    {
        return (string) ($this->config['auth_url'] ?? 'https://business-api.tiktok.com/portal/auth');
    }
}
