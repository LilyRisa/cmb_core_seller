<?php

namespace CMBcoreSeller\Modules\Accounting\Services;

use CMBcoreSeller\Modules\Accounting\Events\PeriodClosed;
use CMBcoreSeller\Modules\Accounting\Events\PeriodReopened;
use CMBcoreSeller\Modules\Accounting\Exceptions\AccountingException;
use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Quản lý kỳ kế toán: tạo theo lịch, đóng/mở, lock. Phase 7.1 — SPEC 0019.
 *
 * Năm tài chính = năm dương lịch (chốt 2026-05-15). Mỗi tenant onboard ⇒
 * AccountingSetupService tạo 12 tháng + 4 quý + 1 năm cho năm hiện tại.
 */
class PeriodService
{
    /**
     * Resolve hoặc tự tạo kỳ `month` chứa `$date`.
     */
    public function resolveForDate(int $tenantId, Carbon $date): FiscalPeriod
    {
        $start = $date->copy()->startOfMonth()->startOfDay();
        $end = $date->copy()->endOfMonth()->startOfDay();
        $code = $date->format('Y-m');

        $period = FiscalPeriod::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->where('kind', FiscalPeriod::KIND_MONTH)
            ->first();
        if ($period !== null) {
            return $period;
        }

        // Auto-create — chỉ khi gần hiện tại (chống nhập sai date cách 100 năm).
        $now = now();
        $diffMonths = $now->diffInMonths($date, false);
        $back = (int) config('accounting.auto_create_periods_back_months', 24);
        $fwd = (int) config('accounting.auto_create_periods_forward_months', 24);
        if ($diffMonths < -$back || $diffMonths > $fwd) {
            throw AccountingException::invalidLines("Ngày {$date->toDateString()} ngoài cửa sổ tự tạo kỳ — vui lòng tạo kỳ thủ công ở /accounting/periods.");
        }

        return FiscalPeriod::query()->create([
            'tenant_id' => $tenantId,
            'code' => $code,
            'kind' => FiscalPeriod::KIND_MONTH,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => FiscalPeriod::STATUS_OPEN,
        ]);
    }

    /** Trả kỳ mở kế tiếp (tự tạo nếu thiếu). */
    public function nextOpen(int $tenantId, FiscalPeriod $current): FiscalPeriod
    {
        $cursor = Carbon::parse($current->end_date)->copy()->addDay();
        for ($i = 0; $i < 24; $i++) {
            $next = $this->resolveForDate($tenantId, $cursor);
            if ($next->status === FiscalPeriod::STATUS_OPEN) {
                return $next;
            }
            $cursor = $cursor->addMonthNoOverflow();
        }
        throw AccountingException::reopenBlocked('Không tìm thấy kỳ mở kế tiếp trong 24 tháng tới.');
    }

    /** Đóng kỳ. Yêu cầu kỳ đang `open`. Idempotent (gọi lại trên closed = no-op). */
    public function close(FiscalPeriod $period, ?int $userId = null, ?string $note = null): FiscalPeriod
    {
        if ($period->status === FiscalPeriod::STATUS_LOCKED) {
            throw AccountingException::periodLocked($period->code);
        }
        if ($period->status === FiscalPeriod::STATUS_CLOSED) {
            return $period;
        }

        DB::transaction(function () use ($period, $userId, $note) {
            $row = FiscalPeriod::query()->whereKey($period->id)->lockForUpdate()->first();
            $row?->forceFill([
                'status' => FiscalPeriod::STATUS_CLOSED,
                'closed_at' => now(),
                'closed_by' => $userId,
                'close_note' => $note,
            ])->save();
        });
        $period->refresh();
        PeriodClosed::dispatch($period);

        return $period;
    }

    /** Mở lại kỳ closed. Chặn nếu kỳ kế tiếp đã đóng (cascade integrity). */
    public function reopen(FiscalPeriod $period, ?int $userId = null): FiscalPeriod
    {
        if ($period->status === FiscalPeriod::STATUS_LOCKED) {
            throw AccountingException::periodLocked($period->code);
        }
        if ($period->status === FiscalPeriod::STATUS_OPEN) {
            return $period;
        }

        // Kỳ kế tiếp (cùng kind) đã closed ⇒ chặn.
        $next = FiscalPeriod::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $period->tenant_id)
            ->where('kind', $period->kind)
            ->where('start_date', '>', $period->end_date)
            ->orderBy('start_date')->first();
        if ($next !== null && $next->status !== FiscalPeriod::STATUS_OPEN) {
            throw AccountingException::reopenBlocked("Không thể mở lại kỳ {$period->code} — kỳ kế tiếp {$next->code} đã đóng/khoá.");
        }

        $period->forceFill([
            'status' => FiscalPeriod::STATUS_OPEN,
            'closed_at' => null,
            'closed_by' => null, // audit-fix: rõ ràng kỳ open, không giữ user đã đóng trước đó.
            'close_note' => null,
        ])->save();
        // `$userId` param giữ lại để audit log (Phase sau) ghi `reopened_by` riêng.
        PeriodReopened::dispatch($period);

        return $period;
    }

    /** Lock kỳ — vĩnh viễn không reopen được. Chỉ owner. */
    public function lock(FiscalPeriod $period, ?int $userId = null): FiscalPeriod
    {
        $period->forceFill([
            'status' => FiscalPeriod::STATUS_LOCKED,
            'closed_at' => $period->closed_at ?? now(),
            'closed_by' => $period->closed_by ?? $userId,
        ])->save();

        return $period;
    }

    /**
     * Tạo lịch kỳ cho 1 năm (12 tháng + 4 quý + 1 năm). Idempotent.
     */
    public function ensureYear(int $tenantId, int $year): int
    {
        $count = 0;
        for ($m = 1; $m <= 12; $m++) {
            $code = sprintf('%04d-%02d', $year, $m);
            $exists = FiscalPeriod::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('code', $code)->exists();
            if (! $exists) {
                $start = Carbon::create($year, $m, 1);
                FiscalPeriod::query()->create([
                    'tenant_id' => $tenantId,
                    'code' => $code,
                    'kind' => FiscalPeriod::KIND_MONTH,
                    'start_date' => $start->toDateString(),
                    'end_date' => $start->copy()->endOfMonth()->toDateString(),
                    'status' => FiscalPeriod::STATUS_OPEN,
                ]);
                $count++;
            }
        }
        for ($q = 1; $q <= 4; $q++) {
            $code = sprintf('%04d-Q%d', $year, $q);
            $exists = FiscalPeriod::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('code', $code)->exists();
            if (! $exists) {
                $startMonth = ($q - 1) * 3 + 1;
                $start = Carbon::create($year, $startMonth, 1);
                $end = $start->copy()->addMonths(2)->endOfMonth();
                FiscalPeriod::query()->create([
                    'tenant_id' => $tenantId,
                    'code' => $code,
                    'kind' => FiscalPeriod::KIND_QUARTER,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'status' => FiscalPeriod::STATUS_OPEN,
                ]);
                $count++;
            }
        }
        $yearCode = (string) $year;
        $yearExists = FiscalPeriod::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('code', $yearCode)->exists();
        if (! $yearExists) {
            $start = Carbon::create($year, 1, 1);
            FiscalPeriod::query()->create([
                'tenant_id' => $tenantId,
                'code' => $yearCode,
                'kind' => FiscalPeriod::KIND_YEAR,
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->endOfYear()->toDateString(),
                'status' => FiscalPeriod::STATUS_OPEN,
            ]);
            $count++;
        }

        return $count;
    }
}
