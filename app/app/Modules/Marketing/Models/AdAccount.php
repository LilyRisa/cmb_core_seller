<?php

namespace CMBcoreSeller\Modules\Marketing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A connected Facebook ad account (act_<id>) with its OWN ads_read token
 * (separate from page/messaging tokens). SPEC 2026-06-04.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $provider
 * @property ?string $business_id
 * @property ?string $business_name
 * @property ?string $business_picture_url
 * @property ?int $fb_account_status
 * @property ?int $disable_reason
 * @property ?Carbon $health_checked_at
 * @property string $external_account_id
 * @property ?string $name
 * @property ?string $currency
 * @property string $status
 * @property ?string $access_token
 * @property ?string $refresh_token
 * @property ?Carbon $token_expires_at
 * @property ?Carbon $last_synced_at
 * @property ?Carbon $insights_synced_at
 * @property ?array<string,mixed> $meta
 * @property ?int $created_by
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class AdAccount extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'tenant_id', 'provider', 'business_id', 'business_name', 'business_picture_url', 'external_account_id', 'name', 'currency', 'status',
        'fb_account_status', 'disable_reason', 'health_checked_at',
        'access_token', 'refresh_token', 'token_expires_at', 'last_synced_at', 'insights_synced_at',
        'meta', 'created_by',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'insights_synced_at' => 'datetime',
            'health_checked_at' => 'datetime',
            'fb_account_status' => 'integer',
            'disable_reason' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * The SAME Facebook ad account can be connected by more than one tenant (two
     * shops both granted access). To avoid conflicting/compounding automation, ONE
     * connection is the "automation owner" (the earliest = smallest id); only that
     * one runs auto-monitors / writes. Soft-deleted (disconnected) rows don't count.
     */
    public function isAutomationOwner(): bool
    {
        // Explicit owner (after a take-over) wins — as long as that connection is
        // still active; otherwise fall back to the earliest active connection.
        $record = AdAccountAutomation::query()
            ->where('provider', $this->provider)
            ->where('external_account_id', $this->external_account_id)
            ->first();
        if ($record !== null) {
            $ownerActive = static::withoutGlobalScope(TenantScope::class)->whereKey($record->owner_ad_account_id)->exists();
            if ($ownerActive) {
                return (int) $record->owner_ad_account_id === (int) $this->getKey();
            }
        }

        $ownerId = static::withoutGlobalScope(TenantScope::class)
            ->where('provider', $this->provider)
            ->where('external_account_id', $this->external_account_id)
            ->orderBy('id')
            ->value('id');

        return $ownerId === null || (int) $ownerId === (int) $this->getKey();
    }

    /** Transfer automation/write ownership of this FB account to this connection. */
    public function claimAutomation(): void
    {
        AdAccountAutomation::query()->updateOrCreate(
            ['provider' => $this->provider, 'external_account_id' => $this->external_account_id],
            ['owner_ad_account_id' => (int) $this->getKey(), 'owner_tenant_id' => (int) $this->tenant_id],
        );
    }

    /** 403 unless this connection owns automation/writes for the FB account. */
    public function assertAutomationOwner(): void
    {
        abort_unless(
            $this->isAutomationOwner(),
            403,
            'Tài khoản này đang được shop khác sở hữu tự động hoá/chỉnh sửa. Hãy "Tiếp quản quyền" trước khi sửa hoặc xuất bản.',
        );
    }

    /** True if another tenant has this same FB ad account connected. */
    public function sharedWithOtherTenants(): bool
    {
        return static::withoutGlobalScope(TenantScope::class)
            ->where('provider', $this->provider)
            ->where('external_account_id', $this->external_account_id)
            ->where('tenant_id', '!=', $this->tenant_id)
            ->exists();
    }

    /** @return HasMany<AdEntity, $this> */
    public function entities(): HasMany
    {
        return $this->hasMany(AdEntity::class);
    }
}
