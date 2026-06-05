<?php

namespace CMBcoreSeller\Modules\Marketing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Cached AI analysis for one campaign (one latest row per campaign). On-demand only.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $ad_account_id
 * @property string $campaign_external_id
 * @property array<string,mixed> $payload
 * @property array<string,mixed> $params
 * @property ?string $provider_code
 * @property ?string $model
 * @property Carbon $generated_at
 */
class CampaignAiInsight extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'ad_account_id', 'campaign_external_id', 'payload', 'params', 'provider_code', 'model', 'generated_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'params' => 'array', 'generated_at' => 'datetime'];
    }
}
