<?php

namespace CMBcoreSeller\Modules\Customers\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A buyer in the tenant's internal registry, matched across orders by normalized
 * phone hash. `phone`/`email` are encrypted at rest. See SPEC 0002 §5.1.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $phone_hash
 * @property string|null $phone
 * @property string|null $name
 * @property string|null $email
 * @property string|null $email_hash
 * @property array|null $addresses_meta
 * @property array $lifetime_stats
 * @property int $reputation_score
 * @property string $reputation_label
 * @property array $tags
 * @property bool $is_blocked
 * @property Carbon|null $blocked_at
 * @property int|null $blocked_by_user_id
 * @property string|null $block_reason
 * @property string|null $manual_note
 * @property Carbon $first_seen_at
 * @property Carbon $last_seen_at
 * @property int|null $merged_into_customer_id
 * @property Carbon|null $pii_anonymized_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Customer extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const LABEL_OK = 'ok';

    public const LABEL_WATCH = 'watch';

    public const LABEL_RISK = 'risk';

    public const LABEL_BLOCKED = 'blocked';

    protected $fillable = [
        'tenant_id', 'phone_hash', 'phone', 'name', 'email', 'email_hash', 'addresses_meta',
        'lifetime_stats', 'reputation_score', 'reputation_label', 'tags', 'is_blocked',
        'blocked_at', 'blocked_by_user_id', 'block_reason', 'manual_note',
        'first_seen_at', 'last_seen_at', 'merged_into_customer_id', 'pii_anonymized_at',
    ];

    protected $hidden = ['phone', 'email'];

    protected $attributes = [
        'reputation_score' => 100,
        'reputation_label' => self::LABEL_OK,
    ];

    protected function casts(): array
    {
        return [
            'phone' => 'encrypted',
            'email' => 'encrypted',
            'addresses_meta' => 'array',
            'lifetime_stats' => 'array',
            'tags' => 'array',
            'reputation_score' => 'integer',
            'is_blocked' => 'boolean',
            'blocked_at' => 'datetime',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'pii_anonymized_at' => 'datetime',
        ];
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class)->orderByDesc('id');
    }

    public function scopeReputationIn(Builder $q, array $labels): Builder
    {
        return $q->whereIn('reputation_label', $labels);
    }

    public function isAnonymized(): bool
    {
        return $this->pii_anonymized_at !== null;
    }

    /** `09xx xxx 321`-style mask of the stored (encrypted) phone. */
    public function maskedPhone(): ?string
    {
        $p = $this->phone;
        if (! $p) {
            return null;
        }
        $len = strlen($p);

        return $len <= 4 ? str_repeat('*', $len) : substr($p, 0, 3).str_repeat('*', max(0, $len - 5)).substr($p, -3);
    }
}
