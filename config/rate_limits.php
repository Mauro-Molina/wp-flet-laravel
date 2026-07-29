<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auth endpoints (unauthenticated) — keyed by IP
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'login_per_minute' => (int) env('RATE_LIMIT_AUTH_LOGIN', 10),
        'register_per_minute' => (int) env('RATE_LIMIT_AUTH_REGISTER', 5),
        'refresh_per_minute' => (int) env('RATE_LIMIT_AUTH_REFRESH', 20),
        'two_factor_per_minute' => (int) env('RATE_LIMIT_AUTH_2FA', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authenticated API — keyed by tenant_id, limit from plan
    |--------------------------------------------------------------------------
    */
    'api_default_per_minute' => (int) env('RATE_LIMIT_API_DEFAULT', 60),

    'plans' => [
        'starter' => (int) env('RATE_LIMIT_PLAN_STARTER', 60),
        'pro' => (int) env('RATE_LIMIT_PLAN_PRO', 300),
        'enterprise' => (int) env('RATE_LIMIT_PLAN_ENTERPRISE', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugin HMAC endpoints — keyed by X-Site-Id
    |--------------------------------------------------------------------------
    */
    'plugin_per_minute' => (int) env('RATE_LIMIT_PLUGIN', 120),
];
