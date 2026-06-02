<?php

namespace Tests\Unit;

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

class AttendanceProgressBulkTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function calculate_bulk_returns_progress_for_each_student(): void
    {
        $institution = Institution::create(['name' => 'Inst', 'is_active' => true]);
        $teacher     = User::create([
            'institution_id' => $institution->id,
            'first_name'     => 'Doc',
            'last_name'      => 'Ente',
            'email'          => 'teacher-bulk@test.com',
            'password_hash'  => bcrypt('Password1!'),
            'is_active'      => true,
        ]);
        $studentA = User::create([
            'institution_id' => $institution->id,
            'first_name'     => 'Alumno',
            'last_name'      => 'A',
            'email'          => 'student-a-bulk@test.com',
            'password_hash'  => bcrypt('Password1!'),
            'is_active'      => true,
        ]);
        $studentB = User::create([
            'institution_id' => $institution->id,
            'first_name'     => 'Alumno',
            'last_name'      => 'B',
            'email'          => 'student-b-bulk@test.com',
            'password_hash'  => bcrypt('Password1!'),
            'is_active'      => true,
        ]);

        $classroom = Classroom::withoutGlobalScopes()->create([
            'institution_id'     => $institution->id,
            'teacher_id'         => $teacher->id,
            'subject_name'       => 'Test',
            'period'             => '2026-A',
            'grupo'              => '100001',
            'max_capacity'       => 30,
            'min_attendance_pct' => 80,
            'is_active'          => true,
        ]);

        foreach ([$studentA, $studentB] as $student) {
            Enrollment::create([
                'classroom_id' => $classroom->id,
                'student_id'   => $student->id,
                'enrolled_at'  => now(),
                'is_active'    => true,
            ]);
        }

        $session = Session::create([
            'classroom_id' => $classroom->id,
            'session_date' => today()->toDateString(),
            'started_at'   => now()->format('H:i:s'),
            'is_active'    => true,
        ]);

        Attendance::create([
            'session_id' => $session->id,
            'student_id' => $studentA->id,
            'status'     => 'present',
        ]);

        $service = app(AttendanceProgressService::class);
        $bulk    = $service->calculateBulk($classroom->id, [$studentA->id, $studentB->id]);

        $this->assertArrayHasKey($studentA->id, $bulk);
        $this->assertArrayHasKey($studentB->id, $bulk);
        $this->assertSame(100.0, $bulk[$studentA->id]['attendance_pct']);
        $this->assertSame(0.0, $bulk[$studentB->id]['attendance_pct']);
    }
}
