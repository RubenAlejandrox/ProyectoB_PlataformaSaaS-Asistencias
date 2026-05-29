<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Session;
use App\Models\User;
use App\Services\AttendanceProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MateriaDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);

        $institution = Institution::create(['name' => 'Test', 'is_active' => true]);

        $teacher = User::create([
            'institution_id' => $institution->id,
            'first_name'     => 'Doc',
            'last_name'      => 'Test',
            'email'          => 'teacher@test.com',
            'password_hash'  => bcrypt('password'),
            'is_active'      => true,
        ]);

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
            'teacher_id'         => $teacher->id,
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
    }

    #[Test]
    public function student_can_view_materia_detail(): void
    {
        $this->actingAs($this->student)
            ->get(route('materias.show', $this->classroom))
            ->assertOk()
            ->assertSee('Matemáticas')
            ->assertSee('Calendario del mes')
            ->assertSee('Mis justificantes');
    }

    #[Test]
    public function projection_calculates_remaining_absences(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $session = Session::create([
                'classroom_id' => $this->classroom->id,
                'session_date' => now()->subDays($i)->toDateString(),
                'started_at'   => '08:00:00',
                'is_active'    => false,
            ]);

            if ($i < 9) {
                Attendance::create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'status'     => 'present',
                ]);
            }
        }

        $progress   = app(AttendanceProgressService::class)->calculate($this->student->id, $this->classroom->id);
        $projection = app(AttendanceProgressService::class)->projectRemainingAbsences($progress);

        $this->assertEquals(90.0, $progress['attendance_pct']);
        $this->assertEquals(2, $projection['remaining']);
    }
}
