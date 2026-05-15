<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Resources;

use CMBcoreSeller\Modules\Accounting\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin JournalEntry */
class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => $this->code,
            'posted_at' => $this->posted_at?->toIso8601String(),
            'period_id' => $this->period_id,
            'period_code' => $this->whenLoaded('period', fn () => $this->period?->code),
            'narration' => $this->narration,
            'source_module' => $this->source_module,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'idempotency_key' => $this->idempotency_key,
            'is_adjustment' => (bool) $this->is_adjustment,
            'is_reversal_of_id' => $this->is_reversal_of_id,
            'adjusted_period_id' => $this->adjusted_period_id,
            'total_debit' => (int) $this->total_debit,
            'total_credit' => (int) $this->total_credit,
            'currency' => $this->currency,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'is_auto' => $this->source_module !== JournalEntry::SOURCE_MANUAL,
            'lines' => JournalLineResource::collection($this->whenLoaded('lines')),
        ];
    }
}
