<?php

namespace App\Services;

use Composer\CaBundle\CaBundle;
use PayPalCheckoutSdk\Core\PayPalHttpClient;

/**
 * Subclase de PayPalHttpClient que resuelve el problema de certificado SSL
 * en entornos Windows donde curl.cainfo no está configurado.
 *
 * Le indica al SDK la ruta al bundle de CA del sistema usando composer/ca-bundle,
 * que provee un cacert.pem confiable cross-platform.
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
