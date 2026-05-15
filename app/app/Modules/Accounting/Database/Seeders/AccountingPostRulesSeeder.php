<?php

namespace CMBcoreSeller\Modules\Accounting\Database\Seeders;

use CMBcoreSeller\Modules\Accounting\Models\AccountingPostRule;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Mapping rule mặc định cho auto-post. Phase 7.1 — SPEC 0019.
 *
 * Tenant đổi mapping qua UI `/settings/accounting/post-rules`. Đổi không ảnh hưởng entry đã post.
 *
 * Bảng `event_key` đầy đủ cho Phase 7.1 → 7.5:
 *   inventory.goods_receipt.confirmed   → Dr 156 / Cr 331 (Phase 7.1)
 *   inventory.stock_transfer            → Dr 156(to) / Cr 156(from) — Listener tự đặt dim_warehouse
 *   inventory.stocktake_adjust.in       → Dr 156 / Cr 711 (thừa khi kiểm)
 *   inventory.stocktake_adjust.out      → Dr 811 / Cr 156 (thiếu khi kiểm)
 *   orders.shipped.revenue              → Dr 131 / Cr 511 + Cr 33311 (Phase 7.2)
 *   orders.shipped.cogs                 → Dr 632 / Cr 156 (Phase 7.2)
 *   orders.cancelled                    → reverse — Listener tự gọi reverse(), không qua rule
 *   finance.settlement.fee              → Dr 6421 / Cr 131 (Phase 7.2)
 *   finance.settlement.payout           → Dr 1121 / Cr 131 (Phase 7.2)
 *   procurement.vendor_bill             → đã trùng `inventory.goods_receipt.confirmed` (Phase 7.3 mở rộng `purchase_invoice` cho hoá đơn không qua GR)
 *   cash.receipt                        → Dr 1111/1121 / Cr 131 (Phase 7.4)
 *   cash.payment                        → Dr 331 / Cr 1111/1121 (Phase 7.4)
 *
 * v1: chỉ seed những rule có dùng cùng phase tenant onboard. Phase sau bổ sung trong migration mới.
 */
class AccountingPostRulesSeeder
{
    public function run(int $tenantId): int
    {
        // Mapping mặc định — dùng TK LÁ (postable). TT133: 156/333/511/642 đều là TK tổng (không postable).
        $rules = [
            // Phase 7.1
            ['event_key' => 'inventory.goods_receipt.confirmed', 'debit_account_code' => '1561', 'credit_account_code' => '331'],
            ['event_key' => 'inventory.stock_transfer', 'debit_account_code' => '1561', 'credit_account_code' => '1561'],
            ['event_key' => 'inventory.stocktake_adjust.in', 'debit_account_code' => '1561', 'credit_account_code' => '711'],
            ['event_key' => 'inventory.stocktake_adjust.out', 'debit_account_code' => '811', 'credit_account_code' => '1561'],
            // Phase 7.2
            ['event_key' => 'orders.shipped.revenue', 'debit_account_code' => '131', 'credit_account_code' => '5111'],
            ['event_key' => 'orders.shipped.vat', 'debit_account_code' => '131', 'credit_account_code' => '33311'],
            ['event_key' => 'orders.shipped.cogs', 'debit_account_code' => '632', 'credit_account_code' => '1561'],
            ['event_key' => 'finance.settlement.commission', 'debit_account_code' => '6421', 'credit_account_code' => '131'],
            ['event_key' => 'finance.settlement.payment_fee', 'debit_account_code' => '6421', 'credit_account_code' => '131'],
            ['event_key' => 'finance.settlement.shipping_fee', 'debit_account_code' => '6421', 'credit_account_code' => '131'],
            ['event_key' => 'finance.settlement.voucher_seller', 'debit_account_code' => '521', 'credit_account_code' => '131'],
            ['event_key' => 'finance.settlement.adjustment', 'debit_account_code' => '6421', 'credit_account_code' => '131'],
            ['event_key' => 'finance.settlement.payout', 'debit_account_code' => '1121', 'credit_account_code' => '131'],
            // Phase 7.3
            ['event_key' => 'procurement.vendor_bill.recorded', 'debit_account_code' => '1561', 'credit_account_code' => '331'],
            ['event_key' => 'procurement.vendor_bill.vat', 'debit_account_code' => '1331', 'credit_account_code' => '331'],
            // Phase 7.4
            ['event_key' => 'cash.receipt.from_customer', 'debit_account_code' => '1111', 'credit_account_code' => '131'],
            ['event_key' => 'cash.payment.to_supplier', 'debit_account_code' => '331', 'credit_account_code' => '1111'],
            ['event_key' => 'bank.receipt.from_customer', 'debit_account_code' => '1121', 'credit_account_code' => '131'],
            ['event_key' => 'bank.payment.to_supplier', 'debit_account_code' => '331', 'credit_account_code' => '1121'],
        ];

        $created = 0;
        foreach ($rules as $r) {
            $exists = AccountingPostRule::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('event_key', $r['event_key'])->exists();
            if (! $exists) {
                AccountingPostRule::query()->create(array_merge($r, [
                    'tenant_id' => $tenantId,
                    'is_enabled' => true,
                ]));
                $created++;
            }
        }

        return $created;
    }
}
