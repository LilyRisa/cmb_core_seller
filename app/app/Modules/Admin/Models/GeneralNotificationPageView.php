<?php

namespace CMBcoreSeller\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Plan C (2026-07-23) — 1 lượt xem trang "Chung" của 1 user (unique per page+user, idempotent).
 *
 * @property int $id
 * @property int $page_id
 * @property int $tenant_id
 * @property int $user_id
 * @property Carbon $viewed_at
 */
class GeneralNotificationPageView extends Model
{
    protected $fillable = ['page_id', 'tenant_id', 'user_id', 'viewed_at'];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime'];
    }
}
