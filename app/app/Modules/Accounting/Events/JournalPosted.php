<?php

namespace CMBcoreSeller\Modules\Accounting\Events;

use CMBcoreSeller\Modules\Accounting\Models\JournalEntry;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Phát sau khi {@see \CMBcoreSeller\Modules\Accounting\Services\JournalService::post} ghi
 * entry vào sổ. Các listener khác (Reports cache) có thể bám vào để recompute balances incremental.
 *
 * Phase 7.1 — SPEC 0019.
 */
class JournalPosted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly JournalEntry $entry) {}
}
