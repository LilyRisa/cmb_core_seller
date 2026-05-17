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

/**
 * Tenant user (seller / staff member).
 *
 * Spec 2026-05-17 — super-admin đã tách sang `admin_users`; cờ `is_super_admin`
 * cũ đã bị drop. Cột `suspended_at` cho phép super-admin tạm khoá user (block
 * truy cập tenant ở EnsureTenant middleware).
 */
class User extends Authenticatable implements CanResetPassword, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'suspended_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
        ];
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
