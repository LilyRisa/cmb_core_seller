<?php

namespace CMBcoreSeller\Modules\Fulfillment\Models;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $paper
 * @property int $paper_w_mm
 * @property int $paper_h_mm
 * @property int $schema_version
 * @property array{fields: array<int, array<string, mixed>>} $schema
 * @property bool $is_default
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class ShippingLabelTemplate extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'paper', 'paper_w_mm', 'paper_h_mm',
        'schema_version', 'schema', 'is_default', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'is_default' => 'boolean',
            'paper_w_mm' => 'integer',
            'paper_h_mm' => 'integer',
            'schema_version' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
