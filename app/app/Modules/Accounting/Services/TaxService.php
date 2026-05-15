<?php

namespace CMBcoreSeller\Modules\Accounting\Services;

use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Accounting\Models\TaxFiling;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * VAT tổng hợp & tờ khai. Phase 7.5 — SPEC 0019.
 *
 * Tờ khai 01/GTGT theo kỳ tháng: output VAT (TK 33311) − input VAT (TK 1331) ⇒ net payable.
 * V1 chỉ aggregate dữ liệu thực và lưu vào `tax_filings` cho người dùng review.
 */
class TaxService
{
    /**
     * @return array{output_vat:int, input_vat:int, net_payable:int}
     */
    public function aggregate(int $tenantId, FiscalPeriod $period): array
    {
        $start = $period->start_date->copy()->startOfDay();
        $end = $period->end_date->copy()->endOfDay();

        $out = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('account_code', '33311')
            ->whereBetween('posted_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(cr_amount),0) as cr, COALESCE(SUM(dr_amount),0) as dr')
            ->first();
        $in = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('account_code', '1331')
            ->whereBetween('posted_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(cr_amount),0) as cr, COALESCE(SUM(dr_amount),0) as dr')
            ->first();
        $outputVat = (int) ($out->cr ?? 0) - (int) ($out->dr ?? 0);
        $inputVat = (int) ($in->dr ?? 0) - (int) ($in->cr ?? 0);

        return [
            'output_vat' => $outputVat,
            'input_vat' => $inputVat,
            'net_payable' => max(0, $outputVat - $inputVat),
        ];
    }

    public function generateFiling(int $tenantId, FiscalPeriod $period): TaxFiling
    {
        $agg = $this->aggregate($tenantId, $period);
        $code = sprintf('01/GTGT-%s', $period->code);

        $row = TaxFiling::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('code', $code)->first();
        if ($row) {
            $row->forceFill([
                'total_output_vat' => $agg['output_vat'],
                'total_input_vat' => $agg['input_vat'],
                'net_payable' => $agg['net_payable'],
                'lines' => $agg,
            ])->save();

            return $row;
        }

        return TaxFiling::query()->create([
            'tenant_id' => $tenantId,
            'code' => $code,
            'period_id' => $period->id,
            'tax_kind' => 'vat',
            'status' => TaxFiling::query()->newModelInstance()->getAttribute('status') ?? 'draft',
            'lines' => $agg,
            'total_output_vat' => $agg['output_vat'],
            'total_input_vat' => $agg['input_vat'],
            'net_payable' => $agg['net_payable'],
        ]);
    }
}
