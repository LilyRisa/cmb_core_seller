<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Services;

use CMBcoreSeller\Integrations\Channels\Contracts\ListingValidator;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Channels\Lazada\LazadaListingValidator;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeListingValidator;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokListingValidator;
use CMBcoreSeller\Modules\Products\Models\ListingDraft;
use CMBcoreSeller\Modules\Products\Models\ListingDraftSku;
use CMBcoreSeller\Modules\Products\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Drives the marketplace product-publishing draft lifecycle: seed a draft from a
 * master {@see Product}, let the seller edit per-provider fields, then revalidate
 * the draft against the matching {@see ListingValidator} to mark it READY.
 *
 * The integration layer never learns marketplace names from core; this service
 * resolves the right validator via {@see self::validatorFor()} and maps the draft
 * onto a normalized {@see ListingDraftDTO} for validation.
 */
final class ListingDraftService
{
    /**
     * Create (or return the existing) draft for a master product on a channel.
     * Enforces the unique (tenant, product, channel_account) tuple — repeat calls
     * are idempotent and return the same draft.
     */
    public function createDraft(int $productId, int $channelAccountId, string $provider): ListingDraft
    {
        return DB::transaction(function () use ($productId, $channelAccountId, $provider) {
            $product = Product::with('skus')->findOrFail($productId);

            $existing = ListingDraft::where('product_id', $productId)
                ->where('channel_account_id', $channelAccountId)
                ->first();

            if ($existing) {
                return $existing->load('skus');
            }

            $draft = new ListingDraft;
            $draft->product_id = $product->getKey();
            $draft->channel_account_id = $channelAccountId;
            $draft->provider = $provider;
            $draft->status = ListingDraft::STATUS_DRAFT;
            $draft->created_by = auth()->id();
            $draft->save();

            foreach ($product->skus as $i => $sku) {
                $draft->skus()->create([
                    'master_variant_id' => $sku->getKey(),
                    'seller_sku' => $sku->sku_code !== ''
                        ? $sku->sku_code
                        : 'MP'.$productId.'-'.$i,
                    'sale_props' => is_array($sku->attributes) ? $sku->attributes : [],
                    'price' => (int) ($sku->ref_sale_price ?? 0),
                    'stock' => $sku->availableTotal(),
                    'image_ref' => $sku->image_url,
                ]);
            }

            return $draft->load('skus');
        });
    }

    /**
     * Apply editable fields onto the draft and its SKUs, then revalidate.
     *
     * @param  array<string,mixed>  $data
     */
    public function update(int $listingId, array $data): ListingDraft
    {
        $draft = ListingDraft::with('skus')->findOrFail($listingId);

        DB::transaction(function () use ($draft, $data) {
            foreach (['category_id', 'brand_id', 'attributes', 'media_refs', 'logistics'] as $key) {
                if (array_key_exists($key, $data)) {
                    $draft->{$key} = $data[$key];
                }
            }
            $draft->save();

            if (array_key_exists('skus', $data) && is_array($data['skus'])) {
                $existing = $draft->skus()->get();
                foreach (array_values($data['skus']) as $i => $row) {
                    $model = $existing->get($i);
                    $fill = [];
                    foreach (['seller_sku', 'price', 'stock', 'sale_props', 'package_weight', 'package_dims'] as $f) {
                        if (array_key_exists($f, $row)) {
                            $fill[$f] = $row[$f];
                        }
                    }

                    if ($model) {
                        $model->fill($fill);
                        $model->save();
                    } else {
                        $draft->skus()->create($fill);
                    }
                }
            }
        });

        return $this->revalidate($draft->fresh(['skus']));
    }

    /**
     * Build a {@see ListingDraftDTO} from the draft and validate it with the
     * provider's validator. Empty violations → READY; otherwise DRAFT with the
     * stored errors. The DTO title is sourced from the master product name.
     */
    public function revalidate(ListingDraft $draft): ListingDraft
    {
        $dto = $this->toDraftDTO($draft);

        $errors = $this->validatorFor($draft->provider)->validate($dto);

        if ($errors === []) {
            $draft->status = ListingDraft::STATUS_READY;
            $draft->validation_errors = null;
        } else {
            $draft->status = ListingDraft::STATUS_DRAFT;
            $draft->validation_errors = $errors;
        }
        $draft->save();

        return $draft->load('skus');
    }

    /**
     * Map a {@see ListingDraft} (and its SKUs) onto a normalized
     * {@see ListingDraftDTO}. Single source of truth for draft → DTO mapping,
     * shared by {@see self::revalidate()} and the publish job.
     */
    public function toDraftDTO(ListingDraft $draft): ListingDraftDTO
    {
        $product = Product::findOrFail($draft->product_id);
        $title = (string) $product->name;

        $media = array_map(
            fn ($m) => is_array($m)
                ? new MediaRefDTO((string) ($m['ref'] ?? ''), (string) ($m['kind'] ?? 'cdn_url'))
                : new MediaRefDTO((string) $m, 'cdn_url'),
            $draft->media_refs ?? [],
        );

        $logistics = $draft->logistics ?? [];

        $skus = $draft->skus->map(fn (ListingDraftSku $s) => [
            'seller_sku' => $s->seller_sku,
            'price' => $s->price,
            'stock' => $s->stock,
            'sale_props' => $s->sale_props ?? [],
            'package_weight' => $s->package_weight,
            'package_dims' => $s->package_dims ?? [],
            'warehouse_id' => $logistics['warehouse_id'] ?? null,
        ])->all();

        return new ListingDraftDTO(
            title: $title,
            description: (string) ($draft->attributes['description'] ?? ''),
            categoryId: (string) ($draft->category_id ?? ''),
            brandId: $draft->brand_id,
            attributes: $draft->attributes ?? [],
            media: $media,
            skus: $skus,
            logistics: $logistics,
        );
    }

    private function validatorFor(string $provider): ListingValidator
    {
        return match ($provider) {
            'lazada' => new LazadaListingValidator,
            'tiktok' => new TikTokListingValidator,
            'shopee' => new ShopeeListingValidator,
            default => throw UnsupportedOperation::for($provider, 'listings.validate'),
        };
    }
}
