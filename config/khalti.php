<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Khalti Public Key
    |--------------------------------------------------------------------------
    |
    | Used for client-side configurations or specific API integrations if needed.
    |
    */
    'public_key' => env('KHALTI_PUBLIC_KEY', 'key_live_secret_default'),

    /*
    |--------------------------------------------------------------------------
    | Khalti Secret Key
    |--------------------------------------------------------------------------
    |
    | The main authorization key used in the backend header: "Key <secret_key>".
    |
    */
    'secret_key' => env('KHALTI_SECRET_KEY', 'key_live_secret_default'),

    /*
    |--------------------------------------------------------------------------
    | Khalti Base URL
    |--------------------------------------------------------------------------
    |
    | Base API URL for initiation and verification (sandbox vs production).
    |
    */
    'base_url' => env('KHALTI_BASE_URL', 'https://dev.khalti.com/api/v2'),
];
