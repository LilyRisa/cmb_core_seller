<?php

namespace CMBcoreSeller\Modules\Channels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Short-lived OAuth CSRF state. NOT tenant-scoped via global scope: the OAuth
 * callback has no trusted session, so this row is the link back to the tenant.
 * See docs/05-api/webhooks-and-oauth.md §2 rule 2.
 */
class OAuthState extends Model
{
    protected $table = 'oauth_states';

    protected $fillable = ['state', 'provider', 'tenant_id', 'created_by', 'redirect_after', 'expires_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public static function issue(string $provider, int $tenantId, ?int $userId, ?string $redirectAfter = null, int $ttlMinutes = 10): self
    {
        return static::create([
            'state' => Str::random(48),
            'provider' => $provider,
            'tenant_id' => $tenantId,
            'created_by' => $userId,
            'redirect_after' => $redirectAfter,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
