<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Cấu hình Messaging cấp tenant (1 row / tenant). PK = tenant_id.
 *
 * `ai_provider_code` — provider tenant chọn (phải ∈ ai_providers active).
 * `away_hours` — khung giờ vắng mặt cho auto-reply schedule (S5 đọc).
 *
 * AI tự gửi tất cả (ADR-0022) tách theo nhóm kênh: `auto_mode_marketplace`
 * (sàn TMĐT) + `auto_mode_facebook`. `auto_mode` cũ deprecated (xem migration).
 *
 * @property int $tenant_id
 * @property ?string $ai_provider_code
 * @property bool $ai_enabled
 * @property bool $auto_mode
 * @property bool $auto_mode_marketplace
 * @property bool $auto_mode_facebook
 * @property ?array $away_hours
 * @property ?int $fallback_template_id
 * @property ?array $settings
 */
class MessagingSetting extends Model
{
    use BelongsToTenant;

    protected $primaryKey = 'tenant_id';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'ai_provider_code', 'ai_enabled', 'auto_mode',
        'auto_mode_marketplace', 'auto_mode_facebook',
        'away_hours', 'fallback_template_id', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'ai_enabled' => 'boolean',
            'auto_mode' => 'boolean',
            'auto_mode_marketplace' => 'boolean',
            'auto_mode_facebook' => 'boolean',
            'away_hours' => 'array',
            'settings' => 'array',
        ];
    }
}
