<?php

namespace CMBcoreSeller\Integrations\Ads\Facebook;

/**
 * Budget money mapping for Graph WRITE calls. Core stores money as integer VND;
 * Graph wants the account-currency MINOR unit. Zero-decimal currencies pass
 * through, others ×100. The ONLY place money is rescaled on write — keep it here
 * to avoid the 100× budget bug. Returns a string (Graph budgets are string ints).
 */
final class FacebookMoney
{
    /** ISO-4217 zero-decimal currencies (no minor unit). */
    private const ZERO_DECIMAL = ['VND', 'JPY', 'KRW', 'CLP', 'ISK', 'HUF', 'TWD', 'UGX'];

    public static function toMinorUnits(int $majorAmount, string $currency): string
    {
        $factor = in_array(strtoupper($currency), self::ZERO_DECIMAL, true) ? 1 : 100;

        return (string) ($majorAmount * $factor);
    }
}
