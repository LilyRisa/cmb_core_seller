<?php

namespace CMBcoreSeller\Modules\Marketing\Jobs;

use CMBcoreSeller\Modules\Marketing\Services\AdMonitorEvaluator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Evaluate all enabled ad monitors (raise budget / pause by cost-per-result).
 * Scheduled every 30'. ShouldBeUnique guards overlap.
 */
class RunAdMonitors implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 1500;

    public function __construct()
    {
        $this->onQueue('marketing-sync');
    }

    public function uniqueId(): string
    {
        return 'run-ad-monitors';
    }

    public function handle(AdMonitorEvaluator $evaluator): void
    {
        $evaluator->evaluateAll();
    }
}
