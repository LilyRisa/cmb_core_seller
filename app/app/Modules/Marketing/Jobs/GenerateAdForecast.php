<?php

namespace CMBcoreSeller\Modules\Marketing\Jobs;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Notifications\MarketingForecastReadyNotification;
use CMBcoreSeller\Modules\Marketing\Services\AdsForecastService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

/**
 * Generate the AI marketing forecast for one ad account (cooldown already gated by
 * the controller ⇒ force), then email it to every Owner/Admin of the tenant.
 */
class GenerateAdForecast implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 900;

    public function __construct(public int $adAccountId)
    {
        $this->onQueue('marketing-ai');
    }

    public function uniqueId(): string
    {
        return "forecast:{$this->adAccountId}";
    }

    public function handle(): void
    {
        /** @var AdAccount|null $account */
        $account = AdAccount::withoutGlobalScope(TenantScope::class)->find($this->adAccountId);
        if (! $account) {
            return;
        }

        $forecast = app(AdsForecastService::class)->generate($account, true);

        /** @var Tenant|null $tenant */
        $tenant = Tenant::find($account->tenant_id);
        if (! $tenant) {
            return;
        }
        $recipients = $tenant->users()->wherePivotIn('role', [Role::Owner->value, Role::Admin->value])->get();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new MarketingForecastReadyNotification($account, $forecast));
        }
    }
}
