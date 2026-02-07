<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Audit Logging
    |--------------------------------------------------------------------------
    |
    | This option controls whether audit logging is enabled globally.
    | Set to false to disable all audit logging.
    |
    */
    'enabled' => env('AUDIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Log Views
    |--------------------------------------------------------------------------
    |
    | Whether to log view/read actions. Enabling this can generate a lot
    | of logs, so it's disabled by default.
    |
    */
    'log_views' => env('AUDIT_LOG_VIEWS', false),

    /*
    |--------------------------------------------------------------------------
    | Retention Days
    |--------------------------------------------------------------------------
    |
    | Number of days to keep audit logs before automatic cleanup.
    | Set to 0 to disable automatic cleanup.
    |
    */
    'retention_days' => env('AUDIT_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Excluded Models
    |--------------------------------------------------------------------------
    |
    | Models that should not be audited even if they use the Auditable trait.
    |
    */
    'excluded_models' => [
        // App\Models\Session::class,
        // App\Models\PersonalAccessToken::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Fields
    |--------------------------------------------------------------------------
    |
    | Fields that should always be redacted from audit logs.
    |
    */
    'excluded_fields' => [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'api_token',
        'secret',
        'credit_card',
        'cvv',
    ],

    /*
    |--------------------------------------------------------------------------
    | Log IP Address
    |--------------------------------------------------------------------------
    |
    | Whether to log IP addresses in audit logs.
    |
    */
    'log_ip' => env('AUDIT_LOG_IP', true),

    /*
    |--------------------------------------------------------------------------
    | Log User Agent
    |--------------------------------------------------------------------------
    |
    | Whether to log user agent strings in audit logs.
    |
    */
    'log_user_agent' => env('AUDIT_LOG_USER_AGENT', true),
];
