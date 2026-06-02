<?php

/**
 * @descripcion  Archivo de configuración Laravel: paypal.
 *
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 *
 * @version      1.0.0
 * @creado       2026-06-02
 * @modificado   2026-06-02
 *
 * @cambios       *               2026-06-02 - Incorporación de cabecera de prólogo conforme estándar
 */


return [
    'client_id' => env('PAYPAL_CLIENT_ID'),
    'secret'    => env('PAYPAL_SECRET'),
    'mode'      => env('PAYPAL_MODE', 'sandbox'),
    'currency'  => env('PAYPAL_CURRENCY', 'MXN'),

    // Locale en formato BCP 47 (con guión, no guión bajo) — PayPal Orders v2
    'locale'    => env('PAYPAL_LOCALE', 'es-MX'),
];