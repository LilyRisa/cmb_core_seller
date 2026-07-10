<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ProTrialGrant extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'granted_at', 'expires_at', 'previous_plan_id',
        'previous_cycle', 'previous_period_end', 'terms_accepted_at',
        'terms_version', 'reverted_at',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
            'previous_period_end' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'reverted_at' => 'datetime',
        ];
    }
}
