<?php

namespace CMBcoreSeller\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Email nhận thông báo cấp nền tảng (SPEC 2026-07-15). KHÔNG tenant-scoped.
 *
 * @property int $id
 * @property string $email
 * @property ?string $label
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int,AdminNotificationSubscription> $subscriptions
 */
class AdminNotificationRecipient extends Model
{
    protected $fillable = ['email', 'label', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(AdminNotificationSubscription::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /** Yêu cầu `subscriptions` đã được load (eager/lazy) trước khi gọi. */
    public function subscribedTo(string $type): bool
    {
        return $this->subscriptions->contains('notification_type', $type);
    }
}
