<?php

namespace CMBcoreSeller\Modules\Marketing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Wizard draft for one Facebook ad. `payload` holds the per-step state; external
 * ids + status transition at publish time (PublishAdDraft, Plan 4).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $ad_account_id
 * @property ?int $created_by
 * @property ?string $name
 * @property string $status
 * @property ?string $objective
 * @property ?array<string,mixed> $payload
 * @property ?string $idempotency_key
 * @property ?string $campaign_external_id
 * @property ?string $adset_external_id
 * @property ?string $ad_external_id
 * @property ?string $last_error
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class AdDraft extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHING = 'publishing';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_FAILED = 'failed';

    /** @var array<string, mixed> */
    protected $attributes = ['status' => self::STATUS_DRAFT];

    protected $fillable = [
        'tenant_id', 'ad_account_id', 'created_by', 'name', 'status', 'objective', 'payload',
        'idempotency_key', 'campaign_external_id', 'adset_external_id', 'ad_external_id', 'last_error',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
