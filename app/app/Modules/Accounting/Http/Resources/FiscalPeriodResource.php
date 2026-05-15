<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Resources;

use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FiscalPeriod */
class FiscalPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => $this->code,
            'kind' => $this->kind,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'status_label' => match ($this->status) {
                FiscalPeriod::STATUS_OPEN => 'Đang mở',
                FiscalPeriod::STATUS_CLOSED => 'Đã đóng',
                FiscalPeriod::STATUS_LOCKED => 'Đã khoá',
                default => $this->status,
            },
            'closed_at' => $this->closed_at?->toIso8601String(),
            'closed_by' => $this->closed_by,
            'close_note' => $this->close_note,
        ];
    }
}
