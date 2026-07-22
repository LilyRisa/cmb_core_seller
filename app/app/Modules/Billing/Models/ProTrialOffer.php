<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

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
