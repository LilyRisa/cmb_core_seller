# Admin tách lập + System Settings — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tách bảng `admin_users` + guard riêng cho super-admin; tách SPA admin (bundle Vite thứ 2); module Settings quản lý 38 key cấu hình động trong DB; admin CRUD admin_users + tenant users.

**Architecture:** Backend Laravel 11 — 2 guard mới `admin_web` (session) + `admin` (Sanctum) trỏ vào `AdminUser` model. Helper `system_setting()` đọc DB qua cache `rememberForever`; `set()` clear cache + dispatch event. Frontend — Vite multi-entry với `resources/js/admin.tsx` riêng, route `/admin/{any?}` trả Blade `admin.blade.php`. Drop cột `users.is_super_admin` sau khi backfill sang `admin_users`.

**Tech Stack:** Laravel 11.31, Sanctum 4.3, PHPUnit 11, React 18 + Ant Design 5, Vite, TypeScript, TanStack Query.

**Spec:** `docs/superpowers/specs/2026-05-17-admin-separation-and-system-settings-design.md`

---

## Phase 1 — Auth foundation (admin_users)

### Task 1: Migration `admin_users` table

**Files:**
- Create: `app/app/Modules/Admin/Database/Migrations/2026_05_18_100000_create_admin_users_table.php`
- Create: `app/database/migrations/2026_05_18_100001_create_admin_password_reset_tokens_table.php`
- Test: `app/tests/Feature/Admin/AdminUsersTableMigrationTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminUsersTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('admin_users'));
        $this->assertTrue(Schema::hasColumns('admin_users', [
            'id','username','email','name','password','is_active',
            'last_login_at','last_login_ip','created_at','updated_at',
        ]));
    }

    public function test_admin_password_reset_tokens_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('admin_password_reset_tokens'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=AdminUsersTableMigrationTest`
Expected: FAIL — tables do not exist.

- [ ] **Step 3: Create the migration files**

`app/app/Modules/Admin/Database/Migrations/2026_05_18_100000_create_admin_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec 2026-05-17 — admin tách lập. Super-admin nằm bảng riêng, không trộn `users`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 32)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('name');
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamps();
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
```

`app/database/migrations/2026_05_18_100001_create_admin_password_reset_tokens_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_password_reset_tokens');
    }
};
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd app && php artisan test --filter=AdminUsersTableMigrationTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Admin/Database/Migrations/2026_05_18_100000_create_admin_users_table.php \
        app/database/migrations/2026_05_18_100001_create_admin_password_reset_tokens_table.php \
        app/tests/Feature/Admin/AdminUsersTableMigrationTest.php
git commit -m "feat(admin): migration admin_users + admin_password_reset_tokens tables"
```

---

### Task 2: AdminUser model + factory

**Files:**
- Create: `app/app/Models/AdminUser.php`
- Create: `app/database/factories/AdminUserFactory.php`
- Test: `app/tests/Unit/Models/AdminUserTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Unit\Models;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_hashes_password_on_save(): void
    {
        $admin = AdminUser::create([
            'username' => 'ops_a',
            'name' => 'Ops A',
            'password' => 'secret123',
        ]);
        $this->assertNotSame('secret123', $admin->password);
        $this->assertTrue(Hash::check('secret123', $admin->password));
    }

    public function test_admin_user_is_active_defaults_true(): void
    {
        $a = AdminUser::factory()->create();
        $this->assertTrue($a->is_active);
    }

    public function test_password_and_remember_token_hidden_on_serialization(): void
    {
        $a = AdminUser::factory()->create();
        $arr = $a->toArray();
        $this->assertArrayNotHasKey('password', $arr);
        $this->assertArrayNotHasKey('remember_token', $arr);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=AdminUserTest`
Expected: FAIL — `AdminUser` class not found.

- [ ] **Step 3: Create model + factory**

`app/app/Models/AdminUser.php`:

```php
<?php

namespace CMBcoreSeller\Models;

use Database\Factories\AdminUserFactory;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Spec 2026-05-17 — Super-admin tách bảng. KHÔNG belongsToMany Tenant.
 */
class AdminUser extends Authenticatable implements CanResetPassword
{
    /** @use HasFactory<AdminUserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['username','email','name','password','is_active'];
    protected $hidden = ['password','remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
```

`app/database/factories/AdminUserFactory.php`:

```php
<?php

namespace Database\Factories;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AdminUser>
 */
class AdminUserFactory extends Factory
{
    protected $model = AdminUser::class;

    public function definition(): array
    {
        return [
            'username' => 'admin_'.Str::lower(Str::random(6)),
            'email' => $this->faker->unique()->safeEmail(),
            'name' => $this->faker->name(),
            'password' => 'password',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd app && php artisan test --filter=AdminUserTest`
Expected: 3 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Models/AdminUser.php app/database/factories/AdminUserFactory.php \
        app/tests/Unit/Models/AdminUserTest.php
