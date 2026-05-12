<?php

namespace CMBcoreSeller\Modules\Fulfillment\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A tenant's credentials for one shipping carrier (GHN, GHTK, J&T, …). `manual`
 * is the built-in "I manage tracking myself" carrier — needs no credentials.
 * `credentials` is encrypted at rest. See SPEC 0006 §5.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $carrier
 * @property string $name
 * @property array|null $credentials
 * @property string|null $default_service
 * @property bool $is_default
 * @property bool $is_active
 * @property array|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CarrierAccount extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'carrier', 'name', 'credentials', 'default_service', 'is_default', 'is_active', 'meta'];

    protected function casts(): array
    {
        return ['credentials' => 'encrypted:array', 'meta' => 'array', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    /** Shape passed to CarrierConnector methods (the `$account` array argument). */
    public function toConnectorArray(): array
    {
        return [
            'id' => $this->id,
            'carrier' => $this->carrier,
            'credentials' => $this->credentials ?? [],
            'default_service' => $this->default_service,
            'meta' => $this->meta ?? [],
        ];
    }
}
