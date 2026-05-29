<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterNameValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);

        $institution = Institution::create([
            'name'      => 'GAMA Demo',
            'is_active' => true,
        ]);

        $plan = Plan::create([
            'name'            => 'Pro',
            'price'           => 499.00,
            'max_students'    => 50,
            'max_classrooms'  => 10,
            'duration_months' => 1,
            'is_active'       => true,
        ]);

        Subscription::create([
            'institution_id' => $institution->id,
            'plan_id'        => $plan->id,
            'start_date'     => now(),
            'end_date'       => now()->addMonth(),
            'status'         => 'active',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name'            => 'María',
            'last_name'             => 'García',
            'email'                 => 'maria@test.com',
            'role'                  => 'Student',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'privacy_accepted'      => '1',
            '_form'                 => 'register',
        ], $overrides);
    }

    #[Test]
    public function register_rejects_names_with_numbers(): void
    {
        $response = $this->from('/login')->post('/register', $this->validPayload([
            'first_name' => 'Juan2',
        ]));

        $response->assertSessionHasErrors('first_name');
    }

    #[Test]
    public function register_rejects_empty_names(): void
    {
        $response = $this->from('/login')->post('/register', $this->validPayload([
            'first_name' => '   ',
        ]));

        $response->assertSessionHasErrors('first_name');
    }

    #[Test]
    public function register_accepts_valid_names_with_spaces_and_hyphens(): void
    {
        $response = $this->post('/register', $this->validPayload([
            'first_name' => 'María José',
            'last_name'  => 'López-Pérez',
            'email'      => 'valid.name@test.com',
        ]));

        $response->assertRedirect(route('dashboard'));
    }
}
