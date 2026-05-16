<?php

namespace CMBcoreSeller\Models;

use CMBcoreSeller\Modules\Notifications\Notifications\ResetPasswordNotification;
use CMBcoreSeller\Modules\Notifications\Notifications\VerifyEmailNotification;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements CanResetPassword, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_super_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    /** SPEC 0020 — super-admin hệ thống (xuyên tenant). Promote bằng `php artisan admin:promote`. */
    public function isSuperAdmin(): bool
    {
        return (bool) ($this->is_super_admin ?? false);
    }

    /** Tenants this user is a member of. */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')
            ->withPivot(['role', 'channel_account_scope'])
            ->withTimestamps();
    }

    /**
     * Override Laravel default — dùng notification class branded `CMBcoreSeller`
     * (SPEC 0022). Notification implement ShouldQueue ⇒ tự enqueue vào queue
     * `notifications`.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /** Override Laravel default — dùng template Blade branded thay vì plain text. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
