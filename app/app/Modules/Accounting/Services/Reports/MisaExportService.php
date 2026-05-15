<?php

namespace CMBcoreSeller\Modules\Accounting\Services\Reports;

use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Models\JournalEntry;
use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Export dữ liệu kế toán ra CSV để import vào MISA AMIS. Phase 7.5 — SPEC 0019.
 *
 * Format đơn giản:
 *  - 3 file CSV riêng (zip nếu cần): `chart_of_accounts.csv`, `journal_entries.csv`, `journal_lines.csv`.
 *  - UTF-8 BOM (Excel VN đọc chuẩn).
 *  - Tiền: số nguyên VND đồng (không format).
 *  - Encoding ngày: ISO 8601 yyyy-mm-dd.
 *
 * Service trả mảng `{filename: string => csv_content: string}` — controller stream file.
 *
 * V1 không gom thành Excel xlsx (cần thư viện riêng — phpoffice/phpspreadsheet); MISA AMIS import được
 * CSV trực tiếp qua module "Nhập khẩu phát sinh".
 */
class MisaExportService
{
    /**
     * @return array<string, string>
     */
    public function generate(int $tenantId, FiscalPeriod $period): array
    {
        $bom = "\xEF\xBB\xBF"; // UTF-8 BOM

        // 1. Chart of accounts.
        $coa = $bom.$this->csvLine(['MaTK', 'TenTK', 'LoaiTK', 'TKCha', 'SoDuBinhThuong', 'ChoPhepGhi']);
        ChartAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->orderBy('sort_order')->orderBy('code')
            ->chunk(500, function ($rows) use (&$coa) {
                foreach ($rows as $a) {
                    $parent = $a->parent_id ? (ChartAccount::query()->withoutGlobalScope(TenantScope::class)->whereKey($a->parent_id)->value('code') ?? '') : '';
                    $coa .= $this->csvLine([
                        $a->code, $a->name, $a->type, $parent,
                        $a->normal_balance, $a->is_postable ? '1' : '0',
                    ]);
                }
            });

        // 2. Journal entries (header).
        $je = $bom.$this->csvLine(['MaBT', 'NgayHachToan', 'KyKT', 'DienGiai', 'TongNo', 'TongCo', 'NguonChungTu', 'LoaiChungTu', 'MaChungTu']);
        JournalEntry::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereBetween('posted_at', [$period->start_date->copy()->startOfDay(), $period->end_date->copy()->endOfDay()])
            ->orderBy('posted_at')->orderBy('id')
            ->chunk(500, function ($rows) use (&$je) {
                foreach ($rows as $e) {
                    $je .= $this->csvLine([
                        $e->code,
                        $e->posted_at->format('Y-m-d'),
                        $e->period_id,
                        $e->narration ?? '',
                        (int) $e->total_debit,
                        (int) $e->total_credit,
                        $e->source_module,
                        $e->source_type,
                        (string) ($e->source_id ?? ''),
                    ]);
                }
            });

        // 3. Journal lines.
        $jl = $bom.$this->csvLine(['MaBT', 'STT', 'MaTK', 'No', 'Co', 'PartyType', 'PartyId', 'KhoID', 'GianHangID', 'SKUID', 'DonHangID', 'DienGiaiDong']);
        $entryCodes = JournalEntry::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereBetween('posted_at', [$period->start_date->copy()->startOfDay(), $period->end_date->copy()->endOfDay()])
            ->pluck('code', 'id');
        JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereBetween('posted_at', [$period->start_date->copy()->startOfDay(), $period->end_date->copy()->endOfDay()])
            ->orderBy('entry_id')->orderBy('line_no')
            ->chunk(1000, function ($rows) use (&$jl, $entryCodes) {
                foreach ($rows as $l) {
                    $jl .= $this->csvLine([
                        (string) ($entryCodes[$l->entry_id] ?? ''),
                        (int) $l->line_no,
                        $l->account_code,
                        (int) $l->dr_amount,
                        (int) $l->cr_amount,
                        $l->party_type ?? '',
                        (string) ($l->party_id ?? ''),
                        (string) ($l->dim_warehouse_id ?? ''),
                        (string) ($l->dim_shop_id ?? ''),
                        (string) ($l->dim_sku_id ?? ''),
                        (string) ($l->dim_order_id ?? ''),
                        $l->memo ?? '',
                    ]);
                }
            });

        return [
            'chart_of_accounts.csv' => $coa,
            'journal_entries.csv' => $je,
            'journal_lines.csv' => $jl,
        ];
    }

    /** Escape 1 dòng CSV theo RFC 4180 — quote nếu có `,`, `"`, newline. */
    private function csvLine(array $cols): string
    {
        $out = [];
        foreach ($cols as $c) {
            $s = (string) $c;
            $needsQuote = (strpbrk($s, ",\"\n\r") !== false);
            if ($needsQuote) {
                $s = '"'.str_replace('"', '""', $s).'"';
            }
            $out[] = $s;
        }

        return implode(',', $out)."\r\n";
    }
}