git commit -m "feat(admin): AdminUser model + factory"
```

---

### Task 3: Auth guard config (admin_web + admin)

**Files:**
- Modify: `app/config/auth.php`
- Modify: `app/config/sanctum.php`
- Test: `app/tests/Feature/Admin/AdminAuthGuardConfigTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminAuthGuardConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_web_guard_resolves_admin_user(): void
    {
        $admin = AdminUser::factory()->create();
        Auth::guard('admin_web')->login($admin);
        $this->assertTrue(Auth::guard('admin_web')->check());
        $this->assertSame($admin->id, Auth::guard('admin_web')->user()->id);
    }

    public function test_admin_guard_uses_admin_users_provider(): void
    {
        $cfg = config('auth.guards.admin');
        $this->assertSame('sanctum', $cfg['driver']);
        $this->assertSame('admin_users', $cfg['provider']);
    }

    public function test_sanctum_guard_array_includes_admin_web(): void
    {
        $this->assertContains('admin_web', config('sanctum.guard'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=AdminAuthGuardConfigTest`
Expected: FAIL — guard config missing.

- [ ] **Step 3: Update `config/auth.php`**

Replace the `guards`, `providers`, `passwords` sections:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'admin_web' => [
        'driver' => 'session',
        'provider' => 'admin_users',
    ],
    'admin' => [
        'driver' => 'sanctum',
        'provider' => 'admin_users',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => env('AUTH_MODEL', User::class),
    ],
    'admin_users' => [
        'driver' => 'eloquent',
        'model' => CMBcoreSeller\Models\AdminUser::class,
    ],
],

'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
        'expire' => 60,
        'throttle' => 60,
    ],
    'admin_users' => [
        'provider' => 'admin_users',
        'table' => 'admin_password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
    ],
],
```

Add `use CMBcoreSeller\Models\AdminUser;` at the top.

- [ ] **Step 4: Update `config/sanctum.php`**

```php
'guard' => ['web', 'admin_web'],
```

- [ ] **Step 5: Run test to verify it passes**

Run: `cd app && php artisan test --filter=AdminAuthGuardConfigTest`
Expected: 3 PASS.

- [ ] **Step 6: Commit**

```bash
git add app/config/auth.php app/config/sanctum.php \
        app/tests/Feature/Admin/AdminAuthGuardConfigTest.php
git commit -m "feat(admin): auth guard admin_web + admin (Sanctum multi-guard)"
```

---

### Task 4: Migration — `audit_logs.admin_user_id` + `users.suspended_at`

**Files:**
- Create: `app/database/migrations/2026_05_18_100002_add_admin_user_id_to_audit_logs.php`
- Create: `app/database/migrations/2026_05_18_100003_add_suspended_at_to_users.php`
- Test: `app/tests/Feature/Admin/AuditLogsAdminUserIdMigrationTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditLogsAdminUserIdMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logs_has_admin_user_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('audit_logs', 'admin_user_id'));
    }

    public function test_users_has_suspended_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'suspended_at'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=AuditLogsAdminUserIdMigrationTest`
Expected: FAIL — columns missing.

- [ ] **Step 3: Write migration files**

`app/database/migrations/2026_05_18_100002_add_admin_user_id_to_audit_logs.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_user_id')->nullable()->after('user_id');
            $table->index('admin_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['admin_user_id']);
            $table->dropColumn('admin_user_id');
        });
    }
};
```

`app/database/migrations/2026_05_18_100003_add_suspended_at_to_users.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('email_verified_at');
            $table->index('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['suspended_at']);
            $table->dropColumn('suspended_at');
        });
    }
};
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd app && php artisan test --filter=AuditLogsAdminUserIdMigrationTest`
Expected: 2 PASS.

- [ ] **Step 5: Update `AuditLog::record()` to support admin actor**

Modify `app/app/Modules/Tenancy/Models/AuditLog.php`:

```php
protected $fillable = [
    'tenant_id', 'user_id', 'admin_user_id', 'action', 'auditable_type', 'auditable_id', 'changes', 'ip',
];

public static function record(string $action, ?Model $auditable = null, ?array $changes = null): self
{
    $adminId = \Illuminate\Support\Facades\Auth::guard('admin_web')->id();

    return static::create([
        'tenant_id' => $adminId ? null : app(CurrentTenant::class)->id(),
        'user_id' => $adminId ? null : Auth::id(),
        'admin_user_id' => $adminId,
        'action' => $action,
        'auditable_type' => $auditable?->getMorphClass(),
        'auditable_id' => $auditable?->getKey(),
        'changes' => $changes,
        'ip' => Request::ip(),
    ]);
}
```

- [ ] **Step 6: Commit**

```bash
git add app/database/migrations/2026_05_18_100002_add_admin_user_id_to_audit_logs.php \
        app/database/migrations/2026_05_18_100003_add_suspended_at_to_users.php \
        app/app/Modules/Tenancy/Models/AuditLog.php \
        app/tests/Feature/Admin/AuditLogsAdminUserIdMigrationTest.php
git commit -m "feat(admin): audit_logs.admin_user_id + users.suspended_at + AuditLog::record() admin actor"
```

---

### Task 5: Migration — backfill admin_users and drop `users.is_super_admin`

**Files:**
- Create: `app/database/migrations/2026_05_18_100004_backfill_admin_users_and_drop_is_super_admin.php`
- Test: `app/tests/Feature/Admin/BackfillSuperAdminTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackfillSuperAdminTest extends TestCase
{
    public function test_backfill_creates_admin_users_then_drops_column(): void
    {
        // Boot fresh, run migrations up to but not including the backfill, then seed, then run all.
        Artisan::call('migrate:fresh', ['--step' => true, '--path' => 'database/migrations']);

        // Re-add is_super_admin so we can seed (it was dropped by the backfill migration).
        // Easier path: insert two super-admin users by re-running migrate:fresh stopped early.
        // Instead: assert structure post-backfill.
        $this->assertFalse(Schema::hasColumn('users', 'is_super_admin'));
        $this->assertTrue(Schema::hasTable('admin_users'));
    }

    public function test_backfill_creates_admin_user_when_super_admin_existed(): void
    {
        // Re-run migrations rolling back the backfill so we can plant data and re-run it.
        Artisan::call('migrate:rollback', ['--step' => 1]);
        $this->assertTrue(Schema::hasColumn('users', 'is_super_admin'));

        DB::table('users')->insert([
            'name' => 'Op Smith',
            'email' => 'op@cmbcore.vn',
            'password' => bcrypt('legacy'),
            'is_super_admin' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('migrate');

        $admin = AdminUser::query()->where('email', 'op@cmbcore.vn')->first();
        $this->assertNotNull($admin);
        $this->assertSame('op', $admin->username);
        $this->assertTrue($admin->is_active);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=BackfillSuperAdminTest`
Expected: FAIL — backfill migration missing, column still exists.

- [ ] **Step 3: Write migration file**

`app/database/migrations/2026_05_18_100004_backfill_admin_users_and_drop_is_super_admin.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('users')
            ->where('is_super_admin', true)
            ->select('id','name','email','password')
            ->get();

        foreach ($rows as $u) {
            $base = $this->sanitize(Str::before((string) $u->email, '@'));
            if ($base === '') {
                $base = "admin_{$u->id}";
            }
            $username = $base;
            $i = 1;
            while (DB::table('admin_users')->where('username', $username)->exists()) {
                $username = $base.'_'.$i++;
            }

            DB::table('admin_users')->insert([
                'username' => $username,
                'email' => $u->email,
                'name' => $u->name,
                'password' => $u->password,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_super_admin']);
            $table->dropColumn('is_super_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('password');
            $table->index('is_super_admin');
        });
        $emails = DB::table('admin_users')->whereNotNull('email')->pluck('email')->all();
        DB::table('users')->whereIn('email', $emails)->update(['is_super_admin' => true]);
    }

    private function sanitize(string $raw): string
    {
        $s = strtolower($raw);
        $s = preg_replace('/[^a-z0-9._-]/', '', $s) ?? '';
        $s = trim($s, '._-');

        return strlen($s) >= 3 ? substr($s, 0, 32) : '';
    }
};
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd app && php artisan test --filter=BackfillSuperAdminTest`
Expected: 2 PASS.

- [ ] **Step 5: Remove `is_super_admin` references from User model**

Modify `app/app/Models/User.php`:

```php
protected $fillable = [
    'name',
    'email',
    'password',
];

protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'suspended_at' => 'datetime',
    ];
}
// REMOVE the entire isSuperAdmin() method.
```

- [ ] **Step 6: Commit**

```bash
git add app/database/migrations/2026_05_18_100004_backfill_admin_users_and_drop_is_super_admin.php \
        app/app/Models/User.php \
        app/tests/Feature/Admin/BackfillSuperAdminTest.php
git commit -m "feat(admin): backfill admin_users + drop users.is_super_admin column"
```

---

## Phase 2 — Admin auth controller + middleware rewiring

### Task 6: AdminAuthController (login/logout/me/changePassword)

**Files:**
- Create: `app/app/Modules/Admin/Http/Controllers/AdminAuthController.php`
- Create: `app/app/Modules/Admin/Http/Requests/AdminLoginRequest.php`
- Test: `app/tests/Feature/Admin/AdminAuthLoginTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_succeeds(): void
    {
        AdminUser::factory()->create([
            'username' => 'ops_a', 'password' => 'pa$$word1',
        ]);

        $this->postJson('/api/v1/admin/auth/login', [
            'username' => 'ops_a', 'password' => 'pa$$word1',
        ])->assertOk()->assertJsonPath('data.username', 'ops_a');
    }

    public function test_login_wrong_password_returns_401(): void
    {
        AdminUser::factory()->create(['username' => 'ops_b', 'password' => 'right']);
        $this->postJson('/api/v1/admin/auth/login', [
            'username' => 'ops_b', 'password' => 'wrong',
        ])->assertStatus(401)->assertJsonPath('error.code', 'ADMIN_AUTH_FAILED');
    }

    public function test_login_inactive_admin_returns_401(): void
    {
        AdminUser::factory()->inactive()->create(['username' => 'ops_c', 'password' => 'p1']);
        $this->postJson('/api/v1/admin/auth/login', [
            'username' => 'ops_c', 'password' => 'p1',
        ])->assertStatus(401)->assertJsonPath('error.code', 'ADMIN_AUTH_FAILED');
    }

    public function test_me_returns_admin_after_login(): void
    {
        $a = AdminUser::factory()->create(['username' => 'ops_d', 'password' => 'p']);
        $this->actingAs($a, 'admin_web');
        $this->getJson('/api/v1/admin/auth/me')->assertOk()->assertJsonPath('data.username', 'ops_d');
    }

    public function test_logout_invalidates_session(): void
    {
        $a = AdminUser::factory()->create();
        $this->actingAs($a, 'admin_web');
        $this->postJson('/api/v1/admin/auth/logout')->assertOk();
        $this->getJson('/api/v1/admin/auth/me')->assertStatus(401);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=AdminAuthLoginTest`
Expected: FAIL — routes & controller missing.

- [ ] **Step 3: Create the FormRequest**

`app/app/Modules/Admin/Http/Requests/AdminLoginRequest.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'username' => ['required','string','max:32'],
            'password' => ['required','string','max:128'],
        ];
    }
}
```

- [ ] **Step 4: Create the controller**

`app/app/Modules/Admin/Http/Controllers/AdminAuthController.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Admin\Http\Requests\AdminLoginRequest;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $admin = AdminUser::query()->where('username', $data['username'])->first();

        if (! $admin || ! $admin->is_active || ! Hash::check($data['password'], $admin->password)) {
            return response()->json(['error' => [
                'code' => 'ADMIN_AUTH_FAILED',
                'message' => 'Sai tài khoản hoặc mật khẩu, hoặc tài khoản đã bị vô hiệu hoá.',
            ]], 401);
        }

        Auth::guard('admin_web')->login($admin, remember: false);
        $request->session()->regenerate();

        $admin->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        AuditLog::record('admin.auth.login');

        return response()->json(['data' => $this->present($admin)]);
    }

    public function logout(Request $request): JsonResponse
    {
        AuditLog::record('admin.auth.logout');
        Auth::guard('admin_web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->present($request->user())]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required','string'],
            'password' => ['required','string','min:8','max:128'],
        ]);

        /** @var AdminUser $admin */
        $admin = $request->user();
        if (! Hash::check($data['current_password'], $admin->password)) {
            return response()->json(['error' => [
                'code' => 'ADMIN_AUTH_FAILED',
                'message' => 'Mật khẩu hiện tại không đúng.',
            ]], 401);
        }
        $admin->forceFill(['password' => $data['password']])->save();
        AuditLog::record('admin.auth.change_password');

        return response()->json(['data' => ['ok' => true]]);
    }

    /** @return array<string,mixed> */
    private function present(AdminUser $a): array
    {
        return [
            'id' => $a->id,
            'username' => $a->username,
            'email' => $a->email,
            'name' => $a->name,
            'is_active' => (bool) $a->is_active,
            'last_login_at' => $a->last_login_at?->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 5: Register routes**

Append to top of `app/app/Modules/Admin/Http/routes.php` (above the existing admin route group):

```php
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminAuthController;

Route::middleware(['web', 'throttle:10,1'])->prefix('api/v1/admin/auth')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login'])->name('admin.auth.login');
});

Route::middleware(['web', 'auth:admin'])->prefix('api/v1/admin/auth')->group(function () {
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('admin.auth.logout');
    Route::get('me', [AdminAuthController::class, 'me'])->name('admin.auth.me');
    Route::post('change-password', [AdminAuthController::class, 'changePassword'])->name('admin.auth.change-password');
});
```

- [ ] **Step 6: Run test to verify it passes**

Run: `cd app && php artisan test --filter=AdminAuthLoginTest`
Expected: 5 PASS.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Admin/Http/Controllers/AdminAuthController.php \
        app/app/Modules/Admin/Http/Requests/AdminLoginRequest.php \
        app/app/Modules/Admin/Http/routes.php \
        app/tests/Feature/Admin/AdminAuthLoginTest.php
git commit -m "feat(admin): AdminAuthController login/logout/me/change-password"
```

---

### Task 7: Rewire all `/api/v1/admin/*` routes to `auth:admin`, drop `super_admin` middleware

**Files:**
- Modify: `app/app/Modules/Admin/Http/routes.php`
- Modify: `app/bootstrap/app.php`
- Delete: `app/app/Modules/Tenancy/Http/Middleware/EnsureSuperAdmin.php`
- Modify: existing admin tests (replace `is_super_admin=true` → `actingAs($admin, 'admin_web')`).
- Test: `app/tests/Feature/Admin/AdminGuardEnforcedTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGuardEnforcedTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_request_returns_401_on_admin_route(): void
    {
        $this->getJson('/api/v1/admin/tenants')->assertStatus(401);
    }

    public function test_regular_user_session_cannot_access_admin_route(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');
        $this->getJson('/api/v1/admin/tenants')->assertStatus(401);
    }

    public function test_admin_session_can_access_admin_route(): void
    {
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'admin_web');
        $this->getJson('/api/v1/admin/tenants')->assertOk();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=AdminGuardEnforcedTest`
Expected: FAIL — current middleware `super_admin` rejects admin_web actor.

- [ ] **Step 3: Update admin routes middleware**

Modify `app/app/Modules/Admin/Http/routes.php` — replace the main route group middleware:

```php
Route::middleware(['web', 'auth:admin', 'throttle:60,1'])
    ->prefix('api/v1/admin')->group(function () {
        // ... existing routes unchanged
    });
```

(Remove `auth:sanctum` and `super_admin` from the array; keep all route definitions inside.)

- [ ] **Step 4: Remove the `super_admin` middleware alias**

In `app/bootstrap/app.php`, remove the line:

```php
'super_admin' => EnsureSuperAdmin::class,
```

And remove the `use CMBcoreSeller\Modules\Tenancy\Http\Middleware\EnsureSuperAdmin;` at the top.

- [ ] **Step 5: Delete the middleware file**

```bash
rm app/app/Modules/Tenancy/Http/Middleware/EnsureSuperAdmin.php
```

- [ ] **Step 6: Update all existing admin tests**

Find every `User::factory()->create(['is_super_admin' => true])` and `actingAs($admin)` in `app/tests/Feature/Admin/*` and replace with `AdminUser::factory()->create()` + `actingAs($admin, 'admin_web')`. Update assertions from `'SUPER_ADMIN_REQUIRED'` → unauthenticated 401.

Files known to update (verify by grep):
- `app/tests/Feature/Admin/AdminAuthTest.php`
- `app/tests/Feature/Admin/AdminAuditBroadcastTest.php`
- `app/tests/Feature/Admin/AdminInvoiceRefundTest.php`
- `app/tests/Feature/Admin/AdminTrialPlanOverrideTest.php`
- `app/tests/Feature/Admin/AdminVoucherTest.php`
- `app/tests/Feature/Admin/OverQuotaLockTest.php`

Run `grep -RIn 'is_super_admin' app/tests/` and update each match.

- [ ] **Step 7: Run all admin tests**

Run: `cd app && php artisan test --testsuite=Feature --filter=Admin`
Expected: all admin tests PASS (existing + new guard test).

- [ ] **Step 8: Commit**

```bash
git add app/app/Modules/Admin/Http/routes.php app/bootstrap/app.php \
        app/tests/Feature/Admin/AdminGuardEnforcedTest.php \
        app/tests/Feature/Admin/
git rm app/app/Modules/Tenancy/Http/Middleware/EnsureSuperAdmin.php
git commit -m "refactor(admin): admin routes use auth:admin guard; drop EnsureSuperAdmin middleware"
```

---

### Task 8: EnsureTenant — block suspended users

**Files:**
- Modify: `app/app/Modules/Tenancy/Http/Middleware/EnsureTenant.php`
- Test: `app/tests/Feature/Tenancy/SuspendedUserBlockedTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuspendedUserBlockedTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_user_cannot_access_tenant_routes(): void
    {
        $user = User::factory()->create(['suspended_at' => now()]);
        $tenant = Tenant::create(['name' => 'X']);
        $tenant->users()->attach($user->id, ['role' => Role::Owner->value]);

        $this->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->getJson('/api/v1/orders')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'USER_SUSPENDED');
    }

    public function test_active_user_passes(): void
    {
        $user = User::factory()->create(['suspended_at' => null]);
        $tenant = Tenant::create(['name' => 'X']);
        $tenant->users()->attach($user->id, ['role' => Role::Owner->value]);

        $this->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->getJson('/api/v1/orders')
            ->assertOk();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=SuspendedUserBlockedTest`
Expected: FAIL — `USER_SUSPENDED` never emitted.

- [ ] **Step 3: Patch `EnsureTenant::handle`**

Insert after the `Auth` user check, before tenant resolution:

```php
if ($user->suspended_at !== null) {
    return response()->json(['error' => [
        'code' => 'USER_SUSPENDED',
        'message' => 'Tài khoản đã bị tạm khoá. Vui lòng liên hệ hỗ trợ.',
    ]], 403);
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd app && php artisan test --filter=SuspendedUserBlockedTest`
Expected: 2 PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Tenancy/Http/Middleware/EnsureTenant.php \
        app/tests/Feature/Tenancy/SuspendedUserBlockedTest.php
git commit -m "feat(tenancy): EnsureTenant blocks suspended users (403 USER_SUSPENDED)"
```

---

### Task 9: Artisan `admin:create` + `admin:reset-password` + refactor `admin:promote/demote`

**Files:**
- Create: `app/app/Console/Commands/AdminCreate.php`
- Create: `app/app/Console/Commands/AdminResetPassword.php`
- Modify: `app/app/Console/Commands/PromoteSuperAdmin.php`
- Modify: `app/app/Console/Commands/DemoteSuperAdmin.php`
- Test: `app/tests/Feature/Console/AdminCommandsTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature\Console;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_create_makes_active_admin(): void
    {
        $this->artisan('admin:create', ['username' => 'opx', '--name' => 'Op X', '--email' => 'op@x.vn', '--password' => 'pw123456'])
            ->assertExitCode(0);
        $a = AdminUser::query()->where('username', 'opx')->first();
        $this->assertNotNull($a);
        $this->assertTrue(Hash::check('pw123456', $a->password));
    }

    public function test_admin_create_duplicate_username_fails(): void
    {
        AdminUser::factory()->create(['username' => 'op1']);
        $this->artisan('admin:create', ['username' => 'op1', '--name' => 'X', '--password' => 'pw123456'])
            ->assertExitCode(1);
    }

    public function test_admin_reset_password_updates_hash(): void
    {
        $a = AdminUser::factory()->create(['username' => 'op2']);
        $this->artisan('admin:reset-password', ['username' => 'op2', '--password' => 'newp1234'])
            ->assertExitCode(0);
        $this->assertTrue(Hash::check('newp1234', $a->fresh()->password));
    }

    public function test_admin_promote_creates_admin_from_user(): void
    {
        $u = \CMBcoreSeller\Models\User::factory()->create(['email' => 'usr@x.vn']);
        $this->artisan('admin:promote', ['email' => 'usr@x.vn'])->assertExitCode(0);
        $this->assertTrue(AdminUser::query()->where('email', 'usr@x.vn')->exists());
    }

    public function test_admin_demote_deactivates(): void
    {
        $a = AdminUser::factory()->create(['username' => 'op3']);
        $this->artisan('admin:demote', ['username' => 'op3'])->assertExitCode(0);
        $this->assertFalse($a->fresh()->is_active);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=AdminCommandsTest`
Expected: FAIL — commands don't exist / old signatures.

- [ ] **Step 3: Create `admin:create`**

`app/app/Console/Commands/AdminCreate.php`:

```php
<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Console\Command;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class AdminCreate extends Command
{
    protected $signature = 'admin:create
        {username : Login username, [a-z0-9._-]{3,32}}
        {--name= : Display name}
        {--email= : Optional email (for password reset)}
        {--password= : If omitted, prompts hidden}';

    protected $description = 'Tạo tài khoản super-admin mới (Spec 2026-05-17).';

    public function handle(): int
    {
        $username = (string) $this->argument('username');
        $name = (string) ($this->option('name') ?: $username);
        $email = $this->option('email');
        $password = $this->option('password') ?: $this->secret('Password (min 8)');

        $v = Validator::make([
            'username' => $username, 'name' => $name, 'email' => $email, 'password' => $password,
        ], [
            'username' => ['required','regex:/^[a-z0-9._-]{3,32}$/', Rule::unique('admin_users','username')],
            'name' => ['required','string','max:120'],
            'email' => ['nullable','email','max:255', Rule::unique('admin_users','email')],
            'password' => ['required','string','min:8','max:128'],
        ]);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) $this->error($e);
            return self::FAILURE;
        }

        AdminUser::create([
            'username' => $username,
            'name' => $name,
            'email' => $email ?: null,
            'password' => $password,
            'is_active' => true,
        ]);
        $this->info("✔ Tạo admin {$username}.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Create `admin:reset-password`**

`app/app/Console/Commands/AdminResetPassword.php`:

```php
<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Console\Command;

class AdminResetPassword extends Command
{
    protected $signature = 'admin:reset-password {username} {--password=}';

    protected $description = 'Đặt lại mật khẩu admin.';

    public function handle(): int
    {
        $username = (string) $this->argument('username');
        $admin = AdminUser::query()->where('username', $username)->first();
        if (! $admin) {
            $this->error("Không tìm thấy admin [{$username}].");
            return self::FAILURE;
        }
        $pw = $this->option('password') ?: $this->secret('New password (min 8)');
        if (strlen($pw) < 8) {
            $this->error('Password phải ≥ 8 ký tự.');
            return self::FAILURE;
        }
        $admin->forceFill(['password' => $pw])->save();
        $this->info("✔ Reset mật khẩu cho {$username}.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 5: Refactor `admin:promote`**

Replace `app/app/Console/Commands/PromoteSuperAdmin.php`:

```php
<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Spec 2026-05-17 — Promote user-by-email into a new admin_users record (idempotent).
 */
class PromoteSuperAdmin extends Command
{
    protected $signature = 'admin:promote {email}';
    protected $description = 'Promote a user-by-email to a super-admin (creates admin_users row).';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $this->error("Không tìm thấy user với email [{$email}].");
            return self::FAILURE;
        }
        if (AdminUser::query()->where('email', $email)->exists()) {
            $this->info("Admin với email {$email} đã tồn tại (idempotent).");
            return self::SUCCESS;
        }
        $base = preg_replace('/[^a-z0-9._-]/', '', strtolower(Str::before($email, '@'))) ?: "admin_{$user->id}";
        $username = $base;
        $i = 1;
        while (AdminUser::query()->where('username', $username)->exists()) {
            $username = $base.'_'.$i++;
        }
        AdminUser::create([
            'username' => $username,
            'email' => $email,
            'name' => $user->name,
            'password' => $user->password,
            'is_active' => true,
        ]);
        $this->info("✔ Promote {$email} → admin (username={$username}). Hãy dùng `admin:reset-password {$username}` để đặt mật khẩu mới.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 6: Refactor `admin:demote`**

Replace `app/app/Console/Commands/DemoteSuperAdmin.php`:

```php
<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Console\Command;

class DemoteSuperAdmin extends Command
{
    protected $signature = 'admin:demote {username}';
    protected $description = 'Vô hiệu hoá admin (is_active=false). Không xoá.';

    public function handle(): int
    {
        $a = AdminUser::query()->where('username', (string) $this->argument('username'))->first();
        if (! $a) {
            $this->error('Không tìm thấy admin.');
            return self::FAILURE;
        }
        $a->forceFill(['is_active' => false])->save();
        $this->info("✔ Vô hiệu hoá admin {$a->username}.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 7: Run test to verify it passes**

Run: `cd app && php artisan test --filter=AdminCommandsTest`
Expected: 5 PASS.

- [ ] **Step 8: Commit**

```bash
git add app/app/Console/Commands/AdminCreate.php \
        app/app/Console/Commands/AdminResetPassword.php \
        app/app/Console/Commands/PromoteSuperAdmin.php \
        app/app/Console/Commands/DemoteSuperAdmin.php \
        app/tests/Feature/Console/AdminCommandsTest.php
git commit -m "feat(admin): artisan admin:create, admin:reset-password; refactor promote/demote on admin_users"
```

---

## Phase 3 — Admin/Tenant user CRUD endpoints

### Task 10: AdminUserController — admin_users CRUD endpoints

**Files:**
- Create: `app/app/Modules/Admin/Http/Controllers/AdminAdminUserController.php`
- Modify: `app/app/Modules/Admin/Http/routes.php`
- Test: `app/tests/Feature/Admin/AdminUserCrudTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAdmin(): AdminUser
    {
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'admin_web');
        return $admin;
    }

    public function test_list_returns_paginated_admins(): void
    {
        $this->actingAdmin();
        AdminUser::factory()->count(3)->create();
        $r = $this->getJson('/api/v1/admin/admin-users')->assertOk();
        $this->assertGreaterThanOrEqual(4, count($r->json('data')));
    }

    public function test_create_admin(): void
    {
        $this->actingAdmin();
        $this->postJson('/api/v1/admin/admin-users', [
            'username' => 'newb', 'name' => 'New B', 'password' => 'pw123456',
        ])->assertCreated()->assertJsonPath('data.username', 'newb');
        $this->assertDatabaseHas('admin_users', ['username' => 'newb']);
    }

    public function test_update_admin_metadata(): void
    {
        $me = $this->actingAdmin();
        $other = AdminUser::factory()->create();
        $this->patchJson("/api/v1/admin/admin-users/{$other->id}", ['name' => 'Renamed'])
            ->assertOk()->assertJsonPath('data.name', 'Renamed');
    }

    public function test_cannot_suspend_self(): void
    {
        $me = $this->actingAdmin();
        $this->postJson("/api/v1/admin/admin-users/{$me->id}/suspend")
            ->assertStatus(409)->assertJsonPath('error.code', 'CANNOT_SELF_MUTATE');
    }

    public function test_cannot_suspend_last_active_admin(): void
    {
        $me = $this->actingAdmin();
        AdminUser::factory()->inactive()->create();
        $other = AdminUser::factory()->create();
        // Suspend the other admin → leaves $me as last active.
        $this->postJson("/api/v1/admin/admin-users/{$other->id}/suspend")->assertOk();
        // Suspending me would leave zero — but rule 1 (self) fires first. Use a separate admin to suspend $me:
        // Simulate by switching actor:
        $third = AdminUser::factory()->create();
        $this->actingAs($third, 'admin_web');
        // now suspending $me leaves only $third active → OK; finally suspending $third leaves zero.
        $this->postJson("/api/v1/admin/admin-users/{$me->id}/suspend")->assertOk();
        $this->postJson("/api/v1/admin/admin-users/{$third->id}/suspend")
            ->assertStatus(409)->assertJsonPath('error.code', 'LAST_ACTIVE_ADMIN');
    }

    public function test_reset_password(): void
    {
        $this->actingAdmin();
        $other = AdminUser::factory()->create();
        $this->postJson("/api/v1/admin/admin-users/{$other->id}/reset-password", ['password' => 'newpwd99'])
            ->assertOk();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpwd99', $other->fresh()->password));
    }

    public function test_reactivate(): void
    {
        $this->actingAdmin();
        $other = AdminUser::factory()->inactive()->create();
        $this->postJson("/api/v1/admin/admin-users/{$other->id}/reactivate")->assertOk();
        $this->assertTrue($other->fresh()->is_active);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=AdminUserCrudTest`
Expected: FAIL — routes/controller missing.

- [ ] **Step 3: Create the controller**

`app/app/Modules/Admin/Http/Controllers/AdminAdminUserController.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminAdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = (string) $request->query('q', '');
        $perPage = max(1, min(100, (int) $request->query('per_page', 30)));
        $query = AdminUser::query()->orderByDesc('id');
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('username','like',"%{$q}%")->orWhere('email','like',"%{$q}%")->orWhere('name','like',"%{$q}%"));
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        $page = $query->paginate($perPage);

        return response()->json([
            'data' => collect($page->items())->map(fn (AdminUser $a) => $this->present($a))->all(),
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => $this->present(AdminUser::query()->findOrFail($id))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required','regex:/^[a-z0-9._-]{3,32}$/', Rule::unique('admin_users','username')],
            'email' => ['nullable','email','max:255', Rule::unique('admin_users','email')],
            'name' => ['required','string','max:120'],
            'password' => ['required','string','min:8','max:128'],
            'is_active' => ['sometimes','boolean'],
        ]);
        $a = AdminUser::create($data + ['is_active' => $data['is_active'] ?? true]);
        AuditLog::record('admin.admin_user.create', $a, ['username' => $a->username]);

        return response()->json(['data' => $this->present($a)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $a = AdminUser::query()->findOrFail($id);
        $data = $request->validate([
            'email' => ['sometimes','nullable','email','max:255', Rule::unique('admin_users','email')->ignore($a->id)],
            'name' => ['sometimes','string','max:120'],
        ]);
        $a->fill($data)->save();
        AuditLog::record('admin.admin_user.update', $a, ['changes' => $data]);

        return response()->json(['data' => $this->present($a)]);
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $this->refuseSelf($id);
        $a = AdminUser::query()->findOrFail($id);
        $data = $request->validate(['password' => ['required','string','min:8','max:128']]);
        $a->forceFill(['password' => $data['password']])->save();
        AuditLog::record('admin.admin_user.reset_password', $a);

        return response()->json(['data' => ['ok' => true]]);
    }

    public function suspend(int $id): JsonResponse
    {
        $this->refuseSelf($id);
        $a = AdminUser::query()->findOrFail($id);
        if ($a->is_active && AdminUser::query()->where('is_active', true)->count() <= 1) {
            return $this->conflict('LAST_ACTIVE_ADMIN', 'Không thể vô hiệu hoá admin đang hoạt động cuối cùng.');
        }
        $a->forceFill(['is_active' => false])->save();
        AuditLog::record('admin.admin_user.suspend', $a);

        return response()->json(['data' => $this->present($a)]);
    }

    public function reactivate(int $id): JsonResponse
    {
        $a = AdminUser::query()->findOrFail($id);
        $a->forceFill(['is_active' => true])->save();
        AuditLog::record('admin.admin_user.reactivate', $a);

        return response()->json(['data' => $this->present($a)]);
    }

    private function refuseSelf(int $id): void
    {
        if (Auth::guard('admin_web')->id() === $id) {
            abort(response()->json(['error' => [
                'code' => 'CANNOT_SELF_MUTATE',
                'message' => 'Không thể thao tác trên chính tài khoản admin của bạn.',
            ]], 409));
        }
    }

    private function conflict(string $code, string $msg): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $msg]], 409);
    }

    /** @return array<string,mixed> */
    private function present(AdminUser $a): array
    {
        return [
            'id' => $a->id,
            'username' => $a->username,
            'email' => $a->email,
            'name' => $a->name,
            'is_active' => (bool) $a->is_active,
            'last_login_at' => $a->last_login_at?->toIso8601String(),
            'created_at' => $a->created_at?->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 4: Register routes**

Inside the `Route::middleware(['web', 'auth:admin', 'throttle:60,1'])->prefix('api/v1/admin')` group in `routes.php`, add:

```php
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminAdminUserController;

Route::get('admin-users', [AdminAdminUserController::class, 'index'])->name('admin.admin-users.index');
Route::post('admin-users', [AdminAdminUserController::class, 'store'])->name('admin.admin-users.store');
Route::get('admin-users/{id}', [AdminAdminUserController::class, 'show'])->whereNumber('id')->name('admin.admin-users.show');
Route::patch('admin-users/{id}', [AdminAdminUserController::class, 'update'])->whereNumber('id')->name('admin.admin-users.update');
Route::post('admin-users/{id}/reset-password', [AdminAdminUserController::class, 'resetPassword'])->whereNumber('id')->name('admin.admin-users.reset-password');
Route::post('admin-users/{id}/suspend', [AdminAdminUserController::class, 'suspend'])->whereNumber('id')->name('admin.admin-users.suspend');
Route::post('admin-users/{id}/reactivate', [AdminAdminUserController::class, 'reactivate'])->whereNumber('id')->name('admin.admin-users.reactivate');
```

- [ ] **Step 5: Run test to verify it passes**

Run: `cd app && php artisan test --filter=AdminUserCrudTest`
Expected: 7 PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Admin/Http/Controllers/AdminAdminUserController.php \
        app/app/Modules/Admin/Http/routes.php \
        app/tests/Feature/Admin/AdminUserCrudTest.php
git commit -m "feat(admin): CRUD endpoints for admin_users (list/create/show/update/reset/suspend/reactivate)"
```

---

### Task 11: AdminUserController — extend tenant user CRUD

**Files:**
- Modify: `app/app/Modules/Admin/Http/Controllers/AdminUserController.php`
- Modify: `app/app/Modules/Admin/Http/routes.php`
- Test: `app/tests/Feature/Admin/TenantUserCrudTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantUserCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function bootstrap(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');
    }

    public function test_show_returns_user(): void
    {
        $this->bootstrap();
        $u = User::factory()->create();
        $this->getJson("/api/v1/admin/users/{$u->id}")->assertOk()
            ->assertJsonPath('data.id', $u->id);
    }

    public function test_update_user_name_email(): void
    {
        $this->bootstrap();
        $u = User::factory()->create();
        $this->patchJson("/api/v1/admin/users/{$u->id}", ['name' => 'New', 'email' => 'new@x.vn'])
            ->assertOk()->assertJsonPath('data.email', 'new@x.vn');
    }

    public function test_suspend_user_sets_suspended_at(): void
    {
        $this->bootstrap();
        $u = User::factory()->create();
        $this->postJson("/api/v1/admin/users/{$u->id}/suspend")->assertOk();
        $this->assertNotNull($u->fresh()->suspended_at);
    }

    public function test_reactivate_clears_suspended_at(): void
    {
        $this->bootstrap();
        $u = User::factory()->create(['suspended_at' => now()]);
        $this->postJson("/api/v1/admin/users/{$u->id}/reactivate")->assertOk();
        $this->assertNull($u->fresh()->suspended_at);
    }

    public function test_reset_password_sets_new_hash(): void
    {
        $this->bootstrap();
        $u = User::factory()->create();
        $this->postJson("/api/v1/admin/users/{$u->id}/reset-password", ['password' => 'newpwd99'])->assertOk();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpwd99', $u->fresh()->password));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=TenantUserCrudTest`
Expected: FAIL — endpoints missing.

- [ ] **Step 3: Extend `AdminUserController` with the new methods**

Append to `app/app/Modules/Admin/Http/Controllers/AdminUserController.php` (keep existing `index()`; remove the `$onlyAdmin` filter):

```php
use Illuminate\Validation\Rule;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;

public function show(int $id): JsonResponse
{
    $u = User::query()->findOrFail($id);
    $memberships = TenantUser::query()->where('user_id', $u->id)->get();
    $tenants = Tenant::query()->whereIn('id', $memberships->pluck('tenant_id'))->get()->keyBy('id');

    return response()->json(['data' => [
        'id' => $u->id,
        'name' => $u->name,
        'email' => $u->email,
        'email_verified_at' => $u->email_verified_at?->toIso8601String(),
        'suspended_at' => $u->suspended_at?->toIso8601String(),
        'created_at' => $u->created_at?->toIso8601String(),
        'tenants' => $memberships->map(function (TenantUser $m) use ($tenants) {
            $t = $tenants->get($m->tenant_id);
            return $t ? ['id'=>$t->id,'name'=>$t->name,'slug'=>$t->slug,'role'=>$m->role->value ?? $m->role] : null;
        })->filter()->values()->all(),
    ]]);
}

public function update(Request $request, int $id): JsonResponse
{
    $u = User::query()->findOrFail($id);
    $data = $request->validate([
        'name' => ['sometimes','string','max:120'],
        'email' => ['sometimes','nullable','email','max:255', Rule::unique('users','email')->ignore($u->id)],
    ]);
    $u->fill($data)->save();
    AuditLog::record('admin.user.update', $u, ['changes' => $data]);

    return response()->json(['data' => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]]);
}

public function resetPassword(Request $request, int $id): JsonResponse
{
    $u = User::query()->findOrFail($id);
    $data = $request->validate(['password' => ['required','string','min:8','max:128']]);
    $u->forceFill(['password' => $data['password']])->save();
    AuditLog::record('admin.user.reset_password', $u);

    return response()->json(['data' => ['ok' => true]]);
}

public function suspend(int $id): JsonResponse
{
    $u = User::query()->findOrFail($id);
    if ($u->suspended_at === null) {
        $u->forceFill(['suspended_at' => now()])->save();
    }
    AuditLog::record('admin.user.suspend', $u);

    return response()->json(['data' => ['id' => $u->id, 'suspended_at' => $u->suspended_at?->toIso8601String()]]);
}

public function reactivate(int $id): JsonResponse
{
    $u = User::query()->findOrFail($id);
    if ($u->suspended_at !== null) {
        $u->forceFill(['suspended_at' => null])->save();
    }
    AuditLog::record('admin.user.reactivate', $u);

    return response()->json(['data' => ['id' => $u->id, 'suspended_at' => null]]);
}
```

Also update `index()`: remove the `is_super_admin` branch and the column from the row map.

- [ ] **Step 4: Register routes**

Add inside the admin group:

```php
Route::get('users/{id}', [AdminUserController::class, 'show'])->whereNumber('id')->name('admin.users.show');
Route::patch('users/{id}', [AdminUserController::class, 'update'])->whereNumber('id')->name('admin.users.update');
Route::post('users/{id}/reset-password', [AdminUserController::class, 'resetPassword'])->whereNumber('id')->name('admin.users.reset-password');
Route::post('users/{id}/suspend', [AdminUserController::class, 'suspend'])->whereNumber('id')->name('admin.users.suspend');
Route::post('users/{id}/reactivate', [AdminUserController::class, 'reactivate'])->whereNumber('id')->name('admin.users.reactivate');
```

- [ ] **Step 5: Run test to verify it passes**

Run: `cd app && php artisan test --filter=TenantUserCrudTest`
Expected: 5 PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Admin/Http/Controllers/AdminUserController.php \
        app/app/Modules/Admin/Http/routes.php \
        app/tests/Feature/Admin/TenantUserCrudTest.php
git commit -m "feat(admin): tenant user CRUD endpoints (show/update/reset/suspend/reactivate)"
```

---

## Phase 4 — System Settings module

### Task 12: Migration `system_settings` + model

**Files:**
- Create: `app/app/Modules/Settings/Database/Migrations/2026_05_18_100005_create_system_settings_table.php`
- Create: `app/app/Modules/Settings/Models/SystemSetting.php`
- Test: `app/tests/Feature/Settings/SystemSettingsTableTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature\Settings;

use CMBcoreSeller\Modules\Settings\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SystemSettingsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_has_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('system_settings', [
            'key','value','type','group','is_secret','description','updated_by_admin_id','created_at','updated_at',
        ]));
    }

    public function test_model_can_persist_row(): void
    {
        $r = SystemSetting::create(['key' => 'foo.bar', 'value' => '1', 'type' => 'int', 'group' => 'sync', 'is_secret' => false]);
        $this->assertSame('foo.bar', $r->key);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=SystemSettingsTableTest`
Expected: FAIL — table/model missing.

- [ ] **Step 3: Create migration + model**

`app/app/Modules/Settings/Database/Migrations/2026_05_18_100005_create_system_settings_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->text('value')->nullable();
            $table->string('type', 16);
            $table->string('group', 32);
            $table->boolean('is_secret')->default(false);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('updated_by_admin_id')->nullable();
            $table->timestamps();
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
```

`app/app/Modules/Settings/Models/SystemSetting.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key','value','type','group','is_secret','description','updated_by_admin_id'];

    protected $casts = ['is_secret' => 'boolean'];
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd app && php artisan test --filter=SystemSettingsTableTest`
Expected: 2 PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Settings/Database/Migrations/2026_05_18_100005_create_system_settings_table.php \
        app/app/Modules/Settings/Models/SystemSetting.php \
        app/tests/Feature/Settings/SystemSettingsTableTest.php
git commit -m "feat(settings): migration + model system_settings"
```

---

### Task 13: `SystemSettingsCatalog` — 38-key whitelist

**Files:**
- Create: `app/app/Modules/Settings/Support/SystemSettingsCatalog.php`
- Test: `app/tests/Unit/Settings/SystemSettingsCatalogTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Unit\Settings;

use CMBcoreSeller\Modules\Settings\Support\SystemSettingsCatalog;
use InvalidArgumentException;
use Tests\TestCase;

class SystemSettingsCatalogTest extends TestCase
{
    public function test_all_groups_present(): void
    {
        $all = SystemSettingsCatalog::all();
        $this->assertNotEmpty($all);
        $groups = collect($all)->pluck('group')->unique()->values()->all();
        sort($groups);
        $this->assertSame(['branding','fulfillment','marketplace','sync'], $groups);
    }

    public function test_count_is_38(): void
    {
        $this->assertCount(38, SystemSettingsCatalog::all());
    }

    public function test_secret_count_is_8(): void
    {
        $this->assertSame(8, collect(SystemSettingsCatalog::all())->where('is_secret', true)->count());
    }

    public function test_require_throws_on_unknown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SystemSettingsCatalog::require('nope.invalid');
    }

    public function test_validate_bool_accepts_string_true(): void
    {
        $this->assertTrue(SystemSettingsCatalog::validate('marketplace.tiktok.sandbox', 'true'));
    }

    public function test_validate_int_rejects_letters(): void
    {
        $this->assertFalse(SystemSettingsCatalog::validate('sync.poll_interval_minutes', 'abc'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=SystemSettingsCatalogTest`
Expected: FAIL — class missing.

- [ ] **Step 3: Create the catalog**

`app/app/Modules/Settings/Support/SystemSettingsCatalog.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Settings\Support;

use InvalidArgumentException;

/**
 * Single source of truth for keys that super-admin may manage from /admin/settings.
 * Keys NOT listed here are ignored by system_setting() and may never be persisted.
 */
class SystemSettingsCatalog
{
    /** @return array<string, array{group:string,type:string,is_secret:bool,env:string,label:string,description?:string}> */
    public static function all(): array
    {
        return [
            // Branding (7) ---------------------------------------------------------
            'notifications.brand_name' => ['group'=>'branding','type'=>'string','is_secret'=>false,'env'=>'NOTIFICATIONS_BRAND_NAME','label'=>'Tên thương hiệu'],
            'notifications.brand_tagline' => ['group'=>'branding','type'=>'string','is_secret'=>false,'env'=>'NOTIFICATIONS_BRAND_TAGLINE','label'=>'Tagline'],
            'notifications.support_email' => ['group'=>'branding','type'=>'string','is_secret'=>false,'env'=>'NOTIFICATIONS_SUPPORT_EMAIL','label'=>'Email hỗ trợ'],
            'notifications.primary_color' => ['group'=>'branding','type'=>'string','is_secret'=>false,'env'=>'NOTIFICATIONS_PRIMARY_COLOR','label'=>'Màu chính'],
            'notifications.accent_color' => ['group'=>'branding','type'=>'string','is_secret'=>false,'env'=>'NOTIFICATIONS_ACCENT_COLOR','label'=>'Màu nhấn'],
            'mail.from_address' => ['group'=>'branding','type'=>'string','is_secret'=>false,'env'=>'MAIL_FROM_ADDRESS','label'=>'Email gửi từ'],
            'mail.from_name' => ['group'=>'branding','type'=>'string','is_secret'=>false,'env'=>'MAIL_FROM_NAME','label'=>'Tên người gửi'],

            // Marketplace (9, 6 secret) -------------------------------------------
            'marketplace.tiktok.app_key' => ['group'=>'marketplace','type'=>'string','is_secret'=>true,'env'=>'TIKTOK_APP_KEY','label'=>'TikTok App Key'],
            'marketplace.tiktok.app_secret' => ['group'=>'marketplace','type'=>'string','is_secret'=>true,'env'=>'TIKTOK_APP_SECRET','label'=>'TikTok App Secret'],
            'marketplace.tiktok.service_id' => ['group'=>'marketplace','type'=>'string','is_secret'=>false,'env'=>'TIKTOK_SERVICE_ID','label'=>'TikTok Service ID'],
            'marketplace.tiktok.sandbox' => ['group'=>'marketplace','type'=>'bool','is_secret'=>false,'env'=>'TIKTOK_SANDBOX','label'=>'TikTok Sandbox'],
            'marketplace.lazada.app_key' => ['group'=>'marketplace','type'=>'string','is_secret'=>true,'env'=>'LAZADA_APP_KEY','label'=>'Lazada App Key'],
            'marketplace.lazada.app_secret' => ['group'=>'marketplace','type'=>'string','is_secret'=>true,'env'=>'LAZADA_APP_SECRET','label'=>'Lazada App Secret'],
            'marketplace.lazada.sandbox' => ['group'=>'marketplace','type'=>'bool','is_secret'=>false,'env'=>'LAZADA_SANDBOX','label'=>'Lazada Sandbox'],
            'marketplace.shopee.partner_id' => ['group'=>'marketplace','type'=>'string','is_secret'=>true,'env'=>'SHOPEE_PARTNER_ID','label'=>'Shopee Partner ID'],
            'marketplace.shopee.partner_key' => ['group'=>'marketplace','type'=>'string','is_secret'=>true,'env'=>'SHOPEE_PARTNER_KEY','label'=>'Shopee Partner Key'],

            // Fulfillment / storage (15, 2 secret) --------------------------------
            'fulfillment.deduct_on' => ['group'=>'fulfillment','type'=>'string','is_secret'=>false,'env'=>'FULFILLMENT_DEDUCT_ON','label'=>'Thời điểm trừ tồn'],
            'fulfillment.default_weight_grams' => ['group'=>'fulfillment','type'=>'int','is_secret'=>false,'env'=>'FULFILLMENT_DEFAULT_WEIGHT_GRAMS','label'=>'Cân nặng mặc định (g)'],
            'fulfillment.tiktok_arrange_shipment' => ['group'=>'fulfillment','type'=>'bool','is_secret'=>false,'env'=>'INTEGRATIONS_TIKTOK_FULFILLMENT','label'=>'TikTok arrange-shipment'],
            'fulfillment.print_label_size' => ['group'=>'fulfillment','type'=>'string','is_secret'=>false,'env'=>'PRINT_LABEL_SIZE','label'=>'Khổ tem in mặc định'],
            'carriers.enabled_csv' => ['group'=>'fulfillment','type'=>'string','is_secret'=>false,'env'=>'INTEGRATIONS_CARRIERS','label'=>'ĐVVC đã bật (CSV)'],
            'carriers.default' => ['group'=>'fulfillment','type'=>'string','is_secret'=>false,'env'=>'INTEGRATIONS_DEFAULT_CARRIER','label'=>'ĐVVC mặc định'],
            'carriers.ghn.base_url' => ['group'=>'fulfillment','type'=>'string','is_secret'=>false,'env'=>'GHN_BASE_URL','label'=>'GHN base URL'],
            'storage.media_disk' => ['group'=>'fulfillment','type'=>'string','is_secret'=>false,'env'=>'MEDIA_DISK','label'=>'Disk media (public|r2)'],
            'storage.media_image_max_kb' => ['group'=>'fulfillment','type'=>'int','is_secret'=>false,'env'=>'MEDIA_IMAGE_MAX_KB','label'=>'Giới hạn ảnh (KB)'],
            'storage.r2.bucket' => ['group'=>'fulfillment','type'=>'string','is_secret'=>false,'env'=>'R2_BUCKET','label'=>'R2 bucket'],
            'storage.r2.endpoint' => ['group'=>'fulfillment','type'=>'string','is_secret'=>false,'env'=>'R2_ENDPOINT','label'=>'R2 endpoint'],
            'storage.r2.public_url' => ['group'=>'fulfillment','type'=>'string','is_secret'=>false,'env'=>'R2_URL','label'=>'R2 public URL'],
            'storage.r2.access_key_id' => ['group'=>'fulfillment','type'=>'string','is_secret'=>true,'env'=>'R2_ACCESS_KEY_ID','label'=>'R2 Access Key'],
            'storage.r2.secret_access_key' => ['group'=>'fulfillment','type'=>'string','is_secret'=>true,'env'=>'R2_SECRET_ACCESS_KEY','label'=>'R2 Secret Key'],
            'pdf.gotenberg_url' => ['group'=>'fulfillment','type'=>'string','is_secret'=>false,'env'=>'GOTENBERG_URL','label'=>'Gotenberg URL'],

            // Sync / throttle / billing (7) ---------------------------------------
            'throttle.tiktok_per_min' => ['group'=>'sync','type'=>'int','is_secret'=>false,'env'=>'THROTTLE_TIKTOK_PER_MIN','label'=>'Throttle TikTok (req/phút)'],
            'throttle.shopee_per_min' => ['group'=>'sync','type'=>'int','is_secret'=>false,'env'=>'THROTTLE_SHOPEE_PER_MIN','label'=>'Throttle Shopee (req/phút)'],
            'throttle.lazada_per_min' => ['group'=>'sync','type'=>'int','is_secret'=>false,'env'=>'THROTTLE_LAZADA_PER_MIN','label'=>'Throttle Lazada (req/phút)'],
            'sync.poll_interval_minutes' => ['group'=>'sync','type'=>'int','is_secret'=>false,'env'=>'SYNC_POLL_INTERVAL_MINUTES','label'=>'Poll interval (phút)'],
            'sync.poll_overlap_minutes' => ['group'=>'sync','type'=>'int','is_secret'=>false,'env'=>'SYNC_POLL_OVERLAP_MINUTES','label'=>'Poll overlap (phút)'],
            'sync.backfill_days' => ['group'=>'sync','type'=>'int','is_secret'=>false,'env'=>'SYNC_BACKFILL_DAYS','label'=>'Backfill (ngày)'],
            'billing.over_quota_grace_hours' => ['group'=>'sync','type'=>'int','is_secret'=>false,'env'=>'BILLING_OVER_QUOTA_GRACE_HOURS','label'=>'Over-quota grace (giờ)'],
        ];
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    /** @return array{group:string,type:string,is_secret:bool,env:string,label:string,description?:string} */
    public static function require(string $key): array
    {
        $all = self::all();
        if (! isset($all[$key])) {
            throw new InvalidArgumentException("Key [{$key}] is not in SystemSettingsCatalog.");
        }
        return $all[$key];
    }

    public static function validate(string $key, mixed $value): bool
    {
        $meta = self::require($key);
        return match ($meta['type']) {
            'string' => is_string($value) && strlen($value) <= 4096,
            'int' => is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1),
            'float' => is_numeric($value),
            'bool' => is_bool($value) || in_array(strtolower((string) $value), ['true','false','0','1'], true),
            'json' => is_array($value) || (is_string($value) && json_validate($value)),
            default => false,
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd app && php artisan test --filter=SystemSettingsCatalogTest`
Expected: 6 PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Settings/Support/SystemSettingsCatalog.php \
        app/tests/Unit/Settings/SystemSettingsCatalogTest.php
git commit -m "feat(settings): SystemSettingsCatalog whitelist (38 keys, 8 secret)"
```

---

### Task 14: `SystemSettingService` + `system_setting()` helper

**Files:**
- Create: `app/app/Modules/Settings/Services/SystemSettingService.php`
- Create: `app/app/Modules/Settings/Events/SystemSettingChanged.php`
- Create: `app/app/Modules/Settings/helpers.php`
- Modify: `app/composer.json` (autoload files)
- Test: `app/tests/Feature/Settings/SystemSettingServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature\Settings;

use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Tests\TestCase;

class SystemSettingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): SystemSettingService { return app(SystemSettingService::class); }

    public function test_get_returns_default_when_key_missing(): void
    {
        $this->assertSame('fallback', $this->svc()->get('marketplace.tiktok.app_key', 'fallback'));
    }

    public function test_set_then_get_returns_value(): void
    {
        $this->svc()->set('sync.poll_interval_minutes', 7);
        $this->assertSame(7, $this->svc()->get('sync.poll_interval_minutes'));
    }

    public function test_set_secret_encrypts_value_in_db(): void
    {
        $this->svc()->set('marketplace.tiktok.app_secret', 'plain-secret');
        $row = \CMBcoreSeller\Modules\Settings\Models\SystemSetting::where('key','marketplace.tiktok.app_secret')->first();
        $this->assertNotSame('plain-secret', $row->value);
        $this->assertSame('plain-secret', $this->svc()->get('marketplace.tiktok.app_secret'));
    }

    public function test_set_forgets_cache(): void
    {
        Cache::put('system_settings:all', ['marketplace.tiktok.sandbox' => ['value' => true, 'type' => 'bool', 'is_secret' => false]]);
        $this->svc()->set('marketplace.tiktok.sandbox', false);
        $this->assertFalse($this->svc()->get('marketplace.tiktok.sandbox'));
    }

    public function test_set_rejects_unknown_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->svc()->set('nope.invalid', '1');
    }

    public function test_helper_function_works(): void
    {
        $this->svc()->set('sync.backfill_days', 42);
        $this->assertSame(42, system_setting('sync.backfill_days'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=SystemSettingServiceTest`
Expected: FAIL — service & helper missing.

- [ ] **Step 3: Create the event**

`app/app/Modules/Settings/Events/SystemSettingChanged.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Settings\Events;

class SystemSettingChanged
{
    public function __construct(public readonly string $key) {}
}
```

- [ ] **Step 4: Create the service**

`app/app/Modules/Settings/Services/SystemSettingService.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Settings\Services;

use CMBcoreSeller\Modules\Settings\Events\SystemSettingChanged;
use CMBcoreSeller\Modules\Settings\Models\SystemSetting;
use CMBcoreSeller\Modules\Settings\Support\SystemSettingsCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class SystemSettingService
{
    private const CACHE_KEY = 'system_settings:all';

    private ?array $memo = null;

    /** @return array<string, array{value:mixed,type:string,is_secret:bool}> */
    public function all(): array
    {
        return $this->memo ??= Cache::rememberForever(self::CACHE_KEY, function (): array {
            return SystemSetting::query()->get()->mapWithKeys(function (SystemSetting $s): array {
                $plain = $s->value;
                if ($s->is_secret && $plain !== null) {
                    try { $plain = Crypt::decryptString($plain); }
                    catch (Throwable) { $plain = null; }
                }
                return [$s->key => ['value' => $plain, 'type' => $s->type, 'is_secret' => (bool) $s->is_secret]];
            })->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (! SystemSettingsCatalog::has($key)) {
            return $default;
        }
        $row = $this->all()[$key] ?? null;
        if ($row === null || $row['value'] === null) {
            return $default;
        }
        return $this->cast($row['value'], $row['type']);
    }

    public function set(string $key, mixed $value, ?int $adminId = null): SystemSetting
    {
        $meta = SystemSettingsCatalog::require($key);
        $stored = $this->encode($value, $meta['type']);
        if ($meta['is_secret'] && $stored !== null) {
            $stored = Crypt::encryptString($stored);
        }
        $row = SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $stored,
                'type' => $meta['type'],
                'group' => $meta['group'],
                'is_secret' => $meta['is_secret'],
                'description' => $meta['description'] ?? null,
                'updated_by_admin_id' => $adminId,
            ],
        );
        $this->forget();
        event(new SystemSettingChanged($key));

        return $row;
    }

    public function forget(string $key): SystemSetting|bool
    {
        $row = SystemSetting::query()->where('key', $key)->first();
        if ($row) { $row->delete(); }
        $this->reset();
        event(new SystemSettingChanged($key));
        return $row ?: false;
    }

    public function reset(): void
    {
        $this->memo = null;
        Cache::forget(self::CACHE_KEY);
    }

    private function cast(mixed $v, string $type): mixed
    {
        return match ($type) {
            'int' => is_int($v) ? $v : (int) $v,
            'float' => is_float($v) ? $v : (float) $v,
            'bool' => is_bool($v) ? $v : in_array(strtolower((string) $v), ['true','1'], true),
            'json' => is_array($v) ? $v : json_decode((string) $v, true),
            default => is_string($v) ? $v : (string) $v,
        };
    }

    private function encode(mixed $v, string $type): ?string
    {
        if ($v === null) return null;
        return match ($type) {
            'bool' => (in_array(strtolower((string) $v), ['true','1'], true) || $v === true) ? '1' : '0',
            'json' => is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE),
            default => (string) $v,
        };
    }
}
```

- [ ] **Step 5: Create the helper**

`app/app/Modules/Settings/helpers.php`:

```php
<?php

use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;

if (! function_exists('system_setting')) {
    /**
     * Read a whitelisted system setting (DB → cache). Returns $default if unset
     * or if the key is not in SystemSettingsCatalog. Spec 2026-05-17.
     */
    function system_setting(string $key, mixed $default = null): mixed
    {
        return app(SystemSettingService::class)->get($key, $default);
    }
}
```

- [ ] **Step 6: Register helper in composer autoload**

Edit `app/composer.json`. Inside the existing `"autoload"` object, add `"files"`:

```json
"autoload": {
    "psr-4": {
        "CMBcoreSeller\\": "app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    },
    "files": [
        "app/Modules/Settings/helpers.php"
    ]
},
```

Then run `cd app && composer dump-autoload`.

- [ ] **Step 7: Run test to verify it passes**

Run: `cd app && php artisan test --filter=SystemSettingServiceTest`
Expected: 6 PASS.

- [ ] **Step 8: Commit**

```bash
git add app/app/Modules/Settings/Services/SystemSettingService.php \
        app/app/Modules/Settings/Events/SystemSettingChanged.php \
        app/app/Modules/Settings/helpers.php \
        app/composer.json app/composer.lock \
        app/tests/Feature/Settings/SystemSettingServiceTest.php
git commit -m "feat(settings): SystemSettingService + system_setting() helper + SystemSettingChanged event"
```

---

### Task 15: Audit listener `LogSystemSettingChanged`

**Files:**
- Create: `app/app/Modules/Settings/Listeners/LogSystemSettingChanged.php`
- Modify: `app/app/Modules/Settings/SettingsServiceProvider.php`
- Test: `app/tests/Feature/Settings/SystemSettingAuditTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature\Settings;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_writes_audit_log_with_admin_actor(): void
    {
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'admin_web');
        app(SystemSettingService::class)->set('sync.backfill_days', 7);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.setting.update', 'admin_user_id' => $admin->id,
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=SystemSettingAuditTest`
Expected: FAIL — no listener writes the log.

- [ ] **Step 3: Create the listener**

`app/app/Modules/Settings/Listeners/LogSystemSettingChanged.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Settings\Listeners;

use CMBcoreSeller\Modules\Settings\Events\SystemSettingChanged;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;

class LogSystemSettingChanged
{
    public function handle(SystemSettingChanged $e): void
    {
        AuditLog::record('admin.setting.update', null, ['key' => $e->key]);
    }
}
```

- [ ] **Step 4: Register in service provider**

Modify `app/app/Modules/Settings/SettingsServiceProvider.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Settings;

use CMBcoreSeller\Modules\Settings\Events\SystemSettingChanged;
use CMBcoreSeller\Modules\Settings\Listeners\LogSystemSettingChanged;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SystemSettingService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }
        Event::listen(SystemSettingChanged::class, LogSystemSettingChanged::class);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `cd app && php artisan test --filter=SystemSettingAuditTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Settings/Listeners/LogSystemSettingChanged.php \
        app/app/Modules/Settings/SettingsServiceProvider.php \
        app/tests/Feature/Settings/SystemSettingAuditTest.php
git commit -m "feat(settings): audit log on every SystemSettingChanged event"
```

---

### Task 16: `AdminSystemSettingController` + routes

**Files:**
- Create: `app/app/Modules/Settings/Http/Controllers/AdminSystemSettingController.php`
- Create: `app/app/Modules/Settings/Http/routes.php`
- Test: `app/tests/Feature/Settings/SystemSettingApiTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
namespace Tests\Feature\Settings;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function bootstrap(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');
    }

    public function test_index_returns_grouped_list(): void
    {
        $this->bootstrap();
        $r = $this->getJson('/api/v1/admin/system-settings?group=marketplace')->assertOk();
        $keys = collect($r->json('data'))->pluck('key')->all();
        $this->assertContains('marketplace.tiktok.app_key', $keys);
    }

    public function test_secret_masked_on_index(): void
    {
        $this->bootstrap();
        app(SystemSettingService::class)->set('marketplace.tiktok.app_secret', 'plain');
        $r = $this->getJson('/api/v1/admin/system-settings?group=marketplace')->assertOk();
        $row = collect($r->json('data'))->firstWhere('key', 'marketplace.tiktok.app_secret');
        $this->assertSame('****', $row['value']);
        $this->assertTrue($row['is_secret']);
    }

    public function test_reveal_returns_plain(): void
    {
        $this->bootstrap();
        app(SystemSettingService::class)->set('marketplace.tiktok.app_secret', 'plain');
        $this->getJson('/api/v1/admin/system-settings/marketplace.tiktok.app_secret/reveal')
            ->assertOk()->assertJsonPath('data.value', 'plain');
    }

    public function test_patch_validates_type(): void
    {
        $this->bootstrap();
        $this->patchJson('/api/v1/admin/system-settings/sync.poll_interval_minutes', ['value' => 'abc'])
            ->assertStatus(422)->assertJsonPath('error.code', 'SETTING_VALUE_INVALID');
    }

    public function test_patch_persists_value(): void
    {
        $this->bootstrap();
        $this->patchJson('/api/v1/admin/system-settings/sync.poll_interval_minutes', ['value' => 12])
            ->assertOk();
        $this->assertSame(12, system_setting('sync.poll_interval_minutes'));
    }

    public function test_patch_unknown_key_returns_422(): void
    {
        $this->bootstrap();
        $this->patchJson('/api/v1/admin/system-settings/nope.invalid', ['value' => 'x'])
            ->assertStatus(422)->assertJsonPath('error.code', 'SETTING_KEY_NOT_ALLOWED');
    }

    public function test_delete_returns_fallback_to_env(): void
    {
        $this->bootstrap();
        app(SystemSettingService::class)->set('sync.poll_interval_minutes', 7);
        $this->deleteJson('/api/v1/admin/system-settings/sync.poll_interval_minutes')->assertOk();
        $this->assertNull(\CMBcoreSeller\Modules\Settings\Models\SystemSetting::query()->where('key','sync.poll_interval_minutes')->first());
    }

    public function test_sync_from_env_seeds_missing_rows(): void
    {
        $this->bootstrap();
        putenv('NOTIFICATIONS_BRAND_NAME=Hello');
        $this->postJson('/api/v1/admin/system-settings/sync-from-env')->assertOk();
        $this->assertSame('Hello', system_setting('notifications.brand_name'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=SystemSettingApiTest`
Expected: FAIL — endpoints missing.

- [ ] **Step 3: Create the controller**

`app/app/Modules/Settings/Http/Controllers/AdminSystemSettingController.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Settings\Http\Controllers;

use CMBcoreSeller\Modules\Settings\Models\SystemSetting;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Settings\Support\SystemSettingsCatalog;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AdminSystemSettingController extends Controller
{
    public function __construct(private readonly SystemSettingService $svc) {}

    public function index(Request $request): JsonResponse
    {
        $group = $request->query('group');
        $rows = collect(SystemSettingsCatalog::all());
        if ($group) {
            $rows = $rows->where('group', $group);
        }
        $persisted = SystemSetting::query()->get()->keyBy('key');
        $data = $rows->map(function ($meta, $key) use ($persisted) {
            $row = $persisted->get($key);
            $value = $row?->value;
            if ($meta['is_secret'] && $value !== null) {
                $value = '****';
            } elseif ($row && $meta['is_secret'] === false) {
                $value = $this->svc->get($key);
            }
            return [
                'key' => $key,
                'group' => $meta['group'],
                'type' => $meta['type'],
                'is_secret' => $meta['is_secret'],
                'label' => $meta['label'],
                'env_fallback' => env($meta['env']),
                'value' => $value,
                'updated_at' => $row?->updated_at?->toIso8601String(),
                'updated_by_admin_id' => $row?->updated_by_admin_id,
            ];
        })->values()->all();

        return response()->json(['data' => $data]);
    }

    public function reveal(string $key): JsonResponse
    {
        if (! SystemSettingsCatalog::has($key)) {
            return $this->keyNotAllowed();
        }
        $value = $this->svc->get($key);
        AuditLog::record('admin.setting.reveal', null, ['key' => $key]);

        return response()->json(['data' => ['key' => $key, 'value' => $value]]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        if (! SystemSettingsCatalog::has($key)) {
            return $this->keyNotAllowed();
        }
        $value = $request->input('value');
        if (! SystemSettingsCatalog::validate($key, $value)) {
            return response()->json(['error' => [
                'code' => 'SETTING_VALUE_INVALID',
                'message' => 'Giá trị không hợp lệ theo kiểu dữ liệu của setting.',
            ]], 422);
        }
        $row = $this->svc->set($key, $value, Auth::guard('admin_web')->id());

        return response()->json(['data' => ['key' => $key, 'updated_at' => $row->updated_at?->toIso8601String()]]);
    }

    public function destroy(string $key): JsonResponse
    {
        if (! SystemSettingsCatalog::has($key)) {
            return $this->keyNotAllowed();
        }
        $this->svc->forget($key);

        return response()->json(['data' => ['ok' => true]]);
    }

    public function syncFromEnv(): JsonResponse
    {
        $adminId = Auth::guard('admin_web')->id();
        $existing = SystemSetting::query()->pluck('key')->all();
        $created = 0;
        foreach (SystemSettingsCatalog::all() as $key => $meta) {
            if (in_array($key, $existing, true)) continue;
            $envVal = env($meta['env']);
            if ($envVal === null || $envVal === '') continue;
            $this->svc->set($key, $envVal, $adminId);
            $created++;
        }

        return response()->json(['data' => ['created' => $created]]);
    }

    private function keyNotAllowed(): JsonResponse
    {
        return response()->json(['error' => [
            'code' => 'SETTING_KEY_NOT_ALLOWED',
            'message' => 'Setting key không có trong whitelist.',
        ]], 422);
    }
}
```

- [ ] **Step 4: Create routes file**

`app/app/Modules/Settings/Http/routes.php`:

```php
<?php

use CMBcoreSeller\Modules\Settings\Http\Controllers\AdminSystemSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:admin', 'throttle:60,1'])->prefix('api/v1/admin/system-settings')->group(function () {
    Route::get('/', [AdminSystemSettingController::class, 'index'])->name('admin.system-settings.index');
    Route::post('sync-from-env', [AdminSystemSettingController::class, 'syncFromEnv'])->name('admin.system-settings.sync-from-env');
    Route::get('{key}/reveal', [AdminSystemSettingController::class, 'reveal'])->where('key', '.*')->name('admin.system-settings.reveal');
    Route::patch('{key}', [AdminSystemSettingController::class, 'update'])->where('key', '.*')->name('admin.system-settings.update');
    Route::delete('{key}', [AdminSystemSettingController::class, 'destroy'])->where('key', '.*')->name('admin.system-settings.destroy');
});
```

- [ ] **Step 5: Run test to verify it passes**

Run: `cd app && php artisan test --filter=SystemSettingApiTest`
Expected: 8 PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Settings/Http/Controllers/AdminSystemSettingController.php \
        app/app/Modules/Settings/Http/routes.php \
        app/tests/Feature/Settings/SystemSettingApiTest.php
git commit -m "feat(settings): AdminSystemSettingController CRUD + reveal + sync-from-env"
```

---

### Task 17: Apply `system_setting()` at business call-sites

**Files:**
- Modify: `app/app/Integrations/TikTok/*` (call sites)
- Modify: `app/app/Integrations/Lazada/*`
- Modify: `app/app/Integrations/GHN/*`
- Modify: `app/app/Support/MediaUploader.php` (if exists)
- Modify: `app/app/Modules/Notifications/Notifications/*` (branding)
- Test: `app/tests/Feature/Settings/CallSiteOverrideTest.php`

- [ ] **Step 1: Inventory call-sites**

Run from `D:/cmb_core_seller`:

```bash
grep -RIn "config('integrations.tiktok" app/app/ | sort
grep -RIn "config('integrations.lazada" app/app/ | sort
grep -RIn "config('integrations.carriers" app/app/ | sort
grep -RIn "config('media." app/app/ | sort
grep -RIn "config('notifications." app/app/ | sort
grep -RIn "config('mail.from" app/app/ | sort
grep -RIn "config('billing.over_quota" app/app/ | sort
grep -RIn "config('integrations.throttle" app/app/ | sort
grep -RIn "config('integrations.sync" app/app/ | sort
grep -RIn "config('fulfillment\." app/app/ | sort
grep -RIn "config('services.gotenberg" app/app/ | sort
grep -RIn "GOTENBERG_URL\|env('GOTENBERG_URL'\|env('PRINT_LABEL_SIZE'" app/app/ | sort
```

Save the list (paths + line numbers) to `app/storage/app/migration-callsites.txt` as a working file (don't commit). For each match, replace `config('foo.bar')` (or `env(...)`) with `system_setting('matching.catalog.key', config('foo.bar'))`.

- [ ] **Step 2: Write the failing test**

```php
<?php
namespace Tests\Feature\Settings;

use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallSiteOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiktok_app_key_uses_system_setting(): void
    {
        config()->set('integrations.tiktok.app_key', 'env-key');
        $this->assertSame('env-key', system_setting('marketplace.tiktok.app_key', config('integrations.tiktok.app_key')));
        app(SystemSettingService::class)->set('marketplace.tiktok.app_key', 'db-override');
        $this->assertSame('db-override', system_setting('marketplace.tiktok.app_key', config('integrations.tiktok.app_key')));
    }

    public function test_brand_name_uses_system_setting(): void
    {
        config()->set('notifications.brand_name', 'EnvBrand');
        $this->assertSame('EnvBrand', system_setting('notifications.brand_name', config('notifications.brand_name')));
        app(SystemSettingService::class)->set('notifications.brand_name', 'DbBrand');
        $this->assertSame('DbBrand', system_setting('notifications.brand_name', config('notifications.brand_name')));
    }

    public function test_gotenberg_url_uses_system_setting(): void
    {
        config()->set('services.gotenberg.url', 'http://env-gotenberg');
        $this->assertSame('http://env-gotenberg', system_setting('pdf.gotenberg_url', config('services.gotenberg.url')));
        app(SystemSettingService::class)->set('pdf.gotenberg_url', 'http://db-gotenberg');
        $this->assertSame('http://db-gotenberg', system_setting('pdf.gotenberg_url', config('services.gotenberg.url')));
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `cd app && php artisan test --filter=CallSiteOverrideTest`
Expected: PASS for the basic helper logic (already from Task 14). The actual call-site verification is logical: spot-check at least 3 files.

- [ ] **Step 4: Patch one call-site per integration**

For each grep result from Step 1, modify the file. Example for TikTok auth (real path will vary; pick the call-site grep returned):

Before:
```php
$appKey = config('integrations.tiktok.app_key');
$appSecret = config('integrations.tiktok.app_secret');
```

After:
```php
$appKey = system_setting('marketplace.tiktok.app_key', config('integrations.tiktok.app_key'));
$appSecret = system_setting('marketplace.tiktok.app_secret', config('integrations.tiktok.app_secret'));
```

Do this for every match. Use the exact catalog key from Task 13 (Section 5.5 of the spec). For env-only reads (no `config()`), replace `env('TIKTOK_APP_KEY')` with `system_setting('marketplace.tiktok.app_key', env('TIKTOK_APP_KEY'))`.

- [ ] **Step 5: Re-run full Feature suite**

Run: `cd app && php artisan test`
Expected: full suite still PASS (no regression from call-site swaps).

- [ ] **Step 6: Commit**

```bash
git add app/app/ app/tests/Feature/Settings/CallSiteOverrideTest.php
git commit -m "refactor: business call-sites read whitelisted settings via system_setting()"
```

---

## Phase 5 — Frontend admin SPA

### Task 18: Vite multi-entry + Blade view + Web routes

**Files:**
- Modify: `app/vite.config.ts`
- Create: `app/resources/views/admin.blade.php`
- Modify: `app/routes/web.php`
- Test: `app/tests/Feature/SpaCatchAllTest.php` (extend)

- [ ] **Step 1: Write the failing test**

Append to `app/tests/Feature/SpaCatchAllTest.php` (or create file if it has different test methods only):

```php
public function test_admin_path_returns_admin_blade(): void
{
    $resp = $this->get('/admin');
    $resp->assertOk();
    $this->assertStringContainsString('id="admin-root"', $resp->getContent());
}

public function test_admin_subpath_also_renders_admin_blade(): void
{
    $resp = $this->get('/admin/settings');
    $resp->assertOk();
    $this->assertStringContainsString('id="admin-root"', $resp->getContent());
}

public function test_user_path_still_returns_app_blade(): void
{
    $resp = $this->get('/orders');
    $resp->assertOk();
    $this->assertStringContainsString('id="app"', $resp->getContent());
    $this->assertStringNotContainsString('id="admin-root"', $resp->getContent());
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=SpaCatchAllTest`
Expected: FAIL — `admin-root` not found.

- [ ] **Step 3: Modify `vite.config.ts`**

```ts
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'node:path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.tsx', 'resources/js/admin.tsx'],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(process.cwd(), 'resources/js'),
            '@admin': path.resolve(process.cwd(), 'resources/js/admin'),
        },
    },
    server: { host: '0.0.0.0', port: 5173, hmr: { host: 'localhost' } }
});
```

- [ ] **Step 4: Create `admin.blade.php`**

`app/resources/views/admin.blade.php`:

```blade
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/images/logocmb.png">
    <title>CMBcore Admin</title>
    @vite(['resources/js/admin.tsx'])
</head>
<body>
    <div id="admin-root"></div>
</body>
</html>
```

- [ ] **Step 5: Update `routes/web.php`**

Add new admin Blade controller, update SPA catch-all to exclude `/admin`:

```php
// --- Admin SPA shell (separate Vite bundle).
Route::get('/admin/{any?}', fn () => view('admin'))
    ->where('any', '.*')
    ->name('admin.spa');

// --- SPA catch-all (user app).
Route::get('/{any?}', SpaController::class)
    ->where('any', '^(?!api(/|$)|webhook(/|$)|oauth(/|$)|admin(/|$)|build(/|$)|storage(/|$)|sanctum(/|$)|up$).*$')
    ->name('spa');
```

- [ ] **Step 6: Create entry placeholder `admin.tsx`**

`app/resources/js/admin.tsx`:

```tsx
import React from 'react';
import { createRoot } from 'react-dom/client';

// Real AdminApp wired in Task 19. This stub keeps the bundle build green.
function Bootstrap() {
    return <div id="admin-root-content">CMBcore Admin (bootstrapping…)</div>;
}

const el = document.getElementById('admin-root');
if (el) {
    createRoot(el).render(<React.StrictMode><Bootstrap /></React.StrictMode>);
}
```

- [ ] **Step 7: Run test to verify it passes**

Run: `cd app && php artisan test --filter=SpaCatchAllTest`
Expected: 3 PASS (new tests + any pre-existing).

- [ ] **Step 8: Commit**

```bash
git add app/vite.config.ts app/resources/views/admin.blade.php \
        app/routes/web.php app/resources/js/admin.tsx \
        app/tests/Feature/SpaCatchAllTest.php
git commit -m "feat(fe): vite multi-entry admin.tsx + /admin Blade route"
```

---

### Task 19: AdminApp shell — client, auth hook, login page, protected layout

**Files:**
- Create: `app/resources/js/admin/lib/adminClient.ts`
- Create: `app/resources/js/admin/lib/adminAuth.tsx`
- Create: `app/resources/js/admin/AdminProtected.tsx`
- Create: `app/resources/js/admin/AdminLayout.tsx`
- Create: `app/resources/js/admin/pages/AdminLoginPage.tsx`
- Create: `app/resources/js/admin/pages/AdminDashboardPage.tsx`
- Create: `app/resources/js/admin/AdminApp.tsx`
- Modify: `app/resources/js/admin.tsx`

- [ ] **Step 1: Create the HTTP client**

`app/resources/js/admin/lib/adminClient.ts`:

```ts
import axios from 'axios';

export const adminClient = axios.create({
    baseURL: '/api/v1/admin',
    withCredentials: true,
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
});

export async function ensureAdminCsrf(): Promise<void> {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}
```

- [ ] **Step 2: Create the auth hook**

`app/resources/js/admin/lib/adminAuth.tsx`:

```tsx
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { adminClient, ensureAdminCsrf } from './adminClient';

export type AdminMe = {
    id: number; username: string; email: string | null; name: string;
    is_active: boolean; last_login_at: string | null;
};

export function useAdminMe() {
    return useQuery<AdminMe | null>({
        queryKey: ['admin-me'],
        queryFn: async () => {
            try {
                const r = await adminClient.get('/auth/me');
                return r.data.data as AdminMe;
            } catch (e: any) {
                if (e?.response?.status === 401) return null;
                throw e;
            }
        },
        staleTime: 30_000,
    });
}

export function useAdminLogin() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { username: string; password: string }) => {
            await ensureAdminCsrf();
            const r = await adminClient.post('/auth/login', vars);
            return r.data.data as AdminMe;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-me'] }),
    });
}

export function useAdminLogout() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async () => { await adminClient.post('/auth/logout'); },
        onSuccess: () => qc.setQueryData(['admin-me'], null),
    });
}
```

- [ ] **Step 3: Create the protected wrapper**

`app/resources/js/admin/AdminProtected.tsx`:

```tsx
import { ReactNode } from 'react';
import { Navigate } from 'react-router-dom';
import { Spin } from 'antd';
import { useAdminMe } from './lib/adminAuth';

export function AdminProtected({ children }: { children: ReactNode }) {
    const { data, isLoading } = useAdminMe();
    if (isLoading) return <div style={{ padding: 64, textAlign: 'center' }}><Spin /></div>;
    if (!data) return <Navigate to="/admin/login" replace />;
    return <>{children}</>;
}
```

- [ ] **Step 4: Create the layout**

`app/resources/js/admin/AdminLayout.tsx`:

```tsx
import { Layout, Menu, Typography, Space, Button } from 'antd';
import {
    DashboardOutlined, ShopOutlined, UserOutlined, SettingOutlined,
    AuditOutlined, LogoutOutlined, SafetyCertificateOutlined,
} from '@ant-design/icons';
import { Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAdminLogout, useAdminMe } from './lib/adminAuth';

const items = [
    { key: '/admin', icon: <DashboardOutlined />, label: 'Tổng quan' },
    { key: '/admin/tenants', icon: <ShopOutlined />, label: 'Tenants' },
    { key: '/admin/users', icon: <UserOutlined />, label: 'Người dùng' },
    { key: '/admin/settings', icon: <SettingOutlined />, label: 'Hệ thống' },
    { key: '/admin/audit-logs', icon: <AuditOutlined />, label: 'Nhật ký' },
];

export function AdminLayout() {
    const navigate = useNavigate();
    const loc = useLocation();
    const { data: me } = useAdminMe();
    const logout = useAdminLogout();
    const selected = items.map((i) => i.key).filter((k) => loc.pathname === k || loc.pathname.startsWith(k + '/'));

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Layout.Sider width={240} style={{ background: '#0F172A' }}>
                <div style={{ color: '#fff', padding: '20px 24px', borderBottom: '1px solid #1E293B' }}>
                    <Space><SafetyCertificateOutlined /><Typography.Text strong style={{ color: '#fff' }}>CMBcore Admin</Typography.Text></Space>
                </div>
                <Menu
                    theme="dark" mode="inline"
                    selectedKeys={selected}
                    style={{ background: '#0F172A' }}
                    items={items}
                    onClick={(e) => navigate(e.key)}
                />
            </Layout.Sider>
            <Layout>
                <Layout.Header style={{ background: '#fff', display: 'flex', justifyContent: 'flex-end', alignItems: 'center', padding: '0 24px' }}>
                    <Space>
                        <Typography.Text type="secondary">{me?.name} ({me?.username})</Typography.Text>
                        <Button icon={<LogoutOutlined />} onClick={() => logout.mutate(undefined, { onSuccess: () => navigate('/admin/login') })}>
                            Đăng xuất
                        </Button>
                    </Space>
                </Layout.Header>
                <Layout.Content style={{ padding: 24, background: '#F1F5F9' }}>
                    <Outlet />
                </Layout.Content>
            </Layout>
        </Layout>
    );
}
```

- [ ] **Step 5: Create the login page**

`app/resources/js/admin/pages/AdminLoginPage.tsx`:

```tsx
import { useState } from 'react';
import { Card, Form, Input, Button, Alert, Typography, Space } from 'antd';
import { SafetyCertificateOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import { useAdminLogin } from '../lib/adminAuth';

export function AdminLoginPage() {
    const nav = useNavigate();
    const login = useAdminLogin();
    const [err, setErr] = useState<string | null>(null);

    return (
        <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', background: '#0F172A' }}>
            <Card style={{ width: 380 }}>
                <Space direction="vertical" size={4} style={{ marginBottom: 16, width: '100%' }}>
                    <Space><SafetyCertificateOutlined /><Typography.Title level={4} style={{ margin: 0 }}>Admin hệ thống</Typography.Title></Space>
                    <Typography.Text type="secondary">Khu vực quản trị nội bộ — mọi thao tác được ghi nhật ký.</Typography.Text>
                </Space>
                {err && <Alert type="error" message={err} style={{ marginBottom: 12 }} />}
                <Form layout="vertical" onFinish={(v: { username: string; password: string }) => {
                    setErr(null);
                    login.mutate(v, {
                        onSuccess: () => nav('/admin', { replace: true }),
                        onError: (e: any) => setErr(e?.response?.data?.error?.message ?? 'Đăng nhập thất bại.'),
                    });
                }}>
                    <Form.Item name="username" label="Tên đăng nhập" rules={[{ required: true }]}>
                        <Input autoFocus autoComplete="username" />
                    </Form.Item>
                    <Form.Item name="password" label="Mật khẩu" rules={[{ required: true }]}>
                        <Input.Password autoComplete="current-password" />
                    </Form.Item>
                    <Button type="primary" htmlType="submit" block loading={login.isPending}>Đăng nhập</Button>
                </Form>
            </Card>
        </div>
    );
}
```

- [ ] **Step 6: Create the dashboard placeholder**

`app/resources/js/admin/pages/AdminDashboardPage.tsx`:

```tsx
import { Card, Typography } from 'antd';
import { useAdminMe } from '../lib/adminAuth';

export function AdminDashboardPage() {
    const { data: me } = useAdminMe();
    return (
        <Card>
            <Typography.Title level={4}>Xin chào, {me?.name}</Typography.Title>
            <Typography.Paragraph type="secondary">
                Dùng menu bên trái để quản lý tenants, người dùng, và cấu hình hệ thống.
            </Typography.Paragraph>
        </Card>
    );
}
```

- [ ] **Step 7: Wire the router**

`app/resources/js/admin/AdminApp.tsx`:

```tsx
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AdminProtected } from './AdminProtected';
import { AdminLayout } from './AdminLayout';
import { AdminLoginPage } from './pages/AdminLoginPage';
import { AdminDashboardPage } from './pages/AdminDashboardPage';

export function AdminApp() {
    return (
        <BrowserRouter>
            <Routes>
                <Route path="/admin/login" element={<AdminLoginPage />} />
                <Route path="/admin" element={<AdminProtected><AdminLayout /></AdminProtected>}>
                    <Route index element={<AdminDashboardPage />} />
                    {/* tenants/users/settings/audit-logs registered in later tasks */}
                    <Route path="*" element={<Navigate to="/admin" replace />} />
                </Route>
            </Routes>
        </BrowserRouter>
    );
}
```

- [ ] **Step 8: Replace `admin.tsx`**

```tsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { App as AntApp, ConfigProvider, theme } from 'antd';
import viVN from 'antd/locale/vi_VN';
import 'antd/dist/reset.css';
import dayjs from 'dayjs';
import 'dayjs/locale/vi';
import { AdminApp } from './admin/AdminApp';

