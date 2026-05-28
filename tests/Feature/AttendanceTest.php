<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Plan;
use App\Models\Session;
use App\Models\SessionKey;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User $teacher;
    private User $student;
    private User $outsider;
    private Classroom $classroom;
    private Session $session;
    private SessionKey $validKey;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);

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

        $this->teacher = $this->createUser('teacher@test.com', 'Teacher');
        $this->student = $this->createUser('student@test.com', 'Student');
        $this->outsider = $this->createUser('outsider@test.com', 'Student');

        $this->classroom = Classroom::withoutGlobalScopes()->create([
            'institution_id'     => $this->institution->id,
            'teacher_id'         => $this->teacher->id,
            'subject_name'       => 'Matemáticas',
            'period'             => '2026-A',
            'max_capacity'       => 30,
            'min_attendance_pct' => 80,
            'is_active'          => true,
        ]);

        Enrollment::create([
            'classroom_id' => $this->classroom->id,
            'student_id'   => $this->student->id,
            'enrolled_at'  => now(),
            'is_active'    => true,
        ]);

        $this->session = Session::create([
            'classroom_id' => $this->classroom->id,
            'session_date' => now()->toDateString(),
            'started_at'   => now()->format('H:i:s'),
            'is_active'    => true,
        ]);

        $this->validKey = SessionKey::create([
            'session_id' => $this->session->id,
            'access_key' => 'VALIDKEY',
            'expires_at' => now()->addHour(),
            'is_active'  => true,
        ]);
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
    public function valid_key_registers_present_attendance(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson('/api/attendances', [
                'access_key' => 'validkey',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'present');

        $this->assertDatabaseHas('attendances', [
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'status'     => 'present',
        ]);
    }

    #[Test]
    public function expired_key_returns_422(): void
    {
        $this->validKey->update([
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($this->student)
            ->postJson('/api/attendances', [
                'access_key' => 'VALIDKEY',
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Clave de asistencia inválida o expirada.']);

        $this->assertDatabaseCount('attendances', 0);
    }

    #[Test]
    public function duplicate_registration_returns_409(): void
    {
        Attendance::create([
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'status'     => 'present',
        ]);

        $response = $this->actingAs($this->student)
            ->postJson('/api/attendances', [
                'access_key' => 'VALIDKEY',
            ]);

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => 'Ya registraste asistencia en esta sesión.']);

        $this->assertEquals(
            1,
            Attendance::where('session_id', $this->session->id)
                ->where('student_id', $this->student->id)
                ->count()
        );
    }

    #[Test]
    public function student_not_enrolled_returns_403(): void
    {
        $response = $this->actingAs($this->outsider)
            ->postJson('/api/attendances', [
                'access_key' => 'VALIDKEY',
            ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'No estás inscrito en el aula de esta sesión.']);

        $this->assertDatabaseCount('attendances', 0);
    }
}
