<?php

return [

    /*
    |--------------------------------------------------------------------------
    | eSewa Merchant Code
    |--------------------------------------------------------------------------
    |
    | The merchant/product code provided by eSewa. For UAT testing, use
    | 'EPAYTEST'. For production, use the code assigned to your merchant.
    |
    */
    'merchant_code' => env('ESEWA_MERCHANT_CODE', 'EPAYTEST'),

    /*
    |--------------------------------------------------------------------------
    | eSewa Secret Key
    |--------------------------------------------------------------------------
    |
    | The secret key used for HMAC-SHA256 signature generation. For UAT
    | testing, use '8gBm/:&EnhH.1/q'. For production, use the key
    | provided by eSewa.
    |
    */
    'secret_key' => env('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q'),

    /*
    |--------------------------------------------------------------------------
    | eSewa Payment URL
    |--------------------------------------------------------------------------
    |
    | The URL where the payment form is submitted. Use the UAT URL for
    | testing and the production URL for live transactions.
    |
    | UAT:        https://rc-epay.esewa.com.np/api/epay/main/v2/form
    | Production: https://epay.esewa.com.np/api/epay/main/v2/form
    |
    */
    'payment_url' => env('ESEWA_PAYMENT_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form'),

    /*
    |--------------------------------------------------------------------------
    | eSewa Transaction Status URL
    |--------------------------------------------------------------------------
    |
    | The URL to check the status of a transaction.
    |
    | UAT:        https://rc.esewa.com.np/api/epay/transaction/status/
    | Production: https://esewa.com.np/api/epay/transaction/status/
    |
    */
    'status_url' => env('ESEWA_STATUS_URL', 'https://rc.esewa.com.np/api/epay/transaction/status/'),

];
