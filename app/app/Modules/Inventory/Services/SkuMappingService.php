<?php

namespace CMBcoreSeller\Modules\Inventory\Services;

use CMBcoreSeller\Modules\Inventory\Events\InventoryChanged;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\SkuMapping;
use CMBcoreSeller\Modules\Inventory\Support\SkuCodeNormalizer;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Manages channel_listing ↔ master SKU mappings: set/replace a mapping (single or
 * combo), remove it, and bulk auto-match unmapped listings on `seller_sku ==
 * sku_code`. Any change re-pushes the affected SKUs' stock. See SPEC 0003 §4.
 */
class SkuMappingService
{
    /**
     * Replace the listing's mapping with `$lines` ([{sku_id, quantity}, ...]).
     *
     * @param  list<array{sku_id:int,quantity?:int}>  $lines
     * @return list<SkuMapping>
     */
    public function setMapping(int $tenantId, ChannelListing $listing, string $type, array $lines, ?int $userId = null): array
    {
        $type = in_array($type, [SkuMapping::TYPE_SINGLE, SkuMapping::TYPE_BUNDLE], true) ? $type : SkuMapping::TYPE_SINGLE;
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'Cần ít nhất một SKU.']);
        }
        if ($type === SkuMapping::TYPE_SINGLE && count($lines) !== 1) {
            throw ValidationException::withMessages(['lines' => 'Mapping "single" chỉ có đúng 1 SKU (dùng "bundle" cho combo).']);
        }
        $skuIds = array_map(fn ($l) => (int) $l['sku_id'], $lines);
        $valid = Sku::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->whereIn('id', $skuIds)->pluck('id')->all();
        $missing = array_diff($skuIds, $valid);
        if ($missing !== []) {
            throw ValidationException::withMessages(['lines' => 'SKU không tồn tại: '.implode(', ', $missing)]);
        }

        $affected = DB::transaction(function () use ($tenantId, $listing, $type, $lines, $userId) {
            $old = SkuMapping::withoutGlobalScope(TenantScope::class)->where('channel_listing_id', $listing->getKey())->pluck('sku_id')->all();
            SkuMapping::withoutGlobalScope(TenantScope::class)->where('channel_listing_id', $listing->getKey())->delete();
            $created = [];
            foreach ($lines as $l) {
                $created[] = SkuMapping::withoutGlobalScope(TenantScope::class)->create([
                    'tenant_id' => $tenantId, 'channel_listing_id' => $listing->getKey(), 'sku_id' => (int) $l['sku_id'],
                    'quantity' => max(1, (int) ($l['quantity'] ?? 1)), 'type' => $type, 'created_by' => $userId,
                ]);
            }

            return [$created, array_values(array_unique(array_merge($old, array_map(fn ($l) => (int) $l['sku_id'], $lines))))];
        });
        [$created, $touchedSkuIds] = $affected;

        InventoryChanged::dispatch($tenantId, $touchedSkuIds, 'sku_mapping_changed');

        return $created;
    }

    public function removeMapping(SkuMapping $mapping): void
    {
        $tenantId = (int) $mapping->tenant_id;
        $skuId = (int) $mapping->sku_id;
        $mapping->delete();
        InventoryChanged::dispatch($tenantId, [$skuId], 'sku_mapping_removed');
    }

    /** Create `single×1` mappings for every unmapped listing whose seller_sku equals a SKU code. Returns count matched. */
    public function autoMatchUnmapped(int $tenantId, ?int $userId = null): int
    {
        $skuByCode = [];
        foreach (Sku::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->whereNull('deleted_at')->get(['id', 'sku_code']) as $sku) {
            $skuByCode[SkuCodeNormalizer::normalize($sku->sku_code)] = (int) $sku->getKey();
        }
        if ($skuByCode === []) {
            return 0;
        }

        $matched = 0;
        ChannelListing::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->unmapped()->whereNotNull('seller_sku')
            ->orderBy('id')->chunkById(200, function ($listings) use (&$matched, $skuByCode, $tenantId, $userId) {
                foreach ($listings as $listing) {
                    $code = SkuCodeNormalizer::normalize($listing->seller_sku);
                    if ($code === '' || ! isset($skuByCode[$code])) {
                        continue;
                    }
                    SkuMapping::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                        ['channel_listing_id' => $listing->getKey(), 'sku_id' => $skuByCode[$code]],
                        ['tenant_id' => $tenantId, 'quantity' => 1, 'type' => SkuMapping::TYPE_SINGLE, 'created_by' => $userId],
                    );
                    $matched++;
                    InventoryChanged::dispatch($tenantId, [$skuByCode[$code]], 'sku_auto_matched');
                }
            });

        return $matched;
    }
}
