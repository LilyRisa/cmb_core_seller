<?php

namespace CMBcoreSeller\Modules\Marketing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A connected Facebook ad account (act_<id>) with its OWN ads_read token
 * (separate from page/messaging tokens). SPEC 2026-06-04.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $provider
 * @property string $external_account_id
 * @property ?string $name
 * @property ?string $currency
 * @property string $status
 * @property ?string $access_token
 * @property ?string $refresh_token
 * @property ?Carbon $token_expires_at
 * @property ?Carbon $last_synced_at
 * @property ?Carbon $insights_synced_at
 * @property ?array<string,mixed> $meta
 * @property ?int $created_by
 */
class AdAccount extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'tenant_id', 'provider', 'external_account_id', 'name', 'currency', 'status',
        'access_token', 'refresh_token', 'token_expires_at', 'last_synced_at', 'insights_synced_at',
        'meta', 'created_by',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'insights_synced_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /** @return HasMany<AdEntity, $this> */
    public function entities(): HasMany
    {
        return $this->hasMany(AdEntity::class);
    }
}
