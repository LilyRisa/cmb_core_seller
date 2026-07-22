<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property Carbon $offered_at
 * @property Carbon|null $declined_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ProTrialOffer extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'offered_at', 'declined_at'];

    protected function casts(): array
    {
        return [
            'offered_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }
}
