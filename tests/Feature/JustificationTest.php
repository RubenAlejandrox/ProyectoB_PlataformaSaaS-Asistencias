<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Justification;
use App\Models\Plan;
use App\Models\Session;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SupabaseStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JustificationTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User $teacher;
    private User $student;
    private User $otherStudent;
    private Classroom $classroom;
    private Attendance $attendance;

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

        $this->teacher      = $this->createUser('teacher@test.com', 'Teacher');
        $this->student      = $this->createUser('student@test.com', 'Student');
        $this->otherStudent = $this->createUser('other@test.com', 'Student');

        $this->classroom = Classroom::withoutGlobalScopes()->create([
            'institution_id'     => $this->institution->id,
            'teacher_id'         => $this->teacher->id,
            'subject_name'       => 'Matemáticas',
            'period'             => '2026-A',
            'max_capacity'       => 30,
            'min_attendance_pct' => 80,
            'is_active'          => true,
        ]);

        foreach ([$this->student, $this->otherStudent] as $student) {
            Enrollment::create([
                'classroom_id' => $this->classroom->id,
                'student_id'   => $student->id,
                'enrolled_at'  => now(),
                'is_active'    => true,
            ]);
        }

        $session = Session::create([
            'classroom_id' => $this->classroom->id,
            'session_date' => now()->toDateString(),
            'started_at'   => now()->format('H:i:s'),
            'is_active'    => true,
        ]);

        $this->attendance = Attendance::create([
            'session_id'  => $session->id,
            'student_id'  => $this->student->id,
            'status'      => 'absent',
        ]);

        $this->mockStorage();
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

    private function mockStorage(): void
    {
        $this->mock(SupabaseStorageService::class, function ($mock) {
            $mock->shouldReceive('isAllowedMime')
                ->andReturn(true);
            $mock->shouldReceive('upload')
                ->andReturn('https://example.supabase.co/storage/v1/object/public/justification-files/test.pdf');
        });
    }

    #[Test]
    public function student_uploads_valid_file_creates_pending_justification(): void
    {
        $response = $this->actingAs($this->student)
            ->post('/api/justifications', [
                'attendance_id' => $this->attendance->id,
                'reason'        => 'Consulta médica',
                'file'          => UploadedFile::fake()->create('justificante.pdf', 100, 'application/pdf'),
            ], ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('justifications', [
            'attendance_id' => $this->attendance->id,
            'student_id'    => $this->student->id,
            'status'        => 'pending',
        ]);

        $this->assertNotNull(
            Justification::where('attendance_id', $this->attendance->id)->value('file_url')
        );
    }

    #[Test]
    public function teacher_approval_sets_approved_and_reviewed_at(): void
    {
        $justification = Justification::create([
            'attendance_id' => $this->attendance->id,
            'student_id'    => $this->student->id,
            'file_url'      => 'https://example.supabase.co/file.pdf',
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($this->teacher)
            ->patchJson("/api/justifications/{$justification->id}/review", [
                'status' => 'approved',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $justification->refresh();
        $this->assertNotNull($justification->reviewed_at);
        $this->assertEquals($this->teacher->id, $justification->reviewed_by);
    }

    #[Test]
    public function modifying_reviewed_at_after_review_throws_exception(): void
    {
        $justification = Justification::create([
            'attendance_id' => $this->attendance->id,
            'student_id'    => $this->student->id,
            'file_url'      => 'https://example.supabase.co/file.pdf',
            'status'        => 'approved',
            'reviewed_at'   => now(),
            'reviewed_by'   => $this->teacher->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Justification review is immutable.');

        $justification->update(['reviewed_at' => now()->addDay()]);
    }

    #[Test]
    public function student_cannot_review_justification_returns_403(): void
    {
        $justification = Justification::create([
            'attendance_id' => $this->attendance->id,
            'student_id'    => $this->student->id,
            'file_url'      => 'https://example.supabase.co/file.pdf',
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($this->otherStudent)
            ->patchJson("/api/justifications/{$justification->id}/review", [
                'status' => 'approved',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('justifications', [
            'id'     => $justification->id,
            'status' => 'pending',
        ]);
    }
}
