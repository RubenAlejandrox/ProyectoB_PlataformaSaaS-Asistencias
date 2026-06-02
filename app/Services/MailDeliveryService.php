<?php

/**
 * @descripcion  Servicio de dominio MailDelivery: encapsula reglas de negocio reutilizables.
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

use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MailDeliveryService
{
    /**
     * Verifica que el correo esté configurado para envío real (no log/array ni remitente de ejemplo).
     *
     * @return void
     * @throws \RuntimeException Si MAIL_MAILER o MAIL_FROM_ADDRESS no permiten entrega externa
     */
    public function ensureCanDeliver(): void
    {
        $mailer = config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            throw new \RuntimeException(
                'El correo no se envía al exterior: MAIL_MAILER está en "' . $mailer . '". '
                . 'Configure SMTP en su archivo .env (MAIL_MAILER=smtp, MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD) '
                . 'y reinicie el servidor.'
            );
        }

        $from = config('mail.from.address');
        if (! $from || str_contains($from, 'example.com')) {
            throw new \RuntimeException(
                'Configure MAIL_FROM_ADDRESS en .env con un correo real verificado (mismo dominio o cuenta SMTP).'
            );
        }
    }

    /**
     * Ejecuta el envío de correo tras validar la configuración; encapsula errores SMTP.
     *
     * @param callable $callback Función que dispara el Mailable o Mail::send
     * @return void
     * @throws \RuntimeException Si la configuración es inválida o falla el transporte SMTP
     */
    public function send(callable $callback): void
    {
        $this->ensureCanDeliver();

        try {
            $callback();
        } catch (TransportExceptionInterface $e) {
            Log::error('Error SMTP al enviar correo', [
                'message' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'No se pudo entregar el correo. Verifique usuario, contraseña de aplicación y MAIL_HOST en .env. '
                . 'Detalle: ' . $e->getMessage()
            );
        }
    }
}
