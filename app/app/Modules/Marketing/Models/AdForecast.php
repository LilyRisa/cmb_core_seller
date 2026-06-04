<?php

namespace CMBcoreSeller\Modules\Marketing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Cached AI forecast/strategy per ad account (one latest row). On-demand only.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $ad_account_id
 * @property array<string,mixed> $payload
 * @property ?string $provider_code
 * @property ?string $model
 * @property \Illuminate\Support\Carbon $generated_at
 */
class AdForecast extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'ad_account_id', 'payload', 'provider_code', 'model', 'generated_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'generated_at' => 'datetime'];
    }
}
