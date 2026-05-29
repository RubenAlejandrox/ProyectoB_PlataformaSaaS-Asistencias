<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;
    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);

        $institution = Institution::create(['name' => 'Test Inst', 'is_active' => true]);

        $plan = Plan::create([
            'name'            => 'Pro',
            'price'           => 499.00,
            'max_students'    => 50,
            'max_classrooms'  => 10,
            'duration_months' => 1,
            'is_active'       => true,
        ]);

        $subscription = Subscription::create([
            'institution_id' => $institution->id,
            'plan_id'        => $plan->id,
            'start_date'     => now(),
            'end_date'       => now()->addMonth(),
            'status'         => 'active',
        ]);

        $this->payment = Payment::create([
            'institution_id'    => $institution->id,
            'subscription_id'   => $subscription->id,
            'amount'            => 499.00,
            'currency'          => 'MXN',
            'status'            => 'completed',
            'paypal_order_id'   => 'ORDER-123',
            'paypal_capture_id' => 'CAPTURE-456',
            'payment_method'    => 'paypal',
            'paid_at'           => now(),
        ]);

        $this->admin = User::create([
            'institution_id' => $institution->id,
            'first_name'     => 'Admin',
            'last_name'      => 'Test',
            'email'          => 'admin@pay.test',
            'password_hash'  => bcrypt('password'),
            'is_active'      => true,
        ]);
        $this->admin->assignRole('Administrator');

        $this->teacher = User::create([
            'institution_id' => $institution->id,
            'first_name'     => 'Doc',
            'last_name'      => 'Test',
            'email'          => 'teacher@pay.test',
            'password_hash'  => bcrypt('password'),
            'is_active'      => true,
        ]);
        $this->teacher->assignRole('Teacher');
    }

    #[Test]
    public function admin_can_view_payment_history(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.pagos.index'))
            ->assertOk()
            ->assertSee('Historial de Pagos')
            ->assertSee('CAPTURE-456')
            ->assertSee('Completado')
            ->assertSee('Test Inst');
    }

    #[Test]
    public function teacher_cannot_view_payment_history(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('admin.pagos.index'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_download_invoice_for_completed_payment(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.pagos.factura', $this->payment));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
    }

    #[Test]
    public function cannot_download_invoice_for_failed_payment(): void
    {
        $failed = Payment::create([
            'institution_id'    => $this->payment->institution_id,
            'subscription_id'   => $this->payment->subscription_id,
            'amount'            => 100,
            'currency'          => 'MXN',
            'status'            => 'failed',
            'payment_method'    => 'paypal',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.pagos.factura', $failed))
            ->assertStatus(422);
    }

    #[Test]
    public function admin_can_filter_by_status(): void
    {
        Payment::create([
            'institution_id'    => $this->payment->institution_id,
            'subscription_id'   => $this->payment->subscription_id,
            'amount'            => 100,
            'currency'          => 'MXN',
            'status'            => 'failed',
            'payment_method'    => 'paypal',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.pagos.index', ['status' => 'failed']))
            ->assertOk()
            ->assertSee('Fallido')
            ->assertDontSee('CAPTURE-456');
    }
}
