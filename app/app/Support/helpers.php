<?php

if (! function_exists('app_display_tz')) {
    /**
     * The display / business timezone (UTC+7 Asia/Ho_Chi_Minh by default).
     *
     * Storage & transport are always UTC; this is the timezone used to render
     * user-facing dates and to compute business-day boundaries (reports,
     * dashboards) and the task scheduler. Configure via `APP_DISPLAY_TIMEZONE`.
     */
    function app_display_tz(): string
    {
        return (string) config('app.display_timezone', 'Asia/Ho_Chi_Minh');
    }
}
