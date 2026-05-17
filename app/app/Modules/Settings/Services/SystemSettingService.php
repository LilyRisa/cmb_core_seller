<?php

namespace CMBcoreSeller\Modules\Settings\Services;

use CMBcoreSeller\Modules\Settings\Events\SystemSettingChanged;
use CMBcoreSeller\Modules\Settings\Models\SystemSetting;
use CMBcoreSeller\Modules\Settings\Support\SystemSettingsCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Spec 2026-05-17 — đọc/ghi cấu hình do super-admin quản lý.
 *
 * Lifecycle:
 *   - `all()` lazy load bộ nhớ + cache `rememberForever('system_settings:all')`.
 *   - `set()` ghi DB → forget cache → reset memo → phát event
 *     `SystemSettingChanged` (Listener `LogSystemSettingChanged` ghi audit).
 *   - `forget()` xoá row DB + clear cache + event.
 *
 * Sai key trong catalog ⇒ `set()` throw `InvalidArgumentException` (controller
 * bắt và trả `SETTING_KEY_NOT_ALLOWED`). `get()` bỏ qua key ngoài catalog →
 * trả default — bảo vệ call-site khỏi consume rác.
 *
 * Secret: encrypt bằng Laravel `Crypt` (AES-256-CBC dựa APP_KEY). Decrypt fail
 * (APP_KEY đổi) ⇒ log warning, trả null — KHÔNG crash.
 *
 * Cluster: production phải dùng cache driver `redis` hoặc `database`. Array
 * driver chỉ cho test — KHÔNG đồng bộ giữa worker.
 *
 * Singleton: register trong SettingsServiceProvider → memo per-request đảm
 * bảo trong cùng request không hit cache nhiều lần.
 */
class SystemSettingService
{
    public const CACHE_KEY = 'system_settings:all';

    /** @var array<string, array{value:mixed, type:string, is_secret:bool}>|null */
    private ?array $memo = null;

    /** @return array<string, array{value:mixed, type:string, is_secret:bool}> */
    public function all(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        $this->memo = Cache::rememberForever(self::CACHE_KEY, function (): array {
            // Migration pending / test boot ⇒ trả [] để fallback env, KHÔNG crash app.
            try {
                $rows = SystemSetting::query()->get();
            } catch (Throwable $e) {
                Log::warning('SystemSetting: read failed (table missing?), falling back to env', [
                    'error' => $e->getMessage(),
                ]);

                return [];
            }

            return $rows->mapWithKeys(function (SystemSetting $s): array {
                $plain = $s->value;
                if ($s->is_secret && $plain !== null) {
                    try {
                        $plain = Crypt::decryptString($plain);
                    } catch (Throwable $e) {
                        Log::warning('SystemSetting: failed to decrypt secret', [
                            'key' => $s->key, 'error' => $e->getMessage(),
                        ]);
                        $plain = null;
                    }
                }

                return [$s->key => [
                    'value' => $plain,
                    'type' => $s->type,
                    'is_secret' => (bool) $s->is_secret,
                ]];
            })->all();
        });

        return $this->memo;
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

        $this->invalidate();
        event(new SystemSettingChanged($key));

        return $row;
    }

    public function forget(string $key): bool
    {
        $deleted = (bool) SystemSetting::query()->where('key', $key)->delete();
        $this->invalidate();
        if ($deleted) {
            event(new SystemSettingChanged($key));
        }

        return $deleted;
    }

    /** Khi cluster muốn buộc reload (vd subscriber event từ worker khác). */
    public function invalidate(): void
    {
        $this->memo = null;
        Cache::forget(self::CACHE_KEY);
    }

    private function cast(mixed $v, string $type): mixed
    {
        if ($v === null) {
            return null;
        }

        return match ($type) {
            'int' => is_int($v) ? $v : (int) $v,
            'float' => is_float($v) ? $v : (float) $v,
            'bool' => is_bool($v) ? $v : in_array(strtolower((string) $v), ['true', '1'], true),
            'json' => is_array($v) ? $v : json_decode((string) $v, true),
            default => is_string($v) ? $v : (string) $v,
        };
    }

    private function encode(mixed $v, string $type): ?string
    {
        if ($v === null) {
            return null;
        }

        return match ($type) {
            'bool' => (in_array(strtolower((string) $v), ['true', '1'], true) || $v === true) ? '1' : '0',
            'json' => is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE),
            default => (string) $v,
        };
    }
}
