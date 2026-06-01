<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Justification;
use App\Models\Session;
use App\Models\User;
use App\Services\ReportGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportGeneratorService $service;
    private Classroom $classroom;
    private User $student1;
    private User $student2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ReportGeneratorService::class);

        $institution = Institution::create(['name' => 'Inst', 'is_active' => true]);
        $teacher = User::create([
            'institution_id' => $institution->id,
            'first_name' => 'Doc',
            'last_name' => 'Uno',
            'email' => 'doc@test.com',
            'password_hash' => bcrypt('secret'),
            'is_active' => true,
        ]);

        $this->student1 = User::create([
            'institution_id' => $institution->id,
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana@test.com',
            'password_hash' => bcrypt('secret'),
            'is_active' => true,
        ]);
        $this->student2 = User::create([
            'institution_id' => $institution->id,
            'first_name' => 'Beto',
            'last_name' => 'Ruiz',
            'email' => 'beto@test.com',
            'password_hash' => bcrypt('secret'),
            'is_active' => true,
        ]);

        $this->classroom = Classroom::withoutGlobalScopes()->create([
            'institution_id' => $institution->id,
            'teacher_id' => $teacher->id,
            'subject_name' => 'Matematicas',
            'period' => '2026-A',
            'grupo'  => '189900',
            'min_attendance_pct' => 80,
            'max_capacity' => 30,
            'is_active' => true,
        ]);

        Enrollment::create(['classroom_id' => $this->classroom->id, 'student_id' => $this->student1->id, 'enrolled_at' => now(), 'is_active' => true]);
        Enrollment::create(['classroom_id' => $this->classroom->id, 'student_id' => $this->student2->id, 'enrolled_at' => now(), 'is_active' => true]);
    }

    #[Test]
    public function builds_matrix_payload_with_a_f_j_values(): void
    {
        $s1 = Session::create(['classroom_id' => $this->classroom->id, 'session_date' => '2026-05-01', 'started_at' => '08:00:00', 'is_active' => false]);
        $s2 = Session::create(['classroom_id' => $this->classroom->id, 'session_date' => '2026-05-02', 'started_at' => '08:00:00', 'is_active' => false]);

        Attendance::create(['session_id' => $s1->id, 'student_id' => $this->student1->id, 'status' => 'present']);
        $absent = Attendance::create(['session_id' => $s2->id, 'student_id' => $this->student1->id, 'status' => 'absent']);
        Justification::create([
            'attendance_id' => $absent->id,
            'student_id' => $this->student1->id,
            'file_url' => 'https://x.test/file.pdf',
            'status' => 'approved',
        ]);

        $payload = $this->service->buildMatrixPayload($this->classroom);

        $this->assertCount(7, $payload['headings']); // Alumno + 2 fechas + 3 totales + %
        $this->assertCount(2, $payload['rows']);
        $first = $payload['rows'][0];
        $this->assertContains('A', $first);
        $this->assertContains('J', $first);
    }

    #[Test]
    public function builds_monthly_payload_with_expected_totals(): void
    {
        $session = Session::create(['classroom_id' => $this->classroom->id, 'session_date' => '2026-05-10', 'started_at' => '08:00:00', 'is_active' => false]);
        Attendance::create(['session_id' => $session->id, 'student_id' => $this->student1->id, 'status' => 'present']);
        Attendance::create(['session_id' => $session->id, 'student_id' => $this->student2->id, 'status' => 'absent']);

        $payload = $this->service->buildMonthlyPayload($this->classroom, '2026-05');

        $this->assertCount(7, $payload['headings']);
        $this->assertCount(2, $payload['rows']);
        $ana = collect($payload['rows'])->first(fn ($r) => str_contains($r[0], 'Ana'));
        $this->assertEquals(1, $ana[3]); // A
        $this->assertEquals(0, $ana[4]); // F
        $this->assertEquals(100.0, $ana[6]); // %
    }
}
