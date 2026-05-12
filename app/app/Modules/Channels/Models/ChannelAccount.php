<?php

namespace CMBcoreSeller\Modules\Channels\Models;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A connected shop of a tenant on a marketplace. Tokens are encrypted at rest.
 * See docs/00-overview/glossary.md ("Channel Account").
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $provider
 * @property string $external_shop_id
 * @property string|null $shop_name
 * @property string|null $shop_region
 * @property string|null $seller_type
 * @property string $status
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property Carbon|null $token_expires_at
 * @property Carbon|null $refresh_token_expires_at
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $last_webhook_at
 * @property array|null $meta
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class ChannelAccount extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';     // token refresh failed — needs reconnect

    public const STATUS_REVOKED = 'revoked';     // seller deauthorized / disconnected (history kept)

    public const STATUS_DISABLED = 'disabled';   // paused by the user

    protected $fillable = [
        'tenant_id', 'provider', 'external_shop_id', 'shop_name', 'shop_region', 'seller_type',
        'status', 'access_token', 'refresh_token', 'token_expires_at', 'refresh_token_expires_at',
        'last_synced_at', 'last_webhook_at', 'meta', 'created_by',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'refresh_token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'last_webhook_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForProvider(Builder $q, string $provider): Builder
    {
        return $q->where('provider', $provider);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** Everything a connector needs to make an authenticated call for this shop. */
    public function authContext(): AuthContext
    {
        return new AuthContext(
            channelAccountId: (int) $this->getKey(),
            provider: $this->provider,
            externalShopId: $this->external_shop_id,
            accessToken: (string) $this->access_token,
            region: $this->shop_region ?: 'VN',
            extra: array_filter([
                'shop_cipher' => $this->meta['shop_cipher'] ?? null,
                'open_id' => $this->meta['open_id'] ?? null,
            ], fn ($v) => $v !== null),
        );
    }

    public function tokenExpiresWithin(\DateInterval|int $window): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }
        $threshold = is_int($window) ? now()->addSeconds($window) : now()->add($window);

        return $this->token_expires_at->lte($threshold);
    }
}
