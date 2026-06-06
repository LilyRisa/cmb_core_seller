<?php

namespace CMBcoreSeller\Modules\Channels\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Một sự kiện điểm phạt/vi phạm nhận qua webhook sàn (Shopee). Read-mostly: webhook ghi vào,
 * Báo cáo sàn đọc ra để hiện "Cảnh báo gần đây". SPEC 2026-06-06.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $channel_account_id
 * @property string $provider
 * @property string $kind
 * @property int $points
 * @property ?int $violation_type
 * @property ?string $violation_label
 * @property ?int $tier
 * @property ?string $item_id
 * @property ?string $item_name
 * @property ?int $webhook_event_id
 * @property ?Carbon $occurred_at
 * @property ?array<string,mixed> $raw
 */
class ShopPenaltyEvent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'channel_account_id', 'provider', 'kind', 'points',
        'violation_type', 'violation_label', 'tier', 'item_id', 'item_name',
        'webhook_event_id', 'occurred_at', 'raw',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'violation_type' => 'integer',
            'tier' => 'integer',
            'occurred_at' => 'datetime',
            'raw' => 'array',
        ];
    }
}
