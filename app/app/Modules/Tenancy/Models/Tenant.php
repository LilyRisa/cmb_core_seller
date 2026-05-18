<?php

namespace CMBcoreSeller\Modules\Tenancy\Models;

use CMBcoreSeller\Models\User;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): Factory
    {
        return TenantFactory::new();
    }

    protected $fillable = ['name', 'slug', 'status', 'settings'];

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
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->using(TenantUser::class)
            ->withPivot(['role', 'channel_account_scope'])
            ->withTimestamps();
    }
}
