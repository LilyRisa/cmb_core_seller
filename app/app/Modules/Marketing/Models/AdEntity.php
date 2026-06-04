<?php

namespace CMBcoreSeller\Modules\Marketing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Campaign / ad set / ad in an ad account's tree (self-nested via parent_id).
 *
 * @property int $tenant_id
 * @property int $ad_account_id
 * @property string $level
 * @property string $external_id
 * @property ?string $parent_external_id
 * @property ?int $parent_id
 */
class AdEntity extends Model
{
    use BelongsToTenant;

    public const LEVEL_CAMPAIGN = 'campaign';

    public const LEVEL_ADSET = 'adset';

    public const LEVEL_AD = 'ad';

    protected $fillable = [
        'tenant_id', 'ad_account_id', 'level', 'external_id', 'parent_external_id', 'parent_id',
        'name', 'status', 'effective_status', 'daily_budget', 'lifetime_budget', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'daily_budget' => 'integer',
            'lifetime_budget' => 'integer',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<AdAccount, AdEntity> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class, 'ad_account_id');
    }

    /** @return HasMany<AdInsightSnapshot> */
    public function snapshots(): HasMany
    {
        return $this->hasMany(AdInsightSnapshot::class, 'ad_entity_id');
    }
}
