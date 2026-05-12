<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A stock location. Exactly one warehouse per tenant is `is_default` — used by
 * Phase-2 stock effects when no explicit warehouse is chosen. See SPEC 0003 §5.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $code
 * @property array|null $address
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Warehouse extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'code', 'address', 'is_default'];

    protected function casts(): array
    {
        return ['address' => 'array', 'is_default' => 'boolean'];
    }

    /** The tenant's default warehouse — created lazily if none exists yet. */
    public static function defaultFor(int $tenantId): self
    {
        $existing = self::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_default')->orderBy('id')->first();
        if ($existing) {
            return $existing;
        }

        return self::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenantId, 'name' => 'Kho mặc định', 'code' => 'MAIN', 'is_default' => true,
        ]);
    }
}
