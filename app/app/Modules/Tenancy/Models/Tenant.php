<?php

namespace CMBcoreSeller\Modules\Tenancy\Models;

use CMBcoreSeller\Models\User;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $code 5-char [a-z0-9] shop code (SPEC 0031)
 * @property string $status
 * @property array<string,mixed>|null $settings
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): Factory
    {
        return TenantFactory::new();
    }

    protected $fillable = ['name', 'slug', 'code', 'status', 'settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (empty($tenant->slug)) {
                $base = Str::slug($tenant->name) ?: 'tenant';
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.(++$i);
                }
                $tenant->slug = $slug;
            }

            // 5-char [a-z0-9] shop code — used to build sub-account usernames. Immutable.
            if (empty($tenant->code)) {
                do {
                    $code = Str::lower(Str::random(5));
                } while (static::where('code', $code)->exists());
                $tenant->code = $code;
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->using(TenantUser::class)
            ->withPivot(['role', 'role_id', 'channel_account_scope'])
            ->withTimestamps();
    }

    /** Custom roles defined in this tenant. */
    public function roles(): HasMany
    {
        return $this->hasMany(TenantRole::class);
    }
}
