<?php

namespace CMBcoreSeller\Modules\Procurement\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Nhà cung cấp (NCC) — Phase 6.1.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $tax_code
 * @property string|null $address
 * @property int $payment_terms_days
 * @property string|null $note
 * @property bool $is_active
 * @property int|null $created_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, SupplierPrice> $prices
 * @property-read Collection<int, PurchaseOrder> $purchaseOrders
 */
class Supplier extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = ['tenant_id', 'code', 'name', 'phone', 'email', 'tax_code', 'address', 'payment_terms_days', 'note', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'payment_terms_days' => 'integer'];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(SupplierPrice::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /** Sinh mã NCC kế tiếp cho tenant (NCC-0001, NCC-0002, …). */
    public static function nextCode(int $tenantId): string
    {
        $n = self::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->withTrashed()->count() + 1;

        return sprintf('NCC-%04d', $n);
    }
}
