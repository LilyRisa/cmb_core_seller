<?php

namespace CMBcoreSeller\Modules\Fulfillment\Jobs;

use CMBcoreSeller\Modules\Fulfillment\Events\PrintJobCompleted;
use CMBcoreSeller\Modules\Fulfillment\Models\PrintJob;
use CMBcoreSeller\Modules\Fulfillment\Services\PrintService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Renders a print_job's PDF (queue `labels`). See SPEC 0006 §3.3, domain doc rule 3. */
class RenderPrintJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 20;

    public function __construct(public readonly int $printJobId) {}

    public function handle(PrintService $service): void
    {
        $job = PrintJob::withoutGlobalScope(TenantScope::class)->find($this->printJobId);
        if (! $job || $job->status === PrintJob::STATUS_DONE) {
            return;
        }
        $service->render($job);
        PrintJobCompleted::dispatch($job->refresh());
    }
}
