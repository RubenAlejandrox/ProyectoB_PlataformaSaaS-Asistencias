<?php

/**
 * @descripcion  Modelo Eloquent PayPalSdkClient: representa entidad y relaciones del dominio.
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


declare(strict_types=1);

namespace App\Services;

use Composer\CaBundle\CaBundle;
use PayPalCheckoutSdk\Core\PayPalHttpClient;

/**
 * Cliente HTTP del SDK de PayPal con resolución de certificados SSL en Windows.
 *
 * Usa composer/ca-bundle para localizar un cacert.pem confiable cuando curl.cainfo no está definido.
 */
class PayPalSdkClient extends PayPalHttpClient
{
    protected function getCACertFilePath()
    {
        $path = CaBundle::getSystemCaRootBundlePath();

        if ($path && is_file($path)) {
            return $path;
        }

        return parent::getCACertFilePath();
    }
}
