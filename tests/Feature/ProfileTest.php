<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name'      => 'Test Institution',
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
            'institution_id' => $this->institution->id,
            'plan_id'        => $plan->id,
            'start_date'     => now(),
            'end_date'       => now()->addMonth(),
            'status'         => 'active',
        ]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['Administrator', 'Teacher', 'Student'] as $role) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function createUser(string $email, string $role): User
    {
        $user = User::create([
            'institution_id'        => $this->institution->id,
            'first_name'            => $role,
            'last_name'             => 'Test',
            'email'                 => $email,
            'password_hash'         => bcrypt('Password1!'),
            'is_active'             => true,
            'failed_login_attempts' => 0,
        ]);
        $user->assignRole($role);

        return $user;
    }

    #[Test]
    public function guest_cannot_access_profile(): void
    {
        $this->get('/perfil')->assertRedirect(route('login'));
    }

    #[Test]
    public function web_login_persists_session_for_dashboard(): void
    {
        $user = $this->createUser('persist@test.com', 'Administrator');

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'Password1!',
        ])->assertRedirect(route('dashboard'));

        $this->get('/')->assertOk();
    }

    #[Test]
    public function any_role_can_view_profile(): void
    {
        foreach (['Administrator', 'Teacher', 'Student'] as $role) {
            $user = $this->createUser(strtolower($role) . '@test.com', $role);

            $this->actingAs($user)
                ->get('/perfil')
                ->assertOk()
                ->assertSee('Mi Perfil')
                ->assertSee($user->email);
        }
    }

    #[Test]
    public function user_can_update_name_and_email(): void
    {
        $user = $this->createUser('student@test.com', 'Student');

        $this->actingAs($user)
            ->put('/perfil', [
                'first_name' => 'Juan',
                'last_name'  => 'Pérez',
                'email'      => 'juan.perez@test.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('Juan', $user->first_name);
        $this->assertSame('Pérez', $user->last_name);
        $this->assertSame('juan.perez@test.com', $user->email);
    }

    #[Test]
    public function email_must_be_unique(): void
    {
        $this->createUser('other@test.com', 'Teacher');
        $user = $this->createUser('student@test.com', 'Student');

        $this->actingAs($user)
            ->from('/perfil')
            ->put('/perfil', [
                'first_name' => 'Juan',
                'last_name'  => 'Pérez',
                'email'      => 'other@test.com',
            ])
            ->assertSessionHasErrors('email');
    }

    #[Test]
    public function password_change_requires_correct_current_password(): void
    {
        $user = $this->createUser('student@test.com', 'Student');

        $this->actingAs($user)
            ->from('/perfil')
            ->put('/perfil/password', [
                'current_password'      => 'wrong-password',
                'password'              => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('Password1!', $user->fresh()->password_hash));
    }

    #[Test]
    public function user_can_change_password(): void
    {
        $user = $this->createUser('student@test.com', 'Student');

        $this->actingAs($user)
            ->put('/perfil/password', [
                'current_password'      => 'Password1!',
                'password'              => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewPassword1!', $user->fresh()->password_hash));
    }
}
