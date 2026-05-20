<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Cấu hình Messaging cấp tenant (1 row / tenant). PK = tenant_id.
 *
 * `ai_provider_code` — provider tenant chọn (phải ∈ ai_providers active).
 * `away_hours` — khung giờ vắng mặt cho auto-reply schedule (S5 đọc).
 */
class MessagingSetting extends Model
{
    use BelongsToTenant;

    protected $primaryKey = 'tenant_id';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'ai_provider_code', 'ai_enabled', 'auto_mode',
        'away_hours', 'fallback_template_id', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'ai_enabled' => 'boolean',
            'auto_mode' => 'boolean',
            'away_hours' => 'array',
            'settings' => 'array',
        ];
    }
}
