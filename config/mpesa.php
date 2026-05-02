<?php

return [
    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'shortcode' => env('MPESA_BUSINESS_CODE', '174379'),
    'passkey' => env('MPESA_PASSKEY'),
    'callback_url' => env('MPESA_CALLBACK_URL', env('APP_URL') . '/api/mpesa/callback'),
    'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),
    'initiator_name' => env('MPESA_INITIATOR_NAME', 'Chama'),
    'security_credential' => env('MPESA_B2C_SECURITY_CREDENTIAL'),
    
    'b2c' => [
        'queue_timeout_url' => env('MPESA_B2C_QUEUE_TIMEOUT_URL', env('APP_URL') . '/api/mpesa/b2c/timeout'),
        'result_url' => env('MPESA_B2C_RESULT_URL', env('APP_URL') . '/api/mpesa/b2c/result'),
    ],
    
    // Compatibility with SettingController which uses some different keys
    'b2c_shortcode' => env('MPESA_BUSINESS_CODE', '174379'), 
];
