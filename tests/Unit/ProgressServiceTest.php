<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Justification;
use App\Models\Session;
use App\Models\User;
use App\Services\AttendanceProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceProgressService $service;
    private Classroom $classroom;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AttendanceProgressService::class);

        $institution = Institution::create([
            'name'      => 'Test Institution',
            'is_active' => true,
        ]);

        $teacher = User::create([
            'institution_id' => $institution->id,
            'first_name'     => 'Doc',
            'last_name'      => 'Ente',
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
    public function calculates_percentage_with_present_and_approved(): void
    {
        $sessions = collect();
        for ($i = 0; $i < 10; $i++) {
            $sessions->push(Session::create([
                'classroom_id' => $this->classroom->id,
                'session_date' => now()->subDays($i)->toDateString(),
                'started_at'   => '08:00:00',
                'is_active'    => false,
            ]));
        }

        foreach ($sessions->take(7) as $session) {
            Attendance::create([
                'session_id' => $session->id,
                'student_id' => $this->student->id,
                'status'     => 'present',
            ]);
        }

        $absentSession = $sessions[7];
        $absent = Attendance::create([
            'session_id' => $absentSession->id,
            'student_id' => $this->student->id,
            'status'     => 'absent',
        ]);

        Justification::create([
            'attendance_id' => $absent->id,
            'student_id'    => $this->student->id,
            'file_url'      => 'https://example.com/doc.pdf',
            'status'        => 'approved',
        ]);

        $result = $this->service->calculate($this->student->id, $this->classroom->id);

        $this->assertEquals(10, $result['total_sessions']);
        $this->assertEquals(7, $result['present_count']);
        $this->assertEquals(1, $result['approved_count']);
        $this->assertEquals(80.0, $result['attendance_pct']);
        $this->assertEquals('green', $result['light']);
    }

    #[Test]
    public function determines_traffic_lights_by_threshold(): void
    {
        $threshold = 80;

        $this->assertEquals('green', $this->service->determineLight(85, $threshold));
        $this->assertEquals('green', $this->service->determineLight(80, $threshold));
        $this->assertEquals('amber', $this->service->determineLight(75, $threshold));
        $this->assertEquals('amber', $this->service->determineLight(70, $threshold));
        $this->assertEquals('red', $this->service->determineLight(69.9, $threshold));
    }

    #[Test]
    public function zero_sessions_returns_zero_percent_and_red_light(): void
    {
        $result = $this->service->calculate($this->student->id, $this->classroom->id);

        $this->assertEquals(0, $result['total_sessions']);
        $this->assertEquals(0.0, $result['attendance_pct']);
        $this->assertEquals('red', $result['light']);
    }
}
