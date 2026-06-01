<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Justification;
use App\Models\Session;
use App\Models\StudentNotification;
use App\Models\User;
use App\Services\AttendanceProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $teacher;
    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);

        $institution = Institution::create(['name' => 'Test', 'is_active' => true]);

        $this->teacher = User::create([
            'institution_id' => $institution->id,
            'first_name'     => 'Doc',
            'last_name'      => 'Test',
            'email'          => 'teacher@test.com',
            'password_hash'  => bcrypt('password'),
            'is_active'      => true,
        ]);
        $this->teacher->assignRole('Teacher');

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
            'grupo'              => '189900',
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
    public function traffic_light_change_creates_notification(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $session = Session::create([
                'classroom_id' => $this->classroom->id,
                'session_date' => now()->subDays($i)->toDateString(),
                'started_at'   => '08:00:00',
                'is_active'    => false,
            ]);
            Attendance::create([
                'session_id' => $session->id,
                'student_id' => $this->student->id,
                'status'     => 'present',
            ]);
        }

        $service = app(AttendanceProgressService::class);
        $service->dispatchTrafficLightIfChanged($this->student->id, $this->classroom->id, 'red');

        $this->assertDatabaseHas('student_notifications', [
            'user_id' => $this->student->id,
            'type'    => StudentNotification::TYPE_TRAFFIC_LIGHT,
        ]);
    }

    #[Test]
    public function student_can_view_notifications_index(): void
    {
        StudentNotification::create([
            'user_id'      => $this->student->id,
            'classroom_id' => $this->classroom->id,
            'type'         => StudentNotification::TYPE_TRAFFIC_LIGHT,
            'title'        => 'Test',
            'message'      => 'Mensaje de prueba',
        ]);

        $this->actingAs($this->student)
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertSee('Notificaciones')
            ->assertSee('Mensaje de prueba');
    }

    #[Test]
    public function justification_review_creates_notification(): void
    {
        $session = Session::create([
            'classroom_id' => $this->classroom->id,
            'session_date' => now()->toDateString(),
            'started_at'   => '08:00:00',
            'is_active'    => false,
        ]);

        $attendance = Attendance::create([
            'session_id' => $session->id,
            'student_id' => $this->student->id,
            'status'     => 'absent',
        ]);

        $justification = Justification::create([
            'attendance_id' => $attendance->id,
            'student_id'    => $this->student->id,
            'file_url'      => 'https://example.com/doc.pdf',
            'status'        => 'pending',
        ]);

        $this->actingAs($this->teacher)
            ->patch(route('justificantes.review', $justification), ['status' => 'approved']);

        $this->assertDatabaseHas('student_notifications', [
            'user_id' => $this->student->id,
            'type'    => StudentNotification::TYPE_JUSTIFICATION_APPROVED,
        ]);
    }
}
