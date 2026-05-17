<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Spec 2026-05-17 — tạo super-admin mới.
 *
 *   php artisan admin:create ops_lead --name="Le Ops" --email=ops@cmbcore.vn
 *
 * Password lấy từ --password hoặc prompt hidden. Username chuẩn theo regex
 * `[a-z0-9._-]{3,32}`. Email và password unique/min-length enforced bằng
 * Validator (lỗi → exit 1, in từng dòng lỗi).
 */
class AdminCreate extends Command
{
    protected $signature = 'admin:create
        {username : Login username — [a-z0-9._-]{3,32}}
        {--name= : Display name (mặc định = username)}
        {--email= : Optional email (cho reset password)}
        {--password= : Mật khẩu; nếu trống sẽ prompt ẩn}';

    protected $description = 'Tạo tài khoản super-admin mới (Spec 2026-05-17).';

    public function handle(): int
    {
        $username = (string) $this->argument('username');
        $name = (string) ($this->option('name') ?: $username);
        $email = $this->option('email');
        $password = $this->option('password') ?: $this->secret('Mật khẩu (≥ 8)');

        $v = Validator::make([
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'username' => ['required', 'regex:/^[a-z0-9._-]{3,32}$/', Rule::unique('admin_users', 'username')],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('admin_users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:128'],
        ]);

        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }

            return self::FAILURE;
        }

        AdminUser::create([
            'username' => $username,
            'name' => $name,
            'email' => $email ?: null,
            'password' => $password,
            'is_active' => true,
        ]);

        $this->info("✔ Tạo admin [{$username}].");

        return self::SUCCESS;
    }
}
