<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Đăng nhập email vốn phân biệt hoa/thường (không chuẩn hoá ở đâu cả) — nay `User::email` mutator
 * ép lowercase cho MỌI lần ghi mới. Migration này backfill dữ liệu cũ để khớp, theo `id` tăng dần
 * để lần ghi đầu tiên của mỗi email (không phân biệt hoa/thường) "thắng" và giữ nguyên dạng
 * lowercase; nếu 2 user cũ khác nhau chỉ khác hoa/thường của cùng 1 email (hiếm, chưa từng bị chặn
 * trước đây) thì UPDATE sau sẽ vỡ unique constraint — bắt lỗi, GIỮ NGUYÊN row đó (không phá đăng
 * nhập hiện có) và log cảnh báo để tự tay xử lý.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->select('id', 'email')
            ->whereNotNull('email')
            ->orderBy('id')
            ->get()
            ->each(function ($row): void {
                $original = (string) $row->email;
                $lower = mb_strtolower(trim($original));
                if ($lower === $original || $lower === '') {
                    return;
                }

                try {
                    DB::table('users')->where('id', $row->id)->update(['email' => $lower]);
                } catch (QueryException $e) {
                    Log::warning('users.email lowercase backfill skipped — collides with another row', [
                        'user_id' => $row->id,
                        'email' => $original,
                        'target' => $lower,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
    }

    /** Không thể khôi phục dạng hoa/thường gốc — no-op có chủ đích. */
    public function down(): void {}
};
