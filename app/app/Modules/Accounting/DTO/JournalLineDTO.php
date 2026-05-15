<?php

namespace CMBcoreSeller\Modules\Accounting\DTO;

/**
 * DTO cho 1 dòng bút toán (giá trị, không persist).
 *
 * Bất biến tại construct — service post tạo ra JournalLine model.
 */
final class JournalLineDTO
{
    public function __construct(
        public readonly string $accountCode,
        public readonly int $drAmount = 0,
        public readonly int $crAmount = 0,
        public readonly ?string $partyType = null,
        public readonly ?int $partyId = null,
        public readonly ?int $dimWarehouseId = null,
        public readonly ?int $dimShopId = null,
        public readonly ?int $dimSkuId = null,
        public readonly ?int $dimOrderId = null,
        public readonly ?string $dimTaxCode = null,
        public readonly ?string $memo = null,
    ) {}

    public static function debit(string $accountCode, int $amount, array $opts = []): self
    {
        return new self(
            accountCode: $accountCode,
            drAmount: max(0, $amount),
            partyType: $opts['party_type'] ?? null,
            partyId: $opts['party_id'] ?? null,
            dimWarehouseId: $opts['dim_warehouse_id'] ?? null,
            dimShopId: $opts['dim_shop_id'] ?? null,
            dimSkuId: $opts['dim_sku_id'] ?? null,
            dimOrderId: $opts['dim_order_id'] ?? null,
            dimTaxCode: $opts['dim_tax_code'] ?? null,
            memo: $opts['memo'] ?? null,
        );
    }

    public static function credit(string $accountCode, int $amount, array $opts = []): self
    {
        return new self(
            accountCode: $accountCode,
            crAmount: max(0, $amount),
            partyType: $opts['party_type'] ?? null,
            partyId: $opts['party_id'] ?? null,
            dimWarehouseId: $opts['dim_warehouse_id'] ?? null,
            dimShopId: $opts['dim_shop_id'] ?? null,
            dimSkuId: $opts['dim_sku_id'] ?? null,
            dimOrderId: $opts['dim_order_id'] ?? null,
            dimTaxCode: $opts['dim_tax_code'] ?? null,
            memo: $opts['memo'] ?? null,
        );
    }
}
