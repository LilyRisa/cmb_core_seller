<?php

namespace CMBcoreSeller\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Gửi 1 email kiểm tra ĐỒNG BỘ (không qua queue) để lộ lỗi SMTP ngay lập tức.
 *
 *   php artisan mail:test ban@gmail.com
 *   # trên prod (Portainer):
 *   docker compose -f docker-compose.prod.yml exec app php artisan mail:test ban@gmail.com
 *
 * In ra cấu hình mail app đang thực sự dùng (KHÔNG in mật khẩu) rồi gọi
 * `Mail::raw()` (synchronous — không phụ thuộc Horizon/queue). Nếu Brevo/SMTP
 * từ chối (sender chưa verify, auth sai, connection…), exception gốc được in
 * đầy đủ kèm chuỗi previous để biết chính xác nguyên nhân.
 */
class MailTest extends Command
{
    protected $signature = 'mail:test
        {email : Địa chỉ nhận email test}
        {--subject= : Tiêu đề (mặc định kèm timestamp)}';

    protected $description = 'Gửi email test đồng bộ (không qua queue) để chẩn đoán lỗi SMTP nhanh.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Email không hợp lệ: {$email}");

            return self::INVALID;
        }

        $mailer = (string) config('mail.default');
        $smtp = (array) config("mail.mailers.{$mailer}", []);
        $from = (array) config('mail.from');

        $this->line('Cấu hình mail đang dùng (runtime):');
        $this->table(['Khoá', 'Giá trị'], [
            ['mail.default', $mailer],
            ['transport', (string) ($smtp['transport'] ?? '—')],
            ['host', (string) ($smtp['host'] ?? '—')],
            ['port', (string) ($smtp['port'] ?? '—')],
            ['scheme', (string) ($smtp['scheme'] ?? '(mặc định)')],
            ['username', $this->mask($smtp['username'] ?? null)],
            ['password', ($smtp['password'] ?? '') !== '' ? 'đã đặt ('.strlen((string) $smtp['password']).' ký tự)' : 'TRỐNG'],
            ['from.address', (string) ($from['address'] ?? '—')],
            ['from.name', (string) ($from['name'] ?? '—')],
        ]);

        if ($mailer === 'log') {
            $this->warn('mail.default = "log" — email chỉ GHI VÀO LOG, không gửi thật. Đặt MAIL_MAILER=smtp rồi recreate container.');
        }

        $subject = (string) ($this->option('subject') ?: '[CMB] Mail test '.now()->toDateTimeString());
        $this->newLine();
        $this->line("Đang gửi đồng bộ (không qua queue) tới {$email} ...");

        $start = microtime(true);
        try {
            Mail::raw(
                "Đây là email kiểm tra từ CMBcoreSeller.\nThời điểm: ".now()->toIso8601String(),
                function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                },
            );
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('GỬI THẤT BẠI — chuỗi exception:');
            $depth = 0;
            for ($x = $e; $x !== null; $x = $x->getPrevious()) {
                $this->error(str_repeat('  ', $depth).'• ['.$x::class.'] '.$x->getMessage());
                $depth++;
            }

            return self::FAILURE;
        }

        $ms = (int) ((microtime(true) - $start) * 1000);
        $this->info("✔ Gửi không lỗi trong {$ms}ms.");
        if ($mailer === 'log') {
            $this->warn('Vì driver là "log", hãy xem storage/logs/laravel.log — KHÔNG có mail thật.');
        } else {
            $this->line("Kiểm tra hộp thư (cả mục Spam) của {$email}.");
        }

        return self::SUCCESS;
    }

    /** Che bớt giá trị nhạy cảm (username/email) khi in ra console. */
    private function mask(?string $value): string
    {
        $value = (string) $value;
        if ($value === '') {
            return 'TRỐNG';
        }
        if (str_contains($value, '@')) {
            [$user, $domain] = explode('@', $value, 2);

            return mb_substr($user, 0, 2).'***@'.$domain;
        }

        return mb_substr($value, 0, 2).str_repeat('*', max(0, mb_strlen($value) - 2));
    }
}
