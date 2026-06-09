<?php

namespace CMBcoreSeller\Modules\Accounting\Services;

use CMBcoreSeller\Modules\Accounting\DTO\JournalEntryDTO;
use CMBcoreSeller\Modules\Accounting\DTO\JournalLineDTO;
use CMBcoreSeller\Modules\Accounting\Exceptions\AccountingException;
use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Models\JournalEntry;
use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;

/**
 * Bút toán kết chuyển cuối kỳ (xác định kết quả kinh doanh). Phase 7.6 — SPEC 0019.
 *
 * Tạo 2 bút toán theo chuẩn VAS/TT133, posted vào ngày cuối kỳ:
 *   A. Kết chuyển doanh thu, thu nhập, chi phí, giá vốn về TK 911 (Xác định KQKD)
 *      - TK doanh thu/thu nhập (dư Có: 511, 515, 711…): Nợ TK đó / Có 911
 *      - TK chi phí/giá vốn/giảm trừ (dư Nợ: 632, 635, 642, 811, 821, 521…): Nợ 911 / Có TK đó
 *   B. Kết chuyển lãi/lỗ: lãi ⇒ Nợ 911 / Có 4211; lỗ ⇒ Nợ 4211 / Có 911
 *
 * Idempotent qua idempotency_key theo period_id ⇒ gọi lại trả về bút toán đã tạo (không nhân đôi).
 * Muốn kết chuyển lại sau khi số liệu thay đổi: đảo 2 bút toán này rồi gọi lại với kỳ đã mở.
 */
class ClosingEntryService
{
    /** TK trung gian xác định KQKD. */
    private const CLEARING = '911';

    /** TK Lợi nhuận sau thuế chưa phân phối — năm nay. */
    private const RETAINED = '4211';

    public function __construct(private readonly JournalService $journals) {}

    /**
     * @return array{already:bool, net_income:int, entries:array<int, JournalEntry>}
     */
    public function carryForward(int $tenantId, FiscalPeriod $period, ?int $userId = null): array
    {
        if ($period->status !== FiscalPeriod::STATUS_OPEN) {
            throw AccountingException::invalidLines('Chỉ kết chuyển được khi kỳ đang mở. Hãy mở lại kỳ trước khi kết chuyển.');
        }

        $keyPl = "accounting.period_carry.{$period->id}.pl";
        $keyResult = "accounting.period_carry.{$period->id}.result";

        // Đã kết chuyển trước đó ⇒ trả về ngay (idempotent). Phải kiểm TRƯỚC khi tính phát sinh,
        // vì sau khi kết chuyển các TK kết quả đã về 0 nên tính lại sẽ tưởng "không có phát sinh".
        $already = JournalEntry::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('idempotency_key', [$keyPl, $keyResult])
            ->get();
        if ($already->isNotEmpty()) {
            return ['already' => true, 'net_income' => 0, 'entries' => $already->all()];
        }

        $postedAt = Carbon::parse($period->end_date);

        // Tổng hợp phát sinh các TK kết quả trong kỳ.
        $accounts = ChartAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('type', ['revenue', 'cogs', 'expense', 'contra_revenue'])
            ->where('is_postable', true)
            ->get();

        $sums = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereBetween('posted_at', [$period->start_date->copy()->startOfDay(), $period->end_date->copy()->endOfDay()])
            ->selectRaw('account_id, SUM(dr_amount) as dr, SUM(cr_amount) as cr')
            ->groupBy('account_id')->get()->keyBy('account_id');

        $plLines = [];
        $netCredit = 0; // > 0 = lãi (911 dư Có), < 0 = lỗ.
        foreach ($accounts as $acc) {
            $s = $sums->get($acc->id);
            $dr = (int) ($s->dr ?? 0);
            $cr = (int) ($s->cr ?? 0);
            $bal = $acc->isDebitNormal() ? ($dr - $cr) : ($cr - $dr);
            if ($bal === 0) {
                continue;
            }
            if ($acc->isDebitNormal()) {
                // Chi phí/giá vốn (dư Nợ) ⇒ ghi Có để tất toán.
                $plLines[] = $bal > 0
                    ? JournalLineDTO::credit($acc->code, $bal, ['memo' => 'Kết chuyển '.$acc->name])
                    : JournalLineDTO::debit($acc->code, -$bal, ['memo' => 'Kết chuyển '.$acc->name]);
                $netCredit -= $bal;
            } else {
                // Doanh thu/thu nhập (dư Có) ⇒ ghi Nợ để tất toán.
                $plLines[] = $bal > 0
                    ? JournalLineDTO::debit($acc->code, $bal, ['memo' => 'Kết chuyển '.$acc->name])
                    : JournalLineDTO::credit($acc->code, -$bal, ['memo' => 'Kết chuyển '.$acc->name]);
                $netCredit += $bal;
            }
        }

        if ($plLines === []) {
            return ['already' => false, 'net_income' => 0, 'entries' => []];
        }

        // Dòng cân TK 911 cho bút toán A.
        if ($netCredit > 0) {
            $plLines[] = JournalLineDTO::credit(self::CLEARING, $netCredit, ['memo' => 'Lãi — kết chuyển vào 911']);
        } elseif ($netCredit < 0) {
            $plLines[] = JournalLineDTO::debit(self::CLEARING, -$netCredit, ['memo' => 'Lỗ — kết chuyển vào 911']);
        }

        $entries = [];
        $entries[] = $this->journals->post(new JournalEntryDTO(
            tenantId: $tenantId,
            postedAt: $postedAt,
            sourceModule: 'accounting',
            sourceType: 'period_carry',
            sourceId: $period->id,
            idempotencyKey: $keyPl,
            lines: $plLines,
            narration: "Kết chuyển doanh thu, chi phí xác định KQKD kỳ {$period->code}",
            createdBy: $userId,
        ));

        // Bút toán B — kết chuyển lãi/lỗ 911 → 4211.
        if ($netCredit !== 0) {
            $resultLines = $netCredit > 0
                ? [JournalLineDTO::debit(self::CLEARING, $netCredit, ['memo' => 'Kết chuyển lãi']), JournalLineDTO::credit(self::RETAINED, $netCredit, ['memo' => 'Lãi chưa phân phối'])]
                : [JournalLineDTO::debit(self::RETAINED, -$netCredit, ['memo' => 'Lỗ chưa phân phối']), JournalLineDTO::credit(self::CLEARING, -$netCredit, ['memo' => 'Kết chuyển lỗ'])];

            $entries[] = $this->journals->post(new JournalEntryDTO(
                tenantId: $tenantId,
                postedAt: $postedAt,
                sourceModule: 'accounting',
                sourceType: 'period_carry',
                sourceId: $period->id,
                idempotencyKey: $keyResult,
                lines: $resultLines,
                narration: ($netCredit > 0 ? 'Kết chuyển lãi' : 'Kết chuyển lỗ')." kỳ {$period->code} vào TK 4211",
                createdBy: $userId,
            ));
        }

        return ['already' => false, 'net_income' => $netCredit, 'entries' => $entries];
    }
}
