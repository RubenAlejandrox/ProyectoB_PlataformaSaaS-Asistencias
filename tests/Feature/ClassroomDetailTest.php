<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClassroomDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $otherTeacher;

    private User $student;

    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['Teacher', 'Student'] as $role) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $institution = Institution::create(['name' => 'Test', 'is_active' => true]);

        $this->teacher = User::create([
            'institution_id' => $institution->id,
            'first_name'     => 'Doc',
            'last_name'      => 'Principal',
            'email'          => 'teacher@test.com',
            'password_hash'  => bcrypt('password'),
            'is_active'      => true,
        ]);
        $this->teacher->assignRole('Teacher');

        $this->otherTeacher = User::create([
            'institution_id' => $institution->id,
            'first_name'     => 'Otro',
            'last_name'      => 'Docente',
            'email'          => 'other-teacher@test.com',
            'password_hash'  => bcrypt('password'),
            'is_active'      => true,
        ]);
        $this->otherTeacher->assignRole('Teacher');

        $this->student = User::create([
            'institution_id' => $institution->id,
            'first_name'     => 'Ana',
            'last_name'      => 'Alumna',
            'email'          => 'student@test.com',
            'password_hash'  => bcrypt('password'),
            'is_active'      => true,
        ]);
        $this->student->assignRole('Student');

        $this->classroom = Classroom::withoutGlobalScopes()->create([
            'institution_id'     => $institution->id,
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

        $session = Session::withoutGlobalScopes()->create([
            'classroom_id'  => $this->classroom->id,
            'session_date'  => now()->toDateString(),
            'started_at'    => '08:00:00',
            'is_active'     => false,
        ]);

        Attendance::withoutGlobalScopes()->create([
            'session_id'  => $session->id,
            'student_id'  => $this->student->id,
            'status'      => 'present',
        ]);
    }

    #[Test]
    public function teacher_can_view_classroom_detail(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('aulas.show', $this->classroom))
            ->assertOk()
            ->assertSee('Matemáticas')
            ->assertSee('Alumnos inscritos')
            ->assertSee('Historial de sesiones del ciclo')
            ->assertSee('Ana Alumna')
            ->assertSee('Descargar lista');
    }

    #[Test]
    public function student_cannot_view_classroom_detail(): void
    {
        $this->actingAs($this->student)
            ->get(route('aulas.show', $this->classroom))
            ->assertForbidden();
    }

    #[Test]
    public function other_teacher_cannot_view_classroom_detail(): void
    {
        $this->actingAs($this->otherTeacher)
            ->get(route('aulas.show', $this->classroom))
            ->assertForbidden();
    }

    #[Test]
    public function teacher_can_export_student_list(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('aulas.alumnos.export', $this->classroom))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
