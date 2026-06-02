<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ForgotPasswordFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);
    }

    #[Test]
    public function forgot_password_shows_admin_contact_for_existing_user(): void
    {
        $institution = Institution::create(['name' => 'Instituto GAMA', 'is_active' => true]);

        $admin = User::create([
            'institution_id' => $institution->id,
            'first_name' => 'Ana',
            'last_name' => 'Admin',
            'email' => 'admin@gama.test',
            'password_hash' => bcrypt('secret123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Administrator');

        $teacher = User::create([
            'institution_id' => $institution->id,
            'first_name' => 'Luis',
            'last_name' => 'Docente',
            'email' => 'docente@gama.test',
            'password_hash' => bcrypt('secret123'),
            'is_active' => true,
        ]);
        $teacher->assignRole('Teacher');

        $this->post(route('password.forgot'), ['email' => 'docente@gama.test'])
            ->assertOk()
            ->assertSee('Ana Admin')
            ->assertSee('admin@gama.test')
            ->assertSee('Instituto GAMA');
    }

    #[Test]
    public function forgot_password_returns_generic_view_when_user_does_not_exist(): void
    {
        $this->post(route('password.forgot'), ['email' => 'inexistente@gama.test'])
            ->assertOk()
            ->assertSee('Si tu cuenta existe y está activa');
    }

    #[Test]
    public function admin_can_reset_user_password_with_default_value(): void
    {
        $institution = Institution::create(['name' => 'Instituto GAMA', 'is_active' => true]);

        $admin = User::create([
            'institution_id' => $institution->id,
            'first_name' => 'Ana',
            'last_name' => 'Admin',
            'email' => 'admin@gama.test',
            'password_hash' => bcrypt('secret123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Administrator');

        $teacher = User::create([
            'institution_id' => $institution->id,
            'first_name' => 'Luis',
            'last_name' => 'Docente',
            'email' => 'docente@gama.test',
            'password_hash' => bcrypt('old-pass'),
            'is_active' => true,
        ]);
        $teacher->assignRole('Teacher');

        $this->actingAs($admin)
            ->put(route('admin.usuario.reset-password', $teacher))
            ->assertRedirect();

        $teacher->refresh();
        $this->assertTrue(Hash::check('GamaSolu1234$+', $teacher->password_hash));
    }

    #[Test]
    public function admin_can_reset_user_password_from_different_institution(): void
    {
        $adminInstitution = Institution::create(['name' => 'Admin Inst', 'is_active' => true]);
        $otherInstitution = Institution::create(['name' => 'Otra Inst', 'is_active' => true]);

        $admin = User::create([
            'institution_id' => $adminInstitution->id,
            'first_name' => 'Ana',
            'last_name' => 'Admin',
            'email' => 'admin2@gama.test',
            'password_hash' => bcrypt('secret123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Administrator');

        $student = User::create([
            'institution_id' => $otherInstitution->id,
            'first_name' => 'Mauro',
            'last_name' => 'Alumno',
            'email' => 'mauro@miescuela.com',
            'password_hash' => bcrypt('old-pass'),
            'is_active' => true,
        ]);
        $student->assignRole('Student');

        $this->actingAs($admin)
            ->put(route('admin.usuario.reset-password', $student))
            ->assertRedirect();

        $student->refresh();
        $this->assertTrue(Hash::check('GamaSolu1234$+', $student->password_hash));
    }
}