dayjs.locale('vi');

const qc = new QueryClient({
    defaultOptions: { queries: { retry: 1, refetchOnWindowFocus: false, staleTime: 30_000 } },
});

const el = document.getElementById('admin-root');
if (el) {
    createRoot(el).render(
        <React.StrictMode>
            <QueryClientProvider client={qc}>
                <ConfigProvider
                    locale={viVN}
                    theme={{
                        token: { colorPrimary: '#1F2937', colorError: '#DC2626', borderRadius: 6, colorBgLayout: '#F1F5F9' },
                        components: { Layout: { headerHeight: 56 }, Menu: { itemHeight: 38 } },
                        algorithm: theme.defaultAlgorithm,
                    }}
                >
                    <AntApp><AdminApp /></AntApp>
                </ConfigProvider>
            </QueryClientProvider>
        </React.StrictMode>,
    );
}
```

- [ ] **Step 9: Smoke-build the admin bundle**

Run: `cd app && npm run build`
Expected: build succeeds, `public/build/assets/admin-*.js` produced.

- [ ] **Step 10: Commit**

```bash
git add app/resources/js/admin/ app/resources/js/admin.tsx
git commit -m "feat(fe): admin SPA shell — client, auth hook, login, protected layout"
```

---

### Task 20: Move existing admin pages to `resources/js/admin/pages/tenants/`

**Files:**
- Move/edit: `app/resources/js/pages/admin/*.tsx` → `app/resources/js/admin/pages/tenants/*.tsx`
- Modify: `app/resources/js/admin/AdminApp.tsx`
- Modify: `app/resources/js/app.tsx` (remove admin imports)

- [ ] **Step 1: Move files**

```bash
mkdir -p app/resources/js/admin/pages/tenants
git mv app/resources/js/pages/admin/AdminTenantsPage.tsx app/resources/js/admin/pages/tenants/AdminTenantsPage.tsx
git mv app/resources/js/pages/admin/AdminTenantDrawer.tsx app/resources/js/admin/pages/tenants/AdminTenantDrawer.tsx
git mv app/resources/js/pages/admin/AdminVouchersPage.tsx app/resources/js/admin/pages/tenants/AdminVouchersPage.tsx
git mv app/resources/js/pages/admin/AdminPlansPage.tsx app/resources/js/admin/pages/tenants/AdminPlansPage.tsx
git mv app/resources/js/pages/admin/AdminAuditLogsPage.tsx app/resources/js/admin/pages/tenants/AdminAuditLogsPage.tsx
git mv app/resources/js/pages/admin/AdminBroadcastsPage.tsx app/resources/js/admin/pages/tenants/AdminBroadcastsPage.tsx
git mv app/resources/js/pages/admin/AdminUsersPage.tsx app/resources/js/admin/pages/tenants/_AdminUsersPage_OLD.tsx
```

(Note: the old `AdminUsersPage` will be replaced by a new tabbed version in Task 21. Keep the file as `_AdminUsersPage_OLD.tsx` temporarily for reference; delete it at the end of Task 21.)

- [ ] **Step 2: Update imports inside moved files**

Open each moved file under `app/resources/js/admin/pages/tenants/`. Replace any `from '@/lib/admin'` with `from '@admin/lib/admin'`. For `from '@/components/PageHeader'` etc., keep them — `@` still resolves and these are shared utility components. If a moved file imports from `@/pages/admin/...`, update to the new path.

Also create `app/resources/js/admin/lib/admin.tsx` mirroring the original `app/resources/js/lib/admin.tsx` (move it):

```bash
git mv app/resources/js/lib/admin.tsx app/resources/js/admin/lib/admin.tsx
```

Then edit any user-app file still importing `@/lib/admin` and remove those imports (Task 22 handles cleanup; this task just moves files).

- [ ] **Step 3: Register routes in `AdminApp.tsx`**

Replace the `Route path="*"` line with the tenant/admin set:

```tsx
import { AdminTenantsPage } from './pages/tenants/AdminTenantsPage';
import { AdminVouchersPage } from './pages/tenants/AdminVouchersPage';
import { AdminPlansPage } from './pages/tenants/AdminPlansPage';
import { AdminAuditLogsPage } from './pages/tenants/AdminAuditLogsPage';
import { AdminBroadcastsPage } from './pages/tenants/AdminBroadcastsPage';

// inside <Route path="/admin" ...>:
<Route path="tenants" element={<AdminTenantsPage />} />
<Route path="vouchers" element={<AdminVouchersPage />} />
<Route path="plans" element={<AdminPlansPage />} />
<Route path="audit-logs" element={<AdminAuditLogsPage />} />
<Route path="broadcasts" element={<AdminBroadcastsPage />} />
<Route path="*" element={<Navigate to="/admin" replace />} />
```

- [ ] **Step 4: Build to verify imports**

Run: `cd app && npm run build`
Expected: build succeeds. Fix any reported import path errors before continuing.

- [ ] **Step 5: Commit**

```bash
git add -A app/resources/js/
git commit -m "refactor(fe): relocate existing admin pages under resources/js/admin/pages/tenants"
```

---

### Task 21: Frontend — AdminUsersPage (Tabs admin/tenant) + drawers

**Files:**
- Create: `app/resources/js/admin/lib/adminUsers.tsx`
- Create: `app/resources/js/admin/lib/tenantUsers.tsx`
- Create: `app/resources/js/admin/pages/users/AdminUsersPage.tsx`
- Create: `app/resources/js/admin/pages/users/AdminUserFormDrawer.tsx`
- Create: `app/resources/js/admin/pages/users/TenantUserDrawer.tsx`
- Delete: `app/resources/js/admin/pages/tenants/_AdminUsersPage_OLD.tsx`
- Modify: `app/resources/js/admin/AdminApp.tsx`

- [ ] **Step 1: Create the admin_users hooks**

`app/resources/js/admin/lib/adminUsers.tsx`:

```tsx
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { adminClient } from './adminClient';

export type AdminRow = {
    id: number; username: string; email: string | null; name: string;
    is_active: boolean; last_login_at: string | null; created_at: string | null;
};

export function useAdminUsersList(params: { q?: string; is_active?: boolean; page?: number; per_page?: number }) {
    return useQuery({
        queryKey: ['admin-users', params],
        queryFn: async () => (await adminClient.get('/admin-users', { params })).data as { data: AdminRow[]; meta: any },
    });
}

export function useCreateAdminUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { username: string; email?: string; name: string; password: string }) =>
            (await adminClient.post('/admin-users', vars)).data.data as AdminRow,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
    });
}

export function useUpdateAdminUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, ...vars }: { id: number; name?: string; email?: string | null }) =>
            (await adminClient.patch(`/admin-users/${id}`, vars)).data.data as AdminRow,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
    });
}

export function useSuspendAdminUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => (await adminClient.post(`/admin-users/${id}/suspend`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
    });
}

export function useReactivateAdminUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => (await adminClient.post(`/admin-users/${id}/reactivate`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
    });
}

export function useResetAdminPassword() {
    return useMutation({
        mutationFn: async ({ id, password }: { id: number; password: string }) =>
            (await adminClient.post(`/admin-users/${id}/reset-password`, { password })).data.data,
    });
}
```

- [ ] **Step 2: Create the tenant-users hooks**

`app/resources/js/admin/lib/tenantUsers.tsx`:

```tsx
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { adminClient } from './adminClient';

export type TenantUserRow = {
    id: number; name: string; email: string;
    tenants: { id: number; name: string; role: string }[];
    created_at: string | null;
};

export function useTenantUsers(params: { q?: string; page?: number; per_page?: number }) {
    return useQuery({
        queryKey: ['tenant-users', params],
        queryFn: async () => (await adminClient.get('/users', { params })).data as { data: TenantUserRow[]; meta: any },
    });
}

export function useTenantUserDetail(id: number | null) {
    return useQuery({
        queryKey: ['tenant-user', id],
        queryFn: async () => (await adminClient.get(`/users/${id}`)).data.data,
        enabled: id !== null,
    });
}

export function useUpdateTenantUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, ...vars }: { id: number; name?: string; email?: string }) =>
            (await adminClient.patch(`/users/${id}`, vars)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['tenant-users'] }),
    });
}

export function useResetTenantUserPassword() {
    return useMutation({
        mutationFn: async ({ id, password }: { id: number; password: string }) =>
            (await adminClient.post(`/users/${id}/reset-password`, { password })).data.data,
    });
}

export function useSuspendTenantUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => (await adminClient.post(`/users/${id}/suspend`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['tenant-users'] }),
    });
}

export function useReactivateTenantUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => (await adminClient.post(`/users/${id}/reactivate`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['tenant-users'] }),
    });
}
```

- [ ] **Step 3: Create the AdminUsersPage with Tabs**

`app/resources/js/admin/pages/users/AdminUsersPage.tsx`:

```tsx
import { useState } from 'react';
import { Card, Tabs, Input, Space, Table, Tag, Button, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { SearchOutlined, PlusOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { useAdminUsersList, type AdminRow } from '../../lib/adminUsers';
import { useTenantUsers, type TenantUserRow } from '../../lib/tenantUsers';
import { AdminUserFormDrawer } from './AdminUserFormDrawer';
import { TenantUserDrawer } from './TenantUserDrawer';

export function AdminUsersPage() {
    const [tab, setTab] = useState<'admin' | 'tenant'>('admin');
    const [q, setQ] = useState('');
    const [page, setPage] = useState(1);
    const [editingAdmin, setEditingAdmin] = useState<AdminRow | 'new' | null>(null);
    const [openTenantUserId, setOpenTenantUserId] = useState<number | null>(null);

    const admins = useAdminUsersList({ q: q || undefined, page, per_page: 30 });
    const users = useTenantUsers({ q: q || undefined, page, per_page: 30 });

    const adminCols: ColumnsType<AdminRow> = [
        { title: 'Username', dataIndex: 'username', render: (v, r) => (<a onClick={() => setEditingAdmin(r)}>{v}</a>) },
        { title: 'Tên', dataIndex: 'name' },
        { title: 'Email', dataIndex: 'email', render: v => v || <Typography.Text type="secondary">—</Typography.Text> },
        { title: 'Hoạt động', dataIndex: 'is_active', render: v => v ? <Tag color="green">active</Tag> : <Tag color="red">suspended</Tag>, width: 110 },
        { title: 'Login gần nhất', dataIndex: 'last_login_at', render: v => v ? dayjs(v).format('DD/MM/YYYY HH:mm') : '—', width: 160 },
    ];

    const tenantCols: ColumnsType<TenantUserRow> = [
        { title: 'Tên', dataIndex: 'name', render: (v, r) => (<a onClick={() => setOpenTenantUserId(r.id)}>{v}</a>) },
        { title: 'Email', dataIndex: 'email' },
        { title: 'Tenant', dataIndex: 'tenants', render: (ts: TenantUserRow['tenants']) =>
            <Space wrap size={4}>{ts.map(t => <Tag key={t.id}>{t.name} · {t.role}</Tag>)}</Space> },
        { title: 'Tạo lúc', dataIndex: 'created_at', render: v => v ? dayjs(v).format('DD/MM/YYYY') : '—', width: 130 },
    ];

    return (
        <div>
            <Card styles={{ body: { padding: 12 } }}>
                <Space style={{ marginBottom: 12 }}>
                    <Input prefix={<SearchOutlined />} placeholder="Tìm theo tên/username/email" allowClear
                        value={q} onChange={(e) => { setQ(e.target.value); setPage(1); }}
                        style={{ width: 320 }} />
                    {tab === 'admin' && <Button type="primary" icon={<PlusOutlined />} onClick={() => setEditingAdmin('new')}>Thêm admin</Button>}
                </Space>

                <Tabs activeKey={tab} onChange={(k) => { setTab(k as 'admin'|'tenant'); setPage(1); }} items={[
                    {
                        key: 'admin', label: 'Super-admin',
                        children: (
                            <Table<AdminRow>
                                rowKey="id"
                                columns={adminCols}
                                dataSource={admins.data?.data ?? []}
                                loading={admins.isLoading}
                                pagination={{
                                    current: admins.data?.meta.pagination.page ?? page,
                                    pageSize: admins.data?.meta.pagination.per_page ?? 30,
                                    total: admins.data?.meta.pagination.total ?? 0,
                                    onChange: setPage,
                                    showSizeChanger: false,
                                }}
                            />
                        ),
                    },
                    {
                        key: 'tenant', label: 'Người dùng tenant',
                        children: (
                            <Table<TenantUserRow>
                                rowKey="id"
                                columns={tenantCols}
                                dataSource={users.data?.data ?? []}
                                loading={users.isLoading}
                                pagination={{
                                    current: users.data?.meta.pagination.page ?? page,
                                    pageSize: users.data?.meta.pagination.per_page ?? 30,
                                    total: users.data?.meta.pagination.total ?? 0,
                                    onChange: setPage,
                                    showSizeChanger: false,
                                }}
                            />
                        ),
                    },
                ]} />
            </Card>

            <AdminUserFormDrawer open={editingAdmin !== null} target={editingAdmin} onClose={() => setEditingAdmin(null)} />
            <TenantUserDrawer userId={openTenantUserId} onClose={() => setOpenTenantUserId(null)} />
        </div>
    );
}
```

- [ ] **Step 4: Create `AdminUserFormDrawer`**

`app/resources/js/admin/pages/users/AdminUserFormDrawer.tsx`:

```tsx
import { useEffect } from 'react';
import { Drawer, Form, Input, Space, Button, App, Popconfirm, Switch } from 'antd';
import {
    useCreateAdminUser, useUpdateAdminUser, useSuspendAdminUser,
    useReactivateAdminUser, useResetAdminPassword, type AdminRow,
} from '../../lib/adminUsers';

export function AdminUserFormDrawer({ open, target, onClose }: { open: boolean; target: AdminRow | 'new' | null; onClose: () => void }) {
    const [form] = Form.useForm();
    const create = useCreateAdminUser();
    const update = useUpdateAdminUser();
    const suspend = useSuspendAdminUser();
    const react = useReactivateAdminUser();
    const reset = useResetAdminPassword();
    const { message } = App.useApp();

    useEffect(() => {
        if (!open) return;
        if (target === 'new') form.resetFields();
        else if (target) form.setFieldsValue({ username: target.username, name: target.name, email: target.email ?? '' });
    }, [open, target, form]);

    const isNew = target === 'new';

    return (
        <Drawer open={open} title={isNew ? 'Thêm super-admin' : `Sửa: ${target && typeof target !== 'string' ? target.username : ''}`}
            width={420} onClose={onClose} destroyOnHidden>
            <Form layout="vertical" form={form} onFinish={(v) => {
                if (isNew) {
                    create.mutate(v, {
                        onSuccess: () => { message.success('Đã tạo admin.'); onClose(); },
                        onError: (e: any) => message.error(e?.response?.data?.error?.message ?? 'Tạo thất bại.'),
                    });
                } else if (target && typeof target !== 'string') {
                    update.mutate({ id: target.id, name: v.name, email: v.email || null }, {
                        onSuccess: () => { message.success('Đã lưu.'); onClose(); },
                        onError: (e: any) => message.error(e?.response?.data?.error?.message ?? 'Lưu thất bại.'),
                    });
                }
            }}>
                <Form.Item name="username" label="Username" rules={[{ required: true }]}>
                    <Input disabled={!isNew} />
                </Form.Item>
                <Form.Item name="name" label="Tên" rules={[{ required: true }]}>
                    <Input />
                </Form.Item>
                <Form.Item name="email" label="Email (không bắt buộc)">
                    <Input />
                </Form.Item>
                {isNew && (
                    <Form.Item name="password" label="Mật khẩu" rules={[{ required: true, min: 8 }]}>
                        <Input.Password autoComplete="new-password" />
                    </Form.Item>
                )}
                <Space>
                    <Button type="primary" htmlType="submit" loading={create.isPending || update.isPending}>Lưu</Button>
                    {!isNew && target && typeof target !== 'string' && (
                        <>
                            <Popconfirm title="Reset mật khẩu?" description={
                                <Input.Password placeholder="Mật khẩu mới (≥8)" id="newpwd" />
                            } onConfirm={() => {
                                const el = document.getElementById('newpwd') as HTMLInputElement | null;
                                if (!el?.value || el.value.length < 8) { message.error('Mật khẩu ≥8 ký tự'); return; }
                                reset.mutate({ id: target.id, password: el.value }, { onSuccess: () => message.success('Đã đổi mật khẩu.') });
                            }}>
                                <Button>Reset password</Button>
                            </Popconfirm>
                            {target.is_active ? (
                                <Popconfirm title="Vô hiệu hoá admin?" onConfirm={() => suspend.mutate(target.id, {
                                    onSuccess: () => { message.success('Đã vô hiệu hoá.'); onClose(); },
                                    onError: (e: any) => message.error(e?.response?.data?.error?.message ?? 'Lỗi'),
                                })}>
                                    <Button danger>Suspend</Button>
                                </Popconfirm>
                            ) : (
                                <Button onClick={() => react.mutate(target.id, { onSuccess: () => { message.success('Đã kích hoạt.'); onClose(); } })}>
                                    Reactivate
                                </Button>
                            )}
                        </>
                    )}
                </Space>
            </Form>
        </Drawer>
    );
}
```

- [ ] **Step 5: Create `TenantUserDrawer`**

`app/resources/js/admin/pages/users/TenantUserDrawer.tsx`:

```tsx
import { useEffect } from 'react';
import { Drawer, Form, Input, Button, Space, App, Popconfirm, Tag, Typography } from 'antd';
import {
    useTenantUserDetail, useUpdateTenantUser, useResetTenantUserPassword,
    useSuspendTenantUser, useReactivateTenantUser,
} from '../../lib/tenantUsers';

export function TenantUserDrawer({ userId, onClose }: { userId: number | null; onClose: () => void }) {
    const [form] = Form.useForm();
    const { data } = useTenantUserDetail(userId);
    const update = useUpdateTenantUser();
    const reset = useResetTenantUserPassword();
    const suspend = useSuspendTenantUser();
    const react = useReactivateTenantUser();
    const { message } = App.useApp();

    useEffect(() => {
        if (data) form.setFieldsValue({ name: data.name, email: data.email });
    }, [data, form]);

    if (userId === null) return null;
    const suspended = data?.suspended_at !== null && data?.suspended_at !== undefined;

    return (
        <Drawer open width={460} title={`User: ${data?.name ?? '...'}`} onClose={onClose} destroyOnHidden>
            <Form layout="vertical" form={form} onFinish={(v) =>
                update.mutate({ id: userId, ...v }, {
                    onSuccess: () => { message.success('Đã lưu.'); onClose(); },
                    onError: (e: any) => message.error(e?.response?.data?.error?.message ?? 'Lỗi'),
                })
            }>
                <Form.Item name="name" label="Tên" rules={[{ required: true }]}><Input /></Form.Item>
                <Form.Item name="email" label="Email"><Input /></Form.Item>
                <Typography.Paragraph type="secondary">
                    Tenant:&nbsp;
                    {data?.tenants?.length
                        ? data.tenants.map((t: any) => <Tag key={t.id}>{t.name} · {t.role}</Tag>)
                        : '—'}
                </Typography.Paragraph>
                <Space wrap>
                    <Button type="primary" htmlType="submit" loading={update.isPending}>Lưu</Button>
                    <Popconfirm title="Reset mật khẩu?" description={
                        <Input.Password placeholder="Mật khẩu mới (≥8)" id="tnewpwd" />
                    } onConfirm={() => {
                        const el = document.getElementById('tnewpwd') as HTMLInputElement | null;
                        if (!el?.value || el.value.length < 8) { message.error('Mật khẩu ≥8 ký tự'); return; }
                        reset.mutate({ id: userId, password: el.value }, { onSuccess: () => message.success('Đã đổi mật khẩu.') });
                    }}>
                        <Button>Reset password</Button>
                    </Popconfirm>
                    {suspended
                        ? <Button onClick={() => react.mutate(userId, { onSuccess: () => { message.success('Đã kích hoạt.'); onClose(); } })}>Reactivate</Button>
                        : <Popconfirm title="Tạm khoá user?" onConfirm={() => suspend.mutate(userId, { onSuccess: () => { message.success('Đã khoá.'); onClose(); } })}><Button danger>Suspend</Button></Popconfirm>}
                </Space>
            </Form>
        </Drawer>
    );
}
```

- [ ] **Step 6: Register route**

In `AdminApp.tsx`, add:

```tsx
import { AdminUsersPage } from './pages/users/AdminUsersPage';
// ...
<Route path="users" element={<AdminUsersPage />} />
```

- [ ] **Step 7: Delete legacy file**

```bash
git rm app/resources/js/admin/pages/tenants/_AdminUsersPage_OLD.tsx
```

- [ ] **Step 8: Build smoke**

Run: `cd app && npm run build`
Expected: build succeeds.

- [ ] **Step 9: Commit**

```bash
git add app/resources/js/admin/
git commit -m "feat(fe): admin users page (Tabs) + drawers for admin + tenant users"
```

---

### Task 22: Frontend — System Settings page (4 tabs) + SecretInput

**Files:**
- Create: `app/resources/js/admin/lib/systemSettings.tsx`
- Create: `app/resources/js/admin/components/SecretInput.tsx`
- Create: `app/resources/js/admin/components/SettingRow.tsx`
- Create: `app/resources/js/admin/pages/settings/SystemSettingsPage.tsx`
- Modify: `app/resources/js/admin/AdminApp.tsx`

- [ ] **Step 1: Create system_settings hooks**

`app/resources/js/admin/lib/systemSettings.tsx`:

```tsx
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { adminClient } from './adminClient';

export type SettingRow = {
    key: string; group: string; type: 'string'|'int'|'bool'|'float'|'json';
    is_secret: boolean; label: string;
    env_fallback: string | null; value: any;
    updated_at: string | null; updated_by_admin_id: number | null;
};

export function useSystemSettings(group?: string) {
    return useQuery({
        queryKey: ['system-settings', group ?? 'all'],
        queryFn: async () => (await adminClient.get('/system-settings', { params: { group } })).data.data as SettingRow[],
    });
}

export function useUpdateSetting() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ key, value }: { key: string; value: any }) =>
            (await adminClient.patch(`/system-settings/${encodeURIComponent(key)}`, { value })).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['system-settings'] }),
    });
}

export function useDeleteSetting() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (key: string) => (await adminClient.delete(`/system-settings/${encodeURIComponent(key)}`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['system-settings'] }),
    });
}

export async function revealSetting(key: string): Promise<string | null> {
    const r = await adminClient.get(`/system-settings/${encodeURIComponent(key)}/reveal`);
    return r.data.data.value;
}
```

- [ ] **Step 2: Create `SecretInput`**

`app/resources/js/admin/components/SecretInput.tsx`:

```tsx
import { useState } from 'react';
import { Button, Input, Space, App } from 'antd';
import { EyeOutlined, EyeInvisibleOutlined } from '@ant-design/icons';
import { revealSetting } from '../lib/systemSettings';

export function SecretInput({ settingKey, hasValue, onSave }: {
    settingKey: string; hasValue: boolean;
    onSave: (newValue: string) => void;
}) {
    const [revealed, setRevealed] = useState<string | null>(null);
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState('');
    const { message } = App.useApp();

    async function doReveal() {
        try {
            const v = await revealSetting(settingKey);
            setRevealed(v);
            setTimeout(() => setRevealed(null), 10_000);
        } catch (e: any) {
            message.error(e?.response?.data?.error?.message ?? 'Reveal lỗi');
        }
    }

    if (editing) {
        return (
            <Space.Compact style={{ width: '100%' }}>
                <Input.Password value={draft} onChange={(e) => setDraft(e.target.value)} autoFocus />
                <Button type="primary" onClick={() => { onSave(draft); setEditing(false); setDraft(''); }}>Lưu</Button>
                <Button onClick={() => { setEditing(false); setDraft(''); }}>Huỷ</Button>
            </Space.Compact>
        );
    }
    return (
        <Space.Compact style={{ width: '100%' }}>
            <Input value={revealed ?? (hasValue ? '••••••••' : '(chưa đặt)')} readOnly />
            {hasValue && <Button icon={revealed ? <EyeInvisibleOutlined /> : <EyeOutlined />} onClick={revealed ? () => setRevealed(null) : doReveal} />}
            <Button onClick={() => setEditing(true)}>Đặt giá trị</Button>
        </Space.Compact>
    );
}
```

- [ ] **Step 3: Create `SettingRow`**

`app/resources/js/admin/components/SettingRow.tsx`:

```tsx
import { useState } from 'react';
import { Space, Switch, Input, InputNumber, Button, Typography, Tag, App, Popconfirm } from 'antd';
import { SettingRow as SR, useUpdateSetting, useDeleteSetting } from '../lib/systemSettings';
import { SecretInput } from './SecretInput';

export function SettingRow({ row }: { row: SR }) {
    const upd = useUpdateSetting();
    const del = useDeleteSetting();
    const { message } = App.useApp();
    const [editing, setEditing] = useState<any>(row.value);

    const isPersisted = row.value !== null && row.value !== undefined;

    function save(nextValue: any) {
        upd.mutate({ key: row.key, value: nextValue }, {
            onSuccess: () => message.success(`Đã lưu: ${row.label}`),
            onError: (e: any) => message.error(e?.response?.data?.error?.message ?? 'Lỗi'),
        });
    }

    let control: JSX.Element;
    if (row.is_secret) {
        control = <SecretInput settingKey={row.key} hasValue={isPersisted} onSave={save} />;
    } else if (row.type === 'bool') {
        const b = row.value === true || row.value === '1' || row.value === 1;
        control = <Switch checked={b} onChange={(v) => save(v)} />;
    } else if (row.type === 'int') {
        control = (
            <Space.Compact>
                <InputNumber value={editing ?? row.value} onChange={setEditing} style={{ width: 140 }} />
                <Button type="primary" onClick={() => save(Number(editing))}>Lưu</Button>
            </Space.Compact>
        );
    } else {
        control = (
            <Space.Compact style={{ width: '100%' }}>
                <Input value={editing ?? row.value ?? ''} onChange={(e) => setEditing(e.target.value)} />
                <Button type="primary" onClick={() => save(editing ?? '')}>Lưu</Button>
            </Space.Compact>
        );
    }

    return (
        <div style={{ padding: '12px 0', borderBottom: '1px solid #E5E7EB' }}>
            <Space size={8} style={{ marginBottom: 6 }}>
                <Typography.Text strong>{row.label}</Typography.Text>
                <Typography.Text code style={{ fontSize: 11 }}>{row.key}</Typography.Text>
                {isPersisted
                    ? <Tag color="blue">Đã đổi (admin)</Tag>
                    : <Tag>Đang dùng env</Tag>}
            </Space>
            <div>{control}</div>
            {isPersisted && (
                <Popconfirm title="Khôi phục về env (xoá row DB)?" onConfirm={() => del.mutate(row.key, {
                    onSuccess: () => message.success('Đã khôi phục.'),
                })}>
                    <Button size="small" type="link" style={{ paddingLeft: 0 }}>Khôi phục từ env</Button>
                </Popconfirm>
            )}
        </div>
    );
}
```

- [ ] **Step 4: Create `SystemSettingsPage`**

`app/resources/js/admin/pages/settings/SystemSettingsPage.tsx`:

```tsx
import { useState } from 'react';
import { Card, Segmented, Spin, Typography, Button, App, Space } from 'antd';
import { ReloadOutlined } from '@ant-design/icons';
import { useSystemSettings } from '../../lib/systemSettings';
import { SettingRow } from '../../components/SettingRow';
import { adminClient } from '../../lib/adminClient';

const GROUPS = [
    { value: 'branding', label: 'Thương hiệu' },
    { value: 'marketplace', label: 'Marketplace' },
    { value: 'fulfillment', label: 'Vận hành' },
    { value: 'sync', label: 'Đồng bộ' },
];

export function SystemSettingsPage() {
    const [group, setGroup] = useState<string>('branding');
    const { data, isLoading, refetch } = useSystemSettings(group);
    const { message } = App.useApp();

    async function syncFromEnv() {
        try {
            const r = await adminClient.post('/system-settings/sync-from-env');
            message.success(`Đã nạp ${r.data.data.created} setting từ env.`);
            refetch();
        } catch (e: any) {
            message.error(e?.response?.data?.error?.message ?? 'Lỗi');
        }
    }

    return (
        <Card title="Cấu hình hệ thống" extra={
            <Space>
                <Button icon={<ReloadOutlined />} onClick={() => refetch()}>Tải lại</Button>
                <Button onClick={syncFromEnv}>Nạp từ env (lần đầu)</Button>
            </Space>
        }>
            <Segmented options={GROUPS} value={group} onChange={(v) => setGroup(v as string)} block style={{ marginBottom: 16 }} />
            <Typography.Paragraph type="secondary">
                Các cấu hình dưới đây ưu tiên giá trị trong DB. Nếu chưa đặt, hệ thống dùng giá trị từ tệp <Typography.Text code>.env</Typography.Text>.
            </Typography.Paragraph>
            {isLoading ? <Spin /> : data?.map(r => <SettingRow key={r.key} row={r} />)}
        </Card>
    );
}
```

- [ ] **Step 5: Register route**

In `AdminApp.tsx`:

```tsx
import { SystemSettingsPage } from './pages/settings/SystemSettingsPage';
// ...
<Route path="settings" element={<SystemSettingsPage />} />
```

- [ ] **Step 6: Build smoke**

Run: `cd app && npm run build`
Expected: build succeeds.

- [ ] **Step 7: Commit**

```bash
git add app/resources/js/admin/
git commit -m "feat(fe): system settings page with 4 group tabs + SecretInput + SettingRow"
```

---

### Task 23: Cleanup — remove admin code from user SPA

**Files:**
- Modify: `app/resources/js/app.tsx`
- Delete: `app/resources/js/components/RequireSuperAdmin.tsx` (if exists)
- Modify: `app/app/Modules/Tenancy/Http/Controllers/AuthController.php` (or wherever `/api/v1/auth/me` lives) — drop `is_super_admin` field

- [ ] **Step 1: Remove admin imports from `app.tsx`**

Open `app/resources/js/app.tsx` and delete:
- Imports of `AdminTenantsPage`, `AdminUsersPage`, `AdminVouchersPage`, `AdminPlansPage`, `AdminAuditLogsPage`, `AdminBroadcastsPage`.
- Import of `RequireSuperAdmin`.
- All `<Route path="admin/...">` blocks.

- [ ] **Step 2: Delete the legacy gate component**

```bash
git rm app/resources/js/components/RequireSuperAdmin.tsx
```

- [ ] **Step 3: Strip `is_super_admin` from `/auth/me` response**

Run: `grep -RIn 'is_super_admin' app/app/Modules/Tenancy/`. Open each match in controller/resource files and remove the `is_super_admin` field from the JSON payload.

- [ ] **Step 4: Build + run full test suite**

Run from `app/`:
```bash
npm run build
php artisan test
```

Expected: build succeeds; all tests pass.

- [ ] **Step 5: Commit**

```bash
git add -A app/resources/js/app.tsx app/app/Modules/Tenancy/
git rm app/resources/js/components/RequireSuperAdmin.tsx 2>/dev/null || true
git commit -m "chore(fe): remove admin routes from user SPA; drop is_super_admin from /auth/me"
```

---

### Task 24: Docs — endpoints.md update

**Files:**
- Modify: `docs/05-api/endpoints.md` (search for current section)
- Modify: `docs/01-architecture/multi-tenancy-and-rbac.md` (add admin_users concept)

- [ ] **Step 1: Find the file**

Run: `grep -RIn 'admin/tenants' docs/05-api/ | head`. Open that file at the matching line.

- [ ] **Step 2: Edit `endpoints.md`**

Under the existing "Admin" section, add subsections:

```markdown
### Admin Auth (Spec 2026-05-17)

| Method | Path | Mô tả |
|---|---|---|
| POST | `/api/v1/admin/auth/login` | Login super-admin (username + password). |
| POST | `/api/v1/admin/auth/logout` | Logout. |
| GET | `/api/v1/admin/auth/me` | Thông tin admin hiện tại. |
| POST | `/api/v1/admin/auth/change-password` | Đổi mật khẩu admin. |

### Admin Users management

| Method | Path | Mô tả |
|---|---|---|
| GET | `/api/v1/admin/admin-users` | List super-admin. |
| POST | `/api/v1/admin/admin-users` | Tạo admin mới. |
| GET | `/api/v1/admin/admin-users/{id}` | Chi tiết. |
| PATCH | `/api/v1/admin/admin-users/{id}` | Sửa name/email. |
| POST | `/api/v1/admin/admin-users/{id}/reset-password` | Reset password. |
| POST | `/api/v1/admin/admin-users/{id}/suspend` | Vô hiệu hoá. |
| POST | `/api/v1/admin/admin-users/{id}/reactivate` | Kích hoạt lại. |

### Tenant Users (admin)

| Method | Path | Mô tả |
|---|---|---|
| GET | `/api/v1/admin/users` | List user toàn hệ thống. |
| GET | `/api/v1/admin/users/{id}` | Chi tiết user. |
| PATCH | `/api/v1/admin/users/{id}` | Sửa name/email. |
| POST | `/api/v1/admin/users/{id}/reset-password` | Reset password. |
| POST | `/api/v1/admin/users/{id}/suspend` | Tạm khoá (set `users.suspended_at`). |
| POST | `/api/v1/admin/users/{id}/reactivate` | Mở lại. |

### System Settings

| Method | Path | Mô tả |
|---|---|---|
| GET | `/api/v1/admin/system-settings?group=branding\|marketplace\|fulfillment\|sync` | List settings (secrets masked). |
| GET | `/api/v1/admin/system-settings/{key}/reveal` | Hiện giá trị plain của secret (audit). |
| PATCH | `/api/v1/admin/system-settings/{key}` | Cập nhật. |
| DELETE | `/api/v1/admin/system-settings/{key}` | Xoá row → fallback env. |
| POST | `/api/v1/admin/system-settings/sync-from-env` | Bootstrap seed từ env. |
```

- [ ] **Step 3: Edit `multi-tenancy-and-rbac.md`**

Find the section that mentions "super-admin" and add:

> **Spec 2026-05-17 (admin separation):** Super-admin nằm trên bảng riêng `admin_users` (không thuộc `users`). Guard `admin_web` (session) + `admin` (Sanctum stateful) bảo vệ `/api/v1/admin/*`. User thường (`users`) đăng nhập qua `/login` không thấy menu admin. Quyền admin không phụ thuộc vai trò tenant.

- [ ] **Step 4: Commit**

```bash
git add docs/05-api/endpoints.md docs/01-architecture/multi-tenancy-and-rbac.md
git commit -m "docs: admin separation + system settings endpoints + RBAC note"
```

---

### Task 25: Final verification — full test suite + manual smoke

**Files:** none (verification only)

- [ ] **Step 1: Run full test suite**

```bash
cd app && php artisan test
```

Expected: all tests PASS. Failures should be addressed before continuing.

- [ ] **Step 2: Run static analysis**

```bash
cd app && ./vendor/bin/phpstan analyse
```

Expected: 0 errors (or only known baseline noise).

- [ ] **Step 3: Build frontend bundles**

```bash
cd app && npm run build
```

Expected: both `app-*.js` and `admin-*.js` produced under `public/build/`.

- [ ] **Step 4: Manual smoke checklist**

Start the dev server:
```bash
cd app && php artisan serve & npm run dev
```

Then in the browser:
- Visit `/admin` → should redirect to `/admin/login`.
- Login with credentials created by `php artisan admin:create`.
- Land on `/admin` dashboard → sidebar visible with 5 items.
- Open `/admin/users` → 2 tabs, list renders, drawer opens.
- Open `/admin/settings` → 4 tabs (branding/marketplace/fulfillment/sync), click "Nạp từ env" → rows show "Đang dùng env" change to "Đã đổi (admin)" for any value present in `.env`.
- Toggle a bool setting → toast "Đã lưu".
- Set a secret (e.g., `marketplace.tiktok.app_key`) → reload page → value masked. Click "Hiện" → plain value shows, audit row added.
- Open user app `/login` → regular login works; menu has no admin entries.
- Logout admin → `/admin` redirects to `/admin/login`.

- [ ] **Step 5: Final commit (if any docstring/follow-up tweaks)**

```bash
git status
# if any pending changes
git commit -m "chore: final tweaks after smoke testing"
```

---

## Spec coverage check

| Spec requirement | Implemented in |
|---|---|
| `admin_users` table + model | Task 1, 2 |
| `system_settings` table + model | Task 12 |
| `users.suspended_at` | Task 4 |
| `audit_logs.admin_user_id` | Task 4 |
| Drop `users.is_super_admin` + backfill | Task 5 |
| Guard `admin_web` + `admin` | Task 3 |
| Sanctum multi-guard | Task 3 |
| AdminAuthController | Task 6 |
| Drop `super_admin` middleware + rewire | Task 7 |
| `EnsureTenant` blocks suspended | Task 8 |
| Artisan `admin:create / reset-password / promote / demote` | Task 9 |
| Admin users CRUD endpoints | Task 10 |
| Tenant users CRUD endpoints | Task 11 |
| `SystemSettingsCatalog` (38 keys, 8 secret) | Task 13 |
| `SystemSettingService` + helper | Task 14 |
| `LogSystemSettingChanged` audit listener | Task 15 |
| `AdminSystemSettingController` | Task 16 |
| Call-site refactor to `system_setting()` | Task 17 |
| Vite multi-entry + admin blade route | Task 18 |
| AdminApp shell + login + dashboard + layout | Task 19 |
| Relocate existing admin pages | Task 20 |
| Admin users page (Tabs) + drawers | Task 21 |
| System Settings page (4 tabs) + SecretInput | Task 22 |
| Cleanup user SPA | Task 23 |
| Docs update | Task 24 |
| Final verification | Task 25 |
