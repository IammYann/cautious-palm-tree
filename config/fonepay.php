<?php

return [

    /*
    |--------------------------------------------------------------------------
    | FonePay Merchant Code (PID)
    |--------------------------------------------------------------------------
    |
    | The merchant code provided by FonePay via your partner bank.
    | This is sent as the 'PID' parameter in every payment request.
    |
    */
    'merchant_code' => env('FONEPAY_MERCHANT_CODE', 'your_merchant_code'),

    /*
    |--------------------------------------------------------------------------
    | FonePay Secret Key
    |--------------------------------------------------------------------------
    |
    | The secret key used for HMAC-SHA512 signature generation (DV parameter).
    | Provided by your partner bank during FonePay merchant onboarding.
    |
    */
    'secret_key' => env('FONEPAY_SECRET_KEY', 'your_secret_key'),

    /*
    |--------------------------------------------------------------------------
    | FonePay Payment URL
    |--------------------------------------------------------------------------
    |
    | The gateway URL where the payment form is submitted.
    | This redirects the user to FonePay's hosted payment page.
    |
    | Production: https://clientapi.fonepay.com/api/merchantRequest
    |
    */
    'payment_url' => env('FONEPAY_PAYMENT_URL', 'https://clientapi.fonepay.com/api/merchantRequest'),

];
