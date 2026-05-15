<?php

namespace CMBcoreSeller\Console\Commands;

use CMBcoreSeller\Modules\Fulfillment\Models\AdminDistrict;
use CMBcoreSeller\Modules\Fulfillment\Models\AdminProvince;
use CMBcoreSeller\Modules\Fulfillment\Models\AdminWard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * SPEC 0021 — Đồng bộ danh mục địa chỉ hành chính VN từ 2 nguồn vào DB:
 *
 *  - `addresskit.cas.so` — chuẩn MỚI 2-cấp (sau cải cách 2025): Tỉnh → Phường/Xã.
 *  - `provinces.open-api.vn` — chuẩn CŨ 3-cấp (pre-2025): Tỉnh → Quận/Huyện → Phường/Xã.
 *
 * Idempotent — upsert theo `(format, code)`. Chạy thủ công khi deploy hoặc cron tuần:
 *
 *      php artisan addresses:sync                # cả 2 nguồn
 *      php artisan addresses:sync --only=new     # chỉ nguồn mới
 *      php artisan addresses:sync --only=old     # chỉ nguồn cũ
 *      php artisan addresses:sync --fresh        # truncate trước khi nạp lại
 *
 * Mỗi level dùng 1 query upsert batch (PostgreSQL ON CONFLICT). Sau khi xong xoá cache
 * `master-data.*` để endpoint `/master-data/*` đọc dữ liệu mới ngay.
 */
class SyncVnAdminAddresses extends Command
{
    protected $signature = 'addresses:sync
        {--only= : Giới hạn nguồn: new | old}
        {--fresh : Truncate bảng trước khi nạp lại}
        {--timeout=60 : HTTP timeout (giây) khi gọi API}';

    protected $description = 'Đồng bộ danh mục địa chỉ hành chính VN từ AddressKit (mới 2-cấp) + provinces.open-api.vn (cũ 3-cấp).';

    public function handle(): int
    {
        $only = (string) $this->option('only');
        $fresh = (bool) $this->option('fresh');
        $timeout = (int) $this->option('timeout');

        if ($fresh) {
            $this->warn('Truncating admin_provinces / admin_districts / admin_wards...');
            DB::table('admin_wards')->delete();
            DB::table('admin_districts')->delete();
            DB::table('admin_provinces')->delete();
        }

        $stats = ['new_provinces' => 0, 'new_wards' => 0, 'old_provinces' => 0, 'old_districts' => 0, 'old_wards' => 0];

        if ($only === '' || $only === 'new') {
            $this->info('==> Đồng bộ NEW (AddressKit cas.so)...');
            $stats = array_merge($stats, $this->syncNew($timeout));
        }
        if ($only === '' || $only === 'old') {
            $this->info('==> Đồng bộ OLD (provinces.open-api.vn)...');
            $stats = array_merge($stats, $this->syncOld($timeout));
        }

        // Xoá cache 24h ở MasterDataController để endpoint /master-data/* phục vụ dữ liệu mới.
        Cache::forget('master-data.provinces.new');
        Cache::forget('master-data.provinces.old');

        $this->info('Hoàn tất. Thống kê:');
        foreach ($stats as $k => $v) {
            $this->line(sprintf('  %-20s %d', $k, $v));
        }

        return self::SUCCESS;
    }

