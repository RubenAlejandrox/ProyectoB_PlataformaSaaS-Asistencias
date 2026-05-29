<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MailDeliveryService
{
    /**
     * @throws \RuntimeException
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
     * @throws \RuntimeException
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
