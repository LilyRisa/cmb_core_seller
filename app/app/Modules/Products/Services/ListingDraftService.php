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
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
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
     * Map a marketplace QC/publish raw status (từ create result hoặc getListingStatus)
     * về trạng thái {@see ListingDraft}: chờ duyệt → REVIEWING, duyệt xong → LIVE,
     * bị từ chối → FAILED. Mặc định coi như đang duyệt (an toàn — sàn luôn xét duyệt).
     */
    public static function statusFromRaw(string $rawStatus): string
    {
        return match (strtoupper(trim($rawStatus))) {
            'APPROVED', 'LIVE', 'NORMAL', 'ACTIVATE', 'ACTIVE', 'SUCCESS', 'PUBLISHED' => ListingDraft::STATUS_LIVE,
            'REJECTED', 'FAILED', 'BANNED', 'DELETED', 'FROZEN', 'FREEZE' => ListingDraft::STATUS_FAILED,
            default => ListingDraft::STATUS_REVIEWING, // PENDING / AUDITING / PENDING QC / '' …
        };
    }

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
            // Lấy ảnh từ chính dữ liệu đã copy về: ảnh đại diện + meta.image_links (extension đẩy lên).
            $draft->media_refs = $this->seedImagesFromProduct($product);
            // Mang mô tả đã copy (extension lưu trong product.meta.description) vào nháp để
            // người dùng không phải nhập lại — KHÔNG có thì để trống, soạn ở trang chỉnh sửa.
            $description = trim((string) (($product->meta ?? [])['description'] ?? ''));
            if ($description !== '') {
                $draft->attributes = ['description' => $description];
            }
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
            if (array_key_exists('description', $data)) {
                $attrs = $draft->attributes ?? [];
                $attrs['description'] = $data['description'];
                $draft->attributes = $attrs;
            }

            foreach (['category_id', 'brand_id', 'attributes', 'media_refs', 'logistics'] as $key) {
                if (array_key_exists($key, $data)) {
                    if ($key === 'attributes') {
                        $draft->attributes = array_merge($draft->attributes ?? [], $data[$key] ?? []);
                    } else {
                        $draft->{$key} = $data[$key];
                    }
                }
            }
            $draft->save();

            if (array_key_exists('skus', $data) && is_array($data['skus'])) {
                $existing = $draft->skus()->get();
                $firstWarehouseId = null;
                foreach (array_values($data['skus']) as $i => $row) {
                    $model = $existing->get($i);
                    $fill = [];
                    foreach (['seller_sku', 'price', 'stock', 'sale_props', 'package_weight', 'package_dims'] as $f) {
                        if (array_key_exists($f, $row)) {
                            $fill[$f] = $row[$f];
                        }
                    }
                    if ($firstWarehouseId === null && ! empty($row['warehouse_id'])) {
                        $firstWarehouseId = (string) $row['warehouse_id'];
                    }

                    if ($model) {
                        $model->fill($fill);
                        $model->save();
                    } else {
                        $draft->skus()->create($fill);
                    }
                }
                if ($firstWarehouseId !== null) {
                    $logistics = $draft->logistics ?? [];
                    $logistics['warehouse_id'] = $firstWarehouseId;
                    $draft->logistics = $logistics;
                    $draft->save();
                }
            }
        });

        return $this->revalidate($draft->fresh(['skus']));
    }

    /**
     * Copy an existing listing draft/live listing to another connected shop.
     *
     * Same-provider copies reuse validated marketplace fields. Cross-provider
     * copies keep only portable content and remain behind the edit gate.
     */
    public function cloneToChannel(int $sourceListingId, int $targetChannelAccountId): ListingDraft
    {
        return DB::transaction(function () use ($sourceListingId, $targetChannelAccountId) {
            $source = ListingDraft::with('skus')->findOrFail($sourceListingId);
            $targetAccount = ChannelAccount::query()->active()->findOrFail($targetChannelAccountId);

            abort_if((int) $source->channel_account_id === (int) $targetAccount->getKey(), 422, 'Chọn một gian hàng đích khác gian hàng nguồn.');

            $target = ListingDraft::with('skus')
                ->where('product_id', $source->product_id)
                ->where('channel_account_id', $targetAccount->getKey())
                ->first();

            if ($target && $target->external_item_id && $target->status === ListingDraft::STATUS_LIVE) {
                abort(409, 'Gian hàng đích đã có listing live cho sản phẩm này.');
            }

            if (! $target) {
                $target = new ListingDraft;
                $target->product_id = $source->product_id;
                $target->channel_account_id = (int) $targetAccount->getKey();
                $target->created_by = auth()->id();
            }

            $sameProvider = $source->provider === $targetAccount->provider;
            $sourceAttributes = $source->attributes ?? [];

            $target->provider = $targetAccount->provider;
            $target->external_item_id = null;
            $target->raw_qc_status = null;
            $target->last_error = null;
            $target->pushed_at = null;

            if ($sameProvider) {
                $target->category_id = $source->category_id;
                $target->brand_id = $source->brand_id;
                $target->attributes = $sourceAttributes;
                $target->media_refs = $source->media_refs ?? [];
                $target->logistics = $source->logistics ?? [];
            } else {
                $target->category_id = null;
                $target->brand_id = null;
                $target->attributes = array_filter([
                    'description' => $sourceAttributes['description'] ?? null,
                    'source_provider' => $source->provider,
                    'source_listing_id' => $source->getKey(),
                ], fn ($v) => $v !== null && $v !== '');
                $target->media_refs = $source->media_refs ?? [];
                $target->logistics = [];
            }

            $target->status = ListingDraft::STATUS_DRAFT;
            $target->validation_errors = null;
            $target->save();

            $target->skus()->delete();
            foreach ($source->skus as $sku) {
                $target->skus()->create([
                    'master_variant_id' => $sku->master_variant_id,
                    'seller_sku' => $sku->seller_sku,
                    'sale_props' => $sameProvider ? ($sku->sale_props ?? []) : [],
                    'price' => $sku->price,
                    'stock' => $sku->stock,
                    'package_weight' => $sku->package_weight,
                    'package_dims' => $sku->package_dims ?? [],
                    'image_ref' => $sku->image_ref,
                ]);
            }

            return $this->revalidate($target->fresh(['skus']));
        });
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
            description: (string) (($draft->attributes ?? [])['description'] ?? ''),
            categoryId: (string) ($draft->category_id ?? ''),
            brandId: $draft->brand_id,
            attributes: $draft->attributes ?? [],
            media: $media,
            skus: $skus,
            logistics: $logistics,
        );
    }

    /**
     * Ảnh khởi tạo cho nháp đăng sàn: ảnh đại diện + `meta.image_links` (extension
     * copy đẩy lên). Trả về danh sách URL (đã khử trùng lặp).
     *
     * @return string[]
     */
    private function seedImagesFromProduct(Product $product): array
    {
        $images = [];
        if (! empty($product->image)) {
            $images[] = (string) $product->image;
        }
        foreach ((array) (($product->meta ?? [])['image_links'] ?? []) as $u) {
            $u = is_string($u) ? trim($u) : '';
            if ($u !== '' && ! in_array($u, $images, true)) {
                $images[] = $u;
            }
        }

        return $images;
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
