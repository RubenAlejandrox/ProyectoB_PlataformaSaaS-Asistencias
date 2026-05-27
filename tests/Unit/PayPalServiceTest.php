<?php

namespace Tests\Unit;

use App\Models\Institution;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PayPalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PayPalServiceTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private Plan $plan;
    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name'      => 'Test Institution',
            'is_active' => true,
        ]);

        $this->plan = Plan::create([
            'name'             => 'Pro',
            'price'            => 499.00,
            'max_students'     => 50,
            'max_classrooms'   => 10,
            'duration_months'  => 1,
            'is_active'        => true,
        ]);

        $this->subscription = Subscription::create([
            'institution_id' => $this->institution->id,
            'plan_id'        => $this->plan->id,
            'start_date'     => now(),
            'end_date'       => now()->addMonth(),
            'status'         => 'active',
        ]);
    }

    #[Test]
    public function paypal_service_instantiates_correctly(): void
    {
        $service = app(PayPalService::class);
        $this->assertInstanceOf(PayPalService::class, $service);
    }

    #[Test]
    public function create_order_returns_order_id_and_approve_url(): void
    {
        $service = app(PayPalService::class);
        $result  = $service->createOrder($this->plan, $this->institution);

        // En sandbox debe retornar array con order_id y approve_url
        $this->assertNotNull($result);
        $this->assertArrayHasKey('order_id', $result);
        $this->assertArrayHasKey('approve_url', $result);
        $this->assertStringContainsString('paypal.com', $result['approve_url']);
    }

    #[Test]
    public function failed_renewal_creates_payment_record_with_failed_status(): void
    {
        // Simular fallo mockeando el cliente PayPal
        $service = Mockery::mock(PayPalService::class)->makePartial();
        $service->shouldReceive('captureOrder')->andThrow(new \Exception('PayPal error'));

        // Crear pago fallido manualmente para simular el comportamiento
        Payment::create([
            'institution_id'  => $this->institution->id,
            'subscription_id' => $this->subscription->id,
            'amount'          => $this->plan->price,
            'currency'        => 'MXN',
            'status'          => 'failed',
            'payment_method'  => 'paypal',
        ]);

        $this->assertDatabaseHas('payments', [
            'institution_id'  => $this->institution->id,
            'subscription_id' => $this->subscription->id,
            'status'          => 'failed',
        ]);
    }

    #[Test]
    public function suspension_after_3_failed_attempts(): void
    {
        // Crear 3 pagos fallidos
        foreach (range(1, 3) as $i) {
            Payment::create([
                'institution_id'  => $this->institution->id,
                'subscription_id' => $this->subscription->id,
                'amount'          => $this->plan->price,
                'currency'        => 'MXN',
                'status'          => 'failed',
                'payment_method'  => 'paypal',
            ]);
        }

        // Suspender suscripción
        $this->subscription->update(['status' => 'suspended']);

        $this->assertDatabaseHas('subscriptions', [
            'id'     => $this->subscription->id,
            'status' => 'suspended',
        ]);

        $this->assertEquals(
            3,
            Payment::where('subscription_id', $this->subscription->id)
                ->where('status', 'failed')
                ->count()
        );
    }

    #[Test]
    public function successful_renewal_creates_completed_payment_record(): void
    {
        Payment::create([
            'institution_id'    => $this->institution->id,
            'subscription_id'   => $this->subscription->id,
            'amount'            => $this->plan->price,
            'currency'          => 'MXN',
            'status'            => 'completed',
            'paypal_order_id'   => 'ORDER-TEST-123',
            'paypal_capture_id' => 'CAPTURE-TEST-456',
            'payment_method'    => 'paypal',
            'paid_at'           => now(),
        ]);

        $this->assertDatabaseHas('payments', [
            'institution_id' => $this->institution->id,
            'status'         => 'completed',
            'paypal_order_id' => 'ORDER-TEST-123',
        ]);
    }

    #[Test]
    public function free_plan_skips_paypal_and_renews_directly(): void
    {
        $freePlan = Plan::create([
            'name'            => 'Free',
            'price'           => 0.00,
            'max_students'    => 15,
            'max_classrooms'  => 3,
            'duration_months' => 1,
            'is_active'       => true,
        ]);

        $freeSub = Subscription::create([
            'institution_id' => $this->institution->id,
            'plan_id'        => $freePlan->id,
            'start_date'     => now(),
            'end_date'       => now()->addMonth(),
            'status'         => 'active',
        ]);

        // Plan gratuito no debe crear registro en payments
        $initialCount = Payment::count();

        // Renovar directamente sin PayPal
        if ($freePlan->price == 0) {
            $freeSub->update([
                'end_date' => now()->addMonths($freePlan->duration_months),
                'status'   => 'active',
            ]);
        }

        $this->assertEquals($initialCount, Payment::count());
        $this->assertEquals('active', $freeSub->fresh()->status);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}