    /** @return array<string,int> */
    private function syncNew(int $timeout): array
    {
        $base = (string) config('addresses.cas_base_url', 'https://addresskit.cas.so');
        // 1) Provinces
        $res = Http::baseUrl($base)->timeout($timeout)->acceptJson()->get('/api/latest/provinces');
        if (! $res->successful()) {
            $this->error("AddressKit /provinces lỗi: HTTP {$res->status()}");

            return ['new_provinces' => 0, 'new_wards' => 0];
        }
        $provinces = (array) ($res->json('provinces') ?? []);
        $now = now();
        $provRows = [];
        $i = 0;
        foreach ($provinces as $p) {
            $code = trim((string) ($p['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $provRows[] = [
                'format' => 'new', 'code' => $code, 'name' => trim((string) ($p['name'] ?? '')),
                'english_name' => $p['englishName'] ?? null,
                'division_type' => $p['administrativeLevel'] ?? null,
                'codename' => null, 'phone_code' => null,
                'decree' => $p['decree'] ?? null,
                'sort_order' => $i++,
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        $this->upsertChunk('admin_provinces', $provRows, ['format', 'code'], ['name', 'english_name', 'division_type', 'decree', 'sort_order', 'updated_at']);
        $this->line("  Provinces (NEW): {$this->countOf($provRows)}");

        // 2) Wards per province
        $totalWards = 0;
        $bar = $this->output->createProgressBar(count($provinces));
        $bar->start();
        foreach ($provinces as $p) {
            $code = trim((string) ($p['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            try {
                $r = Http::baseUrl($base)->timeout($timeout)->acceptJson()->get("/api/latest/provinces/{$code}/communes");
                if (! $r->successful()) {
                    $bar->advance();

                    continue;
                }
                $communes = (array) ($r->json('communes') ?? []);
                $rows = [];
                foreach ($communes as $w) {
                    $wCode = trim((string) ($w['code'] ?? ''));
                    if ($wCode === '') {
                        continue;
                    }
                    $rows[] = [
                        'format' => 'new', 'code' => $wCode,
                        'province_code' => $code, 'district_code' => null,
                        // Tên ward đôi khi chứa \n hoặc khoảng trắng dư — chuẩn hoá để search/match dễ.
                        'name' => trim(preg_replace('/\s+/', ' ', (string) ($w['name'] ?? '')) ?? ''),
                        'english_name' => $w['englishName'] ?? null,
                        'codename' => null,
                        'division_type' => $w['administrativeLevel'] ?? null,
                        'decree' => $w['decree'] ?? null,
                        'created_at' => $now, 'updated_at' => $now,
                    ];
                }
                $this->upsertChunk('admin_wards', $rows, ['format', 'code'], ['province_code', 'district_code', 'name', 'english_name', 'division_type', 'decree', 'updated_at']);
                $totalWards += count($rows);
            } catch (\Throwable $e) {
                $this->warn("  Bỏ qua province {$code}: {$e->getMessage()}");
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->line("  Wards (NEW): {$totalWards}");

        return ['new_provinces' => count($provRows), 'new_wards' => $totalWards];
    }

    /** @return array<string,int> */
    private function syncOld(int $timeout): array
    {
        $base = (string) config('addresses.open_api_base_url', 'https://provinces.open-api.vn');
        // depth=3 ⇒ provinces + districts + wards trong 1 call (file ~3MB nhưng tiết kiệm ~700 req).
        $res = Http::baseUrl($base)->timeout($timeout * 5)->acceptJson()->get('/api/v1/?depth=3');
        if (! $res->successful()) {
            $this->error("provinces.open-api.vn /?depth=3 lỗi: HTTP {$res->status()}");

            return ['old_provinces' => 0, 'old_districts' => 0, 'old_wards' => 0];
        }
        $body = (array) ($res->json() ?? []);
        $now = now();
        $provRows = $distRows = $wardRows = [];
        $i = 0;
        foreach ($body as $p) {
            $pCode = (string) ($p['code'] ?? '');
            if ($pCode === '') {
                continue;
            }
            $provRows[] = [
                'format' => 'old', 'code' => $pCode, 'name' => (string) ($p['name'] ?? ''),
                'english_name' => null, 'division_type' => $p['division_type'] ?? null,
                'codename' => $p['codename'] ?? null, 'phone_code' => $p['phone_code'] ?? null,
                'decree' => null, 'sort_order' => $i++,
                'created_at' => $now, 'updated_at' => $now,
            ];
            foreach ((array) ($p['districts'] ?? []) as $d) {
                $dCode = (string) ($d['code'] ?? '');
                if ($dCode === '') {
                    continue;
                }
                $distRows[] = [
                    'province_code' => $pCode, 'code' => $dCode,
                    'name' => (string) ($d['name'] ?? ''),
                    'codename' => $d['codename'] ?? null,
                    'division_type' => $d['division_type'] ?? null,
                    'created_at' => $now, 'updated_at' => $now,
                ];
                foreach ((array) ($d['wards'] ?? []) as $w) {
                    $wCode = (string) ($w['code'] ?? '');
                    if ($wCode === '') {
                        continue;
                    }
                    $wardRows[] = [
                        'format' => 'old', 'code' => $wCode,
                        'province_code' => $pCode, 'district_code' => $dCode,
                        'name' => (string) ($w['name'] ?? ''),
                        'english_name' => null,
                        'codename' => $w['codename'] ?? null,
                        'division_type' => $w['division_type'] ?? null,
                        'decree' => null,
                        'created_at' => $now, 'updated_at' => $now,
                    ];
                }
            }
        }

        $this->upsertChunk('admin_provinces', $provRows, ['format', 'code'], ['name', 'division_type', 'codename', 'phone_code', 'sort_order', 'updated_at']);
        $this->line('  Provinces (OLD): '.count($provRows));
        $this->upsertChunk('admin_districts', $distRows, ['code'], ['province_code', 'name', 'codename', 'division_type', 'updated_at']);
        $this->line('  Districts (OLD): '.count($distRows));
        $this->upsertChunk('admin_wards', $wardRows, ['format', 'code'], ['province_code', 'district_code', 'name', 'codename', 'division_type', 'updated_at']);
        $this->line('  Wards (OLD): '.count($wardRows));

        return ['old_provinces' => count($provRows), 'old_districts' => count($distRows), 'old_wards' => count($wardRows)];
    }

    private function countOf(array $rows): int
    {
        return count($rows);
    }

    /**
     * Chunked upsert tránh giới hạn bind parameter của Postgres (max 65535). 800 rows × ~10 cols < 8000 binds.
     *
     * @param  list<array<string,mixed>>  $rows
     * @param  list<string>  $uniqueBy
     * @param  list<string>  $update
     */
    private function upsertChunk(string $table, array $rows, array $uniqueBy, array $update, int $chunkSize = 800): void
    {
        if ($rows === []) {
            return;
        }
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::table($table)->upsert($chunk, $uniqueBy, $update);
        }
    }
}
