<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),
    'APP_GATEWAY_URL' => env('APP_GATEWAY_URL', 'http://127.0.0.1:8000/api'),
    'APP_AUTH_URL' => env('APP_AUTH_URL', 'http://127.0.0.1:8001/api'),
    'APP_ORGANIZATION_URL' => env('APP_ORGANIZATION_URL', 'http://127.0.0.1:8002/api'),
    'APP_LICENSE_URL' => env('APP_LICENSE_URL', 'http://127.0.0.1:8003/api'),
    'APP_COMMUNICATION_URL' => env('APP_COMMUNICATION_URL', 'http://127.0.0.1:8004/api'),
    'APP_VERSIONNING_URL' => env('APP_VERSIONNING_URL', 'http://127.0.0.1:8005/api'),
    'APP_KPI_URL' => env('APP_KPI_URL', 'http://127.0.0.1:8006/api'),
    'APP_FORMATION_URL' => env('APP_FORMATION_URL', 'http://127.0.0.1:8007/api'),
    'APP_MINIO_URL' => env('APP_MINIO_URL', 'http://127.0.0.1:8012/api'),
    'APP_FRONT_URL' => env('APP_FRONT_URL', 'http://localhost:4200'),
    'APP_AUTH_WEB_URL' => env('APP_AUTH_WEB_URL', 'http://127.0.0.1:8000'),
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),


    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
