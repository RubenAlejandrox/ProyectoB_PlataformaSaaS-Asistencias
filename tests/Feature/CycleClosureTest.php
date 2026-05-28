<?php

namespace Tests\Feature;

use App\Models\AcademicCycle;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Justification;
use App\Models\Plan;
use App\Models\Session;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CycleClosureTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private Classroom $classroom;
    private AcademicCycle $cycle;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);

        $institution = Institution::create(['name' => 'Inst', 'is_active' => true]);
        $plan = Plan::create([
            'name' => 'Pro',
            'price' => 499,
            'max_students' => 100,
            'max_classrooms' => 10,
            'duration_months' => 1,
            'is_active' => true,
        ]);
        Subscription::create([
            'institution_id' => $institution->id,
            'plan_id' => $plan->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'active',
        ]);

        $this->teacher = User::create([
            'institution_id' => $institution->id,
            'first_name' => 'Doc',
            'last_name' => 'Test',
            'email' => 'doc@cycle.test',
            'password_hash' => bcrypt('secret'),
            'is_active' => true,
        ]);
        $this->teacher->assignRole('Teacher');

        $this->classroom = Classroom::withoutGlobalScopes()->create([
            'institution_id' => $institution->id,
            'teacher_id' => $this->teacher->id,
            'subject_name' => 'Mate',
            'period' => '2026-A',
            'min_attendance_pct' => 80,
            'max_capacity' => 30,
            'is_active' => true,
        ]);

        $this->cycle = AcademicCycle::create([
            'institution_id' => $institution->id,
            'classroom_id' => $this->classroom->id,
            'name' => '2026-A',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'closure_key_hash' => Hash::make('CERRAR123'),
            'is_closed' => false,
        ]);
    }

    #[Test]
    public function closes_cycle_with_correct_key(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/cycles/'.$this->cycle->id.'/close', [
                'closure_key' => 'CERRAR123',
            ]);

        $response->assertOk();
        $this->assertTrue($this->cycle->fresh()->is_closed);
        $this->assertFalse($this->classroom->fresh()->is_active);
    }

    #[Test]
    public function blocks_after_three_failed_attempts(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->teacher)
                ->postJson('/api/cycles/'.$this->cycle->id.'/close', [
                    'closure_key' => 'BADKEY',
                ]);
        }

        $cycle = $this->cycle->fresh();
        $this->assertEquals(3, $cycle->closure_attempts);
        $this->assertNotNull($cycle->closure_locked_until);
    }

    #[Test]
    public function rejects_closure_when_pending_justifications_exist(): void
    {
        $student = User::create([
            'institution_id' => $this->teacher->institution_id,
            'first_name' => 'Stu',
            'last_name' => 'Dent',
            'email' => 'student@cycle.test',
            'password_hash' => bcrypt('secret'),
            'is_active' => true,
        ]);

        $session = Session::create([
            'classroom_id' => $this->classroom->id,
            'session_date' => now()->toDateString(),
            'started_at' => '08:00:00',
            'is_active' => false,
        ]);

        $attendance = Attendance::create([
            'session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'absent',
        ]);

        Justification::create([
            'attendance_id' => $attendance->id,
            'student_id' => $student->id,
            'file_url' => 'https://x.test/doc.pdf',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->teacher)
            ->postJson('/api/cycles/'.$this->cycle->id.'/close', [
                'closure_key' => 'CERRAR123',
            ]);

        $response->assertStatus(422);
        $this->assertFalse($this->cycle->fresh()->is_closed);
    }
}
