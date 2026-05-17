<?php

namespace CMBcoreSeller\Models;

use Database\Factories\AdminUserFactory;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Spec 2026-05-17 — Super-admin tách bảng.
 *
 * Không belongsToMany Tenant. Không lẫn với `users`. Login bằng `username` qua
 * guard `admin_web` (session) ở `/api/v1/admin/auth/login`. Sanctum stateful
 * resolve về guard `admin` cho mọi route `/api/v1/admin/*` (xem config/auth.php
 * và config/sanctum.php).
 */
class AdminUser extends Authenticatable implements CanResetPassword
{
    /** @use HasFactory<AdminUserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** @var list<string> */
    protected $fillable = ['username', 'email', 'name', 'password', 'is_active'];

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
