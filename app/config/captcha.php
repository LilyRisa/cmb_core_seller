<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CAPTCHA (chống bot/brute-force trên register/login/forgot) — SPEC 2026-06-10
    |--------------------------------------------------------------------------
    |
    | Cloudflare Turnstile. `enabled=false` (mặc định) ⇒ middleware + verifier
    | PASS-THROUGH (dev/test không vướng). Bật ở prod bằng CAPTCHA_ENABLED=true +
    | TURNSTILE_SITE_KEY/SECRET (đặt ở Portainer). site_key lộ cho FE qua
    | GET /api/v1/auth/captcha-config.
    |
    */
    'enabled' => (bool) env('CAPTCHA_ENABLED', false),
    'provider' => env('CAPTCHA_PROVIDER', 'turnstile'),
    'site_key' => env('TURNSTILE_SITE_KEY', ''),
    'secret' => env('TURNSTILE_SECRET', ''),
    'verify_url' => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),

    /*
    |--------------------------------------------------------------------------
    | Domain email dùng-một-lần (disposable) — chặn lúc đăng ký để tránh rác DB
    |--------------------------------------------------------------------------
    |
    | So khớp phần domain (sau @, lowercase). Bổ sung dần khi gặp domain rác mới.
    |
    */
    'disposable_domains' => [
        'mailinator.com', 'guerrillamail.com', 'guerrillamail.info', 'sharklasers.com',
        '10minutemail.com', '10minutemail.net', 'tempmail.com', 'temp-mail.org', 'tempmail.net',
        'yopmail.com', 'yopmail.fr', 'getnada.com', 'dispostable.com', 'trashmail.com',
        'maildrop.cc', 'mailnesia.com', 'mohmal.com', 'fakeinbox.com', 'throwawaymail.com',
        'mailcatch.com', 'spamgourmet.com', 'mintemail.com', 'emailondeck.com', 'tempinbox.com',
        'tmpmail.org', 'tmpmail.net', 'moakt.com', 'inboxkitten.com', 'mailpoof.com',
        'spam4.me', 'grr.la', 'pokemail.net', 'tempr.email', 'discard.email', 'mailtemp.net',
    ],
];
