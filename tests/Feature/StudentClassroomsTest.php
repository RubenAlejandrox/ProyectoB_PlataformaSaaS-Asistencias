<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentClassroomsTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);

        $this->institution = Institution::create([
            'name'      => 'Universidad Real',
            'is_active' => true,
        ]);

        $demo = Institution::create([
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

        foreach ([$this->institution, $demo] as $inst) {
            Subscription::create([
                'institution_id' => $inst->id,
                'plan_id'        => $plan->id,
                'start_date'     => now(),
                'end_date'       => now()->addMonth(),
                'status'         => 'active',
            ]);
        }

        $teacher = User::create([
            'institution_id' => $this->institution->id,
            'first_name'     => 'Doc',
            'last_name'      => 'Ente',
            'email'          => 'teacher@test.com',
            'password_hash'  => bcrypt('password'),
            'is_active'      => true,
        ]);
        $teacher->assignRole('Teacher');

        $this->student = User::create([
            'institution_id' => $demo->id,
            'first_name'     => 'Ana',
            'last_name'      => 'Alumna',
            'email'          => 'student@test.com',
            'password_hash'  => bcrypt('password'),
            'is_active'      => true,
        ]);
        $this->student->assignRole('Student');

        $enrolledClassroom = Classroom::withoutGlobalScopes()->create([
            'institution_id'     => $this->institution->id,
            'teacher_id'         => $teacher->id,
            'subject_name'       => 'Matemáticas',
            'period'             => '2026-A',
            'max_capacity'       => 30,
            'min_attendance_pct' => 80,
            'is_active'          => true,
        ]);

        Classroom::withoutGlobalScopes()->create([
            'institution_id'     => $demo->id,
            'teacher_id'         => $teacher->id,
            'subject_name'       => 'Aula Demo Confusa',
            'period'             => '2026-A',
            'max_capacity'       => 30,
            'min_attendance_pct' => 80,
            'is_active'          => true,
        ]);

        Enrollment::create([
            'classroom_id' => $enrolledClassroom->id,
            'student_id'   => $this->student->id,
            'enrolled_at'  => now(),
            'is_active'    => true,
        ]);
    }

    #[Test]
    public function student_only_sees_enrolled_classrooms(): void
    {
        $response = $this->actingAs($this->student)->get(route('aulas.index'));

        $response->assertOk();
        $response->assertSee('Mis Aulas');
        $response->assertSee('Matemáticas');
        $response->assertDontSee('Aula Demo Confusa');
        $response->assertDontSee('Gestión de aulas y grupos de la institución');
    }
}
