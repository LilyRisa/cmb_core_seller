<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Services;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\PublisherRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use Illuminate\Support\Facades\Cache;

/**
 * Cached read-through proxy over a provider's product-publishing taxonomy
 * (categories / attributes / brands). Taxonomy changes slowly, so results are
 * cached for 12h. Returns plain arrays for the controller to JSON-encode.
 */
final class ListingTaxonomyService
{
    public function __construct(private PublisherRegistry $publishers) {}

    /**
     * Direct children of $parentId (root level when null/empty). Providers return
     * the WHOLE flat tree in one call, so we cache the full tree once and filter
     * here — không đổ cả cây ra cấp gốc nữa.
     *
     * @return array<int,array<string,mixed>>
     */
    public function categories(int $channelAccountId, string $provider, ?string $parentId = null): array
    {
        $parent = self::normalizeParent($parentId);

        return array_values(array_filter(
            $this->fullTree($channelAccountId, $provider),
            fn ($c) => ($c['parent_id'] ?? null) === $parent,
        ));
    }

    /**
     * Advanced category search: leaf categories whose FULL breadcrumb path matches
     * the query — every whitespace-separated token must appear somewhere in the path
     * ("A › B › C"), so the seller can search by ANY level (parent or leaf) and pick
     * the leaf directly. Each hit carries its full path for display.
     *
     * @return array<int,array<string,mixed>>
     */
    public function searchCategories(int $channelAccountId, string $provider, string $query, int $limit = 50): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }

        $tokens = array_values(array_filter(
            preg_split('/\s+/', mb_strtolower($q)) ?: [],
            fn ($t) => $t !== '',
        ));

        $all = $this->fullTree($channelAccountId, $provider);
        $byId = [];
        foreach ($all as $c) {
            $byId[$c['id']] = $c;
        }

        $out = [];
        foreach ($all as $c) {
            if (! ($c['is_leaf'] ?? false)) {
                continue;
            }
            $path = self::pathLabel((string) $c['id'], $byId);
            $haystack = mb_strtolower($path);
            foreach ($tokens as $t) {
                if (! str_contains($haystack, $t)) {
                    continue 2;
                }
            }
            $out[] = ['id' => $c['id'], 'name' => $c['name'], 'is_leaf' => true, 'path' => $path];
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /**
     * Breadcrumb path for one category id (để hiển thị ngành hàng đang chọn).
     *
     * @return array<string,mixed>
     */
    public function categoryPath(int $channelAccountId, string $provider, string $categoryId): array
    {
        $all = $this->fullTree($channelAccountId, $provider);
        $byId = [];
        foreach ($all as $c) {
            $byId[$c['id']] = $c;
        }
        $node = $byId[$categoryId] ?? null;

        return [
            'id' => $categoryId,
            'name' => $node['name'] ?? $categoryId,
            'is_leaf' => $node['is_leaf'] ?? true,
            'path' => $node ? self::pathLabel($categoryId, $byId) : $categoryId,
        ];
    }

    /**
     * Full flat category tree for a shop, cached 6h. Skips nodes with blank id/name
     * and normalizes the root parent ('0'/'' → null) so root filtering works across
     * providers (Shopee dùng parent_category_id=0 cho gốc).
     *
     * @return array<int,array<string,mixed>>
     */
    private function fullTree(int $channelAccountId, string $provider): array
    {
        $auth = $this->authFor($channelAccountId, $provider);
        $key = "listing_tax:tree:$provider:$channelAccountId";

        return Cache::remember($key, now()->addHours(6), function () use ($provider, $auth) {
            $out = [];
            foreach ($this->publishers->for($provider)->getCategoryTree($auth) as $c) {
                if ((string) $c->id === '' || trim($c->name) === '') {
                    continue;
                }
                $out[] = [
                    'id' => $c->id,
                    'parent_id' => self::normalizeParent($c->parentId),
                    'name' => $c->name,
                    'is_leaf' => $c->isLeaf,
                ];
            }

            return $out;
        });
    }

    private static function normalizeParent(?string $parentId): ?string
    {
        return ($parentId === null || $parentId === '' || $parentId === '0') ? null : $parentId;
    }

    /**
     * @param  array<string,array<string,mixed>>  $byId
     */
    private static function pathLabel(string $id, array $byId): string
    {
        $parts = [];
        $cur = $byId[$id] ?? null;
        $guard = 0;
        while ($cur && $guard++ < 20) {
            array_unshift($parts, (string) $cur['name']);
            $pid = $cur['parent_id'] ?? null;
            $cur = $pid !== null ? ($byId[$pid] ?? null) : null;
        }

        return implode(' › ', $parts);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function attributes(int $channelAccountId, string $provider, string $categoryId): array
    {
        $auth = $this->authFor($channelAccountId, $provider);
        $key = "listing_tax:attr:$provider:$categoryId";

        return Cache::remember($key, now()->addHours(12), fn () => array_map(
            fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'required' => $a->required,
                'is_sale_prop' => $a->isSaleProp,
                'input_type' => $a->inputType,
                'values' => $a->values,
            ],
            $this->publishers->for($provider)->getCategoryAttributes($auth, $categoryId),
        ));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function brands(int $channelAccountId, string $provider, string $categoryId): array
    {
        $auth = $this->authFor($channelAccountId, $provider);
        $key = "listing_tax:brand:$provider:$categoryId";

        return Cache::remember($key, now()->addHours(12), fn () => array_map(
            fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'mandatory' => $b->mandatory,
            ],
            $this->publishers->for($provider)->getBrands($auth, $categoryId),
        ));
    }

    /**
     * Tùy chọn vận chuyển của shop (Shopee kênh / TikTok kho+delivery / Lazada package).
     * Cache 30' (thay đổi chậm hơn đơn nhưng nhanh hơn taxonomy).
     *
     * @return array<string,mixed>
     */
    public function shippingOptions(int $channelAccountId, string $provider): array
    {
        $auth = $this->authFor($channelAccountId, $provider);
        $key = "listing_ship:$provider:$channelAccountId";

        return Cache::remember($key, now()->addMinutes(30), fn () => $this->publishers->for($provider)->getShippingOptions($auth));
    }

    private function authFor(int $channelAccountId, string $provider): AuthContext
    {
        /** @var ChannelAccount $account */
        $account = ChannelAccount::query()->findOrFail($channelAccountId);

        return $account->authContext();
    }
}
