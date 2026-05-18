<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Livewire component tag
    |--------------------------------------------------------------------------
    | Usage: <livewire:sumsub.kyc-verification />
    */
    'component_tag' => env('LIVEWIRE_SUMSUB_COMPONENT_TAG', 'sumsub.kyc-verification'),

    /*
    |--------------------------------------------------------------------------
    | Credenciales y API Sumsub
    |--------------------------------------------------------------------------
    | Este paquete instala automáticamente anselmi-dev/sumsub. No necesitas
    | publicar config/sumsub.php: estos valores se sincronizan al arrancar la app.
    |
    | Panel Sumsub: Developer Tools → App Tokens / Webhooks
    */
    'sumsub' => [
        'app_token' => env('SUMSUB_APP_TOKEN', ''),
        'secret_key' => env('SUMSUB_SECRET_KEY', ''),
        'base_url' => env('SUMSUB_BASE_URL', 'https://api.sumsub.com'),
        'webhook_secret' => env('SUMSUB_WEBHOOK_SECRET', ''),
        'default_level_name' => env('SUMSUB_DEFAULT_LEVEL', 'basic-kyc-level'),
        'webhook_route' => env('SUMSUB_WEBHOOK_ROUTE', 'webhooks/sumsub'),
        'webhook_route_name' => env('SUMSUB_WEBHOOK_ROUTE_NAME', 'sumsub.webhook'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sumsub Web SDK (widget en el navegador)
    |--------------------------------------------------------------------------
    */
    'sdk' => [
        'script_url' => env(
            'LIVEWIRE_SUMSUB_SDK_SCRIPT_URL',
            'https://static.sumsub.com/idensic/static/sns-websdk-builder.js'
        ),
        'container_id' => env('LIVEWIRE_SUMSUB_SDK_CONTAINER_ID', 'sumsub-websdk-container'),
        'default_lang' => env('LIVEWIRE_SUMSUB_SDK_LANG', 'es'),
    ],
];
