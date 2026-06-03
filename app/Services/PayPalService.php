<?php

/**
 * @descripcion  Servicio de dominio PayPal: encapsula reglas de negocio reutilizables.
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

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Institution;
use App\Models\Plan;
use Illuminate\Support\Facades\Log;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

class PayPalService
{
    private PayPalHttpClient $client;
    private string $currency;

    /**
     * Inicializa el cliente PayPal según modo sandbox o producción y la moneda configurada.
     *
     * @return void
     */
    public function __construct()
    {
        $clientId = config('paypal.client_id');
        $secret   = config('paypal.secret');
        $mode     = config('paypal.mode', 'sandbox');

        $environment = $mode === 'production'
            ? new ProductionEnvironment($clientId, $secret)
            : new SandboxEnvironment($clientId, $secret);

        $this->client   = new PayPalSdkClient($environment);
        $this->currency = config('paypal.currency', 'MXN');
    }

    /**
     * Crea una orden de pago PayPal para contratar un plan en una institución.
     *
     * @param Plan        $plan        Plan con precio y nombre
     * @param Institution $institution Institución que realiza el pago
     * @return array{order_id: string, approve_url: string}|null Datos de la orden y URL de aprobación, o null si falla
     */
    public function createOrder(Plan $plan, Institution $institution): ?array
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            'intent'              => 'CAPTURE',
            'purchase_units'      => [[
                'reference_id' => $institution->id,
                'description'  => "Plan {$plan->name} — GAMA Asistencias",
                'amount'       => [
                    'currency_code' => $this->currency,
                    'value'         => $this->formatMoney($plan->price),
                ],
            ]],
            'application_context' => [
                'brand_name'          => 'GAMA Solutions',
                'locale'              => config('paypal.locale', 'es_MX'),
                'landing_page'        => 'BILLING',
                'user_action'         => 'PAY_NOW',
                'return_url'          => route('paypal.success'),
                'cancel_url'          => route('paypal.cancel'),
            ],
        ];

        try {
            $response = $this->client->execute($request);
            return [
                'order_id'    => $response->result->id,
                'approve_url' => collect($response->result->links)
                    ->firstWhere('rel', 'approve')
                    ->href,
            ];
        } catch (\Exception $e) {
            Log::error('PayPal createOrder error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Captura el pago de una orden PayPal previamente aprobada.
     *
     * @param string $orderId Identificador de la orden en PayPal
     * @return object|null Resultado de la captura del SDK, o null si falla
     */
    public function captureOrder(string $orderId): ?object
    {
        $request = new OrdersCaptureRequest($orderId);
        $request->prefer('return=representation');

        try {
            $response = $this->client->execute($request);
            return $response->result;
        } catch (\Exception $e) {
            Log::error('PayPal captureOrder error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Procesa la renovación automática de una suscripción (hasta 3 reintentos y registro en payments).
     *
     * @param Subscription $sub Suscripción activa con plan e institución cargados
     * @return bool true si el cobro y la extensión de end_date fueron exitosos; false tras agotar reintentos
     */
    public function processRenewal(Subscription $sub): bool
    {
        $attempts = 0;
        $maxRetries = 3;

        while ($attempts < $maxRetries) {
            try {
                $request = new OrdersCreateRequest();
                $request->prefer('return=representation');
                $request->body = [
                    'intent'         => 'CAPTURE',
                    'purchase_units' => [[
                        'reference_id' => $sub->institution_id,
                        'description'  => "Renovación plan {$sub->plan->name}",
                        'amount'       => [
                            'currency_code' => $this->currency,
                            'value'         => $this->formatMoney($sub->plan->price),
                        ],
                    ]],
                ];

                $response  = $this->client->execute($request);
                $orderId   = $response->result->id;

                // Capturar inmediatamente
                $capture   = $this->captureOrder($orderId);
                $captureId = $capture?->purchase_units[0]->payments->captures[0]->id ?? null;

                // ★ Registrar pago exitoso en tabla payments
                Payment::create([
                    'institution_id'    => $sub->institution_id,
                    'subscription_id'   => $sub->id,
                    'amount'            => $sub->plan->price,
                    'currency'          => $this->currency,
                    'status'            => 'completed',
                    'paypal_order_id'   => $orderId,
                    'paypal_capture_id' => $captureId,
                    'payment_method'    => 'paypal',
                    'paid_at'           => now(),
                ]);

                // Renovar fecha de la suscripción
                $sub->update([
                    'end_date' => now()->addMonths($sub->plan->duration_months),
                    'status'   => 'active',
                ]);

                Log::info("PayPal renewal success for institution {$sub->institution_id}");
                return true;

            } catch (\Exception $e) {
                $attempts++;

                // ★ Registrar intento fallido en payments
                Payment::create([
                    'institution_id'  => $sub->institution_id,
                    'subscription_id' => $sub->id,
                    'amount'          => $sub->plan->price,
                    'currency'        => $this->currency,
                    'status'          => 'failed',
                    'payment_method'  => 'paypal',
                ]);

                Log::warning("PayPal renewal attempt {$attempts}/{$maxRetries} failed: " . $e->getMessage());

                if ($attempts < $maxRetries) {
                    sleep(300); // 5 min entre reintentos (en producción usar jobs)
                }
            }
        }

        // Suspender suscripción tras 3 fallos
        $sub->update(['status' => 'suspended']);

        Log::error("PayPal renewal failed after {$maxRetries} attempts. Subscription {$sub->id} suspended.");

        return false;
    }

    /**
     * Formatea un monto para la API PayPal (decimal:2 de Eloquent llega como string).
     *
     * @param float|string|int $amount Importe del plan o pago
     * @return string Valor con dos decimales y punto decimal
     */
    private function formatMoney(float|string|int $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    /**
     * Consulta el estado actual de una orden PayPal (p. ej. CREATED, APPROVED, COMPLETED).
     *
     * @param string $orderId Identificador de la orden en PayPal
     * @return string|null Estado devuelto por la API, o null si la consulta falla
     */
    public function getOrderStatus(string $orderId): ?string
    {
        try {
            $request  = new \PayPalCheckoutSdk\Orders\OrdersGetRequest($orderId);
            $response = $this->client->execute($request);
            return $response->result->status;
        } catch (\Exception $e) {
            Log::error('PayPal getOrderStatus error: ' . $e->getMessage());
            return null;
        }
    }
}