<?php

return [
    'client_id' => env('PAYPAL_CLIENT_ID'),
    'secret'    => env('PAYPAL_SECRET'),
    'mode'      => env('PAYPAL_MODE', 'sandbox'),
    'currency'  => env('PAYPAL_CURRENCY', 'MXN'),

    // Locale en formato BCP 47 (con guión, no guión bajo) — PayPal Orders v2
    'locale'    => env('PAYPAL_LOCALE', 'es-MX'),
];