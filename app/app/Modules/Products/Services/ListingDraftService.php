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
use Illuminate\Validation\ValidationException;

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
                // Nháp tạo TRƯỚC khi sản phẩm có biến thể (meta.variants/master SKU) sẽ rỗng
                // SKU và kẹt (không đẩy được, bảng SKU trống). Khi mở/tạo lại mà sản phẩm
                // đã có biến thể → seed bù để hiển thị & đẩy được.
                if ($existing->skus()->count() === 0) {
                    $this->seedDraftSkus($existing, $product);
                }

                return $existing->load('skus');
            }

            // Nháp đã XÓA MỀM vẫn giữ unique key (tenant, product, shop) — không dọn thì
            // import lại cùng sản phẩm vào cùng shop sẽ vỡ "duplicate key uq_draft_product_shop".
            // Xóa hẳn dòng cũ để tạo nháp mới sạch. Xem [[softdelete-updateorcreate-unique-violation]].
            ListingDraft::onlyTrashed()
                ->where('product_id', $productId)
                ->where('channel_account_id', $channelAccountId)
                ->forceDelete();

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

            $this->seedDraftSkus($draft, $product);

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
            // Tiêu đề riêng cho listing (override tên SP gốc) — lưu trong attributes['name'].
            // Rỗng ⇒ null để resource fallback về product->name.
            if (array_key_exists('name', $data)) {
                $attrs = $draft->attributes ?? [];
                $attrs['name'] = (trim((string) ($data['name'] ?? '')) !== '') ? trim((string) $data['name']) : null;
                $draft->attributes = $attrs;
            }

            if (array_key_exists('description', $data)) {
                $attrs = $draft->attributes ?? [];
                $attrs['description'] = $data['description'];
                $draft->attributes = $attrs;
            }

            if (array_key_exists('video_url', $data)) {
                $attrs = $draft->attributes ?? [];
                $attrs['video_url'] = ($data['video_url'] ?? '') !== '' ? $data['video_url'] : null;
                $draft->attributes = $attrs;
            }

            foreach (['category_id', 'brand_id', 'attributes', 'media_refs', 'logistics'] as $key) {
                if (array_key_exists($key, $data)) {
                    if ($key === 'attributes') {
                        // array_replace (KHÔNG array_merge): id thuộc tính sàn là chuỗi-số
                        // (vd "100000"); array_merge sẽ ĐÁNH SỐ LẠI khóa số → mất id thật,
                        // phình mảng mỗi lần lưu ⇒ thuộc tính "điền xong lưu lại mất".
                        $draft->attributes = array_replace($draft->attributes ?? [], $data[$key] ?? []);
                    } else {
                        $draft->{$key} = $data[$key];
                    }
                }
            }
            $draft->save();

            if (array_key_exists('skus', $data) && is_array($data['skus'])) {
                $rows = array_values($data['skus']);

                // SKU người bán phải KHÁC NHAU trong cùng một listing (sàn yêu cầu, và DB có
                // unique [listing_draft_id, seller_sku]). Bắt sớm với thông báo rõ ràng thay vì
                // để DB ném UniqueConstraintViolation → 500 khó hiểu ("biến thể không lưu được").
                $sellerSkus = array_filter(
                    array_map(fn ($r) => trim((string) ($r['seller_sku'] ?? '')), $rows),
                    fn ($s) => $s !== '',
                );
                if (count($sellerSkus) !== count(array_unique($sellerSkus))) {
                    throw ValidationException::withMessages([
                        'skus' => 'SKU người bán của các phân loại phải khác nhau.',
                    ]);
                }

                $existing = $draft->skus()->get();
                // Giữ seller_sku gốc theo vị trí để khôi phục khi payload cập nhật-từng-phần
                // (vd chỉ gửi liên kết master) KHÔNG kèm seller_sku.
                $origSellerSku = $existing->map(fn (ListingDraftSku $m) => $m->seller_sku)->all();

                // Pha 1: gán seller_sku TẠM (duy nhất) cho mọi dòng hiện có. Tránh va chạm
                // unique tạm thời khi người dùng HOÁN ĐỔI seller_sku giữa các dòng (A↔B):
                // ghi dòng đầu giá trị cuối có thể trùng với dòng sau chưa kịp ghi.
                foreach ($existing as $m) {
                    $m->forceFill(['seller_sku' => '__tmp_'.$m->getKey()])->save();
                }

                // Pha 2: upsert theo VỊ TRÍ (giữ liên kết master khi payload không gửi field đó).
                $firstWarehouseId = null;
                foreach ($rows as $i => $row) {
                    $model = $existing->get($i);
                    $fill = [];
                    foreach (['seller_sku', 'price', 'stock', 'sale_props', 'package_weight', 'package_dims', 'master_variant_id', 'image_ref'] as $f) {
                        if (array_key_exists($f, $row)) {
                            $fill[$f] = $row[$f];
                        }
                    }
                    // Payload không gửi seller_sku ⇒ khôi phục giá trị gốc (đừng kẹt seller_sku tạm).
                    if (! array_key_exists('seller_sku', $fill) && isset($origSellerSku[$i])) {
                        $fill['seller_sku'] = $origSellerSku[$i];
                    }
                    if ($firstWarehouseId === null && ! empty($row['warehouse_id'])) {
                        $firstWarehouseId = (string) $row['warehouse_id'];
                    }

                    if ($model) {
                        $model->fill($fill)->save();
                    } else {
                        $draft->skus()->create($fill);
                    }
                }

                // Xóa dòng dư (payload ít hơn) — vẫn mang seller_sku tạm từ pha 1.
                foreach ($existing as $i => $m) {
                    if ($i >= count($rows)) {
                        $m->delete();
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

        // Nạp cả product để resource fallback tiêu đề (attributes['name'] rỗng ⇒ product->name).
        return $this->revalidate($draft->fresh(['skus', 'product']));
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
                // Dọn nháp đã xóa mềm giữ cùng unique key (tránh duplicate key uq_draft_product_shop).
                ListingDraft::onlyTrashed()
                    ->where('product_id', $source->product_id)
                    ->where('channel_account_id', $targetAccount->getKey())
                    ->forceDelete();

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
     *
     * @param  MediaRefDTO[]|null  $preparedMedia  Ảnh ĐÃ upload lên sàn (uri/image_id) —
     *                                             PushListingJob truyền vào khi đẩy. Sàn như TikTok/Shopee CHỈ nhận ref do API
     *                                             upload ảnh trả về, KHÔNG nhận URL CDN ngoài. Null (vd lúc revalidate) ⇒ dùng
     *                                             URL nguồn trong media_refs (đủ để validate; không dùng để gọi API tạo listing).
     */
    public function toDraftDTO(ListingDraft $draft, ?array $preparedMedia = null): ListingDraftDTO
    {
        $product = Product::findOrFail($draft->product_id);
        // Tiêu đề riêng của listing (nếu seller đã sửa) ưu tiên hơn tên SP gốc.
        $title = (string) (($draft->attributes ?? [])['name'] ?? '') ?: (string) $product->name;

        $media = $preparedMedia ?? array_map(
            fn ($m) => is_array($m)
                ? new MediaRefDTO((string) ($m['ref'] ?? ''), (string) ($m['kind'] ?? 'cdn_url'))
                : new MediaRefDTO((string) $m, 'cdn_url'),
            $draft->media_refs ?? [],
        );

        $logistics = $draft->logistics ?? [];

        // Mã định danh thương mại (GTIN/EAN/UPC) lấy từ master SKU đã liên kết — sàn như
        // TikTok BẮT BUỘC `identifier_code` ở nhiều ngành. Nạp 1 lượt theo master_variant_id.
        $masterById = $product->skus->keyBy(fn ($s) => (int) $s->getKey());

        $skus = $draft->skus->map(function (ListingDraftSku $s) use ($logistics, $masterById) {
            $master = $s->master_variant_id !== null ? $masterById->get((int) $s->master_variant_id) : null;
            $gtin = self::pickGtin($master?->gtins, $master?->barcode);

            return [
                'seller_sku' => $s->seller_sku,
                'price' => $s->price,
                'stock' => $s->stock,
                'sale_props' => $s->sale_props ?? [],
                'package_weight' => $s->package_weight,
                'package_dims' => $s->package_dims ?? [],
                'warehouse_id' => $logistics['warehouse_id'] ?? null,
                'gtin' => $gtin['code'],
                'gtin_type' => $gtin['type'],
            ];
        })->all();

        return new ListingDraftDTO(
            title: $title,
            description: (string) (($draft->attributes ?? [])['description'] ?? ''),
            categoryId: (string) ($draft->category_id ?? ''),
            brandId: $draft->brand_id,
            attributes: $draft->attributes ?? [],
            media: $media,
            skus: $skus,
            logistics: $logistics,
            videoRef: (($draft->attributes ?? [])['video_url'] ?? null) ?: null,
            videoExternalId: (($draft->attributes ?? [])['video_external_id'] ?? null) ?: null,
            // Khóa idempotency ổn định theo nháp: retry cùng một lần đẩy sẽ KHÔNG tạo
            // sản phẩm trùng trên sàn (sàn dedupe theo key này). Phối với chốt chặn
            // external_item_id trong PushListingJob.
            idempotencyKey: 'listing-draft-'.$draft->getKey(),
        );
    }

    /**
     * Seed SKU nháp đăng sàn. Ưu tiên biến thể copy thô (`product.meta.variants`)
     * để "Phân loại" có dữ liệu mà KHÔNG đẻ master SKU dư thừa — các SKU này để
     * `master_variant_id = null` (liên kết tồn kho là thao tác thủ công sau).
     * Sản phẩm tạo trong app (đã có master SKU thật) thì seed từ master SKU và gán
     * liên kết luôn (không tạo SKU mới).
     */
    private function seedDraftSkus(ListingDraft $draft, Product $product): void
    {
        $variants = is_array(($product->meta ?? [])['variants'] ?? null) ? $product->meta['variants'] : [];

        if ($variants !== []) {
            foreach (array_values($variants) as $i => $v) {
                if (! is_array($v)) {
                    continue;
                }
                $name = trim((string) ($v['name'] ?? ''));
                $draft->skus()->create([
                    'master_variant_id' => null,
                    'seller_sku' => ($v['sku'] ?? '') !== '' ? (string) $v['sku'] : 'MP'.$product->getKey().'-'.($i + 1),
                    'sale_props' => $name !== '' ? ['Phân loại' => $name] : [],
                    'price' => (int) round((float) ($v['price'] ?? 0)),
                    'stock' => (int) ($v['stock'] ?? 0),
                    'image_ref' => ($v['image'] ?? null) ?: $product->image,
                ]);
            }

            return;
        }

        foreach ($product->skus as $i => $sku) {
            $draft->skus()->create([
                'master_variant_id' => $sku->getKey(),
                'seller_sku' => $sku->sku_code !== '' ? $sku->sku_code : 'MP'.$product->getKey().'-'.$i,
                'sale_props' => self::salePropsFromAttributes(is_array($sku->attributes) ? $sku->attributes : []),
                'price' => (int) ($sku->ref_sale_price ?? 0),
                'stock' => $sku->availableTotal(),
                'image_ref' => $sku->image_url,
            ]);
        }
    }

    /**
     * Chọn mã định danh thương mại đầu tiên (GTIN/EAN/UPC) của master SKU và suy ra
     * `type` theo độ dài (TikTok yêu cầu code + type). Ưu tiên mảng `gtins`, fallback
     * `barcode`. Trả `['code' => null, 'type' => null]` khi không có.
     *
     * @param  array<int,string>|null  $gtins
     * @return array{code: string|null, type: string|null}
     */
    private static function pickGtin(?array $gtins, ?string $barcode): array
    {
        $code = '';
        foreach ($gtins ?? [] as $g) {
            if (trim((string) $g) !== '') {
                $code = trim((string) $g);
                break;
            }
        }
        if ($code === '' && $barcode !== null && trim($barcode) !== '') {
            $code = trim($barcode);
        }
        if ($code === '') {
            return ['code' => null, 'type' => null];
        }

        // Suy kiểu theo số chữ số (định dạng chuẩn GS1).
        $type = match (strlen(preg_replace('/\D/', '', $code) ?? '')) {
            12 => 'UPC',
            8, 13 => 'EAN',
            14 => 'GTIN',
            default => 'GTIN',
        };

        return ['code' => $code, 'type' => $type];
    }

    /**
     * Chuyển attributes của master SKU thành sale_props sạch: gộp chuỗi biến thể vào
     * một tier "Phân loại", bỏ khóa nghiệp vụ (source_sku).
     *
     * @param  array<string,mixed>  $attrs
     * @return array<string,mixed>
     */
    private static function salePropsFromAttributes(array $attrs): array
    {
        $variant = trim((string) ($attrs['variant'] ?? ''));
        if ($variant !== '') {
            return ['Phân loại' => $variant];
        }

        unset($attrs['variant'], $attrs['source_sku']);

        return $attrs;
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
