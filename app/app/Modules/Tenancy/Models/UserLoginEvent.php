<?php

namespace CMBcoreSeller\Modules\Tenancy\Models;

use Carbon\Carbon;
use CMBcoreSeller\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 lần đăng nhập thành công của user (guard `web`) — xem `LogUserLogin`.
 *
 * @property int $id
 * @property int $user_id
 * @property ?string $ip_address
 * @property ?string $user_agent
 * @property Carbon $logged_in_at
 * @property-read User|null $user
 */
class UserLoginEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'ip_address', 'user_agent', 'logged_in_at'];

    protected function casts(): array
    {
        return ['logged_in_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
