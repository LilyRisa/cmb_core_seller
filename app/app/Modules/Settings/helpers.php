<?php

use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;

if (! function_exists('system_setting')) {
    /**
     * Spec 2026-05-17 — đọc setting dynamic từ DB qua cache `rememberForever`.
     *
     * Pattern call-site:
     *   $appKey = system_setting('marketplace.tiktok.app_key', config('integrations.tiktok.app_key'));
     *
     * Key ngoài `SystemSettingsCatalog` luôn trả `$default`. Secret tự-decrypt
     * khi tồn tại; decrypt fail (APP_KEY đổi) trả `$default` + log warning.
     *
     * @template T
     *
     * @param  T|null  $default
     * @return T|mixed
     */
    function system_setting(string $key, mixed $default = null): mixed
    {
        return app(SystemSettingService::class)->get($key, $default);
    }
}
