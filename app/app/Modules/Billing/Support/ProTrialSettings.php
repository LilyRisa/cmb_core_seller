<?php

namespace CMBcoreSeller\Modules\Billing\Support;

use Carbon\CarbonImmutable;

/**
 * Đọc cấu hình "Chế độ trải nghiệm Pro" từ system_setting (catalog-registered).
 * Nguồn duy nhất cho eligibility + admin UI.
 */
class ProTrialSettings
{
    public const DEFAULT_DAYS = 30;

    public static function enabled(): bool
    {
        return (bool) system_setting('billing.pro_trial.enabled', false);
    }

    public static function durationDays(): int
    {
        $days = (int) system_setting('billing.pro_trial.duration_days', self::DEFAULT_DAYS);

        return $days > 0 ? $days : self::DEFAULT_DAYS;
    }

    public static function windowStart(): ?CarbonImmutable
    {
        $v = system_setting('billing.pro_trial.window_start');

        return $v ? CarbonImmutable::parse((string) $v)->startOfDay() : null;
    }

    public static function windowEnd(): ?CarbonImmutable
    {
        $v = system_setting('billing.pro_trial.window_end');

        return $v ? CarbonImmutable::parse((string) $v)->endOfDay() : null;
    }

    public static function windowOpen(): bool
    {
        $now = CarbonImmutable::now();
        $start = self::windowStart();
        $end = self::windowEnd();
        if ($start !== null && $now->lt($start)) {
            return false;
        }
        if ($end !== null && $now->gt($end)) {
            return false;
        }

        return true;
    }
}
