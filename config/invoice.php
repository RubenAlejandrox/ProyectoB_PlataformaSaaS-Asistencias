<?php

/**
 * @descripcion  Archivo de configuración Laravel: invoice.
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
    'issuer_name'    => env('INVOICE_ISSUER_NAME', 'GAMA SOLUTIONS'),
    'issuer_rfc'     => env('INVOICE_ISSUER_RFC', ''),
    'issuer_address' => env('INVOICE_ISSUER_ADDRESS', 'México'),
    'issuer_email'   => env('INVOICE_ISSUER_EMAIL', 'contacto@gamasolutions.com'),
];
