<?php

namespace CMBcoreSeller\Modules\Marketing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Tenant-scoped saved detailed-targeting set (include/narrow/exclude), reusable
 * across ad drafts.
 *
 * @property int $id
 * @property int $tenant_id
 * @property ?int $created_by
 * @property string $name
 * @property ?array<string,mixed> $payload
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class AudienceTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'created_by', 'name', 'payload'];

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
