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
     * @return array<int,array<string,mixed>>
     */
    public function categories(int $channelAccountId, string $provider, ?string $parentId = null): array
    {
        $auth = $this->authFor($channelAccountId, $provider);
        $key = "listing_tax:cat:$provider:$channelAccountId:".($parentId ?? 'root');

        return Cache::remember($key, now()->addHours(12), fn () => array_map(
            fn ($c) => [
                'id' => $c->id,
                'parent_id' => $c->parentId,
                'name' => $c->name,
                'is_leaf' => $c->isLeaf,
            ],
            $this->publishers->for($provider)->getCategoryTree($auth, $parentId),
        ));
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

    private function authFor(int $channelAccountId, string $provider): AuthContext
    {
        /** @var ChannelAccount $account */
        $account = ChannelAccount::query()->findOrFail($channelAccountId);

        return $account->authContext();
    }
}
